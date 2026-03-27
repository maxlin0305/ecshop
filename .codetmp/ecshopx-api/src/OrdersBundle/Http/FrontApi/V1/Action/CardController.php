<?php

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use App\Models\EcpayBindCard;
use App\Services\AesCrypter;
use App\Services\EcPayService;
use App\Services\IdGen;
use App\Services\NetworkService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

/**
 * 绿界绑卡获取 token
 */
class CardController extends Controller
{
    /**
     * 解绑卡
     * @param Request $request
     * @return mixed
     */
    public function deleteCard(Request $request)
    {
        $params = $request->only('bindCardID');
        $rules = [
            'bindCardID' => ['required', '绑卡ID 必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/DeleteMemberBindCard';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        $arData = [
            //會員編號
            'MerchantID' => $szMerchantID,
            //交易編號
            'BindCardID' => $params['bindCardID'],
        ];
        $szData = json_encode($arData);
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
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                return $decrypted;
            }
            $code = $decrypted['RtnCode'] ?? null;
            $msg = $decrypted['RtnMsg'] ?? null;
            throw new ResourceException('解绑异常: ' . $code . ' ' . $msg);
        }
        throw new ResourceException('解绑接口回傳沒有 Data');
    }

    /**
     * 获取用户绑卡列表
     * @param Request $request
     * @return array
     */
    public function cardList(Request $request)
    {
        $user = $request->get('auth');
        $oService = new NetworkService();    // // 初始化網路服務物件。
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/GetMemberBindCard';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];
        $arData = [
            //會員編號
            'MerchantID' => $szMerchantID,
            //交易編號
            'MerchantMemberID' => $user['user_id'],
        ];
        $szData = json_encode($arData);
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
        $ret['BindCardList'] = [];
        if (isset($result['Data'])) {
            $DataDec = json_decode($oCrypter->Decrypt($result['Data']), true);
            if (isset($DataDec['BindCardList'])) {
                $ret['BindCardList'] = $DataDec['BindCardList'];
            }
        }
        return $ret;
    }

    /**
     * 用户新增绑卡
     * @param Request $request
     * @return array
     */
    public function addCard(Request $request)
    {
        $user = $request->get('auth');
        $oService = new NetworkService();    // // 初始化網路服務物件。
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/CreateBindCard';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $szMerchantID = config('ecpay.merchant_id');
        $szRqHeader = [
            'Timestamp' => time(),
        ];

        $arData = [
            //會員編號
            'MerchantID' => $szMerchantID,
            //付款代碼
            'BindCardPayToken' => $request->get('bindCardPayToken'),
            //交易編號
            'MerchantMemberID' => $user['user_id'],
        ];
        $szData = json_encode($arData);
        $szData = urlencode($szData);
        $oCrypter = new AESCrypter($szHashKey, $szHashIV);
        $szData = $oCrypter->Encrypt($szData);
        $arParameters = [
            'MerchantID' => $szMerchantID,
            'RqHeader' => $szRqHeader,
            'Data' => $szData
        ];
        $result = $oService->ServerPost(json_encode($arParameters));
        $DataisNull = json_decode($result, true);
        if (isset($DataisNull['Data'])) {
            if ($DataisNull['Data'] !== '') {
                $DataDec = $oCrypter->Decrypt($DataisNull["Data"]);
                $DataDec1 = json_decode($DataDec, true);
                if (isset($DataDec1['ThreeDInfo'])) {
                    return [
                        'ThreeDURL' => $DataDec1['ThreeDInfo'],
                    ];
                }

                $code = $DataDec1['RtnCode'] ?? null;
                $msg = $DataDec1['RtnMsg'] ?? null;
                throw new ResourceException('绑卡接口异常: ' . $code . ' ' . $msg);

            }
            throw new ResourceException('绑卡接口 Data 回傳空值');
        }
        throw new ResourceException('绑卡接口回傳沒有 Data');
    }

    /**
     * 获取绑卡 token
     * @param Request $request
     * @return array
     */
    public function getToken(Request $request)
    {
        $user = $request->get('auth');
        $bind_card = new EcpayBindCard();
        $bind_card->id = IdGen::genId($user['user_id']);
        $bind_card->user_id = $user['user_id'];
        $bind_card->company_id = $user['company_id'];
        $bind_card->save();
        $oService = new NetworkService();
        $oService->ServiceURL = config('ecpay.mpos.base_uri') . '/Merchant/GetTokenbyBindingCard';
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $MerchantID = config('ecpay.merchant_id');
        $RqHeader = [
            'Timestamp' => time(),
        ];

        $arOrderInfo = [
            //交易時間
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            //交易編號
            'MerchantTradeNo' => $bind_card->id,
            //交易金額
            'TotalAmount' => 100,
            //付款回傳結果
            'ReturnURL' => 'http://8aff-211-23-76-78.ngrok.io/php/V4/Auto/simple_ServerReplyPaymentStatus4.php',
            //交易描述
            'TradeDesc' => '123',
            //商品名稱
            'ItemName' => '123',
        ];

        $arConsumerInfo = [
            //消費者會員編號
            'MerchantMemberID' => $bind_card->user_id,
            //電子信箱
            'Email' => 'test@tt.cc',
            //電話
            'Phone' => 886919345254,
            //姓名
            'Name' => '測試人',
            //國別碼
            'CountryCode' => '123',
            //地址
            'Address' => '台北市南港區三重路19-2號',
        ];

        $arData = [
            //會員編號
            'MerchantID' => $MerchantID,
            //交易資訊
            'OrderInfo' => $arOrderInfo,
            //消費者資訊
            'ConsumerInfo' => (object)$arConsumerInfo,
            //3D 驗證綁卡回傳結果
            'OrderResultURL' => env('APP_URL') . '/api/third/ecpay_card/notify',
            //特店自訂欄位
            'CustomField' => 'test',
        ];
        $szData = json_encode($arData);
        $oCrypter = new AESCrypter($szHashKey, $szHashIV);
        $szData = $oCrypter->encrypt($szData);
        $arParameters = [
            'MerchantID' => $MerchantID,
            'RqHeader' => (object)$RqHeader,
            'Data' => $szData
        ];
        $arParameters = json_encode($arParameters);
        $szResult = $oService->serverPost($arParameters);
        $DataisNull = json_decode($szResult, true);
        if (!isset($DataisNull['Data'])) {
            throw new ResourceException('绑卡服务异常');
        }
        if ($DataisNull['Data'] === '') {
            throw new ResourceException('绑卡服务请求异常');
        }
        $DataDec = $oCrypter->decrypt($DataisNull['Data']);
        $DataDec1 = json_decode($DataDec, true);
        $Token = $DataDec1['Token'];
        if (empty($Token)) {
            throw new ResourceException('绑卡服务响应异常');
        }

        $bind_card->token = $Token;
        $bind_card->save();
        return [
            'token' => $Token,
            'MerchantID' => $MerchantID,
            'MerchantMemberID' => $bind_card->user_id,
            'MerchantTradeNo' => $bind_card->id,
        ];
    }
}
