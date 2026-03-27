<?php

namespace PaymentBundle\Services\Payments;

use App\Services\AesCrypter;
use App\Services\EcPayService;
use App\Services\NetworkService;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Ecpayh5Service implements Payment
{

    public function setPaymentSetting($companyId, $params)
    {
        return true;
    }

    public function getPaymentSetting($companyId)
    {
        return true;
    }

    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {

    }

    //$authorizerAppId, $wxaAppId, $attributes
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/CreatePaymentWithCardID';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];

        app('log')->debug('ecpay payment biz params:szHashKey' . $szHashKey);
        app('log')->debug('ecpay payment biz params:szHashIV' . $szHashIV);
        $bizData = [
            //會員編號
            'MerchantID' => $szMerchantID,
            //交易編號
            'BindCardID' => $data['ecpay_card_id'],
            'OrderInfo' => [
                'MerchantTradeDate' => date('Y/m/d H:i:s'),
                'MerchantTradeNo' => $data['order_id'],
                'TotalAmount' => (int)($data['pay_fee'] / 100),
                'ReturnURL' => env('APP_URL') . '/api/ecpay/notify/order_id/' . $data['order_id'] . '/trade_id/' . $data['trade_id'],
                'TradeDesc' => $data['detail'],
                'ItemName' => $data['body'],
            ],
            'ConsumerInfo' => [
                'MerchantMemberID' => $data['user_id'],
            ],
            'CustomField' => $data['trade_id'],
        ];

        app('log')->debug('ecpay payment biz params:' . to_json($bizData));
        $szData = json_encode($bizData);
        $szData = urlencode($szData);
        $oCrypter = new AESCrypter($szHashKey, $szHashIV);
        $szData = $oCrypter->Encrypt($szData);
        $arParameters = [
            'MerchantID' => $szMerchantID,
            'RqHeader' => $szRqHeader,
            'Data' => $szData
        ];
        $result = $oService->ServerPost(json_encode($arParameters));
        $result = json_decode($result, true);
        if (isset($result['Data'])) {
            app('log')->debug('ecpay payment response data:' . to_json($result));
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay payment decrypted data:' . to_json($decrypted));
            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                $status = 'SUCCESS';
                $options['transaction_id'] = $notify['OrderInfo']['TradeNo'] ?? null;
                $tradeService = new TradeService();
                $tradeService->updateStatus($data['trade_id'], $status, $options);

                $tradeService = new TradeService();
                $tradeService->updateOneBy(
                    [
                        'trade_id' => $data['trade_id'],
                    ],
                    [
                        'inital_response' => json_encode($decrypted),
                        'inital_request' => json_encode($bizData),
                    ]
                );
                app('log')->debug('ecpay payment params:' . to_json($bizData));
                app('log')->debug('ecpay payment response decrypt result:' . to_json($decrypted));
                return [
                    'status' => 'SUCCESS',
                    'msg' => '支付成功',
                    'pay_type' => 'ecpay_h5'
                ];
            }

            app('log')->debug('ecpay payment params:' . json_encode($result));
            throw new BadRequestHttpException('支付失敗');
        }
        throw new ResourceException('支付接口回傳沒有 Data');
    }

    public function getPayOrderInfo($companyId, $trade_id)
    {
    }

    public function getRefundOrderInfo($companyId, $data)
    {

    }
}
