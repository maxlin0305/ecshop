<?php

namespace PaymentBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\MemberService;
use AdaPayBundle\Services\OpenAccountService;
use PaymentBundle\Services\Payments\AdaPaymentService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\ChinaumsPayService;

class Payment extends Controller
{
    /**
     * @SWG\Post(
     *     path="/trade/payment/setting",
     *     summary="支付配置信息保存",
     *     tags={"订单"},
     *     description="支付配置信息保存",
     *     operationId="setPaymentSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型", required=true, type="string"),
     *     @SWG\Parameter( name="config", in="query", description="配置信息json数据", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function setPaymentSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->input('distributor_id', 0);

        if ($request->input('pay_type') == 'wxpay') {
            $paymentsService = new WechatPayService($distributorId);
            $config['app_id'] = $request->input('app_id');
            $config['merchant_id'] = $request->input('merchant_id');
            $config['key'] = $request->input('key');
            $config['is_servicer'] = $request->input('is_servicer');
            $config['servicer_merchant_id'] = $request->input('servicer_merchant_id');
            $config['servicer_app_id'] = $request->input('servicer_app_id');
            $config['is_open'] = $request->input('is_open');

            //证书文件
            $config['cert'] = $request->file('cert');
            $config['cert_key'] = $request->file('cert_key');
            // 微信APP支付的应用ID
            $config['app_app_id'] = $request->input('app_app_id');
        } elseif ($request->input('pay_type') == 'alipay') {
            $paymentsService = new AlipayService($distributorId);
            $config['app_id'] = $request->input('app_id');
            $config['private_key'] = $request->input('private_key');
            $config['ali_public_key'] = $request->input('ali_public_key');
            $config['is_open'] = 'true' == $request->input('is_open') ? true : false;
        } elseif ($request->input('pay_type') == 'hfpay') {
            $paymentsService = new HfPayService();
            $config['mer_cust_id'] = $request->input('mer_cust_id');
            $config['acct_id'] = $request->input('acct_id');
            $config['pfx_password'] = $request->input('pfx_password');
            $config['pfx_file'] = $request->file('pfx_file');
            $config['ca_pfx_file'] = $request->file('ca_pfx_file');
            $config['oca31_pfx_file'] = $request->file('oca31_pfx_file');
            $config['is_open'] = $request->input('is_open');
        } elseif ($request->input('pay_type') == 'adapay') {
            $paymentsService = new AdaPaymentService();
            $config['api_key'] = $request->input('api_key');
            $config['agent_public_key'] = $request->input('agent_public_key');
            $config['private_key'] = $request->input('private_key');
            $config['public_key'] = $request->input('public_key');
            $config['is_open'] = $request->input('is_open') == 'true' ? true : false;
        } elseif ($request->input('pay_type') == 'chinaumspay') {
            $operatorType = app('auth')->user()->get('operator_type');
            if ($operatorType == 'dealer') {
                $operatorId = app('auth')->user()->get('operator_id');
                $paymentsService = new ChinaumsPayService('dealer_'.$operatorId);
            } else {
                $distributorId = $request->input('distributor_id', 0);
                if ($distributorId > 0) {
                    $paymentsService = new ChinaumsPayService('distributor_'.$distributorId);
                } else {
                    $paymentsService = new ChinaumsPayService();
                }
            }

            $config['mid'] = $request->input('mid');// 商户号
            $config['tid'] = $request->input('tid');// 终端号
            $config['enterpriseid'] = $request->input('enterpriseid');// 企业用户号
            if ($operatorType != 'dealer' && $distributorId == 0) {
                $config['rate'] = $request->input('rate', '0');// 收单手续费  0.23%  设置0.23的数值
                //证书文件
                $config['rsa_private'] = $request->file('rsa_private');
                $config['password'] = $request->input('password');
                $config['rsa_public'] = $request->file('rsa_public');

                //平台分账信息
                $config['bank_name'] = $request->input('bank_name');// 开户行名称
                $config['bank_code'] = $request->input('bank_code');// 开户行行号
                $config['bank_account'] = $request->input('bank_account');// 开户行账号

                $config['is_open'] = $request->input('is_open') == 'true' ? true : false;
            }
        }

        $service = new PaymentsService($paymentsService);
        $service->setPaymentSetting($companyId, $config);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/payment/setting",
     *     summary="获取支付配置信息",
     *     tags={"订单"},
     *     description="获取支付配置信息",
     *     operationId="setPaymentSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="app_id", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getPaymentSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->input('distributor_id', 0);
        if ($request->input('pay_type') == 'wxpay') {
            $paymentsService = new WechatPayService($distributorId, false);
        } elseif ($request->input('pay_type') == 'alipay') {
            $paymentsService = new AlipayService($distributorId, false);
        } elseif ($request->input('pay_type') == 'hfpay') {
            $paymentsService = new HfPayService();
        } elseif ($request->input('pay_type') == 'adapay') {
            $paymentsService = new AdaPaymentService();
        } elseif ($request->input('pay_type') == 'chinaumspay') {
            $operatorType = app('auth')->user()->get('operator_type');
            if ($operatorType == 'dealer') {
                $operatorId = app('auth')->user()->get('operator_id');
                $paymentsService = new ChinaumsPayService('dealer_'.$operatorId);
            } else {
                $distributorId = $request->input('distributor_id', 0);
                if ($distributorId > 0) {
                    $paymentsService = new ChinaumsPayService('distributor_'.$distributorId);
                } else {
                    $paymentsService = new ChinaumsPayService();
                }
            }
        }

        $service = new PaymentsService($paymentsService);
        $data = $service->getPaymentSetting($companyId);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/list",
     *     summary="获取支付配置信息列表",
     *     tags={"订单"},
     *     description="获取支付配置信息列表",
     *     operationId="list",
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
    public function getPaymentSettingList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $distributorId = $request->input('distributor_id', 0);

        $result = [];

        //adapay
        $service = new AdaPaymentService();
        $adapay = $service->getPaymentSetting($company_id);
        $openAccountService = new OpenAccountService();
        $step = $openAccountService->openAccountStepService($company_id);
        if (!empty($adapay) && $adapay['is_open'] && $step['step'] == 4) {
            $memberService = new MemberService();
            if ($distributorId == 0) {
                $result[] = [
                    'pay_type_code' => 'adapay',
                    'pay_channel' => 'wx_lite',
                    'pay_type_name' => '微信支付'
                ];
            } else {
                $memberInfo = $memberService->getInfo(['company_id' => $company_id, 'operator_id' => $distributorId, 'operator_type' => 'distributor', 'audit_state' => 'E']);
                if ($memberInfo) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => '微信支付'
                    ];
                }
            }
        }

        //如果支持adapay支付默认走adapay  不走wxpay
        if (!$result) {
            //微信设置
            $service = new WechatPayService($distributorId);
            $wechat = $service->getPaymentSetting($company_id);
            if (!empty($wechat) && $wechat['is_open'] == 'true') {
                $result[] = [
                    'pay_type_code' => 'wxpay',
                    'pay_type_name' => '微信支付'
                ];
            }
        }


        //汇付天下设置
        $service = new HfPayService();
        $hfpay = $service->getPaymentSetting($company_id);
        if (!empty($hfpay) && $hfpay['is_open'] === 'true') {
            $result[] = [
                'pay_type_code' => 'hfpay',
                'pay_type_name' => '微信支付'
            ];
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/hfpayversionstatus",
     *     summary="获取汇付版本状态",
     *     tags={"订单"},
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
}
