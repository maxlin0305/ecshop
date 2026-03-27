<?php

namespace EspierBundle\Services\Export;

use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityOrderRelActivityService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;

use AftersalesBundle\Services\AftersalesService;
use PopularizeBundle\Services\BrokerageService;

class NormalMasterOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    protected $order_class = '';

    public function exportData($filter)
    {
        // 是否需要數據脫敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderService = $this->getOrderService('normal');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return [];
        }

        $this->order_class = $filter['order_class'] ?? '';

        if (isset($filter['order_class']) && $filter['order_class'] == 'pointsmall') {
            $fileName = date('YmdHis') . $filter['company_id'] . "master積分商城";
            $title = $this->getPointsmallTitle();
            $dataList = $this->getPointsmallLists($filter, $count, $datapassBlock);
        } else {
            $fileName = date('YmdHis') . $filter['company_id'] . "master";
            $title = $this->getTitle();
            $dataList = $this->getLists($filter, $count, $datapassBlock);
        }

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $dataList);

        return $result;
    }

    private function getTitle()
    {
        $title = [
            'order_id' => '訂單號',//
            'trade_no'=> '訂單序號',//
            'item_fee' => '訂單價格',
            'store_name' => '所屬店鋪',
            'store_code' => '店鋪號',
            'mobile' => '會員手機號',//
            'user_name' => '會員昵稱',
            'create_time' => '下單時間',//
            'freight_fee' => '運費(總)',//
            'total_fee' => '實付金額(總)',//
            'discount_fee' => '優惠金額',
            'discount_info' => '優惠詳情',
            'order_class' => '訂單類型',
            'order_status' => '訂單狀態',//
            'receipt_type' => '收貨方式',//
            'ziti_status' => '自提狀態',
            'receiver_name' => '收貨人姓名',//
            'receiver_mobile' => '收貨人手機',//
            'receiver_zip' => '收貨人郵編',//
            'receiver_state' => '收貨人所在省份',//
            'receiver_city' => '收貨人所在城市',//
            'receiver_district' => '收貨人所在地區、縣',//
            'receiver_address' => '收貨地址',//
            'subdistrict_parent' => '街道',
            'subdistrict' => '居委',
            'building_number' => '樓號',
            'house_number' => '房號',
            'delivery_status' => '發貨狀態',
            'delivery_time' => '發貨時間',
            'delivery_code' => '快遞單號',
            'delivery_corp' => '快遞公司',
            'pay_type' => '支付方式',
            'invoice' => '發票內容',
            'promoter_user_mobile' => '推廣員手機號',
            'rebate' => '分傭金額',
            'remark' => '訂單備註'
        ];
        if ($this->order_class == 'community') {
            $title['activity_status'] = '活動狀態';
            $title['activity_delivery_status'] = '活動發貨狀態';
        }
        return $title;
    }

    private function getDiscountDesc($value)
    {
        if (!isset($value['type'])) {
            return '';
        }
        if ($value['discount_fee'] <= 0) {
            return '';
        }
        $value['discount_fee'] = bcdiv($value['discount_fee'], 100, 2)."元";
        switch ($value['type']) {
            case "full_discount":
                $discountDesc = "滿折：".$value['discount_fee']."; ";
                break;
            case "full_minus":
                $discountDesc = "滿減：".$value['discount_fee']."; ";
                break;
            case "coupon_discount":
                $discountDesc = "折扣優惠券：".$value['discount_fee']."; ";
                break;
            case "cash_discount":
                $discountDesc = "代金優惠券：".$value['discount_fee']."; ";
                break;
            case "limited_time_sale":
                $discountDesc = "限時特惠：".$value['discount_fee']."; ";
                break;
            case "seckill":
                $discountDesc = "秒殺：".$value['discount_fee']."; ";
                break;
            case "groups":
                $discountDesc = "拼團：".$value['discount_fee']."; ";
                break;
            case "member_price":
                $discountDesc = "會員價：".$value['discount_fee']."; ";
                break;
            case "member_tag_targeted_promotio：":
                $discountDesc = "定向促銷：".$value['discount_fee']."; ";
                break;
            default:
                $discountDesc = '';
                break;
        }
        return $discountDesc;
    }


    public function getLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
        $orderClass = [
            'community' => '社區活動訂單',
            'groups' => '拼團活動訂單',
            'seckill' => '秒殺活動訂單',
            'normal' => '普通訂單',
            'drug' => '藥品需求訂單',
            'shopguide' => '代客下單訂單',
            'pointsmall' => '積分商城訂單',
            'bargain' => '砍價訂單',
            'excard' => '兌換券訂單',
            'shopadmin' => '門店訂單',
        ];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家處理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申請，等待消費者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消費者回寄，等待商家收貨確認',
            'SELLER_REFUSE_BUYER' => '售後駁回',
            'SELLER_SEND_GOODS' => '賣家重新發貨 換貨完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款關閉',
            'CLOSED' => '售後關閉',
        ];
        $payTypes = [
            'wxpay' => '微信支付',
            'wxpaypc' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信支付',
            'hfpay' => '微信支付',
            'adapay' => '微信支付',
            'alipay' => '支付寶',
            'alipayh5' => '支付寶',
            'alipayapp' => '支付寶',
            'alipaypos' => '支付寶',
            'point' => '積分支付',
            'deposit' => '余額支付',
            'pos' => '現金支付',
        ];
        $receiptType = ['logistics' => '快遞配送', 'ziti' => '上門自提', 'dada' => '同城配'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $communityOrderRelData = [];
            if ($this->order_class == 'community' && !empty($orderIdList)) {
                $communityOrderRelData = $communityOrderRelService->getLists(['order_id' => $orderIdList]);
                $communityActivityData = [];
                $activityIds = array_values(array_unique(array_column($communityOrderRelData, 'activity_id')));
                if (!empty($activityIds)) {
                    $communityActivityData = $communityActivityService->getLists(['activity_id' => $activityIds]);
                    $communityActivityData = array_column($communityActivityData, null, 'activity_id');
                }
                $communityOrderRelData = array_column($communityOrderRelData, null, 'order_id');
                foreach ($communityOrderRelData as $key => $value) {
                    if (isset($communityActivityData[$value['activity_id']])) {
                        $communityOrderRelData[$key]['activity_data'] = $communityActivityData[$value['activity_id']];
                    }
                }
            }

            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $orderList = [];
            foreach ($orderdata as $newData) {
                $disountFee = 0;
                if ($newData['member_discount'] && $newData['coupon_discount']) {
                    $disountFee = ((int)$newData['member_discount'] + (int)$newData['coupon_discount']);
                } else {
                    $disountFee = (int)$newData['discount_fee'];
                }
                $discountDesc = '';
                if ($newData['discount_info']) {
                    $discountInfo = json_decode($newData['discount_info'], true);
                    foreach ($discountInfo as $value) {
                        $a = $this->getDiscountDesc($value);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }
                $invoice = [];
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
                    foreach ($invoicearr as $key => $val) {
                        switch ($key) {
                            case "title":
                                $invoice[] = 'title:'.$val;
                                break;
                            case "registration_number":
                                $invoice[] = '稅號:'.$val;
                                break;
                            case "content":
                                $invoice[] = '發票擡頭:'.$val;
                                break;
                            case "company_address":
                                $invoice[] = '單位地址:'.$val;
                                break;
                            case "bankname":
                                $invoice[] = '開戶銀行:'.$val;
                                break;
                            case "bankaccount":
                                $invoice[] = '銀行賬戶:'.$val;
                                break;
                            case "company_phone":
                                $invoice[] = '電話號碼:'.$val;
                                break;
                        }
                    }
                }
                $invoiceStr = $invoice ? implode(';'.PHP_EOL, $invoice) : '---無---';

                $orderAssInfo = $orderAssociationService->getOrder($filter['company_id'], $newData['order_id']);

                $bFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $orderAssInfo['promoter_user_id'],
                    'order_id' => $newData['order_id'],
                    'brokerage_type' => 'first_level',
                ];
                $brokerageInfo = $brokerageService->getInfo($bFilter);

                $user_mobile = $memberService->getMobileByUserId($orderAssInfo['promoter_user_id'], $filter['company_id']);

                $payType = $newData['pay_type'] ?? '';
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $orderItem = [
                    'order_id'=> "\"'".$newData['order_id']."\"",//
                    'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                    'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                    'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => $newData['create_date'],
                    'freight_fee' => bcdiv($newData['freight_fee'], 100, 2),//
                    'total_fee' => ($payType == 'dhpoint') ? $newData['total_fee'] : bcdiv($newData['total_fee'], 100, 2),//
                    'discount_fee' => bcdiv($disountFee, 100, 2),
                    'discount_info' => $discountDesc,
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status' => $newData['order_status_msg'],//
                    'receipt_type' => $receiptType[$newData['receipt_type']],//
                    'ziti_status' => ($newData['ziti_status'] == 'DONE') ? '已自提' : '',//
                    'receiver_name' => $newData['receiver_name'],//
                    'receiver_mobile' => $newData['receiver_mobile'],//
                    'receiver_zip' => $newData['receiver_zip'],//
                    'receiver_state' => $newData['receiver_state'],//
                    'receiver_city' => $newData['receiver_city'],//
                    'receiver_district' => $newData['receiver_district'],//
                    'receiver_address' => $newData['receiver_address'],//
                    'subdistrict_parent' => $newData['subdistrict_parent'],
                    'subdistrict' => $newData['subdistrict'],
                    'building_number' => $newData['building_number'],
                    'house_number' => $newData['house_number'],
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已發貨' : '未發貨',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code']. "\t" : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'invoice' => $invoiceStr,
                    'promoter_user_mobile' => $user_mobile ?? '',
                    'rebate' => isset($brokerageInfo['rebate']) ? bcdiv($brokerageInfo['rebate'], 100, 2) : 0,
                    'remark' => $newData['remark']
                ];
                if ($this->order_class == 'community') {
                    $orderItem['activity_status'] = '';
                    $orderItem['activity_delivery_status'] = '';
                    if (isset($communityOrderRelData[$newData['order_id']]['activity_data'])) {
                        $activity_status = $communityOrderRelData[$newData['order_id']]['activity_data']['activity_status'] ?? '';
                        $activity_delivery_status = $communityOrderRelData[$newData['order_id']]['activity_data']['delivery_status'] ?? '';
                        $orderItem['activity_status'] = CommunityActivityService::activity_status[$activity_status] ?? '';
                        $orderItem['activity_delivery_status'] = CommunityActivityService::activity_delivery_status[$activity_delivery_status] ?? '';
                    }
                }
                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    public function getAftersaleList($filter)
    {
        $aftersalesService = new AftersalesService();
        $list = $aftersalesService->getAftersalesList($filter, 1, -1);
        $data = [];
        foreach ($list['list'] as $value) {
            $data[$value['order_id']] = [
                'aftersales_status' => $value['aftersales_status'],
                'aftersales_type' => $value['aftersales_type'],
            ];
        }
        return $data;
    }

    private function getPointsmallTitle()
    {
        $title = [
            'order_id' => '訂單號',//
            'trade_no' => '訂單序號',//
            'item_point' => '訂單價格',
            'mobile' => '會員手機號',//
            'user_name' => '會員昵稱',
            'create_time' => '下單時間',//
            'freight_fee' => '運費(總)',//
            'total_fee' => '實付金額(總)',//
            'order_class' => '訂單類型',
            'order_status' => '訂單狀態',//
            'receipt_type' => '收貨方式',//
            'receiver_name' => '收貨人姓名',//
            'receiver_mobile' => '收貨人手機',//
            'receiver_zip' => '收貨人郵編',//
            'receiver_state' => '收貨人所在省份',//
            'receiver_city' => '收貨人所在城市',//
            'receiver_district' => '收貨人所在地區、縣',//
            'receiver_address' => '收貨地址',//
            'delivery_status' => '發貨狀態',
            'delivery_time' => '發貨時間',
            'delivery_code' => '快遞單號',
            'delivery_corp' => '快遞公司',
            'pay_type' => '支付方式',
            'remark' => '訂單備註'
        ];
        return $title;
    }

    public function getPointsmallLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
        $orderClass = [
            'community' => '社區活動訂單',
            'groups' => '拼團活動訂單',
            'seckill' => '秒殺活動訂單',
            'normal' => '普通訂單',
            'drug' => '藥品需求訂單',
            'shopguide' => '代客下單訂單',
            'pointsmall' => '積分商城訂單',
            'excard' => '兌換券訂單',
            'shopadmin' => '門店訂單',
        ];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家處理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申請，等待消費者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消費者回寄，等待商家收貨確認',
            'SELLER_REFUSE_BUYER' => '售後駁回',
            'SELLER_SEND_GOODS' => '賣家重新發貨 換貨完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款關閉',
            'CLOSED' => '售後關閉',
        ];
        $payTypes = [
            'wxpay' => '微信支付',
            'wxpaypc' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信支付',
            'hfpay' => '微信支付',
            'adapay' => '微信支付',
            'alipay' => '支付寶',
            'alipayh5' => '支付寶',
            'alipayapp' => '支付寶',
            'alipaypos' => '支付寶',
            'point' => '積分支付',
            'deposit' => '余額支付',
            'pos' => '現金支付',
        ];
        $receiptType = ['logistics' => '快遞配送', 'ziti' => '上門自提'];

        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];
            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $orderList = [];
            foreach ($orderdata as $newData) {
                $payType = $newData['pay_type'] ?? '';
                if ($newData['freight_type'] == 'point') {
                    $freight_fee = $newData['freight_fee'] . '積分';
                } else {
                    $freight_fee = bcdiv($newData['freight_fee'], 100, 2);
                }
                if ($payType == 'dhpoint') {
                    $total_fee = $newData['total_fee'];
                } elseif ($payType == 'point') {
                    $total_fee = $newData['point'] . '積分';
                } else {
                    $total_fee = $newData['item_point'] . '積分 + ' . bcdiv($newData['total_fee'], 100, 2);
                }
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $orderList[] = [
                    'order_id' => "\"'" . $newData['order_id'] . "\"",
                    'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'item_point' => $newData['item_point'] . '積分',
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => $newData['create_date'],
                    'freight_fee' => $freight_fee,//
                    'total_fee' => $total_fee,//
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status' => $newData['order_status_msg'],//
                    'receipt_type' => $receiptType[$newData['receipt_type']],//
                    'receiver_name' => $newData['receiver_name'],//
                    'receiver_mobile' => $newData['receiver_mobile'],//
                    'receiver_zip' => $newData['receiver_zip'],//
                    'receiver_state' => $newData['receiver_state'],//
                    'receiver_city' => $newData['receiver_city'],//
                    'receiver_district' => $newData['receiver_district'],//
                    'receiver_address' => $newData['receiver_address'],//
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已發貨' : '未發貨',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code'] . "\t" : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'remark' => $newData['remark']
                ];
            }
            yield $orderList;
        }
    }
}
