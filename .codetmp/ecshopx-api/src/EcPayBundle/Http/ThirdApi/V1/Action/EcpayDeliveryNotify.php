<?php

namespace EcPayBundle\Http\ThirdApi\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OrdersBundle\Services\OrderEcpayDeliveryService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EcpayDeliveryNotify extends Controller
{
    public function handle(Request $request)
    {
        $params = $request->all();
        app('log')->debug('ecpay_delivery_notify callback => ' . var_export($params, 1));
        $orderEcpayDeliveryService = new OrderEcpayDeliveryService();
        $orderEcpayDeliveryService->saveNotify($params);
        return 'OK';
    }
}
