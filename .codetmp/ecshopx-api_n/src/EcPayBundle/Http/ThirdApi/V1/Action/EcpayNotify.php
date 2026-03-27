<?php

namespace EcPayBundle\Http\ThirdApi\V1\Action;

use App\Http\Controllers\Controller;
use App\Services\AesCrypter;
use Illuminate\Http\Request;
use OrdersBundle\Services\TradeService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EcpayNotify extends Controller
{
    public function handle(Request $request)
    {
        $szHashKey = config('ecpay.mpos.hash_key');
        $szHashIV = config('ecpay.mpos.hash_iv');
        $notify = $request->all();
        app('log')->debug('ecpay_notify callback => ' . var_export($notify, 1));
        if (isset($notify['Data'])) {
            $oCrypter = new AESCrypter($szHashKey, $szHashIV);
            $decrypted = json_decode($oCrypter->Decrypt($notify['Data']), true);
            app('log')->debug('ecpay_notify Data decrypted => ' . var_export($decrypted, 1));

            $tradeId = $decrypted['OrderInfo']['MerchantTradeNo'];
            $TradeStatus = $decrypted['OrderInfo']['TradeStatus'] ?? null;
            $RtnCode = $decrypted['RtnCode'] ?? null;

            if ($TradeStatus === '1' && $RtnCode === 1) {
                $status = 'SUCCESS';
                $options['transaction_id'] = $notify['OrderInfo']['TradeNo'] ?? null;
                try {
                    $tradeService = new TradeService();
                    $tradeService->updateOneBy(['trade_id' => $tradeId], ['inital_response' => json_encode($decrypted)]);
                    $tradeService->updateStatus($tradeId, $status, $options);
                } catch (BadRequestHttpException $e) {
                }
            } else {
                $status = 'PAYERROR';
                $tradeService = new TradeService();
                $tradeService->updateOneBy(['trade_id' => $tradeId], ['inital_response' => json_encode($decrypted)]);
                $tradeService->updateStatus($tradeId, $status, null);
            }
        }
        return 'OK';
    }
}
