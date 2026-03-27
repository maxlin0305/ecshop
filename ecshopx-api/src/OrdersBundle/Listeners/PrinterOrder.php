<?php

namespace OrdersBundle\Listeners;

use App\Api\PrinterService;
use App\Api\PrintService;
use App\Config\YlyConfig;
use App\Oauth\YlyOauthClient;
use EspierBundle\Services\PrinterService as PrinterServiceSelf;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;

class PrinterOrder
{
    use GetOrderServiceTrait;

    public function handle(TradeFinishEvent $event)
    {
//        $redis
        $companyId = $event->entities->getCompanyId();
        $distributorId = $event->entities->getDistributorId();
        $orderId = $event->entities->getOrderId();
        $printerService = new PrinterServiceSelf();
        $info = $printerService->getPrinterInfo($companyId, 'yilianyun');
        if ($info && isset($info['is_open']) && $info['is_open'] == 'true') {
            try {
                app('log')->info('--------------------订单：' . $orderId . '小票机开始打印--------------------');
                $config = new YlyConfig($info['app_id'], $info['app_key']);
                $client = new YlyOauthClient($config);
                $tokenKey = 'printer:' . $companyId . ':token';
                $orderNumKey = 'printer:' . $companyId . ':number:' . date('Ymd');
                $orderNum = app('redis')->connection('default')->incr($orderNumKey);
                app('redis')->connection('default')->expire($orderNumKey, 24 * 3600);
                $tokenInfo = app('redis')->connection('default')->get($tokenKey);

                if (!$tokenInfo) {
                    $token = $client->getToken();
                    $tokenInfo = json_encode($token);
                    app('redis')->connection('default')->set($tokenKey, $tokenInfo);
                    app('redis')->connection('default')->expire($tokenKey, $token->expires_in - 1000);
                }

                $token = json_decode($tokenInfo);
                $printer = new PrinterService($token->access_token, $config);
                $shopPrinter = $printerService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
                app('log')->info('shopPrinter:' . var_export($shopPrinter, 1));
                if ($shopPrinter) {
                    $printer->addPrinter($shopPrinter['app_terminal'], $shopPrinter['app_key']);
                    $orderAssociationService = new OrderAssociationService();
                    $order = $orderAssociationService->getOrder($companyId, $orderId);
                    if (!$order) {
                        app('log')->debug('订单：' . $orderId . '不存在， 打印失败');
                        return false;
                    }

                    $orderService = $this->getOrderServiceByOrderInfo($order);
                    $result = $orderService->getOrderInfo($companyId, $orderId);
                    $orderItemInfo = $result['orderInfo']['items'];
                    //58mm排版 排版指令详情请看 http://doc2.10ss.net/php332006
                    $content = "<FS2><center>**#{$orderNum} {$shopPrinter['name']}**</center></FS2>";
                    $content .= str_repeat('.', 32);
                    $content .= "<FS2><center>--在线支付--</center></FS2>";
                    $content .= "订单时间:" . date("Y-m-d H:i") . "\n";
                    $content .= "订单编号:" . $orderId . "\n";
                    $content .= str_repeat('*', 14) . "商品" . str_repeat("*", 14) . "\n";
                    $content .= "<table>";
                    foreach ($orderItemInfo as $v) {
                        $item_fee = bcdiv($v['item_fee'], 100, 2);
                        $content .= "<tr><td>{$v['item_name']}</td><td>x{$v['num']}</td><td>{$item_fee}</td></tr>";
                    }
                    $content .= "</table>";
                    $content .= str_repeat('.', 32) . "\n";
//                $content .= "<QR>这是二维码内容</QR>";
                    // $totalFee = $event->entities->getTotalFee() / 100;
                    $itemFee = bcdiv($result['orderInfo']['item_fee'], 100, 2);
                    $content .= "小计:￥{$itemFee}\n";
                    $freightFee = bcdiv($result['orderInfo']['freight_fee'], 100, 2);
                    $content .= "运费:￥{$freightFee}\n";
                    // $discountFee = $event->entities->getDisCountFee() / 100;
                    $discountFee = bcdiv($event->entities->getDisCountFee(), 100, 2);
                    $content .= "折扣:￥{$discountFee} \n";
                    $content .= str_repeat('*', 32) . "\n";
                    $payfee = bcdiv($event->entities->getPayFee(), 100, 2);
                    $content .= "订单总价:￥{$payfee} \n";
                    if ($result['orderInfo']['receipt_type'] == 'ziti') {
                        $content .= "手机号:{$result['orderInfo']['mobile']} \n";
                        if ($result['distributor']) {
                            $content .= "自提门店:{$result['distributor']['store_name']} \n";
                            $content .= "自提地址:{$result['distributor']['store_address']} \n";
                        }
                    } else {
                        if ($info['is_hide'] == 'true') {
                            $receiver_name = substr_cut($result['orderInfo']['receiver_name']);
                            $content .= "收货人:{$receiver_name} \n";
                        } else {
                            $content .= "收货人:{$result['orderInfo']['receiver_name']} \n";
                        }
                        $content .= "手机号:{$result['orderInfo']['receiver_mobile']} \n";
                        $address = $result['orderInfo']['receiver_state'] .
                            $result['orderInfo']['receiver_city'] .
                            $result['orderInfo']['receiver_district'] .
                            $result['orderInfo']['receiver_address'];
                        $content .= "收货地址:{$address} \n";
                    }
                    $content .= "备注:{$result['orderInfo']['remark']} \n";
                    $content .= "<FS2><center>**#{$orderNum} 完**</center></FS2>";
                    $print = new PrintService($token->access_token, $config);

                    $print->index($shopPrinter['app_terminal'], $content, $orderId);
                    app('log')->debug('订单：' . $orderId . '打印完成');
                }
            } catch (\Exception $e) {
                app('log')->debug('订单：' . $orderId . '打印失败'.$e->getMessage());
            }
        }
    }
}
