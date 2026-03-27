<?php

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use PromotionsBundle\Interfaces\TurntableWinningPrize;

class TurntableFactory
{
    private $prize;

    public function __construct(TurntableWinningPrize $turntableWinningPrize)
    {
        $this->prize = $turntableWinningPrize;
    }

    public function doPrize()
    {
        return $this->prize->grantPrize();
    }
}
