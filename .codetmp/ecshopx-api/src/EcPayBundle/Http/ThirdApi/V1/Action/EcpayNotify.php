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

            # $tradeId = $decrypted['OrderInfo']['MerchantTradeNo'];
            $orderId = $decrypted['OrderInfo']['MerchantTradeNo'];
            $tradeId = $decrypted['CustomField'] ?? '';
            $TradeStatus = $decrypted['OrderInfo']['TradeStatus'] ?? null;
            $RtnCode = $decrypted['RtnCode'] ?? null;

            if ($TradeStatus === '1' && $RtnCode === 1) {
                $status = 'SUCCESS';
                $options['transaction_id'] = $decrypted['OrderInfo']['TradeNo'] ?? null;
                try {
                    $tradeService = new TradeService();
                    $tradeService->updateOneBy(['trade_id' => $tradeId], [
                        'inital_response' => json_encode($decrypted),
                        'merchant_trade_no'=> $orderId
                    ]);
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

    // 3d验证之后，返回app界面
    public function returnApp(Request $request)
    {
        app('log')->debug('ecpay_3D_do_pay_notify callback => ' . var_export($request->all(), 1));
        return redirect(env('H5_URL') . '/subpage/pages/payment/payment-result?status=1');
    }

}
