<?php

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\EcpayDeliveryInfo;
use OrdersBundle\Repositories\EcpayDeliveryInfoRepository;

class OrderEcpayDeliveryService
{

    public function __construct()
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->ecpayDeliveryInfoRepository = app('registry')->getManager('default')->getRepository(EcpayDeliveryInfo::class);
        // $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        // $this->ordersDeliveryRepository = app('registry')->getManager('default')->getRepository(OrdersDelivery::class);
        // $this->ordersDeliveryItemsRepository = app('registry')->getManager('default')->getRepository(OrdersDeliveryItems::class);
        // $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        // $this->companyRelLogisticsRepository = app('registry')->getManager('default')->getRepository(CompanyRelLogistics::class);
    }


    /**
     * 创建发货单
     */
    public function delivery($params)
    {
        //判断订单是否存在

        $order_id = $params['order_id'];
        $order_filter = [
            'order_id' => $order_id
        ];
        $order = $this->normalOrdersRepository->getInfo($order_filter);
        if (empty($order)) {
            throw new ResourceException("订单号为{$order_id}的订单不存在");
        }
        if ($order['order_status'] == 'NOTPAY') {
            throw new ResourceException("订单号为{$order_id}的订单未支付，不能发货");
        }
        if ($order['order_status'] == 'CANCEL') {
            throw new ResourceException("订单号为{$order_id}的订单已取消，不能发货");
        }
        if ($order['cancel_status'] == 'WAIT_PROCESS' || $order['cancel_status'] == 'REFUND_PROCESS') {
            throw new ResourceException("订单号为{$order_id}的订单有退款待处理，不能发货");
        }
        if ($order['delivery_status'] == 'DONE') {
            throw new ResourceException("订单号为{$order_id}的订单发货状态为已发货");
        }

        //查询订单是否已有物流单
        $ecpayDeliveryInfo = $this->ecpayDeliveryInfoRepository->getInfo(['merchant_tradeno'=>$order_id]);
        if ($ecpayDeliveryInfo) {
            return [
                'AllPayLogisticsID' => $ecpayDeliveryInfo['logistics_id'],
            ];
        }
        $order_items_lists = $this->normalOrdersItemsRepository->get($order['company_id'], $order_id);
        $weight = 0;
        foreach ($order_items_lists as $key => $order_items_val) {
            $weight += $order_items_val['weight'] ?? 0;
        }
        $receiverAddress = $order['receiver_state'] . $order['receiver_city'] . $order['receiver_district'] . $order['receiver_address'];
        $form_array = array(
            //廠商編號（必填）
            "MerchantID" => config('ecpay.merchant_id'),
            //廠商交易編號（必填）
            // "MerchantTradeNo" => "ECPay".time(),
            "MerchantTradeNo" => $params['order_id'],
            //廠商交易時間（必填）
            "MerchantTradeDate" => date("Y/m/d H:i:s"),
            //物流類型（必填）
            "LogisticsType" => "HOME",
            //物流子類型（必填）
            "LogisticsSubType" => "TCAT",
            //商品金額（必填）
            "GoodsAmount" => $order['total_fee'],
            //商品名稱。
            "GoodsName" => $order['title'],
            //商品重量
            "GoodsWeight" => $weight,
            //寄件人姓名（必填）
            "SenderName" => "商家",
            //寄件人電話
            "SenderPhone" => "",
            //寄件人手機
            "SenderCellPhone" => "0912345678",
            //寄件人郵遞區號（必填）
            "SenderZipCode" => "403",
            //寄件人地址（必填）
            "SenderAddress" => "台灣省台中市西區民興街114號",
            //收件人姓名（必填）
            "ReceiverName" => $order['receiver_name'],
            //收件人電話
            "ReceiverPhone" => "",
            //收件人手機
            "ReceiverCellPhone" => $order['receiver_mobile'],
            //收件人郵遞區號
            "ReceiverZipCode" => $order['receiver_mobile'],
            //收件人地址
            "ReceiverAddress" => $receiverAddress,
            //收件人email
            "ReceiverEmail" => "",
            //溫層
            // "Temperature" => "",
            //距離
            // "Distance" => "",
            //規格
            //"Specification" => "",
            //預定取件時段
            //"ScheduledPickupTime" => "",
            //預定送達時段
            //"ScheduledDeliveryTime" => "",
            //交易描述
            "TradeDesc" => "",
            //Server 端回覆網址
            "ServerReplyURL" => env('APP_URL') . '/api/ecpay/notify/delivery',
            //Client 端回覆網址
            //"ClientReplyURL" => "",
            //備註。
            "Remark" => $order['remark'],
            //特約合作平台商代號(由ecpay提供)
            //"PlatformID" => "",

        );
        uksort($form_array, array($this,'merchantSort'));
        //取得檢查碼
        $form_array['CheckMacValue'] = $this->_getMacValue($form_array);
        $gateway_url = config('ecpay.logistics.base_uri') . "/Express/Create";
        $result = $this->curlPost($gateway_url, $form_array);
        list($code, $data) = explode('|', $result);
        if ($code !== '1') {
            throw new ResourceException($data);
        } else {
            $resData = $this->_formatResData($data);
            return $resData;
        }
    }

    public function saveNotify($resData)
    {
        try {
            $sign = $resData['CheckMacValue'];
            unset($resData['CheckMacValue']);
            uksort($resData, array($this,'merchantSort'));
            //取得檢查碼
            $gensign = $this->_getMacValue($resData);
            if ($sign != $gensign) {
                throw new \Exception("验签失败");
            }
            $addData = [
                'logistics_id' => $resData['AllPayLogisticsID'],
                'booking_note' => $resData['BookingNote'],
                'cvs_payment_no' => $resData['CVSPaymentNo'],
                'cvs_validation_no' => $resData['CVSValidationNo'],
                'goods_amount' => $resData['GoodsAmount'],
                'logistics_subtype' => $resData['LogisticsSubType'],
                'logistics_type' => $resData['LogisticsType'],
                'merchant_id' => $resData['MerchantID'],
                'merchant_tradeno' => $resData['MerchantTradeNo'],
                'receiver_address' => $resData['ReceiverAddress'],
                'receiver_mobile' => $resData['ReceiverCellPhone'],
                'receiver_email' => $resData['ReceiverEmail'],
                'receiver_name' => $resData['ReceiverName'],
                'receiver_phone' => $resData['ReceiverPhone'],
                'rtn_code' => $resData['RtnCode'],
                'rtn_msg' => $resData['RtnMsg'],
                'update_status_date' => $resData['UpdateStatusDate'],
            ];
            $this->ecpayDeliveryInfoRepository->create($addData);
        } catch (\Exception $e) {
            app('log')->debug('绿界物流回传错误：' .  $e->getMessage());
        }

    }

    //特殊字元置換
    private function _replaceChar($value)
    {
        $search_list = array('%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29');
        $replace_list = array('-', '_', '.', '!', '*', '(', ')');
        $value = str_replace($search_list, $replace_list ,$value);

        return $value;
    }

    public function getDeliveryList($order_delivery_id)
    {
        $ecpayDeliveryInfo = $this->ecpayDeliveryInfoRepository->getLists(['logistics_id'=>$order_delivery_id], 'rtn_msg as AcceptStation,update_status_date as AcceptTime', 1, -1, ['id'=> 'desc']);
        return $ecpayDeliveryInfo;
    }

    //產生檢查碼
    private function _getMacValue($form_array)
    {
        $hash_key = config('ecpay.logistics.hash_key');
        $hash_iv = config('ecpay.logistics.hash_iv');
        $encode_str = "HashKey=" . $hash_key;
        foreach ($form_array as $key => $value)
        {
            $encode_str .= "&" . $key . "=" . $value;
        }
        $encode_str .= "&HashIV=" . $hash_iv;
        $encode_str = strtolower(urlencode($encode_str));
        $encode_str = $this->_replaceChar($encode_str);
        return strtoupper(md5($encode_str));
    }

    //仿自然排序法
    public function merchantSort($a,$b)
    {
        return strcasecmp($a, $b);
    }

    public function curlPost($url, $post_data = array()) {
        $post_string = http_build_query($post_data, '', '&');
        $ch = curl_init();    // 启动一个CURL会话
        curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回
        $result = curl_exec($ch);

        // 打印请求的header信息
        $a = curl_getinfo($ch);
        \Log::debug(var_export($result,1));
        curl_close($ch);
        return $result;
    }

    private function _formatResData($str)
    {
        $arr = explode('&', $str);
        $result = [];
        foreach ($arr as $item) {
            list($key, $value) = explode('=', $item);
            $result[$key] = $value;
        }
        return $result;
    }
}
