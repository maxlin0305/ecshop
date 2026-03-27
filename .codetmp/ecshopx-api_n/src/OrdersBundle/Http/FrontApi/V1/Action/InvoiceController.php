<?php

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller;
use App\Services\AesCrypter;
use App\Services\NetworkService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function check(Request $request)
    {
        $carrier_type = $request->get('carrier_type');
        if (empty($carrier_type)) {
            throw new ResourceException('請選擇載具類型');
        }

        $carrier_type = (int)$carrier_type;
        $ret = [
            'validated' => true,
            'message' => 'OK',
        ];

        //1：綠界電子發票載具
        if ($carrier_type === 1) {
            return $ret;
        }

        //2：自然人憑證號碼 https://developers.ecpay.com.tw/?p=32089
        if ($carrier_type === 2) {
            $customer_identifier = $request->get('customer_identifier');
            if (empty($customer_identifier)) {
                throw new ResourceException('請填寫統一編號');
            }

            $uri = config('ecpay.invoice.base_uri') . '/B2CInvoice/GetCompanyNameByTaxID';
            $oService = new NetworkService();
            $oService->ServiceURL = $uri;
            $data = [
                'MerchantID' => config('ecpay.merchant_id'),
                'UnifiedBusinessNo' => $customer_identifier,
            ];
            $szData = json_encode($data);
            $szData = urlencode($szData);
            $oCrypter = new AESCrypter(config('ecpay.invoice.hash_key'), config('ecpay.invoice.hash_iv'));
            $szData = $oCrypter->Encrypt($szData);
            $arParameters = [
//                'MerchantID' => '2000132',
                'MerchantID' => config('ecpay.merchant_id'),
                'RqHeader' => [
                    'Timestamp' => time(),
                ],
                'Data' => $szData
            ];
            $result = $oService->ServerPost(json_encode($arParameters));
            $result = json_decode($result, true);
            if (isset($result['Data'])) {
                $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
                if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                    $ret['company_name'] = $decrypted['CompanyName'] ?? null;
                    return $ret;
                }
                $ret['validated'] = false;
                $ret['message'] = '请输入有效的统一编码';
                $ret['RtnMsg'] = $decrypted['RtnMsg'] ?? null;
                return $ret;
            }

            $ret['validated'] = false;
            $ret['message'] = '请输入有效的统一编码';
            return $ret;
        }

        //3：手機條碼載具 https://developers.ecpay.com.tw/?p=7886
        if ($carrier_type === 3) {
            $carrier_num = $request->get('carrier_num');
            if (empty($carrier_num)) {
                throw new ResourceException('請填寫載具編號');
            }

            $uri = config('ecpay.invoice.base_uri') . '/B2CInvoice/CheckBarcode';
            $oService = new NetworkService();
            $oService->ServiceURL = $uri;
            $data = [
                'MerchantID' => config('ecpay.merchant_id'),
                'BarCode' => $carrier_num,
            ];

            app('log')->debug('发票:' . json_encode($data));
            app('log')->debug('发票:' . json_encode($data));
            app('log')->debug('发票:$uri:' . config('ecpay'));
            $szData = json_encode($data);
            $szData = urlencode($szData);
            $oCrypter = new AESCrypter(config('ecpay.invoice.hash_key'), config('ecpay.invoice.hash_iv'));
            $szData = $oCrypter->Encrypt($szData);
            $arParameters = [
                'MerchantID' => config('ecpay.merchant_id'),
                'RqHeader' => [
                    'Timestamp' => time(),
                ],
                'Data' => $szData
            ];
            $result = $oService->ServerPost(json_encode($arParameters));
            $result = json_decode($result, true);
            if (isset($result['Data'])) {
                $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
                if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                    if (isset($decrypted['IsExist']) && $decrypted['IsExist'] === 'Y') {
                        return $ret;
                    }
                }
                $ret['validated'] = false;
                $ret['message'] = '请输入有效的載具編號';
                $ret['RtnMsg'] = $decrypted['RtnMsg'] ?? '校驗失敗';
                return $ret;
            }

            $ret['validated'] = false;
            $ret['message'] = '请输入有效的載具編號';
            return $ret;
        }

        throw new ResourceException('無效的載具類別');
    }
}
