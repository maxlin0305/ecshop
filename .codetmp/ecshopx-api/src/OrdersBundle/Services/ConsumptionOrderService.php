<?php

namespace OrdersBundle\Services;

use Illuminate\Support\Facades\DB;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\NormalOrders;

/**
 * *
 * 处理会员等级
 */
class ConsumptionOrderService
{

    public $orderType = 'normal';
    public $pageSize = '100';

    /**
     * 处理会员等级
     */
    public function handleMembershipLevel()
    {
        //获取全部订单 处理每月一号
        $today = date("d");
        app('log')->info("开始处理会员等级！");
        if ($today != 1) {
            //每月一号处理
            app('log')->info("每月一号处理");
            return true;
        }

        $getSettings = self::getSettingsNew();
        $statisticalMonth = $getSettings['statistical_month'] ?? 1;
        $startDate = strtotime(" -$statisticalMonth months");
        $endDate = time();
        return $this->handle($startDate,$endDate);

    }


    /**
     * 运行任务。
     * 订单过了售后期后，进行会员的消费累加，并升级会员等级
     *
     * @param Mailer $mailer
     * @return void
     */
    public function handle($startDate,$endDate)
    {
        $time = time();
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('orders.order_id', 'count(a.aftersales_bn) as aftersales_count')
            ->from('orders_normal_orders', 'orders')
            ->leftJoin('orders', 'aftersales', 'a', 'orders.order_id = a.order_id')
            ->where($qb->expr()->lt('orders.order_auto_close_aftersales_time', $time))
            ->andWhere($qb->expr()->gte('orders.create_time', $startDate))
            ->andWhere($qb->expr()->lte('orders.create_time', $endDate))

            ->andWhere($qb->expr()->eq('orders.order_status', $qb->expr()->literal('DONE')))
            ->andWhere($qb->expr()->eq('orders.is_consumption', 0))
            ->andWhere($qb->expr()->notIn('a.aftersales_status', ['2', '3', '4']))
            ->groupby('orders.order_id');
        $haveAftersalesList = $qb->execute()->fetchAll();
        $have_aftersales = [];
        $haveAftersalesList and $have_aftersales = array_column($haveAftersalesList, 'order_id');
        app('log')->debug('有售后未处理会员升级的订单:' . json_encode($have_aftersales));
        // 查询符合条件的订单
        $filter = [
            'order_auto_close_aftersales_time|lt' => $time,
            'order_status' => 'DONE',
//            'is_consumption' => 0,
        ];
        $have_aftersales and $filter['order_id|notIn'] = $have_aftersales;


        $where = '';
        if (count($have_aftersales) !== 0){
            $where = "and " . $qb->expr()->notIn('order_id', $have_aftersales);
        }


        $conn = app('registry')->getConnection('default');



        $sql = "select user_id,pay_fee
from trade
where trade_state = 'SUCCESS' and pay_type <> 'point'

$where
group by user_id
;";
        $list = DB::select($sql);

        $memberService = new MemberService();
        foreach ($list as $item){


            $memberService->updateMemberConsumptionNew($item->user_id, $item->pay_fee);
        }


//        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
//        $cols = 'order_id';
//        $orderBy = ['create_time' => 'DESC'];
//        $i = 0;
//        while (1) {
//            app('log')->info('line:'.__LINE__.',start i===>'.$i);
//            $orderList = $normalOrdersRepository->getList($filter, 0, $this->pageSize, $orderBy, $cols);
//            if (!$orderList) {
//                app('log')->info('line:'.__LINE__.',not i===>'.$i);
//                break;
//            }
//            var_dump(count($orderList));
////            $order_ids = array_column($orderList, 'order_id');
////            $this->doNotAftersalesConsumption($order_ids);
////            app('log')->info('line:'.__LINE__.',end i===>'.$i);
//            $i++;
//        }
        return true;
    }

    /**
     * 处理没有售后单的订单
     * @param array $order_ids 订单号
     * @return
     */
    public function doNotAftersalesConsumption($order_ids)
    {
        // 查询有已处理售后的订单
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('order_id', 'count(*) as count')
            ->from('aftersales')
            ->where($qb->expr()->in('order_id', $order_ids))
            ->andWhere($qb->expr()->eq('aftersales_status', 2))
            ->groupby('order_id');
        $processedList = $qb->execute()->fetchAll();
        $processed_order_ids = array_column($processedList, 'order_id');
        $refundedList = [];
        if ($processed_order_ids) {
            // 查询已退款金额
            $pay_type = 'point';
            $refund_status = 'SUCCESS';
            $qb = $conn->createQueryBuilder();
            $qb->select('order_id', 'sum(refunded_fee) as sum_refunded_fee')
                ->from('aftersales_refund')
                ->where($qb->expr()->in('order_id', $processed_order_ids))
                ->andWhere($qb->expr()->neq('pay_type', $qb->expr()->literal($pay_type)))
                ->andWhere($qb->expr()->eq('refund_status', $qb->expr()->literal($refund_status)))
                ->groupby('order_id');
            $refundedList = $qb->execute()->fetchAll();
            $refundedList = array_column($refundedList, null, 'order_id');
        }
        // 根据订单的支付单pay_fee 进行处理
        // 查询订单的支付单
        $trade_state = 'SUCCESS';
        $pay_type = 'point';
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('company_id', 'order_id', 'user_id', 'pay_fee')
            ->from('trade')
            ->where($qb->expr()->in('order_id', $order_ids))
            ->andWhere($qb->expr()->eq('trade_state', $qb->expr()->literal($trade_state)))
            ->andWhere($qb->expr()->neq('pay_type', $qb->expr()->literal($pay_type)));
        $tradeList = $qb->execute()->fetchAll();
        $consumption = [];
        foreach ($tradeList as $trade) {
            $pay_fee = $consumption[$trade['user_id']]['pay_fee'] ?? 0;
            $pay_fee = bcadd($pay_fee, $trade['pay_fee']);
            if (isset($refundedList[$trade['order_id']])) {
                $pay_fee = bcsub($pay_fee, $refundedList[$trade['order_id']]['sum_refunded_fee']);
            }
            $consumption[$trade['user_id']]['pay_fee'] = $pay_fee;
            $consumption[$trade['user_id']]['company_id'] = $trade['company_id'];
            $consumption[$trade['user_id']]['order'][] = [
                'order_id' => $trade['order_id'],
                'pay_fee' => $trade['pay_fee'],
                'sum_refunded_fee' => $refundedList[$trade['order_id']]['sum_refunded_fee'] ?? 0,
            ];
        }
        print_r($consumption);
        // 处理会员累加消费金额，并升级等级
//        $this->doConsumption($consumption);
//        $this->updateIsConsumption($order_ids);
        return true;
    }

    /**
     * 处理会员升级
     * @param array $data 需要处理的会员和金额
     * @return
     */
    public function doConsumption($data)
    {
        app('log')->info('doConsumption  data===>' . json_encode($data));
        $memberService = new MemberService();
        foreach ($data as $user_id => $_data) {
            $user_id = intval($user_id);
            if ($user_id <= 0 || intval($_data['pay_fee']) <= 0) {
                continue;
            }
            app('log')->info('user_id:' . $user_id . ',company_id:' . $_data['company_id'] . ',pay_fee:' . $_data['pay_fee']);
            $memberService->updateMemberConsumption($user_id, $_data['company_id'], $_data['pay_fee']);
        }
        return true;
    }

    /**
     * 修改订单的is_consumption状态
     * @param array $order_ids 订单号
     * @return
     */
    public function updateIsConsumption($order_ids)
    {
        // 记录已处理状态
        $filter = [
            'order_id' => $order_ids
        ];
        $data = [
            'is_consumption' => 1,
        ];
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersRepository->updateBy($filter, $data);
        app('log')->debug('已处理会员升级的订单:' . var_export($order_ids, 1));
        return true;
    }


    /**
     * 获取会员卡设置
     */
    public function getSettingsNew(): array
    {

        $config = [
            'statistical_month' => 1,
        ];
        $redis = app('redis')->connection('default');
        $result = $redis->get('membercard-settings');
        if ($result) {
            $result = json_decode($result, true);
        }
        return array_merge($config, $result ?: []);
    }

}
