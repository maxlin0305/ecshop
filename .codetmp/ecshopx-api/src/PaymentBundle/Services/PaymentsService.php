<?php

namespace PaymentBundle\Services;

use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use AdaPayBundle\Services\OpenAccountService;
use AftersalesBundle\Entities\AftersalesRefund;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\MerchantTradeService;
use PaymentBundle\Interfaces\Payment;
use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\AlipayAppService;
use PaymentBundle\Services\Payments\AlipayH5Service;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\AlipayMiniService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\WechatAppPayService;
use PaymentBundle\Services\Payments\WechatH5PayService;
use PaymentBundle\Services\Payments\WechatJSPayService;
use PaymentBundle\Services\Payments\WechatWebPayService;
use PointBundle\Services\PointMemberRuleService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\RefundErrorLogsService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use OrdersBundle\Jobs\TradeRefundStatistics;

// 支付服务
class PaymentsService
{
    /**
     * 支付方式具体实现类
     */
    public $paymentService;

    public function __construct($paymentService = null)
    {
        if ($paymentService && $paymentService instanceof Payment) {
            $this->paymentService = $paymentService;
        }
    }

    /**
     * 保存支付方式配置
     */
    public function setPaymentSetting($companyId, $configData)
    {
        return $this->paymentService->setPaymentSetting($companyId, $configData);
    }

    /**
     * 获取支付方式配置信息 function
     *
     * @return void
     */
    public function getPaymentSetting($companyId)
    {
        return $this->paymentService->getPaymentSetting($companyId);
    }

    /**
     * 获取支付配置并判断是否成功 function
     *
     * @return array
     */
    private function getPayConfig($companyId, $distributorId = 0)
    {
        $payConf = $this->paymentService->getPaymentSetting($companyId, $distributorId);
        if (!$payConf) {
            throw new BadRequestHttpException('不支持支付服务，请联系商家');
        }

        return $payConf;
    }

    /**
     * 用户对储值卡进行储值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        if (!$this->paymentService) {
//            $payType = app('redis')->get('paymentTypeOpenConfig:'. sha1($data['company_id']));
            if ($data['pay_type'] == 'wxpay') {
                $data['pay_type'] = 'wxpay';
                $this->paymentService = new WechatPayService();
            }
        }
        $this->getPayConfig($data['company_id']);
        $result = $this->paymentService->depositRecharge($authorizerAppId, $wxaAppId, $data);

        $result['trade_info'] = [
            'order_id' => $data['deposit_trade_id'],
            'trade_id' => $data['deposit_trade_id'],
        ];

        return $result;
    }

    /**
     * 用户进行付款支付
     */
    public function doPayment($authorizerAppId, $wxaAppId, array $data, $isDiscount = false)
    {
        $data['authorizer_appid'] = $authorizerAppId;
        if ('wxpay' == substr($data['pay_type'], 0, 5)) {
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id']);
            app('log')->info('payConf===>'.var_export($payConf,1));
            $data['mch_id'] = $payConf['merchant_id'];
        } else {
            $payConf = $this->getPayConfig($data['company_id']);
        }
        // 添加交易单号
        $tradeService = new TradeService();
        // $isDiscount 是否需要计算优惠
        // 门店直接支付需要计算优惠信息
        // 创建订单已经在订单中计算好了优惠信息，那么则不需要在计算了
        $returnUrl = '';
        if (isset($data['return_url'])) {
            $returnUrl = $data['return_url'];
        }

        $authCode = '';
        if (isset($data['auth_code'])) {
            $authCode = trim($data['auth_code']);
        }

        $distributorInfo = $data['distributor_info'] ?? [];

        $newData = [];
        if (isset($data['order_id']) && $data['order_id']) {
            $newData = $tradeService->getInfo(['order_id' => $data['order_id'], 'pay_type' => $data['pay_type']]);
        }
        if (!$newData) {
            $newData = $tradeService->create($data, $isDiscount);
        }

        //如果为0元订单，直接支付成功
        if (isset($newData['pay_status']) && $newData['pay_status']) {
            return ['pay_status' => true];
        }

        $attributes = [
            'body' => $data['body'],
            'order_id' => $data['order_id'] ?? '',
            'detail' => $data['detail'] ?: $data['body'],
            'trade_id' => $newData['trade_id'],
            'pay_fee' => $newData['pay_fee'],
            'open_id' => $data['open_id'],
            'company_id' => $data['company_id'],
            'mobile' => $data['mobile'],
            'user_id' => $data['user_id'],
            'shop_id' => $data['shop_id'] ?? '',
            'member_card_code' => $data['member_card_code'] ?? '',
            'return_url' => $returnUrl,
            'auth_code' => $authCode,
            'trade_source_type' => $newData['trade_source_type'],
            'distributor_info' => $distributorInfo,
            'ecpay_card_id' => $data['ecpay_card_id'] ?? '',
        ];

        if ($data['pay_type'] == 'adapay') {
            $attributes['pay_channel'] = $data['pay_channel'];
        }
        if ($data['pay_type'] == 'alipaymini') {
            $attributes['alipay_user_id'] = $data['alipay_user_id'];
        }
        try {
            $result = $this->paymentService->doPay($authorizerAppId, $wxaAppId, $attributes);

            $result['trade_info'] = [
                'order_id' => $attributes['order_id'],
                'trade_id' => $attributes['trade_id'],
                'trade_source_type' => $newData['trade_source_type'],
            ];
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
        return;
    }

    /**
     * 订单退款
     * 订单退款到指定账户
     */
    public function doRefund($companyId, $wxaAppId, array$data)
    {
        $data['company_id'] = $companyId;

        if ('wxpay' == substr($data['pay_type'], 0, 5)) {
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id']);
            if (!$payConf || !isset($payConf['cert_url']) || !isset($payConf['cert_key_url'])) {
                $result['status'] = 'FAIL';
                $result['error_desc'] = '请检查微信支付相关配置是否完成';
                return $result;
            }
        } elseif ('alipay' == substr($data['pay_type'], 0, 6)) {
            $payConf = $this->getPayConfig($data['company_id']);
            if (!$payConf) {
                $result['status'] = 'FAIL';
                $result['error_desc'] = '请检查支付宝支付相关配置是否完成';
                return $result;
            }
        }

        try {
            // 执行付款
            if (!isset($data['refund_fee'])) {
                $data['refund_fee'] = $data['pay_fee'];
            }
            $result = $this->paymentService->doRefund($companyId, $wxaAppId, $data);
            if ($result['status'] == 'FAIL') {
                $this->saveRefundError($companyId, $wxaAppId, $data, $result);

                // $result['status'] = 'SUCCESS';
                $result['refund_id'] = 0;
            } else {
                if (isset($data['refund_bn'])) {
                    $refundFilter = [
                        'company_id' => $data['company_id'],
                        'refund_bn' => $data['refund_bn'],
                    ];
                    $aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
                    $aftersalesRefundRepository->updateOneBy($refundFilter, ['refund_id' => $result['refund_id']]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'FAIL';
            $result['error_code'] = $e->getCode();
            $result['error_desc'] = $e->getMessage();
            app('log')->debug('wechat doRefund result:' . var_export($result, true));

            $this->saveRefundError($companyId, $wxaAppId, $data, $result);
            // $result['status'] = 'SUCCESS';
            $result['refund_id'] = 0;
        }



        if ($result['status'] == 'SUCCESS') {
            $job = (new TradeRefundStatistics($data))->delay(5);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        return $result;
    }

    private function saveRefundError($companyId, $wxaAppId, $data, $result)
    {
        $refundErrorLogsService = new RefundErrorLogsService();
        $errorLogsData = [
            'company_id' => $companyId,
            'order_id' => $data['order_id'],
            'wxa_appid' => $wxaAppId,
            'data_json' => json_encode($data),
            'status' => $result['status'],
            'error_code' => $result['error_code'],
            'error_desc' => $result['error_desc'],
            'merchant_id' => $data['merchant_id'],
            'distributor_id' => $data['distributor_id'],
        ];


        $refundErrorLogsService->create($errorLogsData);
    }



    /**
     * 商家支付
     * 商家打款到指定账户
     */
    public function merchantPayment($companyId, $wxaAppId, array$data)
    {
        $data['company_id'] = $companyId;

        $payConf = $this->getPayConfig($data['company_id']);
        if (!$payConf || !isset($payConf['cert_url']) || !isset($payConf['cert_key_url'])) {
            $result['status'] = 'FAIL';
            $result['error_desc'] = '请检查微信支付相关配置是否完成';
            return $result;
        }

        // 暂时为微信企业付款
        $data['payment_action'] = 'WECHAT';
        $data['check_name'] = 'NO_CHECK';
        $data['mchid'] = $payConf['merchant_id'];
        $data['mch_appid'] = $wxaAppId;
        $merchantTradeService = new MerchantTradeService();
        $data = $merchantTradeService->create($data);

        try {
            // 执行付款
            $result = $this->paymentService->merchantPayment($companyId, $wxaAppId, $data);
        } catch (\Exception $e) {
            $result['status'] = 'FAIL';
            $result['error_code'] = $e->getCode();
            $result['error_desc'] = $e->getMessage();
            app('log')->debug('wechat merchantPayment result:' . var_export($result, true));
        }

        $filter['merchant_trade_id'] = $data['merchant_trade_id'];
        $filter['company_id'] = $companyId;
        return $merchantTradeService->updateStatus($filter, $result);
    }

    /**
     * 用户进行付款支付
     */
    public function query($data)
    {
        try {
            return $this->paymentService->query($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取支付订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        return $this->paymentService->getPayOrderInfo($companyId, $trade_id);
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $refund_bn)
    {
        return $this->paymentService->getRefundOrderInfo($companyId, $refund_bn);
    }

    public function getPaymentSettingList($type, $company_id, $distributorId)
    {
        //adapay
        $service = new AdaPaymentService();
        $adaPay = $service->getPaymentSetting($company_id);
        $openAccountService = new OpenAccountService();
        $adaPayAccount = $openAccountService->openAccountStepService($company_id);
        $adaPayMemberService = new AdaPayMemberService();
        $adaPayMember = $adaPayMemberService->getInfo(['company_id' => $company_id, 'operator_id' => $distributorId, 'operator_type' => 'distributor', 'audit_state' => 'E']);
        $adaPayPayment = [];
        if ($adaPay && $adaPay['is_open'] && $adaPayAccount['step'] == 4) {
            if ($distributorId == 0 || $adaPayMember) {
                $adaPayPayment = json_decode($adaPayAccount['info']['MerchantResident']['add_value_list'], true);
            }
        }

        $result = [];
        switch ($type) {
            case 'wxPlatform':
                if (isset($adaPayPayment['wx_pub'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_pub',
                        'pay_type_name' => '微信支付'
                    ];
                } else {
                    $service = new WechatJSPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayjs',
                            'pay_type_name' => '微信支付'
                        ];
                    }
                }
                break;
            case 'h5':
                if (isset($adaPayPayment['wx_lite'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => '微信支付'
                    ];
                } else {
                    $service = new WechatH5PayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayh5',
                            'pay_type_name' => '微信支付'
                        ];
                    }
                }

                if (isset($adaPayPayment['alipay_wap'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay_wap',
                        'pay_type_name' => '支付宝'
                    ];
                } else {
                    $service = new AlipayH5Service($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipayh5',
                            'pay_type_name' => '支付宝'
                        ];
                    }
                }
                break;
            case 'app':
                if (isset($adaPayPayment['wx_lite'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => '微信支付'
                    ];
                } else {
                    $service = new WechatAppPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayapp',
                            'pay_type_name' => '微信支付'
                        ];
                    }
                }

                if (isset($adaPayPayment['alipay'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay',
                        'pay_type_name' => '支付宝'
                    ];
                } else {
                    $service = new AlipayAppService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipayapp',
                            'pay_type_name' => '支付宝'
                        ];
                    }
                }
                break;
            case 'pc':
                if (isset($adaPayPayment['wx_pub'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_qr',
                        'pay_type_name' => '微信支付'
                    ];
                } else {
                    $service = new WechatWebPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpaypc',
                            'pay_type_name' => '微信支付'
                        ];
                    }
                }

                if (isset($adaPayPayment['alipay_qr'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay_qr',
                        'pay_type_name' => '支付宝'
                    ];
                } else {
                    $service = new AlipayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipay',
                            'pay_type_name' => '支付宝'
                        ];
                    }
                }
                break;
            case 'alipaymini':
                $service = new AlipayMiniService($distributorId);
                $setting = $service->getPaymentSetting($company_id);
                if (!empty($setting) && $setting['is_open'] == 'true') {
                    $result[] = [
                        'pay_type_code' => 'alipaymini',
                        'pay_type_name' => '支付宝'
                    ];
                }
                break;
            case 'wxMiniProgram':
            default:
                if (isset($adaPayPayment['wx_lite'])) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => '微信支付'
                    ];
                } else {
                    //微信设置
                    $service = new WechatPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpay',
                            'pay_type_name' => '微信支付'
                        ];
                    }
                }

                //银行支付
                $umservice = new ChinaumsPayService();
                $ums = $umservice->getPaymentSetting($company_id);
                if ( !empty($ums) && $ums['is_open']) {
                    $result[] = [
                        'pay_type_code' => 'chinaums',
                        'pay_type_name' => '微信支付-银联'
                    ];
                }
                break;
        }
        // if ($type) {
        //     // 积分支付
        //     if ((new PointMemberRuleService($company_id))->getIsOpenPoint()) {
        //         $result[] = [
        //             'pay_type_code' => 'point',
        //             'pay_type_name' => '积分支付'
        //         ];
        //     }
        //     // 预存款
        //     $result[] = [
        //         'pay_type_code' => 'deposit',
        //         'pay_type_name' => '预存款支付'
        //     ];
        // }
        return $result;
    }

    /**
     * 用户获取token
     */
    public function getToken($authorizerAppId, $wxaAppId, array $data, $isDiscount = false)
    {
        $data['authorizer_appid'] = $authorizerAppId;
        if ('wxpay' == substr($data['pay_type'], 0, 5)) {
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id']);
            app('log')->info('payConf===>'.var_export($payConf,1));
            $data['mch_id'] = $payConf['merchant_id'];
        } else {
            $payConf = $this->getPayConfig($data['company_id']);
        }
        // 添加交易单号
        $tradeService = new TradeService();
        // $isDiscount 是否需要计算优惠
        // 门店直接支付需要计算优惠信息
        // 创建订单已经在订单中计算好了优惠信息，那么则不需要在计算了
        $returnUrl = '';
        if (isset($data['return_url'])) {
            $returnUrl = $data['return_url'];
        }

        $authCode = '';
        if (isset($data['auth_code'])) {
            $authCode = trim($data['auth_code']);
        }

        $distributorInfo = $data['distributor_info'] ?? [];

        $newData = [];

        if (isset($data['order_id']) && $data['order_id']) {
            $newData = $tradeService->getInfo(['order_id' => $data['order_id'], 'pay_type' => $data['pay_type']]);
        }
        if (!$newData) {
            $newData = $tradeService->create($data, $isDiscount);
        }
        if ($newData['trade_state'] != 'SUCCESS'){

            //更新订单号
            $merchantTradeNo = $this->generateOrderNumber();
            $tradeService->updateMerchantTradeNo($newData['trade_id'], $merchantTradeNo);
        }else{
            //更新订单号
            $merchantTradeNo = $newData['merchant_trade_no'];
        }


        $attributes = [
            'body' => $data['body'],
            'order_id' => $data['order_id'] ?? '',
            'detail' => $data['detail'] ?: $data['body'],
            'trade_id' => $newData['trade_id'],
            'pay_fee' => $newData['pay_fee'],
            'open_id' => $data['open_id'],
            'company_id' => $data['company_id'],
            'mobile' => $data['mobile'],
            'user_id' => $data['user_id'],
            'shop_id' => $data['shop_id'] ?? '',
            'member_card_code' => $data['member_card_code'] ?? '',
            'return_url' => $returnUrl,
            'auth_code' => $authCode,
            'trade_source_type' => $newData['trade_source_type'],
            'distributor_info' => $distributorInfo,
            'ecpay_card_id' => $data['ecpay_card_id'] ?? '',
            'merchant_trade_no' => $merchantTradeNo
        ];

        if ($data['pay_type'] == 'adapay') {
            $attributes['pay_channel'] = $data['pay_channel'];
        }
        if ($data['pay_type'] == 'alipaymini') {
            $attributes['alipay_user_id'] = $data['alipay_user_id'];
        }

        try {
            return $this->paymentService->getToken($attributes);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 根据前端的payToken发起交易
     */
    public function paymentByPayToken( array $data)
    {

        $tradeService = new TradeService();
        $newData = $tradeService->getInfo(['order_id' => $data['order_id'], 'pay_type' => $data['pay_type']]);

        $attributes = [
            'order_id' => $data['order_id'] ?? '',
            'pay_token' => $data['pay_token'] ?? '',
            'merchant_trade_no' => $newData['merchant_trade_no'] ?? '',
        ];
        // 添加交易单号

        try {
            return $this->paymentService->paymentByPayToken($attributes);
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function generateOrderNumber()
    {
        // 生成一个基于当前时间的唯一订单号
        $randomDigits = mt_rand(10000, 999999); // 生成6位随机数
        $timestamp = time(); // 当前时间戳

        // 将时间戳转换为指定格式的日期时间
        $date = date('YmdHis', $timestamp);

        // 组合订单号
        return  $date . $randomDigits;
    }
}
