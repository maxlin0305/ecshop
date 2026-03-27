<?php

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;
use OrdersBundle\Services\StatementsService;
use OrdersBundle\Services\StatementDetailsService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Services\TradeService;

class GenerateStatementsJob extends Job
{
    private $companyId; //商户ID

    private $distributorId; //店铺ID

    private $period; //结算周期

    private $lastEndTime; //上次结算周期结束时间

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $distributorId, $period, $lastEndTime)
    {
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
        $this->period = $period;
        $this->lastEndTime = $lastEndTime;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $startTime = $this->lastEndTime + 1;
        switch ($this->period[1]) {
            case 'day':
                $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.$this->period[0].' day');
                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-d H:i:s', $endTime) .'+'.$this->period[0].' day');
                }
                break;
            case 'week':                
                if (strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.(7 - date('w', $this->lastEndTime)).' day') == $this->lastEndTime) {
                    $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.($this->period[0] * 7 + 7 - date('w', $this->lastEndTime)).' day');
                } else {
                    $endTime = strtotime(date('Y-m-d H:i:s', $this->lastEndTime) .'+'.($this->period[0] * 7 - date('w', $this->lastEndTime)).' day');
                }

                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-d H:i:s', $endTime) .'+'.$this->period[0].' week');
                }
                break;
            case 'month':
                if (strtotime(date('Y-m-01', $this->lastEndTime).' +1 month') - 1 == $this->lastEndTime) {
                    $endTime = strtotime(date('Y-m-01', $this->lastEndTime).' +'.($this->period[0] + 1).' month') - 1;
                } else {
                    $endTime = strtotime(date('Y-m-01', $this->lastEndTime).' +'.$this->period[0].' month') - 1;
                }

                while ($endTime < time()) {
                    $result = $this->doGenerate($startTime, $endTime);
                    if (!$result) {
                        break;
                    }

                    $startTime = $endTime + 1;
                    $endTime = strtotime(date('Y-m-01', $endTime) .'+'.($this->period[0] + 1).' month') - 1;
                }
                break;
        }

        return true;
    }

    private function doGenerate($startTime, $endTime) {
        $offset = 0;
        $limit = 500;

        $distributorService = new DistributorService();
        $distributor = $distributorService->getInfoSimple(['company_id' => $this->companyId, 'distributor_id' => $this->distributorId]);

        $summarized = [
            'company_id' => $this->companyId,
            'merchant_id' => $distributor['merchant_id'] ?? 0,
            'distributor_id' => $this->distributorId,
            'statement_no' => $this->genId(),
            'order_num' => 0,
            'total_fee' => 0,
            'freight_fee' => 0,
            'intra_city_freight_fee' => 0,
            'rebate_fee' => 0,
            'refund_fee' => 0,
            'statement_fee' => 0,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        $summarizedService = new StatementsService();
        $detailService = new StatementDetailsService();
        $tradeService = new TradeService();

        try {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();

            $summarized = $summarizedService->create($summarized);

            do {
                $qb = $conn->createQueryBuilder();
                $list = $qb->select('t.trade_id,o.order_id,o.total_fee,o.freight_fee,o.receipt_type,coalesce(r.refund_fee, 0) as refund_fee,t.pay_type')
                    ->from('orders_normal_orders', 'o')
                    ->leftJoin('o', 'trade', 't', 'o.order_id = t.order_id')
                    ->leftJoin('o', '(select order_id,sum(refund_fee) as refund_fee from aftersales_refund a where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") group by order_id)', 'r', 'o.order_id = r.order_id')
                    ->andWhere($qb->expr()->eq('o.company_id', $this->companyId))
                    ->andWhere($qb->expr()->eq('o.distributor_id', $this->distributorId))
                    ->andWhere($qb->expr()->eq('t.trade_state', $qb->expr()->literal('SUCCESS')))
                    ->andWhere($qb->expr()->neq('t.is_settled', 1))
                    ->andWhere($qb->expr()->gt('o.order_auto_close_aftersales_time', 0))
                    ->andWhere($qb->expr()->lt('o.order_auto_close_aftersales_time', $endTime))
                    ->andWhere($qb->expr()->notIn('o.order_id', '(select order_id from aftersales_refund where refund_status in("READY", "PROCESSING"))'))
                    ->addOrderBy('o.create_time', 'ASC')
                    ->setFirstResult($offset)->setMaxResults($limit)
                    ->execute()->fetchAll();

                $details = [];
                foreach ($list as $row) {
                    $detail = [
                        'company_id' => $this->companyId,
                        'merchant_id' => $distributor['merchant_id'] ?? 0,
                        'distributor_id' => $this->distributorId,
                        'statement_id' => $summarized['id'],
                        'statement_no' => $summarized['statement_no'],
                        'order_id' => $row['order_id'],
                        'total_fee' => $row['total_fee'],
                        'rebate_fee' => 0, //暂时不考虑
                        'refund_fee' => $row['refund_fee'],
                        'pay_type' => $row['pay_type'],
                    ];
                    if ($row['receipt_type'] == 'dada') {
                        $detail['freight_fee'] = 0;
                        $detail['intra_city_freight_fee'] = $row['freight_fee'];
                    } else {
                        $detail['freight_fee'] = $row['freight_fee'];
                        $detail['intra_city_freight_fee'] = 0;
                    }
                    $detail['statement_fee'] = bcsub($row['total_fee'], bcadd($row['refund_fee'], $detail['intra_city_freight_fee']));

                    $summarized['order_num'] += 1;
                    $summarized['total_fee'] += $detail['total_fee'];
                    $summarized['freight_fee'] += $detail['freight_fee'];
                    $summarized['intra_city_freight_fee'] += $detail['intra_city_freight_fee'];
                    $summarized['rebate_fee'] += $detail['rebate_fee'];
                    $summarized['refund_fee'] += $detail['refund_fee'];
                    $summarized['statement_fee'] += $detail['statement_fee'];

                    $details[] = $detail;
                }

                if (!empty($list)) {
                    $detailService->batchInsert($details);
                    $tradeService->updateBy(['trade_id' => array_column($list, 'trade_id')], ['is_settled' => 1]);
                }

                $offset += $limit;
            } while (count($list) == $limit);

            $summarizedService->updateOneBy(['id' => $summarized['id']], $summarized);
            $summarizedService->setLastEndTime($this->companyId, $this->distributorId, $endTime);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug('生成结算单失败：company_id_'.$this->companyId.' distributor_id_'.$this->distributorId.' '.$e->getMessage());
            return false;
        }

        return true;
    }

    private function genId()
    {
        return date('Ymd').rand(1000, 9999).str_pad($this->distributorId % 10000, 4, '0', STR_PAD_LEFT);
    }
}
