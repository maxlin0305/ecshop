<?php

namespace PaymentBundle\Services\Payments;

use App\Services\AesCrypter;
use App\Services\EcPayService;
use App\Services\NetworkService;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Events\OrderProcessLogEvent;
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
        app('log')->debug('ecpay payment biz params-url:' . $oService->ServiceURL);
        app('log')->debug('ecpay payment biz params-szHashKey' . $szHashKey);
        app('log')->debug('ecpay payment biz params-szHashIV' . $szHashIV);
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

    /**
     * 商家退款到指定账号
     */
    /*
     * 全額退款流程說明
        1.呼叫查詢信用卡單筆明細紀錄API取得訂單狀態
        2.判斷訂單狀態
          若訂單狀態為已授權時，呼叫信用卡請退款API，執行放棄交易 (Action=N)釋放信用卡佔額。
          若訂單狀態為要關帳時，呼叫信用卡請退款API，先執行[取消] (Action=E)，接著進行[放棄] (Action=N)。
          若訂單狀態為已關帳時，呼叫信用卡請退款API，執行[退刷] (Action=R)。
    */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        app('log')->debug('ecpay doRefund start order_id=>' . $data['order_id'].' refund_bn=>' . $data['refund_bn'] .' trade_id=>' . $data['trade_id']);
        $status = $this->checkRefundStatus($wxaAppId, $data);
        $status = str_replace(" ", "", $status); // 去除所有空格
        if(empty($status)){
            throw new BadRequestHttpException('检查信用卡明细状态为空，退款失败');
        }
        # 检查是全额退款还是部分退款
        $is_full_refund = 1;
        if($data['refund_fee'] < $data['pay_fee']){
            $is_full_refund = 0 ;
        }
        /**
         *  1.银行返回状态：
         *      Canceled:此筆交易已取消
         *      Unauthorized:銀行未授權完成
         *      Authorized:銀行已完成授權
         *      To be captured:要關帳
         *      Captured:已關帳
         *      Operation canceled:操作取消
         *  2.根据不同的状态执行不同的操作：
         *      已授权-退款
         *          未关帐订单要进行全额退款(O)执行放弃[N]
         *          未关帐订单要进行部分退款(X)
         *      要关帐-退款(已执行关帐但未送到银行请款的订单)
         *          要关帐订单进行全额退款执行取消关帐[E]执行退款[N]
         *          要关帐订单进行部分退款执行退款[R]
         *      已关帐-退款
         *          已关帐订单要进行全额退款 请执行[R]
         *          已关帐订单要进行部分退款请执行[R]
         *  3.要执行的动作对应：
         *       C:關帳； R:退刷； E:取消； N:放棄
         */
        if($status == 'Authorized'){
            if($is_full_refund == 1){
                app('log')->debug('ecpay refund do biz : ' . '全额退款，银行已完成授权，执行N-放弃');
                $this->pushRefund($data,'N');
            }else{
                app('log')->debug('ecpay refund do biz : ' . '部分退款，银行已完成授权，不支持退款');
                $this->pushRefund($data,'C');
                $this->pushRefund($data,'R');
//                throw new BadRequestHttpException('已授权的信用卡交易单不支持部分退款');
            }
        }else if($status == 'Tobecaptured'){
            if($is_full_refund == 1){
                app('log')->debug('ecpay refund do biz : ' . '全额退款，银行要关账，执行E-取消 和 N-放弃');
                $this->pushRefund($data,'E');
                $this->pushRefund($data,'N');
            }else{
                app('log')->debug('ecpay refund do biz : ' . '部分退款，银行要关账，执行R-退刷');
                $this->pushRefund($data,'R');
            }
        }else if($status == 'Captured'){
            app('log')->debug('ecpay refund do biz : ' . '退款，银行已关账，执行R-退刷');
            $this->pushRefund($data,'R');
        }else{
            throw new BadRequestHttpException('信用卡交易单的状态错误，申请退款失败，状态：'.$status);
        }
        app('log')->debug('ecpay doRefund end');
        $result = [
            'status'    => 'SUCCESS',
            'refund_id' => $data['refund_bn'],
        ];
        return $result;
    }

    /**
     * 查询绿界信用卡信用记录
     * @param $wxaAppId
     * @param $data
     * @return array
     */
    public function checkRefundStatus($wxaAppId, $data)
    {
        $status = '';
        # 1.提供特店查詢信用卡明細記錄。
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri_ext') . '/1.0.0/CreditDetail/QueryTrade';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        app('log')->debug('ecpay refund check status biz params:' . to_json($data));
        $bizData = [
            'MerchantID' => $szMerchantID,
            'MerchantTradeNo' => $data['merchant_trade_no'],
            'TradeNo' => $data['transaction_id']
        ];
        app('log')->debug('ecpay refund check status biz params-url:' . $oService->ServiceURL);
        app('log')->debug('ecpay refund check status biz params-szHashKey:' . $szHashKey);
        app('log')->debug('ecpay refund check status biz params-szHashIV:' . $szHashIV);
        app('log')->debug('ecpay refund check status biz params:' . to_json($bizData));
        $szData = json_encode($bizData);
        $szData = urlencode($szData);
        $oCrypter = new AESCrypter($szHashKey, $szHashIV);
        $szData = $oCrypter->Encrypt($szData);
        $arParameters = [
            'MerchantID' => $szMerchantID,
            'RqHeader' => $szRqHeader,
            'Data' => $szData
        ];
        $result = $res = $oService->ServerPost(json_encode($arParameters));
        $result = json_decode($result, true);

        if(empty($result)){
            app('log')->debug('ecpay refund check status response data:' . to_json($res));
            throw new BadRequestHttpException('查詢信用卡明細記錄失败');
        }
        if (isset($result['Data'])) {
            app('log')->debug('ecpay refund check status response data:' . to_json($result));
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay refund check status decrypted data:' . to_json($decrypted));
            if(isset($decrypted['RtnMsg']) && !empty($decrypted['RtnMsg'])){
                throw new BadRequestHttpException($decrypted['RtnMsg']);
            }
            $status = isset($decrypted['RtnValue']['Status']) ? $decrypted['RtnValue']['Status'] :  '' ;
        }else{
            app('log')->debug('ecpay refund check status response data:' . to_json($result));
            throw new ResourceException('查詢信用卡明細記錄接口错误 没有 Data');
        }
        app('log')->debug('ecpay refund check status end data:' .$status);
        return $status ;
    }

    public function getPayOrderInfo($companyId, $trade_id)
    {
    }

    public function getRefundOrderInfo($companyId, $data)
    {

    }

    // 获取厂商token
    public function getToken( array $data){
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/GetTokenbyTrade';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        $returnUrl = env('APP_URL') . '/api/ecpay/notify/order_id/' . $data['order_id'] . '/trade_id/' . $data['trade_id'];
        $orderResultURL = env('APP_URL') . '/api/ecpay/notify/return_app';
        $bizData = [
            //會員編號
            'MerchantID'   => $szMerchantID,
            "RememberCard" => 1,
            "PaymentUIType"=> 2,
            "ChoosePaymentList"=>"1,2",
            'OrderInfo' => [
                'MerchantTradeDate' => date('Y/m/d H:i:s'),
                'MerchantTradeNo' => $data['merchant_trade_no'],
                'TotalAmount' => (int)($data['pay_fee'] / 100),
                'ReturnURL' => $returnUrl,
                'TradeDesc' => $data['detail'],
                'ItemName'  => $data['body'],
            ],
            'CardInfo' => [
                "OrderResultURL"    => $orderResultURL,
                "CreditInstallment" => "3,6,12"
            ],
            'UnionPayInfo' => [
                'OrderResultURL' => $orderResultURL,
            ],
            'ATMInfo' => [
                "ExpireDate" => 3
            ],
            'ConsumerInfo' => [
                'MerchantMemberID' => $data['user_id'],
                # "Email" => "customer@email.com",
                # "Phone" => "0912345678",
                # "Name"  => "Test",
                # "CountryCode" => "158"
            ],
            'CustomField' => $data['trade_id'],
        ];
        app('log')->debug('ecpay getToken biz params-url:' . $oService->ServiceURL);
        app('log')->debug('ecpay getToken biz params-szHashKey' . $szHashKey);
        app('log')->debug('ecpay getToken biz params-szHashIV' . $szHashIV);
        app('log')->debug('ecpay getToken biz params:' . to_json($bizData));
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
            app('log')->debug('ecpay getToken response data:' . to_json($result));
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay getToken decrypted data:' . to_json($decrypted));

            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                return [
                    'token' => $decrypted['Token'] ?? '',
                ];
            }
            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 5100012) {
                throw new BadRequestHttpException('該訂單已過支付時間有效期，請選擇商品重新下單');
            }
            throw new BadRequestHttpException('獲取token失敗');
        }
        throw new ResourceException('獲取token接口回傳沒有 Data');
    }

    // 根据前端的payToken发起交易
    public function paymentByPayToken( array $data){
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/CreatePayment';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        $bizData = [
            'MerchantID'   => $szMerchantID,
            "PayToken"     => $data['pay_token'],
            'MerchantTradeNo' => $data['merchant_trade_no'],
        ];
        app('log')->debug('ecpay dopay biz params-url:' . $oService->ServiceURL);
        app('log')->debug('ecpay dopay biz params-szHashKey' . $szHashKey);
        app('log')->debug('ecpay dopay biz params-szHashIV' . $szHashIV);
        app('log')->debug('ecpay dopay biz params:' . to_json($bizData));
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
            app('log')->debug('ecpay dopay response data:' . to_json($result));
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay dopay decrypted data:' . to_json($decrypted));
            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                app('log')->debug('ecpay dopay params:' . to_json($bizData));
                app('log')->debug('ecpay dopay response decrypt result:' . to_json($decrypted));
                $three_url = '';
                if(isset($decrypted['ThreeDInfo']['ThreeDURL']) && !empty($decrypted['ThreeDInfo']['ThreeDURL'])){
                    $three_url = $decrypted['ThreeDInfo']['ThreeDURL'];
                }
                # 信用卡
                if(isset($decrypted['ThreeDInfo']['ThreeDURL']) && !empty($decrypted['ThreeDInfo']['ThreeDURL'])){
                    $three_url = $decrypted['ThreeDInfo']['ThreeDURL'];
                }
                # 銀聯卡
                if(isset($decrypted['UnionPayInfo']['UnionPayURL']) && !empty($decrypted['UnionPayInfo']['UnionPayURL'])){
                    $three_url = $decrypted['UnionPayInfo']['UnionPayURL'];
                }
                return [
                    'status' => 'SUCCESS',
                    'msg' => '支付成功',
                    'pay_type' => 'ecpay_h5',
                    'three_url' => $three_url,
                ];
            }

            app('log')->debug('ecpay dopay params:' . json_encode($result));
            throw new BadRequestHttpException('支付失敗');
        }
        throw new ResourceException('支付接口回傳沒有 Data');
    }

    # 发起绿界退款请求
    public function pushRefund($data,$action=''){
        app('log')->debug('ecpay refund do biz action ' . $action);
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri_ext') . '/1.0.0/Credit/DoAction';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        //退款金额 refund_fee = c
        $refund_fee = isset($data['refund_fee']) ? $data['refund_fee'] / 100 : 0;
        //如果是关账的时候   金额用支付金额  ,$data['pay_fee']
        if ($action === 'C'){
            $refund_fee = isset($data['pay_fee']) ? $data['pay_fee'] / 100 : 0;
        }

        $bizData = [
            'MerchantID' => $szMerchantID,
            'MerchantTradeNo' => $data['merchant_trade_no'],
            'TradeNo' => $data['transaction_id'],
            'Action' => $action,
            'TotalAmount' => $refund_fee,
        ];
        app('log')->debug('ecpay refund do biz params-url:' . $oService->ServiceURL);
        app('log')->debug('ecpay refund do biz params-szHashKey' . $szHashKey);
        app('log')->debug('ecpay refund do biz params-szHashIV' . $szHashIV);
        app('log')->debug('ecpay refund do biz params:' . to_json($bizData));
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
            app('log')->debug('ecpay refund do response data:' . to_json($result));
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay refund do decrypted data:' . to_json($decrypted));

            if (isset($decrypted['RtnCode'])) {
                if($decrypted['RtnCode'] === 1){
                    return true;
                }else{
                    throw new BadRequestHttpException('該訂單退款失败，返回信息：'.$decrypted['RtnMsg']);
                }
            }
        }else{
            throw new ResourceException('支付接口回傳沒有 Data');
        }
        return true;
    }
}
