<?php

namespace PromotionsBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use PromotionsBundle\Services\PromotionGroupsTeamService;

class PromotionGroupsTeamTest extends TestBaseService
{
    public function testForceTeamFailIfPaymentTimeOverEndTime()
    {
        (new PromotionGroupsTeamService())->forceTeamFailIfPaymentTimeOverEndTime(["3106644000060164", "3505724000020628"]);
    }
}
