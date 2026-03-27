<?php

namespace ThirdPartyBundle\Listeners;

use ThirdPartyBundle\Events\CustomDeclareOrderEvent;

class RealTimeDataUpload
{
    public function handle(CustomDeclareOrderEvent $event)
    {
        app('log')->debug("\n 海关数据上传 RealTimeDataUpload event=>:".var_export($event->entities, 1));
    }
}
