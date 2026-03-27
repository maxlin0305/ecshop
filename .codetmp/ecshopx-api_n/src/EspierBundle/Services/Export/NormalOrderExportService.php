<?php

namespace EspierBundle\Services\Export;

use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityOrderRelActivityService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;

class NormalOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    protected $order_class = '';

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderService = $this->getOrderService('normal');
        $count = $orderService->getOrderItemCount($filter);
        if (!$count) {
            return [];
        }

        $this->order_class = $filter['order_class'] ?? '';

        if (isset($filter['order_class']) && $filter['order_class'] == 'pointsmall') {
            $fileName = date('YmdHis') . $filter['company_id'] . "order积分商城";
            $title = $this->getPointsmallTitle();
            $orderList = $this->getPointsmallLists($filter, $count, $datapassBlock);
        } else {
            $fileName = date('YmdHis') . $filter['company_id'] . "order";
            $title = $this->getTitle();
            $orderList = $this->getLists($filter, $count, $datapassBlock);
        }

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    public function getLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
        $aftersales_status = [
          'WAIT_SELLER_AGREE' => '等待商家处理',
          'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
          'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
          'SELLER_REFUSE_BUYER' => '售后驳回',
          'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
          'REFUND_SUCCESS' => '退款成功',
          'REFUND_CLOSED' => '退款关闭',
          'CLOSED' => '售后关闭',
      ];
        $orderStatus = [
          'NOTPAY' => '未支付',
          'CANCEL' => '已取消',
          'CANCEL_WAIT_PROCESS' => '取消待处理',
          'DONE' => '已完成',
          'PAYED' => '待发货',
          'REFUND_SUCCESS' => '退款完成',
          'WAIT_BUYER_CONFIRM' => '待收货',
          'REVIEW_PASS' => '审核通过待出库',
          'DADA_0' => '店铺待接单',
          'DADA_1' => '骑士待接单',
          'DADA_2' => '待取货',
          'DADA_100' => '骑士到店',
          'DADA_3' => '配送中',
          'DADA_9' => '未妥投',
          'DADA_10' => '妥投异常',
      ];
        $orderClass = [
          'community' => '社区活动订单',
          'groups' => '拼团活动订单',
          'seckill' => '秒杀活动订单',
          'normal' => '普通订单',
          'drug' => '药品需求订单',
          'shopguide' => '代客下单订单',
          'pointsmall' => '积分商城订单',
          'bargain' => '砍价订单',
          'excard' => '兑换券订单',
          'shopadmin' => '门店订单',
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
            'alipay' => '支付宝',
            'alipayh5' => '支付宝',
            'alipayapp' => '支付宝',
            'alipaypos' => '支付宝',
            'point' => '积分支付',
            'deposit' => '余额支付',
            'pos' => '现金支付',
      ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);

            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                  'company_id' => $filter['company_id'],
                  'user_id' => $userIds,
              ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderIdList = array_column($orderdata['list'], 'order_id');
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

            $storeIds = array_filter($orderdata['distributor_ids']);
            if ($storeIds) {
                $sFilter = [
                  'company_id' => $filter['company_id'],
                  'distributor_id' => $storeIds,
              ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {

              //兼容已自提订单的子订单的发货状态错误的问题，子订单发货状态错误的原因待查
                if ($newData['ziti_status'] == 'DONE' && $newData['order_delivery_status'] == 'DONE') {
                    $newData['delivery_status'] = $newData['order_delivery_status'];
                    $newData['delivery_time'] = $newData['order_delivery_time'];
                    $newData['delivery_code'] = $newData['order_delivery_corp'];
                    $newData['delivery_corp'] = $newData['order_delivery_code'];
                }

                // 处理达达订单状态
                $dada_status = $dadaRelList[$newData['order_id']]['dada_status'] ?? '';
                $dada_status = 'DADA_'.$dada_status;
                if ($newData['receipt_type'] == 'dada' && isset($orderStatus[$dada_status])) {
                    $newData['order_status'] = $dada_status;
                }
                app('log')->info('order_id:'.$newData['order_id'].',receipt_type:'.$newData['receipt_type'].',order_status:'.$newData['order_status'].',dada_status:'.$dada_status);
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
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $orderItem = [
                  'order_id'=> "\"'".$newData['order_id']."\"",//
                  'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                  'id' => $newData['id'],//
                  'item_name' => str_replace('#', '', $newData['item_name']) ,//
                  'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                  'price' => bcdiv($newData['price'], 100, 2),
                  'num' => $newData['num'],
                  'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                  'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                  'mobile' => $newData['mobile'],//
                  'user_name' => $username,
                  'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                  'freight_fee' => bcdiv($newData['freight_fee'], 100, 2),//
                  'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                  'discount_fee' => bcdiv($disountFee, 100, 2),
                  'discount_info' => $discountDesc,
                  'refunded_fee' => bcdiv($newData['refunded_fee'], 100, 2),
                  'order_class' => $orderClass[$newData['order_class']],//
                  'order_status'=> $newData['order_status_msg'],//
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
                  'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                  'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                  'delivery_code' => ($newData['delivery_status'] == 'DONE') ? "\"'".$newData['delivery_code']."\"" : '',
                  'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                  // 'kunnr' => $thirdParams['kunnr'] ?? '',
                  'pay_type' => $payTypes[$payType] ?? $payType,
                  'item_bn' => is_numeric($newData['item_bn']) ? "\"'".$newData['item_bn']."\"" : $newData['item_bn'],
                  'aftersales_status' => $aftersales_status[$newData['aftersales_status']] ?? '',
                  'item_spec_desc' => $newData['item_spec_desc'] ?? '',
                  'remark' => $newData['remark'],
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
            $discountDesc = "满折：".$value['discount_fee'].";";
            break;
        case "full_minus":
            $discountDesc = "满减：".$value['discount_fee'].";";
            break;
        case "coupon_discount":
            $discountDesc = "折扣优惠券：".$value['discount_fee'].";";
            break;
        case "cash_discount":
            $discountDesc = "代金优惠券：".$value['discount_fee'].";";
            break;
        case "limited_time_sale":
            $discountDesc = "限时特惠：".$value['discount_fee'].";";
            break;
        case "seckill":
            $discountDesc = "秒杀：".$value['discount_fee'].";";
            break;
        case "groups":
            $discountDesc = "拼团：".$value['discount_fee'].";";
            break;
        case "member_price":
            $discountDesc = "会员价：".$value['discount_fee'].";";
            break;
        case "member_tag_targeted_promotio：":
            $discountDesc = "定向促销：".$value['discount_fee'].";";
            break;
        default:
            $discountDesc = '';
            break;
        }
        return $discountDesc;
    }


    public function getTitle()
    {
        $title = [
            'order_id' => '主订单号',//
            'trade_no' => '订单序号',//
            'id' => '子订单号',
            'item_name' => '商品名称',
            'item_fee' => '订单价格',
            'price' => '商品价格',
            'num' => '购买数量',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'freight_fee' => '运费(总)',//
            'total_fee' => '实付金额(总)',//
            'discount_fee' => '优惠金额',
            'discount_info' => '优惠详情',
            'refunded_fee' => '退款金额',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',//
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'subdistrict_parent' => '街道',
            'subdistrict' => '居委',
            'building_number' => '楼号',
            'house_number' => '房号',
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'aftersales_status' => '售后状态',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注'
        ];
        if ($this->order_class == 'community') {
            $title['activity_status'] = '活动状态';
            $title['activity_delivery_status'] = '活动发货状态';
        }
        return $title;
    }

    public function getPointsmallTitle()
    {
        $title = [
            'order_id' => '主订单号',//
            'trade_no' => '订单序号',//
            'id' => '子订单号',
            'item_name' => '商品名称',
            'point' => '订单价格',
            'item_point' => '商品价格',
            'num' => '购买数量',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'total_fee' => '实付金额(总)',//
            // 'refunded_fee' => '退款金额',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            // 'ziti_status'=> '自提状态',//
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注'
        ];
        return $title;
    }

    public function getPointsmallLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家处理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
            'SELLER_REFUSE_BUYER' => '售后驳回',
            'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款关闭',
            'CLOSED' => '售后关闭',
        ];
        $orderStatus = [
            'NOTPAY' => '未支付',
            'CANCEL' => '已取消',
            'CANCEL_WAIT_PROCESS' => '取消待处理',
            'DONE' => '已完成',
            'PAYED' => '已支付审核中',
            'REFUND_SUCCESS' => '退款完成',
            'WAIT_BUYER_CONFIRM' => '待收货',
            'REVIEW_PASS' => '审核通过待出库',
        ];
        $orderClass = [
            'community' => '社区活动订单',
            'groups' => '拼团活动订单',
            'seckill' => '秒杀活动订单',
            'normal' => '普通订单',
            'drug' => '药品需求订单',
            'shopguide' => '代客下单订单',
            'pointsmall' => '积分商城订单',
            'excard' => '兑换券订单',
            'shopadmin' => '门店订单',
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
            'alipay' => '支付宝',
            'alipayh5' => '支付宝',
            'alipayapp' => '支付宝',
            'alipaypos' => '支付宝',
            'point' => '积分支付',
            'deposit' => '余额支付',
            'pos' => '现金支付',
        ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);
            $orderIdList = array_column($orderdata['list'], 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $orderList[] = [
                    'order_id' => "\"'" . $newData['order_id'] . "\"",//
                    'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'id' => $newData['id'],//
                    'item_name' => str_replace('#', '', $newData['item_name']),//
                    'item_point' => $newData['item_point'] . '积分',
                    'point' => $newData['point'] . '积分',
                    'num' => $newData['num'],
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                    'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                    // 'refunded_fee' => $newData['refunded_fee'],
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
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? "\"'" . $newData['delivery_code'] . "\"" : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'item_bn' => is_numeric($newData['item_bn']) ? "\"'".$newData['item_bn']."\"" : $newData['item_bn'],
                    'item_spec_desc' => $newData['item_spec_desc'] ?? '',
                    'remark' => $newData['remark'],
                ];
            }
            yield $orderList;
        }
    }
}
