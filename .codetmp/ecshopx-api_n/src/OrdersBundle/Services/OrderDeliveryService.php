<?php

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\Items;
use OrdersBundle\Entities\CompanyRelLogistics;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\OrdersDelivery;
use OrdersBundle\Entities\OrdersDeliveryItems;
use OrdersBundle\Events\NormalOrderDeliveryEvent;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Repositories\OrdersDeliveryRepository;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\OrderSettingTrait;

class OrderDeliveryService
{
    use GetOrderServiceTrait;
    use OrderSettingTrait;
    private $normalOrdersRepository;
    private $normalOrdersItemsRepository;
    private $itemsRepository;
    /** @var OrdersDeliveryRepository */
    public $ordersDeliveryRepository;
    public $ordersDeliveryItemsRepository;
    private $orderAssociationsRepository;
    private $companyRelLogisticsRepository;

    public function __construct()
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->ordersDeliveryRepository = app('registry')->getManager('default')->getRepository(OrdersDelivery::class);
        $this->ordersDeliveryItemsRepository = app('registry')->getManager('default')->getRepository(OrdersDeliveryItems::class);
        $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $this->companyRelLogisticsRepository = app('registry')->getManager('default')->getRepository(CompanyRelLogistics::class);
    }

    public function getDeliveryCorpName($company_id, $delivery_corp)
    {
        $delivery_corp_name = app('redis')->get('kuaidiTypeOpenConfig:' . sha1($company_id));
        if ($delivery_corp_name == 'kuaidi100' && strtolower($delivery_corp) == $delivery_corp) {
            $company_rel_logistics_filter = [
                'company_id' => $company_id,
                'kuaidi_code' => $delivery_corp
            ];
        } else {
            $company_rel_logistics_filter = [
                'company_id' => $company_id,
                'corp_code' => $delivery_corp
            ];
        }

        $company_rel_logistics = $this->companyRelLogisticsRepository->getInfo($company_rel_logistics_filter);
        return $company_rel_logistics['corp_name'] ?? '其他';
    }

    /**
     * 创建发货单
     */
    public function delivery($params)
    {
        $company_id = $params['company_id'];
        $order_id = $params['order_id'];
        $logistics_type = $params['logistics_type'];
        $delivery_corp = $params['delivery_corp'];
        $delivery_code = $params['delivery_code'];
        $package_type = $params['delivery_type'];

        //逻辑验证
        $reslut_data = $this->check($params);
        $order_delivery_items_arr = $reslut_data['order_delivery_items_arr'];
        $order_items_id_arr = $reslut_data['order_items_id_arr'];
        $order_items_delivery_num = $reslut_data['order_items_delivery_num'];
        $order_info_arr = $reslut_data['order_info_arr'];
        $can_aftersales_num = $reslut_data['can_aftersales_num'];

        //数据入库
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            //发货单
            $order_delivery_arr = [
                'company_id' => $company_id,
                'order_id' => $order_id,
                'user_id' => $order_info_arr['user_id'],
                'delivery_corp_name' => $this->getDeliveryCorpName($company_id, $delivery_corp),
                'delivery_corp' => $delivery_corp,
                'logistics_type' => $logistics_type,
                'delivery_code' => $delivery_code,
                'delivery_corp_source' => app('redis')->get('kuaidiTypeOpenConfig:' . sha1($params['company_id'])),
                'receiver_mobile' => $order_info_arr['receiver_mobile'],
                'package_type' => $package_type,
                'delivery_time' => time(),
                'created' => time()
            ];
            $orders_delivery_reslut = $this->ordersDeliveryRepository->create($order_delivery_arr);
            //发货单商品
            foreach ($order_delivery_items_arr as $order_delivery_items_val) {
                $order_delivery_items_val['orders_delivery_id'] = $orders_delivery_reslut['orders_delivery_id'];
                $this->ordersDeliveryItemsRepository->create($order_delivery_items_val);
            }

            //修改订单商品表发货状态
            if (!empty($order_items_id_arr)) {
                $filter = [
                    'id' => $order_items_id_arr
                ];
                $data = [
                    'delivery_status' => 'DONE',
                    'delivery_time' => time(),
                    'logistics_type' => $logistics_type,
                ];
                $this->normalOrdersItemsRepository->updateBy($filter, $data);
            }

            //修改订单商品表发货商品数量
            foreach ($order_items_delivery_num as $order_items_delivery_num_val) {
                $this->normalOrdersItemsRepository->updateBy(['id' => $order_items_delivery_num_val['id']], ['delivery_item_num' => $order_items_delivery_num_val['delivery_item_num']]);
            }

            //修改订单发货状态
            $filter = [
                'order_id' => $order_id,
                'delivery_status' => 'PENDING'
            ];
            $orders_delivery_items = $this->normalOrdersItemsRepository->getRow($filter);
            //部分发货
            if (!empty($orders_delivery_items)) {
                $update_data = [
                    'delivery_status' => 'PARTAIL',
                ];
            } else {
                $finishTime = $this->getOrdersSetting($params['company_id'], 'order_finish_time');
                $finishTime = $finishTime * 24 * 3600; //订单自动完成时间换算为秒数
                $update_data = [
                    'delivery_corp_source' => app('redis')->get('kuaidiTypeOpenConfig:' . sha1($params['company_id'])),
                    'delivery_status' => 'DONE',
                    'delivery_time' => time(),
                    'auto_finish_time' => time() + $finishTime,
                    'order_status' => 'WAIT_BUYER_CONFIRM',
                ];
            }

            $order = $this->normalOrdersRepository->get($params['company_id'], $order_id);
            $update_data['left_aftersales_num'] = $order->getLeftAftersalesNum() + $can_aftersales_num;
            $this->normalOrdersRepository->update(['order_id' => $order_id], $update_data);

            //修改订单主关联表数据
            if (!empty($orders_delivery_items)) {
                $order_associations_update_data = [
                    'delivery_status' => 'PARTAIL',
                ];
            } else {
                $order_associations_update_data = [
                    'delivery_status' => 'DONE',
                    'delivery_time' => time(),
                    'order_status' => 'WAIT_BUYER_CONFIRM',
                ];
            }
            $result = $this->orderAssociationsRepository->update(['order_id' => $order_id], $order_associations_update_data);

            //发送模板消息
            $sendData['company_id'] = $company_id;
            $sendData['order_id'] = $params['order_id'];
            $sendData['delivery_corp_source'] = $order_delivery_arr['delivery_corp_source'];
            if ($package_type == 'sep') {
                foreach ($order_delivery_items_arr as $item) {
                    $sendData['item_name'] = $item['item_name'];
                    $this->getOrderService('normal')->sendDeliverySuccNotice($sendData, $order_delivery_arr, $package_type);
                }
            } elseif ($package_type == 'batch') {
                $this->getOrderService('normal')->sendDeliverySuccNotice($sendData, $order_delivery_arr, $package_type);
            }

            //记录订单操作日志
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $params['operator_type'] ?? 'system',
                'operator_id' => $params['operator_id'] ?? 0,
                'remarks' => '订单发货',
                'detail' => '订单号：' . $params['order_id'] . '，订单发货',
                'params' => $params,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));

            //触发订单发货事件
            $eventData = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
            ];
            event(new NormalOrderDeliveryEvent($eventData));

            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 更新发货单信息
     * @param $params
     */
    public function update($params)
    {
        $filter = [
            'orders_delivery_id' => $params['orders_delivery_id'],
            'company_id' => $params['company_id'],
        ];

        $delivery_corp = $params['delivery_corp'];
        $delivery_code = $params['delivery_code'];

        $delivery = $this->ordersDeliveryRepository->getInfo($filter);
        if (!$delivery) {
            throw new ResourceException("发货单不存在");
        }

        $update_info = [
            'delivery_corp' => $delivery_corp,
            'delivery_code' => $delivery_code,
            'delivery_corp_name' => $this->getDeliveryCorpName($params['company_id'], $delivery_corp),
        ];

        $result = $this->ordersDeliveryRepository->updateOneBy($filter, $update_info);

        $orderProcessLog = [
            'order_id' => $result['order_id'],
            'company_id' => $result['company_id'],
            'operator_type' => $params['operator_type'] ?? 'system',
            'operator_id' => $params['operator_id'] ?? 0,
            'remarks' => '订单发货',
            'detail' => '订单号：' . $result['order_id'] . '，订单发货信息修改',
            'params' => $params,
        ];
        event(new OrderProcessLogEvent($orderProcessLog));

        return $result;
    }

    /**
     * 发货单列表
     */
    public function lists($filter)
    {
        $result = $this->ordersDeliveryRepository->getLists($filter);
        foreach ($result as &$val) {
            $val['delivery_time'] = date('Y-m-d H:i:s', $val['delivery_time']);
        }

        return $result;
    }

    /**
     * @return mixed
     *
     * 发货单列表及商品
     */
    public function deliveryItems($params)
    {
        $company_id = $params['company_id'];
        $order_id = $params['order_id'];
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id
        ];
        $delivery_list = $this->ordersDeliveryRepository->getLists($filter);
        $data = [];
        $delivery_num = 0;
        if (!empty($delivery_list)) {
            foreach ($delivery_list as $val) {
                $items_num = 0;
                $items = [];
                $filter = [
                    'orders_delivery_id' => $val['orders_delivery_id']
                ];
                $delivery_items_list = $this->ordersDeliveryItemsRepository->getLists($filter);
                foreach ($delivery_items_list as $_val) {
                    $items[] = [
                        'pic' => $_val['pic'],
                    ];

                    $items_num += $_val['num'];
                }

                $delivery_info = $this->deliveryInfo($val['orders_delivery_id']);
                $data[] = [
                    'delivery_id' => $val['orders_delivery_id'],
                    'delivery_corp' => $val['delivery_corp'],
                    'delivery_corp_name' => $val['delivery_corp_name'],
                    'delivery_code' => $val['delivery_code'],
                    'items' => $items,
                    'items_num' => $items_num,
                    'status_msg' => '已发货',
                    'delivery_info' => $delivery_info[0]['AcceptStation'] ?? ''
                ];

                $delivery_num++;
            }
        }

        //未发货的商品
        $orders_items = $this->normalOrdersItemsRepository->get($company_id, $order_id);
        $items = [];
        $items_num = 0;
        foreach ($orders_items as $orders_items_val) {
            if ($orders_items_val['delivery_status'] != 'DONE') {
                $num = $orders_items_val['num'] - $orders_items_val['cancel_item_num'] - $orders_items_val['delivery_item_num'];
                if ($num <= 0) {
                    continue;
                }

                $items[] = [
                    'pic' => $orders_items_val['pic'],
                ];

                $items_num += $num;
            }
        }

        if (!empty($items)) {
            $data[] = [
                'delivery_id' => '',
                'delivery_corp' => '',
                'delivery_corp_name' => '',
                'delivery_code' => '',
                'items' => $items,
                'items_num' => $items_num,
                'status_msg' => '未发货',
                'delivery_info' => ''
            ];
        }

        return [
            'delivery_num' => $delivery_num,
            'list' => $data,
        ];
    }

    /**
     * @param string $orders_delivery_id 发货单id
     * @param string $user_id
     *
     * 查询物流信息
     */
    public function deliveryInfo($orders_delivery_id, $user_id = '')
    {
        $filter = [
            'orders_delivery_id' => $orders_delivery_id
        ];
        $orders_delivery = $this->ordersDeliveryRepository->getInfo($filter);
        if (empty($orders_delivery)) {
            return [['AcceptTime' => date('Y-m-d H:i:s', time()), 'AcceptStation' => '暂无物流信息']];
        }

        if (!empty($user_id) && $user_id != $orders_delivery['user_id']) {
            return [['AcceptTime' => date('Y-m-d H:i:s', time()), 'AcceptStation' => '暂无物流信息']];
        }
        try {

            //兼容黑猫
            if ($orders_delivery['logistics_type'] == 2){

                $orderEcpayDeliveryService = new OrderEcpayDeliveryService();
                return $orderEcpayDeliveryService->getDeliveryList($orders_delivery['delivery_code']);
            }
            $tracker = new LogisticTracker();
            if ($result = $tracker->sfbspCheck($orders_delivery['delivery_code'], $orders_delivery['delivery_corp'], $orders_delivery['company_id'], $orders_delivery['receiver_mobile'])) {
                return $result;
            }


            $result = $tracker->kuaidi100($orders_delivery['delivery_corp'], $orders_delivery['delivery_code'], $orders_delivery['company_id'], $orders_delivery['receiver_mobile']);

//            if (isset($orders_delivery['delivery_corp_source']) && $orders_delivery['delivery_corp_source'] == 'kuaidi100') {
//                $result = $tracker->kuaidi100($orders_delivery['delivery_corp'], $orders_delivery['delivery_code'], $orders_delivery['company_id'], $orders_delivery['receiver_mobile']);
//            } else {
//                //需要根据订单
//                $result = $tracker->pullFromHqepay($orders_delivery['delivery_code'], $orders_delivery['delivery_corp'], $orders_delivery['company_id'], $orders_delivery['receiver_mobile']);
//            }
        } catch (\Exception $exception) {
            return [['AcceptTime' => date('Y-m-d H:i:s', time()), 'AcceptStation' => '暂无物流信息','msg' =>$exception->getMessage()]];
        }
        return $result;
    }


    /**
     *  发货逻辑验证
     */
    private function check($params)
    {
        //参数判断
        $rules = [
            'order_id' => ['required', '缺少订单id'],
            'delivery_type' => ['required', '缺少类型'],
            'delivery_corp' => ['required', '缺少快递公司'],
            'delivery_code' => ['required', '缺少物流单号'],
            'sepInfo' => ['required_if:delivery_type,sep', '缺少拆单发货信息'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }


        $company_id = $params['company_id'];
        $order_id = $params['order_id'];
        if ($params['delivery_type'] == 'sep') {
            $order_items = json_decode($params['sepInfo'], true);
            if (empty($order_items)) {
                throw new ResourceException("订单号为{$order_id}的订单,发货快递信息不正确");
            }
        }
        $order_delivery_items_arr = []; //发货单商品
        $order_items_id_arr = []; //完成发货的订单商品表id
        $order_items_delivery_num = []; //订单商品发货数量
        $order_info_arr = [];//订单信息

        //判断订单是否存在
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

        $order_info_arr = [
            'receiver_mobile' => $order['receiver_mobile'],
            'user_id' => $order['user_id']
        ];

        $can_aftersales_num = 0;

        //整单发货
        if ($params['delivery_type'] == 'batch') {
            //根据订单id查询orders_items信息
            $order_items_lists = $this->normalOrdersItemsRepository->get($order['company_id'], $order_id);
            foreach ($order_items_lists as $order_items_val) {
                $order_delivery_items_arr[] = [
                    'company_id' => $company_id,
                    'order_id' => $order_id,
                    'order_items_id' => $order_items_val['id'],
                    'item_id' => $order_items_val['item_id'],
                    'num' => $order_items_val['num'],
                    'item_name' => $order_items_val['item_name'],
                    'pic' => $order_items_val['pic'],
                    'created' => time(),
                ];

                //记录已发货完的商品
                $order_items_id_arr[] = $order_items_val['id'];

                $order_items_delivery_num[] = [
                    'id' => $order_items_val['id'],
                    'delivery_item_num' => $order_items_val['num']
                ];

                $can_aftersales_num += $order_items_val['num'];
            }
        }

        //拆单发货
        if ($params['delivery_type'] == 'sep') {
            foreach ($order_items as $val) {
                $deliveryNum = $val['delivery_num'] ?? 0;
                if (!$deliveryNum) {
                    throw new ResourceException("订单号为{$order_id}的订单,发货商品数量格式错误：" . var_export($val, true));
                }

                $order_items_id = $val['id']; //订单商品表id
                $num = $deliveryNum;//当前发货的数量
                if (!preg_match("/^[1-9][0-9]*$/", $num)) {
                    throw new ResourceException("订单号为{$order_id}的订单,发货商品数量错误:" . $num);
                }

                $order_items_filter = [
                    'id' => $order_items_id
                ];
                $order_items_info = $this->normalOrdersItemsRepository->getRow($order_items_filter);
                if (empty($order_items_info)) {
                    throw new ResourceException("订单号为{$order_id}的订单,发货商品不正确");
                }
                if ($order_items_info['delivery_status'] == 'DONE') {
                    throw new ResourceException("订单号为{$order_id}的订单,发货商品已发货");
                }

                $order_items_num = $order_items_info['num'];         //购买商品数
                $item_name = $order_items_info['item_name'];   //商品名称
                $pic = $order_items_info['pic'];         //商品图片
                $item_id = $order_items_info['item_id'];     //商品id
                $send_num = $order_items_info['delivery_item_num'] ?: 0;//已发货商品数量


                //判断发货数量
                $remainNum = $order_items_num - $send_num;
                if ($num > $remainNum) {
                    throw new ResourceException("订单号为{$order_id}的订单,发货商品数量{$num}大于购买数量{$remainNum}");
                }

                $order_delivery_items_arr[] = [
                    'company_id' => $company_id,
                    'order_id' => $order_id,
                    'order_items_id' => $order_items_id,
                    'item_id' => $item_id,
                    'num' => $num,
                    'item_name' => $item_name,
                    'pic' => $pic,
                    'created' => time(),
                ];

                //记录已发货完的商品
                if ($order_items_num - ($send_num + $num) == 0) {
                    $order_items_id_arr[] = $order_items_id;
                }

                $order_items_delivery_num[] = [
                    'id' => $order_items_id,
                    'delivery_item_num' => $send_num + $num
                ];

                $can_aftersales_num += $num;
            }
        }

        return [
            'order_delivery_items_arr' => $order_delivery_items_arr,
            'order_items_id_arr' => $order_items_id_arr,
            'order_items_delivery_num' => $order_items_delivery_num,
            'order_info_arr' => $order_info_arr,
            'can_aftersales_num' => $can_aftersales_num,
        ];
    }
}
