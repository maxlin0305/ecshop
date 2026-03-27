<?php

namespace DistributionBundle\Tests\Services;

use DistributionBundle\Services\DistributorService;

class DistributorTest extends \EspierBundle\Services\TestBaseService
{
    public function testList()
    {
        $list = (new DistributorService())->lists([
            "company_id" => 1,
            "or" => [
                "name" => "%测试%",
                "distributor_id" => [1,2,3]
            ]
        ], [], 10, 1);
        $this->assertTrue(true);
    }
}
