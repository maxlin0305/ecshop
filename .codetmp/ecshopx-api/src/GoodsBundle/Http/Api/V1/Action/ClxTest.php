<?php

namespace GoodsBundle\Http\Api\V1\Action;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Listeners\NormalOrderCancelAppMsgNotify;
use OrdersBundle\Listeners\TradeFinishAppMsgNotify;

class ClxTest extends Controller
{
    //chulx test
    public function test()
    {
        echo time();
//        $src2 = new TradeFinishAppMsgNotify();
//        $src2->testSendAppMsg();
//        echo '订单下单';
        $src = new NormalOrderCancelAppMsgNotify();
        $src->testSendAppMsg();
        echo '订单取消';

    }

}
