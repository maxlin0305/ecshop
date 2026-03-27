<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use MembersBundle\Services\MemberService;
use OrdersBundle\Services\TradeService;
use EspierBundle\Services\ExportFileService;
use DistributionBundle\Services\DistributorService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use AftersalesBundle\Services\AftersalesRefundService;

class TradeExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        // 是否需要數據脫敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $tradeService = new TradeService();
        $count = $tradeService->getTradeCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."trade";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'orderId' => '訂單號',
            'tradeId' => '交易單號',
            'mobile' => '用戶手機號',
            'user_name' => ' 用戶名',
            'totalFee' => '訂單總金額',
            'payFee' => '訂單實付金額',
            'discountFee' => '訂單優惠金額',
            'transactionId' => '支付流水號',
            'payDate' => '支付時間',
            'body' => '交易描述',
            'detail' => '交易詳情',
            'store_name' => '店鋪名稱',
            'shop_name' => '門店名稱',
            'feeType' => '支付貨幣類型',
            'tradeState' => '交易狀態',
            'payType' => '支付方式',
            'timeStart' => '交易開始時間',
            'timeExpire' => '交易結束時間',
            'tradeSourceType' => '交易單來源類型',
            'if_refund' => '是否退款',
        ];
        return $title;
    }
    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->getTitle();
        $tradeSourceType = [
            'normal' => '實體訂單購買',
            'normal_groups' => '實體商品拼團支付',
            'normal_seckill' => '實體商品秒殺支付',
            'normal_community' => '社區訂單購買',
            'membercard' => '會員卡購買',
            'service' => '服務訂單購買',
            'groups' => '服務商品拼團支付',
            'order_pay' => '門店線下買單',
            'diposit' => '預存款購買'
        ];
        $tradeState = [
            'NOTPAY' => '未支付',
            'REFUND_PROCESS' => '退款中',
            'REFUND_SUCCESS' => '退款成功',
            'SUCCESS' => '支付成功',
        ];
        $payType = [
            'deposit' => '預存款',
            'wxpay' => '微信',
            'pos' => 'POS刷卡',
            'localPay' => '零元訂單'
        ];
        $tradeService = new TradeService();
        $memberService = new MemberService();
        $shopsService = new ShopsService(new WxShopsService());
        $distributorService = new DistributorService();
        $aftersalesRefundService = new AftersalesRefundService();

        $limit = 500;
        $orderBy = ['time_start' => 'DESC'];
        $fileNum = ceil($count / $limit);

        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $data = $tradeService->getTradeList($filter, $orderBy, $limit, $j);

            $userIds = array_unique(array_column($data['list'], 'userId'));
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $shopIds = array_filter(array_unique(array_column($data['list'], 'shopId')));
            if ($shopIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'wx_shop_id' => $shopIds,
                ];
                $shopList = $shopsService->getShopsList($sFilter, 1, $limit);
                $shopData = array_column($shopList['list'], null, 'wxShopId');
            }

            $storeIds = array_filter(array_unique(array_column($data['list'], 'distributorId')));
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $tradeIds = array_column($data['list'], 'tradeId');
            if ($tradeIds) {
                $refunList = $aftersalesRefundService->getList(['trade_id' => $tradeIds, 'refund_status' => 'SUCCESS']);
                $refunList = array_column($refunList['list'], 'refund_bn', 'trade_id');
            }

            foreach ($data['list'] as $key => $value) {
                $username = $userData[$value['userId']]['username'] ?? '--';
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $username != '--' and $username = data_masking('truename', (string) $username);
                }
                foreach ($title as $k => $v) {
                    if (in_array($k, ['orderId', 'tradeId', 'transactionId']) && isset($value[$k])) {
                        $orderList[$key][$k] = "\"'".$value[$k]."\"";
                    } elseif (in_array($k, ['totalFee', 'payFee', 'discountFee']) && isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k] / 100;
                    } elseif (in_array($k, ['timeStart', 'timeExpire']) && isset($value[$k]) && $value[$k]) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "tradeSourceType" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeSourceType[$value[$k]] ?? '--';
                    } elseif ($k == "tradeState" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeState[$value[$k]] ?? '--';
                    } elseif ($k == "payType" && isset($value[$k])) {
                        $orderList[$key][$k] = $payType[$value[$k]] ?? '--';
                    } elseif ($k == "store_name") {
                        $orderList[$key][$k] = $storeData[$value['distributorId']]['name'] ?? '--';
                    } elseif ($k == "shop_name") {
                        $orderList[$key][$k] = $shopData[$value['shopId']]['storeName'] ?? '--';
                    } elseif ($k == "user_name") {
                        $orderList[$key][$k] = $username;
                    } elseif ($k == "payDate") {
                        $orderList[$key][$k] = empty($value[$k]) ? '--' : $value[$k];
                    } elseif ($k == "if_refund") {
                        $orderList[$key][$k] = isset($refunList[$value['tradeId']]) ? '是' : '否';
                    } elseif (isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k];
                    } else {
                        $orderList[$key][$k] = '--';
                    }
                }
                $orderList[$key]['feeType'] = 'TWD';
            }
            yield $orderList;
        }
    }
}
