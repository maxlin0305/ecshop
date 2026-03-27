<?php

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;
use OrdersBundle\Services\TradeService;

class RefundTrade extends Job
{
    public $tradeId;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($tradeId)
    {
        $this->tradeId = $tradeId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $tradeService = new TradeService();
        $tradeService->refundTrade($this->tradeId, false);
        return true;
    }
}
