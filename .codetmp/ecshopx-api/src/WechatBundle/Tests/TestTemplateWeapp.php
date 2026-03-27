<?php

namespace WechatBundle\Tests;

use EspierBundle\Services\TestBaseService;
use WechatBundle\Services\Wxapp\TemplateService;

class TestTemplateWeapp extends TestBaseService
{
    public function testGetTemplateWeappList()
    {
        $list = (new TemplateService())->getTemplateWeappList(1);
        $this->assertIsArray($list);
    }

    public function testGetTemplateWeappDetail()
    {
        $detail = (new TemplateService())->getTemplateWeappDetail(1, 30);
        $this->assertIsArray($detail);
    }
}
