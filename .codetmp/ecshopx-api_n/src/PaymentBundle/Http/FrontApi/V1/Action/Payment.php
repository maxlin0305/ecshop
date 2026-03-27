<?php

namespace PaymentBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\Payments\AdaPaymentService;
use AdaPayBundle\Services\OpenAccountService;
use PaymentBundle\Services\PaymentsService;
use DepositBundle\Services\DepositTrade;
use PaymentBundle\Services\Payments\AlipayService;
use OrdersBundle\Services\TradeService;

class Payment extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/list",
     *     summary="获取支付配置信息列表",
     *     tags={"支付"},
     *     description="获取支付配置信息列表",
     *     operationId="list",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="string"),
     *     @SWG\Parameter( name="platform", in="query", description="平台: wxPlatform:微信公众号, h5, app, pc, wxMiniProgram:微信小程序", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                    @SWG\Property(property="pay_type_code", type="string", description="支付渠道编码"),
     *                    @SWG\Property(property="pay_type_name", type="string", description="支付渠道名称"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getPaymentSettingList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $distributorId = $request->input('distributor_id', 0);

        $paymentService = new PaymentsService();
        $result = $paymentService->getPaymentSettingList($request->input('platform', ''), $company_id, $distributorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/hfpayversionstatus",
     *     summary="获取汇付版本状态",
     *     tags={"支付"},
     *     description="获取汇付版本状态",
     *     operationId="hfpayversionstatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="hfpay_version_status", type="string", description="是否汇付版本 true 汇付版本 false 非汇付版本"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getHfpayVersionStatus()
    {
        $hfpay_is_open = config('common.hfpay_is_open');
        $data = [
            'hfpay_version_status' => $hfpay_is_open
        ];

        return $this->response->array($data);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/trade/withdraw/list",
     *     summary="获取提现方式列表",
     *     tags={"支付"},
     *     description="获取提现方式列表",
     *     operationId="getWithDrawList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                    @SWG\Property(property="pay_type_code", type="string", description="支付渠道编码"),
     *                    @SWG\Property(property="pay_type_name", type="string", description="支付渠道名称"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getWithDrawList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];

        $result = [];


        $result[] = [
            'pay_type_code' => 'alipay',
            'pay_type_name' => '支付宝'
        ];

        //adapay
        $service = new AdaPaymentService();
        $adapay = $service->getPaymentSetting($company_id);
        $openAccountService = new OpenAccountService();
        $step = $openAccountService->openAccountStepService($company_id);
        if (!empty($adapay) && $step['step'] == 4) {
            $result[] = [
                'pay_type_code' => 'bankcard',
                'pay_type_name' => '银行卡'
            ];
        }

        //微信设置
        $service = new WechatPayService();
        $wechat = $service->getPaymentSetting($company_id);
        if (!empty($wechat)) {
            $result[] = [
                'pay_type_code' => 'wechat',
                'pay_type_name' => '微信支付'
            ];
        }


        //汇付天下设置
        $service = new HfPayService();
        $hfpay = $service->getPaymentSetting($company_id);
        if (!empty($hfpay)) {
            $result[] = [
                'pay_type_code' => 'hfpay',
                'pay_type_name' => '微信支付(hfpay)'
            ];
        }

        return $this->response->array($result);
    }

    // PC端支付宝支付完成页面返回更新支付状态
    public function alipayResult(Request $request)
    {
        $authInfo = $request->get('auth');
        $data = $request->input();
        app('log')->info('alipay:response:' . var_export($data, 1));
        // 获取tradeInfo
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $data['out_trade_no']]);
        $distributorId = $tradeInfo['distributor_id'] ?? 0;
        $alipayService = new AlipayService($distributorId);
        $alipay = $alipayService->getPayment($authInfo['company_id']);
        $data = AlipayService::encoding($data, 'utf-8', $data['charset'] ?? 'gb2312');
        $params = $alipay->verify($data); // 是的，验签就这么简单！

        $payResult = $alipay->find($params->out_trade_no, 'web');
        // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
        // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
        // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
        // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
        // 4、验证app_id是否为该商户本身。
        // 5、其它业务逻辑情况
        if ($payResult->trade_status == 'TRADE_SUCCESS' || $payResult->trade_status == 'TRADE_FINISHED') {
            $status = 'SUCCESS';
        } else {
            $status = $payResult->trade_status;
        }

        parse_str(urldecode($payResult['passback_params']), $returnData);

        if (isset($returnData['attach']) && $returnData['attach'] == 'depositRecharge') {
            $depositTrade = new DepositTrade();
            $options['pay_type'] = $returnData['pay_type'];
            $options['transaction_id'] = $payResult->trade_no;
            $depositTrade->rechargeCallback($payResult->out_trade_no, $status, $options);
        } else {
            $options['pay_type'] = $returnData['pay_type'];
            $options['transaction_id'] = $payResult->trade_no;
            $tradeService->updateStatus($payResult->out_trade_no, $status, $options);
        }

        return $this->response->array(['status' => $status]);
    }
}
