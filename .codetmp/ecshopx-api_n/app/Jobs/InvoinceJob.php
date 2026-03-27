<?php

namespace App\Jobs;

use App\Services\AesCrypter;
use App\Services\NetworkService;
use EspierBundle\Jobs\Job;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;

class InvoinceJob extends Job
{
    use GetOrderServiceTrait;

    /** @var NormalOrdersRepository */
    public $normalOrdersRepository;

    private $tradeId;

    public function __construct($tradeId)
    {
        $this->tradeId = $tradeId;
    }

    public function getOrderServiceByOrderInfo($order)
    {
        if (in_array($order['order_type'], ['normal', 'service']) && $order['order_class'] != $order['order_type'] && !in_array($order['order_class'], ['normal', 'service'])) {
            $orderType = $order['order_type'] . '_' . $order['order_class'];
        } else {
            $orderType = $order['order_type'];
        }
        return $this->getOrderService($orderType);
    }

    public function handle(): void
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getInfo(['trade_id' => $this->tradeId, 'trade_state' => 'SUCCESS']);
        if (empty($trade)) {
            app('log')->debug("trade " . __FUNCTION__ . "," . __LINE__ . " 信息不存在");
            return;
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($trade['company_id'], $trade['order_id']);
        if (empty($order)) {
            app('log')->debug("order " . __FUNCTION__ . "," . __LINE__ . " 信息不存在");
            return;
        }

        $result = $this->getOrderServiceByOrderInfo($order)->getOrderInfo($trade['company_id'], $trade['order_id'], true, 'front_detail');
        if ($result['orderInfo']['is_invoiced']) {
            app('log')->debug("order " . __FUNCTION__ . "," . __LINE__ . " 发票已开立");
            return;
        }
        if (empty($result['orderInfo']['invoice'])) {
            return;
        }

        $invoice = $result['orderInfo']['invoice'];
        $uri = config('ecpay.invoice.base_uri') . '/B2CInvoice/Issue';
        $oService = new NetworkService();
        $oService->ServiceURL = $uri;
        $req = [
            'MerchantID' => config('ecpay.merchant_id'),
            'RelateNumber' => $trade['order_id'],
            'CustomerID' => $result['orderInfo']['user_id'],
            'CustomerName' => $result['orderInfo']['invoice']['customer_name'],
            'CustomerAddr' => $result['orderInfo']['invoice']['customer_address'],
            'CustomerPhone' => $result['orderInfo']['invoice']['customer_phone'] ?? null,
            'CustomerEmail' => $result['orderInfo']['invoice']['customer_email'] ?? null,
            'ClearanceMark' => '1',
            'Print' => '0',
            'SalesAmount' => 0,
            'TaxType' => '1',
            'Items' => [],
            'InvType' => '07',
            'vat' => '1',
        ];

        if (isset($result['orderInfo']['invoice']['carrier_type'])) {
            $req['CarrierType'] = (int)$result['orderInfo']['invoice']['carrier_type'];
        }

        if (isset($result['orderInfo']['invoice']['carrier_num'])) {
            $req['CarrierNum'] = $result['orderInfo']['invoice']['carrier_num'];
        }

        if (isset($result['orderInfo']['invoice']['customer_identifier'])) {
            $req['CustomerIdentifier'] = $result['orderInfo']['invoice']['customer_identifier'];
        }

        foreach ($result['orderInfo']['items'] as $k => $item) {
            $req['Items'][] = [
                'ItemSeq' => $k,
                'ItemName' => $item['item_name'],
                'ItemCount' => $item['num'],
                'ItemWord' => '件', //@todo 待确认
                'ItemPrice' => $item['item_fee'],
                'ItemAmount' => $item['cost_fee'],
            ];
            $req['SalesAmount'] += $item['num'] * $item['cost_fee'];
        }

        $szData = json_encode($req);
        app('log')->debug('ecpay invoice bizdata:' . to_json($szData));
        $szData = urlencode($szData);
        $oCrypter = new AESCrypter(config('ecpay.invoice.hash_key'), config('ecpay.invoice.hash_iv'));
        $szData = $oCrypter->Encrypt($szData);
        $arParameters = [
            'MerchantID' => '2000132',
            'RqHeader' => [
                'Timestamp' => time(),
            ],
            'Data' => $szData
        ];

        app('log')->debug('ecpay invoice requestparams:' . to_json($arParameters));
        $result_str = $oService->ServerPost(json_encode($arParameters));
        $result = json_decode($result_str, true);
        if (isset($result['Data'])) {
            $decrypted = json_decode($oCrypter->Decrypt($result['Data']), true);
            app('log')->debug('ecpay invoice responsedata:' . to_json($decrypted));
            if (isset($decrypted['RtnCode']) && $decrypted['RtnCode'] === 1) {
                //更新发票状态
                $filter = [
                    'company_id' => $trade['company_id'],
                    'order_id' => $trade['order_id']
                ];
                $data = [
                    'invoice_number' => $decrypted['InvoiceNo'],
                    'is_invoiced' => 1,
                    'invoice' => $invoice + $decrypted,
                ];
                $this->normalOrdersRepository->updateOneBy($filter, $data);
                app('log')->info($trade['order_id'] . ' 订单自动开票成功: ' . $result_str);
            } else {
                app('log')->info($trade['order_id'] . ' 订单自动开票请求失败: ' . $result_str);
            }
        } else {
            //记录日志
            app('log')->info($trade['order_id'] . ' 订单自动开票失败: 请求异常 ' . $result_str);
        }
    }
}
