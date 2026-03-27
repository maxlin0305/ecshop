<?php

namespace EcPayBundle\Http\ThirdApi\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EcPayCard extends Controller
{
    public function notify(Request $request)
    {
        app('log')->debug('ecpay_bind_card_notify callback => ' . var_export($request->all(), 1));
        return redirect(env('H5_URL') . '/subpages/card/bind-card-result?status=1');
    }
}
