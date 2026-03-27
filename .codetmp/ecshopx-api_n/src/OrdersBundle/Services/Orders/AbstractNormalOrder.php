<?php

namespace OrdersBundle\Services\Orders;

use GoodsBundle\Entities\Items;
use MembersBundle\Entities\MembersDeleteRecord;
use OrdersBundle\Entities\OrdersDelivery;
use OrdersBundle\Entities\OrdersDeliveryItems;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Services\OrderDeliveryService;
use OrdersBundle\Services\OrderEcpayDeliveryService;
use PromotionsBundle\Entities\PromotionGroupsTeamMember;
use PromotionsBundle\Services\GroupItemStoreService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use SalespersonBundle\Services\ProfitService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use DistributionBundle\Entities\Distributor;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Entities\CancelOrders;
use OrdersBundle\Entities\OrderPromotions;
use OrdersBundle\Entities\NormalOrdersRelDada;
use CompanysBundle\Entities\PushMessage;
use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use OrdersBundle\Events\NormalOrderDeliveryEvent;
use OrdersBundle\Events\NormalOrderCancelEvent;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Traits\GetOrderIdTrait;
use OrdersBundle\Traits\GetCartTypeServiceTrait;
use OrdersBundle\Traits\GetUserIdByMobileTrait;
use OrdersBundle\Traits\OrderSettingTrait;
use GoodsBundle\Services\ItemsService;

use GoodsBundle\Services\ItemStoreService;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use DataCubeBundle\Services\SourcesService;
use AftersalesBundle\Services\AftersalesRefundService;

use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\OrderAssociationService;

use Exception;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Interfaces\OrderInterface;

use PopularizeBundle\Services\BrokerageService;

use SuperAdminBundle\Services\LogisticsService;
use SystemLinkBundle\Events\TradeRefundEvent;
use OrdersBundle\Traits\SeckillStoreTicket;
use SystemLinkBundle\Services\ThirdSettingService;

use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\TurntableService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\SpecificCrowdDiscountService;
use PromotionsBundle\Services\PointupvaluationActivityService as PointupvaluationService;
use PromotionsBundle\Services\EmployeePurchaseActivityService;
use KaquanBundle\Services\UserDiscountService;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;
use ThirdPartyBundle\Events\TradeRefundEvent as SaasErpRefundEvent;
use ThirdPartyBundle\Events\TradeRefundCancelEvent as SaasErpRefundCancelEvent;
use ThirdPartyBundle\Services\DadaCentre\OrderService as DadaOrderService;
use OrdersBundle\Services\ShippingTemplatesService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Events\OrderProcessLogEvent;
use PointBundle\Services\PointMemberService;
use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Services\TradeSetting\CancelService;
use OrdersBundle\Services\TradeSettingService;
use PointsmallBundle\Services\ItemStoreService as PointsmallItemStoreService;
use ThirdPartyBundle\Services\SaasErpCentre\ItemService;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use CompanysBundle\Ego\CompanysActivationEgo;
use EspierBundle\Services\SubdistrictService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use AftersalesBundle\Entities\AftersalesRefund;
use OrdersBundle\Services\OrdersRelChinaumspayDivisionService;
use DistributionBundle\Services\PickupLocationService;
use OrdersBundle\Services\NormalOrdersRelZitiService;
use CompanysBundle\Services\PushMessageService;
use CompanysBundle\Services\OperatorDataPassService;

class AbstractNormalOrder implements OrderInterface
{
    use OrderSettingTrait;
    use GetOrderIdTrait;
    use GetCartTypeServiceTrait;
    use GetUserIdByMobileTrait;
    use SeckillStoreTicket;

    /** @var NormalOrdersRepository */
    public $normalOrdersRepository;
    public $normalOrdersItemsRepository;
    public $distributeLogsRepository;
    public $DistributorRepository;
    public $orderAssociationsRepository;
    public $orderPromotionsRepository;
    public $itemsRepository;
    public $limitedTimeSalePromotion = [];
    public $limitedBuyPromotion = [];
    public $UserJoinMarketingNum = [];
    public $ordersDeliveryRepository;
    public $ordersDeliveryItemsRepository;
    public $membersDeleteRecordRepository;
    public $distributorRepository;
    public $normalOrdersRelDadaRepository;
    public $pushMessageRepository;

    public function __construct()
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $this->orderPromotionsRepository = app('registry')->getManager('default')->getRepository(OrderPromotions::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->ordersDeliveryRepository = app('registry')->getManager('default')->getRepository(OrdersDelivery::class);
        $this->ordersDeliveryItemsRepository = app('registry')->getManager('default')->getRepository(OrdersDeliveryItems::class);
        $this->normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $this->membersDeleteRecordRepository = app('registry')->getManager('default')->getRepository(MembersDeleteRecord::class);
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $this->pushMessageRepository = app('registry')->getManager('default')->getRepository(PushMessage::class);
    }

    public function create($orderData)
    {
        $ordersResult = $this->normalOrdersRepository->create($orderData);
        $orderItemsFee = [];
        foreach ($orderData['items'] as $vitem) {
            $orderItemsFee[$vitem['item_id']] = [
                'item_fee' => $vitem['total_fee'],
                'is_profit' => $vitem['is_profit'] ?? false,
            ];
            $normalOrdersResult['items'][] = $this->normalOrdersItemsRepository->create($vitem);
        }
        if ($orderData['items_promotion'] ?? []) {
            $itemOrder = array_column($normalOrdersResult['items'], null, 'item_id');
            foreach ($orderData['items_promotion'] as $promotion) {
                if ($itemOrder[$promotion['item_id']] ?? []) {
                    $promotion['moid'] = $itemOrder[$promotion['item_id']]['order_id'];
                    $promotion['coid'] = $itemOrder[$promotion['item_id']]['id'];

                    // 限时特惠
                    if ($promotion['activity_type'] == 'limited_time_sale') {
                        $store = $itemOrder[$promotion['item_id']]['num'];
                        $price = $itemOrder[$promotion['item_id']]['price'] * $store;
                        $this->limitedTimeSalePromotion[] = [
                            'activity_id' => $promotion['activity_id'],
                            'company_id' => $promotion['company_id'],
                            'user_id' => $promotion['user_id'],
                            'item_id' => $promotion['item_id'],
                            'store' => $store,
                            'price' => $price,
                        ];
                    } elseif ($promotion['activity_type'] == 'limited_buy') {
                        if (!isset($this->limitedBuyPromotion[$promotion['activity_id']])) {
                            $this->limitedBuyPromotion[$promotion['activity_id']] = [];
                        }
                        $this->limitedBuyPromotion[$promotion['activity_id']][] = [
                            'user_id' => $promotion['user_id'],
                            'company_id' => $promotion['company_id'],
                            'distributor_id' => $orderData['distributor_id'],
                            'activity_id' => $promotion['activity_id'],
                            'item_id' => $promotion['item_id'],
                            'store' => $itemOrder[$promotion['item_id']]['num'],
                            'rule' => $promotion['activity_rule'],
                        ];
                    } elseif (in_array($promotion['activity_type'], ['full_discount', 'full_gift', 'full_minus', 'plus_price_buy', 'multi_buy'])) {
                        $this->UserJoinMarketingNum[$promotion['activity_id']] = [
                            'user_id' => $promotion['user_id'],
                            'company_id' => $promotion['company_id'],
                            'activity_id' => $promotion['activity_id'],
                        ];
                    }
                    $orderPromotion['items_promotion'][] = $this->orderPromotionsRepository->create($promotion);
                }
            }
        }
        $this->orderAssociationsRepository->create($orderData);

        if (config('common.product_model') == 'in_purchase') {
            return $ordersResult;
        }
        // 积分商城订单，不支持分润
        if (in_array($orderData['order_class'], ['pointsmall'])) {
            return $ordersResult;
        }
        $distributorService = new DistributorService();
        $defaultdDstributorId = $distributorService->getDefaultDistributorId($orderData['company_id']);
        if ($defaultdDstributorId == $orderData['distributor_id'] && 'ziti' != ($orderData['receipt_type'] ?? '')) {
            // 分润计算
            $shippingTemplatesService = new ShippingTemplatesService();
            // 获取收货地址代码
            $province = $orderData['receiver_state'];
            $city = $orderData['receiver_city'];
            $region = $orderData['receiver_district'];
            $shippingTemplatesService->getLocalRegionV2($province, $city, $region);
        }
        // 创建分润比例
        $orderFee = [
            'total_fee' => bcsub($orderData['total_fee'], $orderData['freight_fee']),
            'pay_fee' => $orderData['total_fee'],
            'item_fee' => $orderItemsFee,
        ];
        $orderProfitService = new OrderProfitService();
        $orderProfitService->profitByOrderResult($orderData, $orderFee);
        return $ordersResult;
    }

    public function minusItemStore($orderData)
    {
        foreach ($orderData['items'] as $vitem) {
            $minusItemStoreParams[] = [
                'item_id' => $vitem['item_id'],
                'key' => $vitem['is_total_store'] ? $vitem['item_id'] : $vitem['distributor_id'] . '_' . $vitem['item_id'],
                'num' => $vitem['num'],
            ];
        }
        if ('pointsmall' == $orderData['order_class']) {
            $itemStoreService = new PointsmallItemStoreService();
        } else {
            $itemStoreService = new ItemStoreService();
        }
        $itemStoreService->batchMinusItemStore($minusItemStoreParams);

        // 限购创建
        if ($this->limitedBuyPromotion) {
            $limitserver = new LimitService();
            foreach ($this->limitedBuyPromotion as $items) {
                foreach ($items as $row) {
                    $limitparams = [
                        'limit_id' => $row['activity_id'],
                        'user_id' => $row['user_id'],
                        'item_id' => $row['item_id'],
                        'company_id' => $row['company_id'],
                        'number' => $row['store'],
                        'day' => $row['rule']['day'],
                        'distributor_id' => $orderData['distributor_id'],
                    ];
                    $limitserver->createLimitPerson($limitparams);
                }
            }
        }
        if ($this->limitedTimeSalePromotion) {
            foreach ($this->limitedTimeSalePromotion as $row) {
                $this->setUserBuysStore($row['activity_id'], $row['company_id'], $row['user_id'], $row['item_id'], $row['store'], $row['price']);
            }
        }

        //记录会员参与某个活动的次数
        if ($this->UserJoinMarketingNum) {
            $marketingService = new MarketingActivityService();
            foreach ($this->UserJoinMarketingNum as $data) {
                $marketingService->saveMarketingJoinNumByUser($data['company_id'], $data['user_id'], $data['activity_id']);
            }
        }
        return true;
    }

    /**
     * 员工内购，累计增加限额、限购
     * @param  array $orderData 订单数据
     */
    public function addEmployeePurchaseLimitData($orderData)
    {
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $activity = $orderData['employee_purchase'] ?? [];
        if (empty($activity)) {
            return true;
        }
        $tagList = $tagIds = [];
        if ($activity['purchanse_items']['item_type'] == 'tag') {
            $orderItemIds = array_column($orderData['items'], 'item_id');
            $itemsService = new ItemsService();
            $tagList = $itemsService->getItemTagList(['company_id' => $orderData['company_id'], 'item_id' => $orderItemIds]);
            foreach ($tagList['select_tags_list'] as $tag) {
                $tagIds[$tag['item_id']][] = $tag['tag_id'];
            }
        }
        $relOrderParams = [
            'company_id' => $orderData['company_id'],
            'purchase_id' => $activity['purchase_id'],
            'order_id' => $orderData['order_id'],
        ];
        foreach ($orderData['items'] as $item) {
            $relOrderParams['order_item_id'] = $item['item_id'];
            $itemId = 0;
            $itemTagIds = $tagIds[$item['item_id']] ?? [];
            if ($itemTagIds) {
                foreach ($itemTagIds as $tag_id) {
                    $flag = $this->getEmployeePurchanseItemData($activity, $item, $tag_id, $itemId);
                    if ($flag) {
                        $employeePurchaseActivityService->setUsedLimitData($relOrderParams, $orderData['company_id'], $activity['purchase_id'], $orderData['user_id'], $itemId, $item['total_fee'], $item['num']);
                    }
                }
            } else {
                $flag = $this->getEmployeePurchanseItemData($activity, $item, 0, $itemId);
                if ($flag) {
                    $employeePurchaseActivityService->setUsedLimitData($relOrderParams, $orderData['company_id'], $activity['purchase_id'], $orderData['user_id'], $itemId, $item['total_fee'], $item['num']);
                }
            }

        }
        // 订单的购买金额
        if ($activity['is_share_limitfee'] == 1) {// 家属共享额度时，记录到员工下
            $employeePurchaseActivityService->setUsedTotalLimitData($relOrderParams, $orderData['company_id'], $activity['purchase_id'], $activity['employee_user_id'], $orderData['total_fee']);
        }

        // 记录会员的订单总购买金额
        $employeePurchaseActivityService->setUsedUserTotalLimitData($relOrderParams, $orderData['company_id'], $activity['purchase_id'], $orderData['user_id'], $orderData['total_fee']);

        return true;
    }

    /**
     * 员工内购，根据活动的商品类型，获取需要设置限购、限额的数据
     * @param array $activity 员工内购活动数据
     * @param array $item     订单的商品数据（单个商品）
     * @param string $tagId    订单的商品的一个标签ID(单个商品)
     * @param string &$itemId  需要设置限购、限额的商品ID(全部商品：0;主类目ID、商品ID、商品标签ID)
     */
    public function getEmployeePurchanseItemData($activity, $item, $tagId, &$itemId)
    {
        $item_type = $activity['purchanse_items']['item_type'];
        $flag = false;

        // 根据商品类型，扣减限额、限购
        switch ($item_type) {
            case 'all':
                $itemId = 0;
                $flag = true;
                break;
            case 'item':// 指定商品
                if (isset($activity['purchanse_items']['items'][$item['item_id']])) {
                    $flag = true;
                    $itemId = $item['item_id'];
                }
                break;
            case 'brand':// 指定商品品牌
                $itemsService = new ItemsService();
                $itemInfo = $itemsService->getItem(['item_id' => $item['item_id']]);
                if (isset($activity['purchanse_items']['items'][$itemInfo['brand_id']])) {
                    $flag = true;
                    $itemId = $itemInfo['brand_id'];
                }
                break;
            case 'category':// 指定商品主类目
                if (isset($activity['purchanse_items']['items'][$item['item_category']])) {
                    $flag = true;
                    $itemId = $item['item_category'];
                }
                break;
            case 'tag':// 指定商品标签
                // 获取购买商品的商品标签
                if (isset($activity['purchanse_items']['items'][$tagId])) {
                    $flag = true;
                    $itemId = $tagId;
                }
                break;
        }
        return $flag;
    }

    //更新订单
    public function update($filter, $updateInfo)
    {
        $order = $this->normalOrdersRepository->get($filter['company_id'], $filter['order_id']);
        $orderItems = $this->normalOrdersItemsRepository->getList(['order_id' => $filter['order_id']]);

        if (!$order) {
            throw new Exception("订单号为{$filter['order_id']}的订单不存在");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->orderAssociationsRepository->update($filter, $updateInfo);
            $result = $this->normalOrdersRepository->update($filter, $updateInfo);

            //更新商品sku售后时效时间
            if (isset($updateInfo['order_auto_close_aftersales_time']) && $updateInfo['order_auto_close_aftersales_time'] > 0) {
                $this->normalOrdersItemsRepository->updateBy(['order_id' => $filter['order_id']], ['auto_close_aftersales_time' => $updateInfo['order_auto_close_aftersales_time']]);
            }

            $conn->commit();

            if (isset($updateInfo['order_status'], $updateInfo['cancel_status'], $result['discount_info']) && $updateInfo['order_status'] == 'CANCEL' && $updateInfo['cancel_status'] == 'SUCCESS') {
                $discountInfo = $result['discount_info'];
                if (!is_array($discountInfo)) {
                    $discountInfo = json_decode($discountInfo, true);
                }
                // 优惠券恢复
                $userDiscountService = new UserDiscountService();
                foreach ($discountInfo as $value) {
                    if ($value && isset($value['coupon_code'])) {
                        $userDiscountService->callbackUserCard($result['company_id'], $value['coupon_code'], $result['user_id']);
                    }
                    //记录定向促销会员日志和恢复最高优惠金额
                    if (($value['type'] ?? '') == 'member_tag_targeted_promotion') {
                        $specificCrowdDiscountService = new SpecificCrowdDiscountService();
                        $specificCrowdDiscountService->setUserTotalDiscount($result['company_id'], $result['user_id'], $result, 'less');
                    }
                }
            }


            if (isset($updateInfo['order_status'], $updateInfo['cancel_status']) && $updateInfo['order_status'] == 'CANCEL' && $updateInfo['cancel_status'] == 'SUCCESS') {
                $itemStoreService = new ItemStoreService();
                $pointsmallItemStoreService = new pointsmallItemStoreService();

                $limitServer = new LimitService();
                $order_class = $order->getOrderClass();
                // 库存以及限购恢复
                foreach ($orderItems['list'] as $row) {
                    if (!in_array($order_class, ['seckill', 'pointsmall'])) {
                        // 总部发货
                        if ($row['is_total_store']) {
                            $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], true);
                        } else {
                            $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], false);
                        }
                    }
                    // 积分商城订单，返还库存
                    if ($order_class == 'pointsmall') {
                        $pointsmallItemStoreService->minusItemStore($row['item_id'], -$row['num'], true);
                    }
                    ##拼团订单，恢复库存
                    if ($order_class == 'groups') {
                        $groupItemStoreService = new GroupItemStoreService();
                        ##查询活动id
                        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                        $promotionGroupsTeamMember = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->getInfo(['company_id' => $filter['company_id'], 'order_id' => $filter['order_id'], 'member_id' => $result['user_id']]);
                        $groupItemStoreService->minusGroupItemStore($promotionGroupsTeamMember['act_id'], -$row['num']);
                    }
                    // 限购商品删除
                    $params = [
                        'company_id' => $row['company_id'],
                        'user_id' => $row['user_id'],
                        'item_id' => $row['item_id'],
                        'number' => $row['num'],
                    ];
                    $limitServer->reduceLimitPerson($params);
                }
                $orderId = $order->getOrderId();
                $userId = $order->getUserId();
                $companyId = $order->getCompanyId();
                $limitedTimeSale = $this->orderPromotionsRepository->lists(['moid' => $orderId]);
                if ($limitedTimeSale['total_count'] > 0) {
                    $marketingService = new MarketingActivityService();
                    $limitedTimeSaleItemIds = [];
                    foreach ($limitedTimeSale['list'] as $value) {
                        if (in_array($value['activity_type'], ['full_discount', 'full_gift', 'full_minus', 'plus_price_buy'])) {
                            $marketingActivity[$value['user_id']][$value['moid']][$value['activity_id']] = $value['activity_id'];
                        }
                        if ($value['activity_type'] == 'limited_time_sale') {
                            $limitedTimeSaleItemIds[$value['item_id']] = $value;
                        }
                    }
                    if ($limitedTimeSaleItemIds) {
                        foreach ($orderItems['list'] as $row) {
                            if (isset($limitedTimeSaleItemIds[$row['item_id']])) {
                                $promotion = $limitedTimeSaleItemIds[$row['item_id']];
                                $totalFee = ($row['num'] * $row['price']);
                                $this->setUserBuysStore($promotion['activity_id'], $promotion['company_id'], $promotion['user_id'], $promotion['item_id'], -$row['num'], -$totalFee);
                            }
                        }
                    }
                    if (isset($marketingActivity[$userId][$orderId])) {
                        $activity = $marketingActivity[$userId][$orderId];
                        $marketingService->lessUserJoinMarketingNum($companyId, $userId, $activity);
                    }
                }
                // 员工内购，累计减少限购数、限额
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',更新订单状态，取消订单，累计减少限购数、限额,company_id:'.$companyId.',order_id:'.$orderId);
                (new EmployeePurchaseActivityService())->minusEmployeePurchaseLimitData($companyId, $orderId);
            }

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function partailCancelOrder($params) {
        $orderInfo = $this->getOrderInfo($params['company_id'], $params['order_id']);

        if ($orderInfo['orderInfo']['order_status'] !== 'PAYED') {
            throw new ResourceException('订单状态已不能申请取消');
        }
        if ($orderInfo['orderInfo']['delivery_status'] != 'PARTAIL') {
            throw new ResourceException("非部分发货订单不能取消");
        }

        $detail = [];
        foreach ($orderInfo['orderInfo']['items'] as $item) {
            if (($num = $item['num'] - $item['delivery_item_num']) > 0) {
                $detail[] = [
                    'id' => $item['id'],
                    'num' => $num,
                ];
            }
        }
        $aftersalesParams = [
            'order_id' => $orderInfo['orderInfo']['order_id'],
            'company_id' => $orderInfo['orderInfo']['company_id'],
            'user_id' => $orderInfo['orderInfo']['user_id'],
            'detail' => $detail,
            'aftersales_type' => 'ONLY_REFUND',
            'reason' => $params['cancel_reason'],
            'is_partial_cancel' => true,
            'operator_type' => $params['operator_type'],
            'operator_id' => $params['operator_id'],
        ];
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->create($aftersalesParams);

        if ($result) {
            // 更新订单状态
            $finishTime = $this->getOrdersSetting($result['company_id'], 'order_finish_time');
            $finishTime = $finishTime * 24 * 3600; //订单自动完成时间换算为秒数
            $updateData = [
                'auto_finish_time' => time() + $finishTime,
                'order_status' => 'WAIT_BUYER_CONFIRM',
            ];
            $this->update(['company_id' => $result['company_id'], 'order_id' => $result['order_id']], $updateData);

            foreach ($detail as $row) {
                $this->normalOrdersItemsRepository->update(['id' => $row['id']], ['cancel_item_num' => $row['num']]);
            }

            // 自动退款
            $autoAfterSales = $this->getOrdersSetting($result['company_id'], 'auto_aftersales');
            if ($autoAfterSales) {
                $reviewParams = [
                    'company_id' => $result['company_id'],
                    'aftersales_bn' => $result['aftersales_bn'],
                    'is_approved' => 1,
                    'refund_fee' => $result['refund_fee'],
                    'refund_point' => $result['refund_point'],
                    'operator_type' => $params['operator_type'],
                    'operator_id' => $params['operator_id'],
                ];
                $aftersalesService->review($reviewParams);
            }
        }

        return $result;
    }

    public function partailCancelRestore($orderId, $isApproved)
    {
        $order = $this->normalOrdersRepository->getInfo(['order_id' => $orderId]);
        if ($isApproved) {
            $orderItems = $this->normalOrdersItemsRepository->getList(['order_id' => $orderId, 'cancel_item_num|gt' => 0]);
            $itemStoreService = new ItemStoreService();
            $pointsmallItemStoreService = new pointsmallItemStoreService();

            $limitServer = new LimitService();
            // 库存以及限购恢复
            foreach ($orderItems['list'] as $row) {
                if (!in_array($order['order_class'], ['seckill', 'pointsmall'])) {
                    // 总部发货
                    if ($row['is_total_store']) {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['cancel_item_num'], $row['distributor_id'], true);
                    } else {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['cancel_item_num'], $row['distributor_id'], false);
                    }
                }
                // 积分商城订单，返还库存
                if ($order['order_class'] == 'pointsmall') {
                    $pointsmallItemStoreService->minusItemStore($row['item_id'], -$row['cancel_item_num'], true);
                }
                ##拼团订单，恢复库存
                if ($order['order_class'] == 'groups') {
                    $groupItemStoreService = new GroupItemStoreService();
                    ##查询活动id
                    $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                    $promotionGroupsTeamMember = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->getInfo(['company_id' => $filter['company_id'], 'order_id' => $filter['order_id'], 'member_id' => $result['user_id']]);
                    $groupItemStoreService->minusGroupItemStore($promotionGroupsTeamMember['act_id'], -$row['cancel_item_num']);
                }
                // 限购商品删除
                $params = [
                    'company_id' => $row['company_id'],
                    'user_id' => $row['user_id'],
                    'item_id' => $row['item_id'],
                    'number' => $row['cancel_item_num'],
                ];
                $limitServer->reduceLimitPerson($params);
            }

            $limitedTimeSale = $this->orderPromotionsRepository->lists(['moid' => $order['order_id']]);
            if ($limitedTimeSale['total_count'] > 0) {
                $limitedTimeSaleItemIds = [];
                foreach ($limitedTimeSale['list'] as $value) {
                    if ($value['activity_type'] == 'limited_time_sale') {
                        $limitedTimeSaleItemIds[$value['item_id']] = $value;
                    }
                }
                if ($limitedTimeSaleItemIds) {
                    foreach ($orderItems['list'] as $row) {
                        if (isset($limitedTimeSaleItemIds[$row['item_id']])) {
                            $promotion = $limitedTimeSaleItemIds[$row['item_id']];
                            $totalFee = ($row['cancel_item_num'] * $row['price']);
                            $this->setUserBuysStore($promotion['activity_id'], $promotion['company_id'], $promotion['user_id'], $promotion['item_id'], -$row['cancel_item_num'], -$totalFee);
                        }
                    }
                }
            }
        } else {
            $updateData = [
                'order_status' => 'PAYED',
            ];
            $this->update(['company_id' => $order['company_id'], 'order_id' => $order['order_id']], $updateData);
            $this->normalOrdersItemsRepository->updateBy(['order_id' => $order['order_id']], ['cancel_item_num' => 0]);
        }
    }

    // 订单支付状态修改操作
    public function orderStatusUpdate($filter, $orderStatus, $payType = '')
    {
        $updateStatus['order_status'] = $orderStatus;
        if ($orderStatus != 'PART_PAYMENT') {
            if ($payType) {
                $updateStatus['pay_type'] = $payType;
            }
            $updateStatus['pay_status'] = 'PAYED';
        }
        $result = $this->normalOrdersRepository->update($filter, $updateStatus);
        app('log')->debug('orderStatusUpdate $normalOrdersRepository:'.var_export($result, 1));
        if ((($result['is_shopscreen'] ?? 0) || $result['order_class'] == 'shopadmin') && $result['pay_status'] == 'PAYED' && $result['receipt_type'] == 'ziti') {
            //大屏订单支付成功自动完成自提
            $updateInfo = ['ziti_status' => 'DONE'];
            if (!($result['is_logistics'] ?? 0)) {
                //更新售后时效时间
                $aftersalesTime = intval($this->getOrdersSetting($filter['company_id'], 'latest_aftersale_time'));
                $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

                //大屏订单且没有线上发货支付成功自动确认收货
                $updateInfo['order_status'] = 'DONE';
                $updateInfo['delivery_status'] = 'DONE';
                $updateInfo['delivery_time'] = time();
                $updateInfo['end_time'] = time();
                $updateInfo['order_auto_close_aftersales_time'] = $auto_close_aftersales_time;
                //更新可收货数量
                $orderItems = $this->normalOrdersItemsRepository->getList($filter);
                $updateInfo['left_aftersales_num'] = array_sum(array_column($orderItems['list'], 'num'));
                // 大屏，部分自提的，需要将自提子单修改为已发货
                $this->shopscreenOrderZitiDelivery($result, $partailDelivery);
            } else {
                // 大屏，部分自提的，需要将自提子单修改为已发货
                $this->shopscreenOrderZitiDelivery($result, $partailDelivery);
                if ($partailDelivery) {
                    $updateInfo['delivery_status'] = 'PARTAIL';
                }
            }
            $this->normalOrdersRepository->update($filter, $updateInfo);
        }
        $result = $this->orderAssociationsRepository->update($filter, ['order_status' => $orderStatus]);
        //联通SaasErp 支付成功订单 创建 添加单笔交易 埋点
        if ($orderStatus != 'PART_PAYMENT') {
            event(new SaasErpUpdateEvent($result));
        }
        return $result;
    }

    /**
     * 大屏订单，支付完成后，处理子单的自提商品的配送状态为已发货
     * @param $orderData
     * @param [bool] $partailDelivery [<主单是否为部分发货>]
     * @return bool
     */
    public function shopscreenOrderZitiDelivery($orderData, &$partailDelivery = 0)
    {
        // 获取自提的子单数据
        $items_filter = [
            'company_id' => $orderData['company_id'],
            'order_id' => $orderData['order_id'],
            'is_logistics' => 0,
        ];
        $orderItemsList = $this->normalOrdersItemsRepository->getList($items_filter);
        if ($orderItemsList['total_count'] <= 0) {
            return true;
        }

        //更新售后时效时间
        $aftersalesTime = intval($this->getOrdersSetting($orderData['company_id'], 'latest_aftersale_time'));
        $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

        foreach ($orderItemsList['list'] as $order_item) {
            $filter = [
                'id' => $order_item['id'],
            ];
            $updateInfo = [
                'delivery_status' => 'DONE',
                'delivery_item_num' => $order_item['num'],
                'auto_close_aftersales_time' => $auto_close_aftersales_time,
            ];
            $this->normalOrdersItemsRepository->update($filter, $updateInfo);
        }
        $partailDelivery = 1;

        return true;
    }

    public function getOrderList($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'], $isGetTotal = true, $from = 'api')
    {
        if (isset($filter['order_type'])) {
            unset($filter['order_type']);
        }
        $filter = $this->checkMobile($filter);
        // 根据达达的订单状态进行查询
        $filter = $this->getOrderIdByDadaStatus($filter);
        $offset = ($page - 1) * $limit;
        $result['list'] = [];
        $fs = [];
        if ($item_name = $filter['item_name'] ?? '') {
            unset($filter['item_name']);
            foreach ($filter as $k => $f) {
                $fs['o.'.$k] = $f;
            }
            $fs['oi.item_name|like'] = '%'.$item_name.'%';
            // 根据item_name查询商品
            $result['list'] = $this->normalOrdersRepository->getListJoinItems($fs, $offset, $limit, $orderBy);
        } elseif (!isset($filter['order_id']) || !empty($filter['order_id'])) {
            $result['list'] = $this->normalOrdersRepository->getList($filter, $offset, $limit, $orderBy);
        }
        $membersDelete = $this->membersDeleteRecordRepository->getLists(['company_id' => $filter['company_id']], 'user_id');
        if (!empty($membersDelete)) {
            $deleteUsers = array_column($membersDelete, 'user_id');
        }
        if ($result['list']) {
            $sourceIds = array_column($result['list'], 'source_id');
            $sourceIds = array_unique($sourceIds);
            $objSource = new SourcesService();
            $sourceInfo = $objSource->getSourcesList(['source_id' => $sourceIds], 1, 100);
            $sourceList = [];
            if ($sourceInfo['list']) {
                $sourceList = array_bind_key($sourceInfo['list'], 'sourceId');
            }

            $distributorService = new DistributorService();
            $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
                return is_numeric($distributorId) && $distributorId >= 0;
            });
            $storeData = [];
            if ($storeIds) {
                $storeList = $distributorService->getDistributorOriginalList([
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ], 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
                // 附加总店信息
                $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
            }
            // 达达同城配数据
            $dada_filter = [
                'order_id' => array_column($result['list'], 'order_id'),
            ];
            $dadaOrderList = $this->normalOrdersRelDadaRepository->getLists($dada_filter);
            $dadaOrderList = array_column($dadaOrderList, null, 'order_id');

            //获取街道居委
            $subdistrictService = new SubdistrictService();
            $subdistrictId = array_column($result['list'], 'subdistrict_id');
            $subdistrictId = array_merge($subdistrictId, array_column($result['list'], 'subdistrict_parent_id'));
            array_unique($subdistrictId);
            $subdistrict = $subdistrictService->lists(['id' => $subdistrictId], 1, -1);
            $subdistrict = array_column($subdistrict['list'], 'label', 'id');

            // 获取自提点
            $normalOrdersRelZitiService = new NormalOrdersRelZitiService();
            $ordersRelZitiList = $normalOrdersRelZitiService->getLists(['company_id' => $filter['company_id'], 'order_id' => array_column($result['list'], 'order_id')]);
            $ordersRelZitiList = array_column($ordersRelZitiList, null, 'order_id');

            $service = new TradeSettingService(new CancelService());
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['subdistrict_parent'] = $subdistrict[$v['subdistrict_parent_id']] ?? '';
                $result['list'][$k]['subdistrict'] = $subdistrict[$v['subdistrict_id']] ?? '';

                $setting = $service->getSetting($v['company_id']);
                $result['list'][$k]['can_apply_cancel'] = 0;
                if ($v['order_status'] == 'NOTPAY' || $v['order_status'] == 'PAYED') {
                    $result['list'][$k]['can_apply_cancel'] = 1;
                }
                if ($v['cancel_status'] != 'NO_APPLY_CANCEL') {
                    if (!($setting['repeat_cancel'] ?? false)) {
                        $result['list'][$k]['can_apply_cancel'] = 0;
                    }

                    if ($v['cancel_status'] != 'FAILS') {
                        $result['list'][$k]['can_apply_cancel'] = 0;
                    }
                }

                if ($v['order_status'] == 'NOTPAY' && $v['auto_cancel_time'] - time() <= 0 && $v['order_class'] != 'drug') {
                    $v['order_status'] = 'CANCEL';
                    $result['list'][$k]['order_status'] = 'CANCEL';
                }
                // 达达同城配数据
                $dadaData = $dadaOrderList[$v['order_id']] ?? [];
                $result['list'][$k]['order_status_msg'] = $this->getOrderStatusMsg($v, $dadaData, $from);
                $result['list'][$k]['order_status_des'] = $v['order_status_des'];
                // 店务app附加数据
                $result['list'][$k]['app_info'] = $v['app_info'] ?? [];

                $result['list'][$k]['source_name'] = '-';
                if ($sourceList && $v['source_id'] > 0) {
                    $result['list'][$k]['source_name'] = $sourceList[$v['source_id']]['sourceName'];
                }

                $result['list'][$k]['distributor_info'] = $storeData[$v['distributor_id']] ?? [];

                $result['list'][$k]['create_date'] = date('Y-m-d H:i:s', $v['create_time']);

                $result['list'][$k]['items'] = $this->normalOrdersItemsRepository->get($v['company_id'], $v['order_id']);
                $result['list'][$k]['distributor_name'] = isset($v['distributor_id']) ? ($storeData[$v['distributor_id']]['name'] ?? '') : '';

                //新團購訂單計算預計延期時間
                if($result['list'][$k]['order_class'] == 'multi_buy'){
                    $result['list'][$k]['multi_buy_total'] = array_sum(array_column($result['list'][$k]['items'],'num'));
                    $result['list'][$k]['multi_buy_left_num'] = $result['list'][$k]['multi_buy_total']-$result['list'][$k]['multi_check_num'];
                    $macketingActivityService = new MarketingActivityService();
                    $macketing_list = $macketingActivityService->getValidActivitys($filter['company_id'],$result['list'][$k]['act_id'],null,null,['multi_buy']);
                    if(!empty($macketing_list)){
                        $prolongMonthInfo = $macketing_list[0]??[];
                        $prolong_month = $prolongMonthInfo['prolong_month']??0;    // 延長多久
                        $order_multi_expire_time = $prolongMonthInfo['commodity_effective_end_time']; // 团购订单过期时间
                        $new_multi_expire_time = strtotime('+'.$prolong_month.' month' ,$order_multi_expire_time);
                        $result['list'][$k]['predict_multi_expire_date'] = date('Y-m-d H:i:s',$new_multi_expire_time);
                        //新增已延期次数
                        $result['list'][$k]['delayed_number'] =  $prolongMonthInfo['delayed_number']??0;
                        //到期时间
                        $result['list'][$k]['multi_expire_time'] = $prolongMonthInfo['commodity_effective_end_time']?date('Y-m-d H:i:s',$prolongMonthInfo['commodity_effective_end_time']):'';
                    }
//                    $result['list'][$k]['multi_expire_time'] = date('Y-m-d H:i:s',$result['list'][$k]['multi_expire_time']);
                }

                //发货单新旧兼容, 部分发货的订单需继续按照原发货流程进行
                $result['list'][$k]['delivery_type'] = 'new';
                if (!empty($v['delivery_code'])) {
                    $result['list'][$k]['delivery_type'] = 'old';
                } else {
                    foreach ($result['list'][$k]['items'] as $items_val) {
                        if (!empty($items_val['delivery_code'])) {
                            $result['list'][$k]['delivery_type'] = 'old';
                            break;
                        }
                    }
                }

                //判断发货单是否整单发货，适用新发货单的模式
                if ($result['list'][$k]['delivery_type'] == 'new') {
                    $_filter = [
                        'order_id' => $v['order_id']
                    ];
                    $orders_delivery_info = $this->ordersDeliveryRepository->getInfo($_filter);
                    if (!empty($orders_delivery_info)) {
                        $result['list'][$k]['orders_delivery_id'] = $orders_delivery_info['orders_delivery_id'];
                        $result['list'][$k]['is_all_delivery'] = $orders_delivery_info['package_type'] == 'batch' ? true : false;
                        $result['list'][$k]['delivery_corp'] = $orders_delivery_info['delivery_corp'];
                        $result['list'][$k]['delivery_corp_name'] = $orders_delivery_info['delivery_corp_name'];
                        $result['list'][$k]['delivery_code'] = $orders_delivery_info['delivery_code'];
                    } else {
                        $result['list'][$k]['orders_delivery_id'] = '';
                        $result['list'][$k]['is_all_delivery'] = '';
                        $result['list'][$k]['delivery_corp'] = '';
                        $result['list'][$k]['delivery_corp_name'] = '';
                        $result['list'][$k]['delivery_code'] = '';
                    }
                }

                $result['list'][$k]['dada'] = $dadaData;
                $result['list'][$k]['user_delete'] = false;
                if (!empty($deleteUsers)) {
                    if (in_array($v['user_id'], $deleteUsers)) {
                        $result['list'][$k]['user_delete'] = true;
                    }
                }

                if ((!$v['order_auto_close_aftersales_time'] || $v['order_auto_close_aftersales_time'] > time()) && $v['left_aftersales_num'] > 0) {
                    $result['list'][$k]['can_apply_aftersales'] = 1;
                }

                // 自提信息
                if (isset($ordersRelZitiList[$v['order_id']])) {
                    $result['list'][$k]['ziti_info'] = $ordersRelZitiList[$v['order_id']];
                }
            }
        }

        if ($isGetTotal) {
            $result['pager']['count'] = 0;
            if ($item_name) {
                $result['pager']['count'] = intval($this->normalOrdersRepository->countJoinItems($fs));
            } elseif (!isset($filter['order_id']) || !empty($filter['order_id'])) {
                $result['pager']['count'] = intval($this->normalOrdersRepository->count($filter));
            }
        }
        $result['pager']['page_no'] = intval($page);
        $result['pager']['page_size'] = intval($limit);

        return $result;
    }

    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        if ($companyId) {
            $filter = ['order_id' => $orderId, 'company_id' => $companyId];
        } else {
            $filter = ['order_id' => $orderId];
        }

        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            throw new Exception("订单号为{$orderId}的订单不存在");
        } elseif (!$companyId) {
            $companyId = $orderInfo['company_id'];
        }

        // 获取订单的店铺信息
        $distributorService = new DistributorService();
        if ($orderInfo['distributor_id'] > 0) {
            $distributorInfo = $distributorService->getInfo([
                "company_id" => $companyId,
                "distributor_id" => $orderInfo["distributor_id"]
            ]);
        } else {
            // 总店信息，在平台版下总店也是自营店
            $distributorInfo = $distributorService->getDistributorSelfSimpleInfo($companyId);
        }
        // 添加店铺名称
        $orderInfo['distributor_name'] = $distributorInfo['name'] ?? "";
        // $distributorInfo = $distributorService->getOrderZitiShopInfo($companyId, $distributorId, $orderInfo['shop_id']);

        $orderItems = $this->normalOrdersItemsRepository->get($companyId, $orderId);
        //新團購訂單計算預計延期時間
        if($orderInfo['order_class'] == 'multi_buy'){
            $orderInfo['multi_buy_total'] = array_sum(array_column($orderItems,'num'));
            $orderInfo['multi_buy_left_num'] = $orderInfo['multi_buy_total']-$orderInfo['multi_check_num'];
            $macketingActivityService = new MarketingActivityService();
            $macketing_list = $macketingActivityService->getValidActivitys($companyId,$orderInfo['act_id'],null,null,['multi_buy']);
            if(!empty($macketing_list)){
                $prolongMonthInfo = $macketing_list[0]??[];
                $prolong_month = $prolongMonthInfo['prolong_month']??0;    // 延長多久
                $order_multi_expire_time = $prolongMonthInfo['commodity_effective_end_time']; // 团购订单过期时间
                $new_multi_expire_time = strtotime('+'.$prolong_month.' month' ,$order_multi_expire_time);
                $orderInfo['predict_multi_expire_date'] = date('Y-m-d H:i:s',$new_multi_expire_time);
                //新增已延期次数
                $orderInfo['delayed_number'] =  $prolongMonthInfo['delayed_number']??0;
                //到期时间
                $orderInfo['multi_expire_time'] = $prolongMonthInfo['commodity_effective_end_time']?date('Y-m-d H:i:s',$prolongMonthInfo['commodity_effective_end_time']):'';
            }
        }
        // 如果是取消订单，显示取消订单信息
        $orderInfo['can_apply_cancel'] = 0;
        if ($orderInfo['order_status'] == 'NOTPAY' || $orderInfo['order_status'] == 'PAYED') {
            $orderInfo['can_apply_cancel'] = 1;
        }
        if ($orderInfo['cancel_status'] != 'NO_APPLY_CANCEL') {
            $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
            $cancelData = $cancelOrderRepository->getInfo(['order_id' => $orderId]);

            $service = new TradeSettingService(new CancelService());
            $setting = $service->getSetting($companyId);
            if (!($setting['repeat_cancel'] ?? false)) {
                $orderInfo['can_apply_cancel'] = 0;
            }

            if ($orderInfo['cancel_status'] != 'FAILS') {
                $orderInfo['can_apply_cancel'] = 0;
            }
        }
        $can_apply_aftersales = 0;
        $orderInfo['can_apply_aftersales'] = 0;

        //获取已经申请售后的金额
        $itemRefundFee = [];
        if ($checkaftersales) {
            $afterSaleService = new AftersalesService();
            $afterSaleInfo = $afterSaleService->getAftersalesList(['company_id' => $companyId, 'order_id' => $orderId, 'aftersales_status' => [0, 1, 2]]);
            if ($afterSaleInfo && $afterSaleInfo['total_count'] > 0) {
                foreach ($afterSaleInfo['list'] as $v) {
                    foreach ($v['detail'] as $vv) {
                        if (isset($itemRefundFee[$vv['sub_order_id']])) {
                            $itemRefundFee[$vv['sub_order_id']]['refund_fee'] += $vv['refund_fee'];
                            $itemRefundFee[$vv['sub_order_id']]['refund_point'] += $vv['refund_point'];
                        } else {
                            $itemRefundFee[$vv['sub_order_id']]['refund_fee'] = $vv['refund_fee'];
                            $itemRefundFee[$vv['sub_order_id']]['refund_point'] = $vv['refund_point'];
                        }
                    }
                }
            }
        }

        foreach ($orderItems as &$item) {
            if ($item['delivery_corp']) {
                $logisticsServices = new LogisticsService();
                if ($orderInfo['delivery_corp_source'] == 'kuaidi100') {
                    $cols = 'kuaidi_code';
                } else {
                    $cols = 'corp_code';
                }
                $companyRelLogistics = $logisticsServices->getLogisticsFirst([$cols => $item['delivery_corp']]);

                $item['delivery_corp_name'] = $companyRelLogistics ? $companyRelLogistics['corp_name'] : $item['delivery_corp'];
                $item['delivery_corp_name'] = $item['delivery_corp_name'] == 'OTHER' ? '其他' : $item['delivery_corp_name'];
            }

            if (isset($itemRefundFee[$item['id']])) {
                //已经存在售后，计算可退款余额
                $item['after_sales_fee'] = $itemRefundFee[$item['id']];
                $item['remain_fee'] = $item['total_fee'] - $itemRefundFee[$item['id']]['refund_fee'];
                $item['remain_point'] = $item['point_fee'] - $itemRefundFee[$item['id']]['refund_point'];
            } else {
                //不存在售后
                $item['after_sales_fee'] = 0;
                $item['remain_fee'] = $item['total_fee'];
                $item['remain_point'] = $item['point_fee'];
            }

            //修复导购发货后，订单无法申请售后的问题
            if ($item['delivery_status'] == 'DONE' && !$item['delivery_item_num']) {
                $item['delivery_item_num'] = $item['num'];
            }

            // 获取售后申请数量@todo部分发货的处理
            if ($checkaftersales && $item['delivery_item_num'] > 0) {
                $aftersalesService = new AftersalesService();
                $applied_num = $aftersalesService->getAppliedNum($item['company_id'], $item['order_id'], $item['id']); // 已申请数量
                $item['left_aftersales_num'] = $item['delivery_item_num'] + $item['cancel_item_num'] - $applied_num; // 剩余申请数量
                $item['show_aftersales'] = $applied_num > $item['cancel_item_num'] ? 1 : 0;
                // 超出售后失效不显示售后按钮
                if ($item['auto_close_aftersales_time'] > 0 && $item['auto_close_aftersales_time'] < time()) {
                    continue;
                }
                $can_apply_aftersales += $item['left_aftersales_num'];
                // 用于判断整个订单是否显示售后申请按钮，只有其中一个商品可以申请售后就显示
                if ($can_apply_aftersales) {
                    $orderInfo['can_apply_aftersales'] = 1;
                }
            }
        }
        //获取交易单信息
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $trade = $tradeRepository->getTradeList($filter);
        if ($trade['list']) {
            $tradeInfo = $trade['list'][0];
        }

        $orderInfo['items'] = $orderItems;
        if ($orderInfo['order_status'] == 'NOTPAY' && $orderInfo['auto_cancel_time'] - time() <= 0 && $orderInfo['order_class'] != 'drug') {
            $orderInfo['order_status'] = 'CANCEL';
        }
        // 达达同城配数据
        $orderInfo['dada'] = [];
        if ($orderInfo['receipt_type'] == 'dada') {
            $filter = [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ];
            $orderInfo['dada'] = $this->normalOrdersRelDadaRepository->getInfo($filter);
            $orderInfo['dada']['delivery_length'] = $this->getDadaDeliveryLength($orderInfo['dada']);
        }
        $orderInfo['order_status_msg'] = $this->getOrderStatusMsg($orderInfo, $orderInfo['dada'], $from);

        if ($orderInfo['delivery_corp']) {
            $logisticsServices = new LogisticsService();
            if ($orderInfo['delivery_corp_source'] == 'kuaidi100') {
                $cols = 'kuaidi_code';
            } else {
                $cols = 'corp_code';
            }
            $companyRelLogistics = $logisticsServices->getLogisticsFirst([$cols => $orderInfo['delivery_corp']]);

            $orderInfo['delivery_corp_name'] = $companyRelLogistics ? $companyRelLogistics['corp_name'] : $orderInfo['delivery_corp'];
            $orderInfo['delivery_corp_name'] = $orderInfo['delivery_corp_name'] == 'OTHER' ? '其他' : $orderInfo['delivery_corp_name'];
        }
        $lastAftersaleSetting = $this->getOrdersSetting($companyId, 'latest_aftersale_time');
        $latestAftersaleTime = 0;
        if ($orderInfo['order_status'] == 'DONE') {
            $latestAftersaleTime = $lastAftersaleSetting ? (strtotime(date('Y-m-d', strtotime('+' . ($lastAftersaleSetting + 1) . 'day', $orderInfo['end_time']))) - 1) - time() : -1;
        }
        $orderInfo['latest_aftersale_time'] = $latestAftersaleTime;
        // 预计赠送积分
        $orderInfo['estimate_get_points'] = bcadd($orderInfo['get_points'], $orderInfo['bonus_points'], 0);

        //开票地址(预留)
        if ($invoiceUrl = env('INVOICE_URL', '')) {
            $invoiceUrl .= '?' . $orderInfo['order_id'];
            $orderInfo['invoice_url'] = $invoiceUrl;
        }

        //发货单新旧兼容, 部分发货的订单需继续按照原发货流程进行
        $orderInfo['delivery_type'] = 'new';
        if (!empty($orderInfo['delivery_code'])) {
            $orderInfo['delivery_type'] = 'old';
        } else {
            foreach ($orderItems as $items_val) {
                if (!empty($items_val['delivery_code'])) {
                    $orderInfo['delivery_type'] = 'old';
                    break;
                }
            }
        }

        //是否整单发货
        if ($orderInfo['delivery_type'] == 'new') {
            $_filter = [
                'order_id' => $orderId
            ];
            $orders_delivery_info = $this->ordersDeliveryRepository->getInfo($_filter);
            $orderInfo['is_all_delivery'] = isset($orders_delivery_info['package_type']) && $orders_delivery_info['package_type'] == 'batch' ? true : false;
        }

        // 过滤折扣活动
        if (!empty($orderInfo['discount_info'])) {
            $orderInfo['discount_info'] = (new PromotionGroupsActivityService())->filterGroupActivity($orderInfo['company_id'], $orderInfo['user_id'], $orderInfo['discount_info']);
        }

        $subdistrictService = new SubdistrictService();
        if (isset($orderInfo['subdistrict_parent_id'])) {
            $subdistrict = $subdistrictService->getInfoById($orderInfo['subdistrict_parent_id']);
            $orderInfo['subdistrict_parent'] = $subdistrict['label'] ?? '';
        }
        if (isset($orderInfo['subdistrict_id'])) {
            $subdistrict = $subdistrictService->getInfoById($orderInfo['subdistrict_id']);
            $orderInfo['subdistrict'] = $subdistrict['label'] ?? '';
        }

        // 自提信息
        if ($orderInfo['receipt_type'] == 'ziti') {
            $normalOrdersRelZitiService = new NormalOrdersRelZitiService();
            $zitiInfo = $normalOrdersRelZitiService->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
            if ($zitiInfo) {
                $orderInfo['ziti_info'] = $zitiInfo;
            }
        }

        return [
            'orderInfo' => $orderInfo,
            'tradeInfo' => $tradeInfo ?? [],
            'distributor' => $distributorInfo,
            'cancelData' => $cancelData ?? [],
        ];
    }

    /**
     * 根据不同来源，获取达达的状态对应的文字
     * @param string $from api:默认接口；front_list:前端列表；front_detail:前端详情
     * @return [type]       [description]
     */
    private function getDadaStatus($from = 'api')
    {
        switch ($from) {
            case 'front_detail':
                $dadaStatus = [
                    '0' => '等待商家接单',
                    '1' => '门店正在拣货，等待骑手接单',
                    '2' => '骑手正赶往商家',
                    '100' => '骑士到店',
                    '3' => '骑手正在快马加鞭向您赶去',
                    '4' => '此次订单已完成',
                    '5' => '您的订单已取消',
                    '9' => '收货地址异常，请联系客服',
                    '10' => '此次订单已完成',
                ];
                break;
            case 'front_list':
                $dadaStatus = [
                    '0' => '商家待接单',
                    '1' => '商家已接单',
                    '2' => '待取货',
                    '100' => '骑士到店',
                    '3' => '配送中',
                    '9' => '未妥投',
                    '10' => '已完成',
                ];
                break;
            default:
                $dadaStatus = [
                    '0' => '店铺待接单',
                    '1' => '骑士待接单',
                    '2' => '待取货',
                    '100' => '骑士到店',
                    '3' => '配送中',
                    '9' => '未妥投',
                    '10' => '妥投异常',
                ];
                break;
        }
        return $dadaStatus;
    }

    private function getOrderStatusMsg(&$order, $dadaData = null, $from = 'api')
    {
        $dadaStatus = $this->getDadaStatus($from);
        switch ($order['order_status']) {
            case "WAIT_GROUPS_SUCCESS":
                $statusMsg = '等待成团';
                $order['order_status_des'] = 'WAIT_GROUPS_SUCCESS';
                break;
            case "NOTPAY":
                $statusMsg = '待支付';
                $order['order_status_des'] = 'NOTPAY';
                break;
            case "PAYED":
                if ($order['cancel_status'] == 'WAIT_PROCESS') {
                    $order['order_status_des'] = 'PAYED_WAIT_PROCESS';
                    $statusMsg = '退款处理中';
                } elseif ($order['ziti_status'] == 'PENDING') {
                    $statusMsg = '待自提';
                    $order['order_status_des'] = 'PAYED_PENDING';
                } elseif ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分发货';
                    $order['order_status_des'] = 'PAYED_PARTAIL';
                } elseif ($order['delivery_status'] == 'DONE') {
                    $order['order_status_des'] = 'WAIT_BUYER_CONFIRM';
                    $statusMsg = '待收货';
                    if (isset($dadaData) && $order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
                        $statusMsg = $dadaStatus[$dadaData['dada_status']];
                    }
                } else {
                    // 判断是否开启OME
                    $service = new ThirdSettingService();
                    $data = $service->getShopexErpSetting($order['company_id']);
                    if (!isset($data) || $data['is_open'] == false) {
                        $statusMsg = '待发货';
                    } else {
                        $statusMsg = '审核中';
                    }

                    $order['order_status_des'] = 'PAYED';

                    if (isset($dadaData) && $order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
                        $statusMsg = $dadaStatus[$dadaData['dada_status']];
                    }
                }
                break;
            case 'REVIEW_PASS':
                if ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分出库';
                    $order['order_status_des'] = 'REVIEW_PASS_PARTAIL';
                } else {
                    $statusMsg = '审核完成,待出库';
                    $order['order_status_des'] = 'REVIEW_PASS';
                    break;
                }
                // no break
            case "CANCEL":
                if ($order['delivery_status'] == 'DONE' || $order['ziti_status'] == 'DONE') {
                    $statusMsg = '已关闭';
                    $order['order_status_des'] = 'CLOSED';
                } elseif ($order['cancel_status'] == 'NO_APPLY_CANCEL') {
                    $statusMsg = '已取消';
                    $order['order_status_des'] = 'CANCEL';
//                    if ($order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
//                        $statusMsg = $dadaStatus[$dadaData['dada_status']];
//                    }
                } elseif ($order['cancel_status'] == 'WAIT_PROCESS ') {
                    $statusMsg = '退款处理中';
                    $order['order_status_des'] = 'CANCEL_WAIT_PROCESS';
                } elseif ($order['cancel_status'] == 'REFUND_PROCESS') {
                    $statusMsg = '退款处理中';
                    $order['order_status_des'] = 'CANCEL_REFUND_PROCESS';
                } elseif ($order['cancel_status'] == 'SUCCESS') {
                    $order['order_status_des'] = 'CANCEL';
                    $statusMsg = '已取消';
                    if (isset($dadaData) && $order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
                        $statusMsg = $dadaStatus[$dadaData['dada_status']];
                    }
                } else {
                    // 退款失败
                    $order['order_status_des'] = 'CANCEL_REFUND_FAIL';
                    $statusMsg = '等待退款';
                }
                break;
            case "WAIT_BUYER_CONFIRM":
                $order['order_status_des'] = 'WAIT_BUYER_CONFIRM';
                $statusMsg = '待收货';
                if (isset($dadaData) && $order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
                    $statusMsg = $dadaStatus[$dadaData['dada_status']];
                }
                break;
            case "DONE":
                $order['order_status_des'] = 'DONE';
                $statusMsg = '已完成';
                if (isset($dadaData) && $order['receipt_type'] == 'dada' && isset($dadaStatus[$dadaData['dada_status']])) {
                    $statusMsg = $dadaStatus[$dadaData['dada_status']];
                }
                break;
            case "REFUND_PROCESS":
                $order['order_status_des'] = 'REFUND_PROCESS';
                $statusMsg = '退款处理中';
                break;
            case "REFUND_SUCCESS":
                $order['order_status_des'] = 'REFUND_SUCCESS';
                $statusMsg = '已退款';
                break;
            case "PART_PAYMENT":
                $order['order_status_des'] = 'PART_PAYMENT';
                $statusMsg = '部分付款';
                break;
            default:
                $order['order_status_des'] = 'ORDER_ABERRANT';
                $statusMsg = '订单异常';
                break;
        }
        if ($from == 'api') {
            // 店务端附加字段处理
            $appAttachService = new OrderAppAttachService();
            $attachParams = [
                'order_id' => $order['order_id'],
                'company_id' => $order['company_id'],
                'order_type' => $order['order_type'],
                'order_class' => $order['order_class'],
                'order_status_des' => $order['order_status_des'],
                'update_time' => $order['update_time'],
                'end_time' => $order['end_time'],
                'order_auto_close_aftersales_time' => $order['order_auto_close_aftersales_time'],
                'left_aftersales_num' => $order['left_aftersales_num'],
            ];
            if ($dadaData && isset($dadaData['dada_status'])) {
                $attachParams['dada_status'] = $dadaData['dada_status'];
            }
            $order['app_info'] = $appAttachService->getAppInfo(
                $order['order_status'],
                $order['receipt_type'],
                $attachParams
            );
        }
        return $statusMsg;
    }

    /**
     * 更新销量
     * @param $orderId 订单id
     */
    public function incrSales($orderId, $companyId)
    {
        $list = $this->normalOrdersItemsRepository->getList(['order_id' => $orderId, 'company_id' => $companyId]);
        $itemsService = new ItemsService();
        foreach ($list['list'] as $v) {
            $itemsService->incrSales($v['item_id'], $v['num']);
            //更新经销商名下商品销量
            if (isset($v['distributor_id']) && $v['distributor_id']) {
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsService->incrSales($v['distributor_id'], $v['item_id'], $v['num']);
            }
        }
        return true;
    }

    // 实体订单发货
    public function delivery($params)
    {
        //兼容逻辑
        //没发货单之前的逻辑
        if (isset($params['type'])) {
            if ($params['type'] == 'old') {
                $result = $this->oldDelivery($params);
            }

            //新发货单的逻辑
            if ($params['type'] == 'new') {
                if ($params['logistics_type'] == 2){
                    //调用绿界物流
                    $orderEcpayDeliveryService = new OrderEcpayDeliveryService();
                    $delivery = $orderEcpayDeliveryService->delivery($params);

                    $params['delivery_corp'] = 'TCAT';
                    $params['delivery_code'] = $delivery['AllPayLogisticsID'];
                }
                $service = new OrderDeliveryService();
                $result = $service->delivery($params);
            }
        } else {
            $result = $this->oldDelivery($params);
        }

        # 判断是否开启到货通知
        $operatorDataPassService = new OperatorDataPassService();
        $status = $operatorDataPassService->getPushMessageStatus($params['merchant_id'] ?? 0 ,$params['company_id'] ?? 0,$params['distributor_id'] ?? 0);
        if($status == 1 ){
            $filter = [
                'order_id' => $params['order_id'],
            ];
            $orderInfo = $this->normalOrdersRepository->getInfo($filter);
            # 获取商品信息
            $orderItemsList = $this->normalOrdersItemsRepository->getList($filter);
            if(isset($orderItemsList['list']) && !empty($orderItemsList['list'])){
                foreach ($orderItemsList['list'] as $i){
                    $items_data[] = [
                        'name' => $i['item_name'],
                        'num'  => $i['num']
                    ];
                }
            }
            $memberService = new MemberService();
            $memberInfo    = $memberService->getMemberInfo(['user_id' => $orderInfo['user_id']]);
            $request_params = [
                'memberId' => $memberInfo['app_member_id'] ?? 0 ,
                'messageId'   => 0,
                'messageType' => 1,//消息类型[1:到货通知]
                'data' => [
                    "orderNo"              =>  $params['order_id'],//订单号
                    "commodityInfo"        =>  $items_data ?? [],//商品信息
                    "deliveryCompany"      =>  $params['delivery_corp'] ?? '',//快递公司
                    "deliveryOne"          =>  $params['delivery_code'] ?? '',//快递单号
                    "deliveryDate"         =>  date("Y-m-d H:i:s",time()),//发货日期
                    "orderCreationDate"    => isset($orderInfo['create_time']) && !empty($orderInfo['create_time']) ? date("Y-m-d H:i:s",$orderInfo['create_time']) : '',//订单创建日期
                ],
            ];
            $request_url = config('common.zgj_app_url').'/v1/api/message/shopping';
            $header_param = [
                'Content-Type'  =>'application/json; charset=utf-8' ,
                'Accept'        => "application/json",
                'appKey'        => config('common.zgj_app_key'),
                'appSecret'     => config('common.zgj_app_secret'),
            ];
            $pushMessageService = new PushMessageService();
            $pushMessageService->createPushMessage('post',
                $request_url,$request_params,1,'物流到貨通知',
                $params['company_id'] ?? 0 ,$params['merchant_id'] ?? 0 ,
                $params['distributor_id'] ?? 0,
                $orderInfo['user_id'] ?? 0,
                $header_param
            );
        }

        //触发订单oms更新的事件
        event(new SaasErpUpdateEvent($result));
        return $result;
    }

    private function oldDelivery($params)
    {
        $params['delivery_type'] = (isset($params['delivery_type']) && $params['delivery_type']) ? $params['delivery_type'] : 'batch';
        $rules = [
            'delivery_type' => ['required', '订单发货类型必选'],
            'order_id' => ['required', '订单号缺失'],
            'company_id' => ['required', '企业id必填'],
            'delivery_corp' => ['required_if:delivery_type,batch', '快递公司必填'],
            'delivery_code' => ['required_if:delivery_type,batch', '快递单号必填'],
            'sepInfo' => ['required_if:delivery_type,sep', '拆单信息必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $order = $this->normalOrdersRepository->get($params['company_id'], $params['order_id']);
        if (!$order) {
            throw new Exception("订单号为{$params['order_id']}的订单不存在");
        }
        if ($order->getOrderStatus() == 'NOTPAY') {
            throw new Exception("订单号为{$params['order_id']}的订单未支付，不能发货");
        }
        if ($order->getOrderStatus() == 'CANCEL') {
            throw new Exception("订单号为{$params['order_id']}的订单已取消，不能发货");
        }
        if ($order->getCancelStatus() == 'WAIT_PROCESS' || $order->getCancelStatus() == 'REFUND_PROCESS') {
            throw new Exception("订单号为{$params['order_id']}的订单有退款待处理，不能发货");
        }
        if ($order->getDeliveryStatus() == 'DONE') {
            throw new Exception("订单号为{$params['order_id']}的订单已发货，不能重复发货");
        }
        $finishTime = $this->getOrdersSetting($params['company_id'], 'order_finish_time');
        $finishTime = $finishTime * 24 * 3600; //订单自动完成时间换算为秒数
        $updateInfo = [
            'delivery_corp' => $params['delivery_corp'],
            'delivery_code' => $params['delivery_code'],
            'delivery_img' => $params['delivery_img'] ?? '',
            'delivery_corp_source' => app('redis')->get('kuaidiTypeOpenConfig:' . sha1($params['company_id'])),
            'delivery_status' => 'DONE',
            'delivery_time' => time(),
            'auto_finish_time' => time() + $finishTime,
            'order_status' => 'WAIT_BUYER_CONFIRM',
        ];
        $filter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
        ];
        // if ($params['delivery_type'] == 'sep') {
        $orderItems = $this->normalOrdersItemsRepository->get($params['company_id'], $params['order_id']);
        if (!$orderItems) {
            throw new Exception("订单号为{$params['order_id']}的子订单不存在");
        }
        $orderItemIds = array_column($orderItems, 'item_id');
        // }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // 拆单发货信息更新
            if ($params['delivery_type'] == 'sep') {
                $deliveryItems = [];
                $sepInfo = json_decode($params['sepInfo'], 1);

                //发货单
                $order_delivery_arr = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id'],
                    'user_id' => $orderItems[0]['user_id'] ?? 0,
                    'logistics_type' => $params['logistics_type'],
                    'delivery_corp_name' => $params['logi_name'] ?? '其他',
                    'delivery_corp' => $params['delivery_corp'],
                    'delivery_code' => $params['delivery_code'],
                    'delivery_corp_source' => app('redis')->get('kuaidiTypeOpenConfig:' . sha1($params['company_id'])),
                    'receiver_mobile' => $params['ship_mobile'],
                    'package_type' => $params['delivery_type'],
                    'delivery_time' => time(),
                    'created' => time()
                ];
                $orders_delivery_res = $this->ordersDeliveryRepository->create($order_delivery_arr);

                $canAftersalesNum = 0;
                //发货单商品
                foreach ($sepInfo as $order_items_val) {
                    if (!isset($order_items_val['ship_num'])) {
                        continue;
                    }
                    $order_delivery_item = [
                        'orders_delivery_id' => $orders_delivery_res['orders_delivery_id'],
                        'company_id' => $params['company_id'],
                        'order_id' => $params['order_id'],
                        'order_items_id' => $order_items_val['id'],
                        'item_id' => $order_items_val['item_id'],
                        'num' => $order_items_val['ship_num'],
                        'item_name' => $order_items_val['item_name'],
                        'pic' => $order_items_val['pic'],
                        'created' => time(),
                    ];
                    $this->ordersDeliveryItemsRepository->create($order_delivery_item);
                    $canAftersalesNum += $order_items_val['ship_num'];
                }


                foreach ($sepInfo as $item) {
                    $itemFilter = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'item_id' => $item['item_id'],
                    ];
                    if ($item['delivery_corp'] && $item['delivery_code']) {
                        if ($item['delivery_status'] == 'DONE') {
                            $deliveryItems[] = $item['item_id'];
                        }
                        $itemInfo = $this->normalOrdersItemsRepository->getRow($itemFilter);
                        if (!$itemInfo) {
                            throw new Exception("订单号为{$params['order_id']},商品id为{$item['item_id']}的子订单不存在");
                        }
                        //if(!$itemInfo['delivery_corp']) {
                        $update = [
                            'delivery_corp' => $item['delivery_corp'],
                            'delivery_code' => $item['delivery_code'],
                            'delivery_item_num' => $item['delivery_item_num'],
                            'delivery_status' => $item['delivery_status'],
                            'delivery_time' => time(),
                        ];
                        $sendData['company_id'] = $order->getCompanyId();
                        $sendData['order_id'] = $order->getOrderId();
                        $sendData['item_name'] = $itemInfo['item_name'];
                        $sendData['delivery_corp_source'] = $updateInfo['delivery_corp_source'];
                        $this->sendDeliverySuccNotice($sendData, $item, 'sep');
                        $this->normalOrdersItemsRepository->update($itemFilter, $update);
                        //}
                    }
                }
                // 如果存在未发货商品，主订单发货状态为部分发货
                $noupdateItem = array_diff($orderItemIds, $deliveryItems);
                if ($noupdateItem) {
                    $updateInfo['delivery_status'] = 'PARTAIL';
                    unset($updateInfo['order_status']);
                }
            } elseif ($params['delivery_type'] == 'batch') {
                $sendData['company_id'] = $order->getCompanyId();
                $sendData['order_id'] = $order->getOrderId();
                $sendData['delivery_corp_source'] = $updateInfo['delivery_corp_source'];
                $this->sendDeliverySuccNotice($sendData, $updateInfo, 'batch');
                // 更新订单字表
                $updateInfo['logistics_type'] = $params['logistics_type'];//发货类型
                $this->normalOrdersItemsRepository->updateBy($filter, $updateInfo);

                $canAftersalesNum = array_sum(array_column($orderItems, 'num'));
            }

            $updateInfo['left_aftersales_num'] = $order->getLeftAftersalesNum() + $canAftersalesNum;
            $this->normalOrdersRepository->update($filter, $updateInfo);
            $result = $this->orderAssociationsRepository->update($filter, $updateInfo);

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
        }
    }

    public function updateDelivery($params)
    {
        $params['delivery_type'] = (isset($params['delivery_type']) && $params['delivery_type']) ? $params['delivery_type'] : 'batch';
        $rules = [
            'delivery_type' => ['required', '订单发货类型必选'],
            'order_id' => ['required', '订单号缺失'],
            'company_id' => ['required', '企业id必填'],
            'delivery_corp' => ['required_if:delivery_type,batch', '快递公司必填'],
            'delivery_code' => ['required_if:delivery_type,batch', '快递单号必填'],
            'sepInfo' => ['required_if:delivery_type,sep', '拆单信息必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $order = $this->normalOrdersRepository->get($params['company_id'], $params['order_id']);
        if (!$order) {
            throw new Exception("订单号为{$params['order_id']}的订单不存在");
        }
        if ($order->getOrderStatus() != 'WAIT_BUYER_CONFIRM') {
            throw new Exception("订单号为{$params['order_id']}的订单未发货，不能修改发货信息");
        }
        $updateInfo = [
            'delivery_corp' => $params['delivery_corp'],
            'delivery_code' => $params['delivery_code'],
        ];
        $filter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
        ];
        if ($params['delivery_type'] == 'sep') {
            $orderItems = $this->normalOrdersItemsRepository->get($params['company_id'], $params['order_id']);
            if (!$orderItems) {
                throw new Exception("订单号为{$params['order_id']}的子订单不存在");
            }
            $orderItemIds = array_column($orderItems, 'item_id');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            // 拆单发货信息更新
            if ($params['delivery_type'] == 'sep') {
                $deliveryItems = [];
                $sepInfo = json_decode($params['sepInfo'], 1);
                foreach ($sepInfo as $item) {
                    $itemFilter = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'item_id' => $item['item_id'],
                    ];
                    if ($item['delivery_corp'] && $item['delivery_code']) {
                        $deliveryItems[] = $item['item_id'];
                        $itemInfo = $this->normalOrdersItemsRepository->getRow($itemFilter);
                        if (!$itemInfo) {
                            throw new Exception("订单号为{$params['order_id']},商品id为{$item['item_id']}的子订单不存在");
                        }
                        $update = [
                            'delivery_corp' => $item['delivery_corp'],
                            'delivery_code' => $item['delivery_code'],
                        ];
                        $this->normalOrdersItemsRepository->update($itemFilter, $update);
                    }
                }
                // 如果存在未发货商品，主订单发货状态为部分发货
                $noupdateItem = array_diff($orderItemIds, $deliveryItems);
                if ($noupdateItem) {
                    $updateInfo['delivery_status'] = 'PARTAIL';
                }
            } elseif ($params['delivery_type'] == 'batch') {
                // 更新订单字表
                $this->normalOrdersItemsRepository->updateBy($filter, $updateInfo);
            }

            $this->normalOrdersRepository->update($filter, $updateInfo);
            $result = $this->orderAssociationsRepository->update($filter, $updateInfo);
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $params['operator_type'] ?? 'system',
                'operator_id' => $params['operator_id'] ?? 0,
                'remarks' => '订单发货',
                'detail' => '订单号：' . $params['order_id'] . '，订单发货信息修改',
                'params' => $params,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     *  订单备注修改
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function updateRemark($params, $is_distribution = false)
    {
        $order = $this->normalOrdersRepository->get($params['company_id'], $params['order_id']);
        if (!$order) {
            throw new Exception("订单号为{$params['order_id']}的订单不存在");
        }

        if ($is_distribution) {
            $updateInfo = [
                'distribution_remark' => $params['remark'],
            ];
        } else {
            $rules = [
                'remark' => ['required', '订单备注必填'],
            ];
            $errorMessage = validator_params($params, $rules);
            if ($errorMessage) {
                throw new ResourceException($errorMessage);
            }
            $updateInfo = [
                'remark' => $params['remark'],
            ];
        }

        $filter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
        ];
        $this->normalOrdersRepository->update($filter, $updateInfo);
        $result = $this->orderAssociationsRepository->update($filter, $updateInfo);
        $orderProcessLog = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'operator_type' => $params['operator_type'] ?? 'system',
            'operator_id' => $params['operator_id'] ?? 0,
            'remarks' => '订单备注',
            'detail' => '订单号：' . $params['order_id'] . '，订单'.($is_distribution ? '商家' : '').'备注修改',
            'params' => $params,
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        return $result;
    }

    public function sendDeliverySuccNotice($orderData, $delivery, $type = 'batch')
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($orderData['company_id'], $orderData['order_id']);

        $openid = 0;
        if ($order['wxa_appid']) {
            $openid = app('wxaTemplateMsg')->getOpenIdBy($order['user_id'], $order['wxa_appid']);
        }

        if ($openid) {
            $logisticsServices = new LogisticsService();
            if ($orderData['delivery_corp_source'] == 'kuaidi100') {
                $cols = 'kuaidi_code';
            } else {
                $cols = 'corp_code';
            }
            $companyRelLogistics = $logisticsServices->getLogisticsFirst([$cols => $delivery['delivery_corp']]);
            $deliveryCorpName = $companyRelLogistics['corp_name'] ?? '其他';

            //发送小程序模版
            $wxaTemplateMsgData = [
                'order_id' => $order['order_id'],
                'delivery_corp' => $deliveryCorpName,
                'delivery_code' => $delivery['delivery_code'],
                'item_name' => $type == 'batch' ? $order['title'] : $orderData['item_name'],
            ];
            $sendData['scenes_name'] = 'orderDeliverySucc';
            $sendData['company_id'] = $order['company_id'];
            $sendData['appid'] = $order['wxa_appid'];
            $sendData['openid'] = $openid;
            $sendData['data'] = $wxaTemplateMsgData;
            app('wxaTemplateMsg')->send($sendData);
        }
    }

    /**
     *  根据自提订单码 获取订单号
     */
    public function getOrderIdByCode($code)
    {
        $orderId = app('redis')->connection('wechat')->get('orderziticode:' . $code);
        return $orderId;
    }

    public function getOrderZitiCode($companyId, $orderId)
    {
        $result = [
            'user_id' => '',
            'barcode_url' => '',
            'qrcode_url' => '',
            'code' => '',
        ];
        if (is_numeric($orderId)) {
            $orderDetail = $this->normalOrdersRepository->get($companyId, $orderId);

            $code = $orderDetail->getZitiCode() . $this->getCode(6);
            app('redis')->connection('wechat')->setex('orderziticode:' . $code, 300, $orderId);

            $dns1d = app('DNS1D')->getBarcodePNG("ZT_" . $code, "C93", 1, 70);
            $dns2d = app('DNS2D')->getBarcodePNG("ZT_" . $code, "QRCODE", 120, 120);

            $result = [
                'user_id' => $orderDetail->getUserId(),
                'barcode_url' => 'data:image/jpg;base64,' . $dns1d,
                'qrcode_url' => 'data:image/jpg;base64,' . $dns2d,
                'code' => $code,
                'ziti_status' => $orderDetail->getZitiStatus(),
            ];
        }
        return $result;
    }

    public function getCode($length = 8, $prefix = '', $suffix = '')
    {
        // $uppercase    = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M'];
        $numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $characters = [];
        $coupon = '';
        // $characters = array_merge($numbers, $uppercase);
        $characters = $numbers;

        $first = $characters[mt_rand(1, count($characters) - 1)];

        for ($i = 0; $i < $length - 1; $i++) {
            $coupon .= $characters[mt_rand(0, count($characters) - 1)];
        }
        return $prefix . $first . $coupon . $suffix;
    }

    // 订单售后状态更新
    public function ItemAftersalesStatusUpdate($filter, $updateInfo)
    {
        $order = $this->normalOrdersItemsRepository->getRow($filter);
        if (!$order) {
            throw new Exception("订单号为{$filter['order_id']},商品id为{$filter['item_id']}的订单不存在");
        }
        $result = $this->normalOrdersItemsRepository->update($filter, $updateInfo);

        return $result;
    }

    public function getOrderItemList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('orders_normal_orders_items', 'orderitem')
            ->leftJoin('orderitem', 'orders_normal_orders', 'orders', 'orderitem.order_id = orders.order_id');

        $row = 'orders.delivery_status as order_delivery_status,';
        $row .= 'orders.delivery_time as order_delivery_time,';
        $row .= 'orders.delivery_corp as order_delivery_corp,';
        $row .= 'orders.delivery_code as order_delivery_code,';
        $row .= 'orders.building_number,orders.house_number,orders.subdistrict_parent_id,orders.subdistrict_id,orders.order_type,orders.cancel_status,orders.end_time,orders.receiver_state,orders.receiver_city,orders.receiver_district,orders.pay_type,orders.order_id,orders.total_fee,orders.freight_fee,orders.distributor_id,orders.mobile,orders.user_id,orders.create_time,orders.order_class,orders.order_status,orders.third_params,orders.receipt_type,orders.ziti_status,orders.receiver_name,orders.receiver_mobile,orders.receiver_zip,orders.receiver_address,orders.invoice,orders.remark,orders.order_auto_close_aftersales_time,orders.left_aftersales_num,orderitem.*';

        $criteria = $this->getFilter($filter, $criteria);

        if ($limit > 0) {
            $criteria->setFirstResult(($offset - 1) * $limit)->setMaxResults($limit);
        }

        foreach ($orderBy as $key => $value) {
            if ($key == 'id' || $key == 'auto_close_aftersales_time') {
                $criteria->addOrderBy('orderitem.' . $key, $value);
            } else {
                $criteria->addOrderBy('orders.' . $key, $value);
            }
        }
        $lists = $criteria->select($row)->execute()->fetchAll();

        // 达达同城配数据
        $dada_filter = [
            'order_id' => array_column($lists, 'order_id'),
        ];
        $dadaOrderList = $this->normalOrdersRelDadaRepository->getLists($dada_filter);
        $dadaOrderList = array_column($dadaOrderList, null, 'order_id');

        //获取街道居委
        $subdistrictService = new SubdistrictService();
        $subdistrictId = array_column($lists, 'subdistrict_id');
        $subdistrictId = array_merge($subdistrictId, array_column($lists, 'subdistrict_parent_id'));
        array_unique($subdistrictId);
        $subdistrict = $subdistrictService->lists(['id' => $subdistrictId], 1, -1);
        $subdistrict = array_column($subdistrict['list'], 'label', 'id');
        foreach ($lists as $key => $value) {
            $result['user_ids'][$value['user_id']] = $value['user_id'];
            $result['distributor_ids'][$value['distributor_id']] = $value['distributor_id'];
            $lists[$key]['mobile'] = fixeddecrypt($value['mobile']);
            $lists[$key]['receiver_name'] = fixeddecrypt($value['receiver_name']);
            $lists[$key]['receiver_mobile'] = fixeddecrypt($value['receiver_mobile']);
            $lists[$key]['receiver_address'] = fixeddecrypt($value['receiver_address']);
            // 达达同城配数据
            $dadaData = $dadaOrderList[$value['order_id']] ?? [];
            $lists[$key]['order_status_msg'] = $this->getOrderStatusMsg($value, $dadaData);
            $lists[$key]['subdistrict_parent'] = $subdistrict[$value['subdistrict_parent_id']] ?? '';
            $lists[$key]['subdistrict'] = $subdistrict[$value['subdistrict_id']] ?? '';
        }
        $result['list'] = $lists;
        return $result;
    }

    public function getOrderItemCount($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('orders_normal_orders_items', 'orderitem')
            ->leftJoin('orderitem', 'orders_normal_orders', 'orders', 'orderitem.order_id = orders.order_id');
        $criteria = $this->getFilter($filter, $criteria);
        return $criteria->execute()->fetchColumn();
    }

    private function getFilter($filter, $criteria)
    {
        $order = ['distributor_id', 'create_time', 'order_id', 'user_id', 'company_id', 'subdistrict_parent_id', 'subdistrict_id', 'act_id'];

        if (isset($filter['aftersales_status']) && $filter['aftersales_status'] == 'null') {
            $criteria = $criteria->andWhere($criteria->expr()->isNull('aftersales_status'));
            $criteria = $criteria->andWhere($criteria->expr()->isNotNull('auto_close_aftersales_time'));
            unset($filter['aftersales_status']);
        }

        if ($filter) {
            if (isset($filter['delivery_status'], $filter['ziti_status'])) {
                $filterValue = $criteria->expr()->literal($filter['delivery_status']);
                $criteria->andWhere($criteria->expr()->andX(
                    $criteria->expr()->eq('orderitem.delivery_status', $filterValue)
                ));
                $filterValue = $criteria->expr()->literal($filter['ziti_status']);
                $criteria->orWhere($criteria->expr()->andX(
                    $criteria->expr()->eq('ziti_status', $filterValue)
                ));
            }
            unset($filter['delivery_status'], $filter['ziti_status']);

            foreach ($filter as $key => $filterValue) {
                if ($filterValue) {
                    if (is_array($filterValue)) {
                        array_walk($filterValue, function (&$value) use ($criteria, $key) {
                            if ($key == 'mobile') {
                                $value = fixedencrypt($value);
                            }
                            $value = $criteria->expr()->literal($value);
                        });
                    } else {
                        if ($key == 'mobile') {
                            $filterValue = fixedencrypt($filterValue);
                        }
                        $filterValue = $criteria->expr()->literal($filterValue);
                    }
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $v = in_array($v, $order) ? 'orders.' . $v : $v;
                        $criteria->andWhere($criteria->expr()->andX(
                            $criteria->expr()->$k($v, $filterValue)
                        ));
                        continue;
                    } else {
                        $key = in_array($key, $order) ? 'orders.' . $key : $key;
                        if (is_array($filterValue)) {
                            $criteria->andWhere($criteria->expr()->andX(
                                $criteria->expr()->in($key, $filterValue)
                            ));
                        } else {
                            $criteria->andWhere($criteria->expr()->andX(
                                $criteria->expr()->eq($key, $filterValue)
                            ));
                        }
                    }
                }
            }
        }
        return $criteria;
    }

    // 根据子订单号等获取子订单信息
    public function getSimpleSubOrderInfo($filter)
    {
        $filter = [
            'company_id' => $filter['company_id'],
            'user_id' => $filter['user_id'],
            'order_id' => $filter['order_id'],
            'id' => $filter['id'],
        ];
        $result = $this->normalOrdersItemsRepository->getRow($filter);

        return $result;
    }

    // 根据订单号等获取主订单信息
    public function getSimpleOrderInfo($filter)
    {
        $filter = [
            'company_id' => $filter['company_id'],
            'order_id' => $filter['order_id'],
            'user_id' => $filter['user_id'],
        ];
        $result = $this->normalOrdersRepository->getInfo($filter);

        return $result;
    }

    // 获取子订单信息
    public function getOrderItemInfo($company_id, $order_id, $item_id)
    {
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'item_id' => $item_id,
        ];
        $orderItems = $this->normalOrdersItemsRepository->getRow($filter);

        return $orderItems;
    }

    /**
     * 根据主键ID获取信息
     */
    public function getOrderItemInfoById($id = 0)
    {
        $filter = [
            'id' => $id,
        ];
        $orderItems = $this->normalOrdersItemsRepository->getRow($filter);

        return $orderItems;
    }

    /**
     * 确认送达
     */
    public function confirmReceipt($params, $operator = null)
    {
        $orderEntity = $this->normalOrdersRepository->get($params['company_id'], $params['order_id']);
        if (!$orderEntity) {
            throw new ResourceException("订单号为{$params['order_id']}的订单不存在");
        }
        $orderInfo = $this->normalOrdersRepository->getServiceOrderData($orderEntity);
        if ($orderInfo['order_status'] != 'WAIT_BUYER_CONFIRM') {
            throw new ResourceException("没有需要完成的订单!");
        }
        if ($orderInfo['delivery_status'] != "DONE" && $orderInfo['delivery_status'] != "PARTAIL") {
            throw new ResourceException("未发货订单不可确认收货");
        }

        if ($orderInfo['cancel_status'] != 'FAILS' && $orderInfo['cancel_status'] != 'NO_APPLY_CANCEL') {
            throw new ResourceException("已取消订单不能确认收货");
        }

        $itemFilter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
            'user_id' => $params['user_id'],
            'aftersales_status|in' => ['WAIT_SELLER_AGREE', 'WAIT_BUYER_RETURN_GOODS', 'WAIT_SELLER_CONFIRM_GOODS'],// 驳回也可以确认收货
        ];
        $aftersalesItem = $this->normalOrdersItemsRepository->getList($itemFilter);
        if (isset($aftersalesItem['total_count']) && $aftersalesItem['total_count'] > 0) {
            throw new ResourceException("售后中的订单不能确认收货");
        }


        //更新售后时效时间
        $aftersalesTime = intval($this->getOrdersSetting($params['company_id'], 'latest_aftersale_time'));
        $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
        ];
        $updateInfo = [
            'order_status' => 'DONE',
            'end_time' => time(),
            'order_auto_close_aftersales_time' => $auto_close_aftersales_time
        ];

        $res = $this->update($filter, $updateInfo);
        if ($operator) {
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $operator['operator_type'] ?? 'system',
                'operator_id' => $operator['operator_id'] ?? 0,
                'remarks' => '订单完成',
                'detail' => '订单号：' . $params['order_id'] . '，订单完成',
            ];
            $dadaUpdate['dada_cancel_from'] = '12';
        } else {
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => 'user',
                'operator_id' => $params['user_id'],
                'remarks' => '订单完成',
                'detail' => '订单单号：' . $params['order_id'] . '，订单完成',
            ];
        }

        event(new OrderProcessLogEvent($orderProcessLog));

        // 创建银联商务支付，分账订单关联表
        if ($orderInfo['pay_type'] == 'chinaums') {
            if ($orderInfo['distributor_id'] > 0) {
                $relDivisionService = new OrdersRelChinaumspayDivisionService();
                $relDivisionService->addRelChinaumsPayDivision((int)$params['company_id'], (string)$orderInfo['order_id']);
            }
        }

        //消费满送大转盘抽奖次数
        $turntableService = new TurntableService();
        $turntableService->payGetTurntableTimes($params['user_id'], $params['company_id'], $orderInfo['total_fee']);
        //消费送积分
        if ($orderInfo['bonus_points'] > 0) {
            $pointMemberService = new PointMemberService();
            $mark = "订单号：" . $orderInfo['order_id'] . " 消费送积分";
            $pointMemberService->addPoint($orderInfo['user_id'], $orderInfo['company_id'], intval($orderInfo['bonus_points']), 7, true, $mark, $orderInfo['order_id']);
        }
        // 确认收获佣金进行结算
        $this->orderFinishBrokerage($params['company_id'], $params['order_id']);


        // $profitService = new ProfitService();
        // $profitService->cashedSuccess($params['company_id'], $params['order_id']);

        $orderProfitService = new OrderProfitService();
        $orderProfitService->orderProfitPlanCloseTime($params['company_id'], $params['order_id']);


        //更新会员等级- 积分支付订单不需要
        // if (!in_array($orderInfo['pay_type'], ['point'])) {
        //     //获取交易单信息
        //     $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        //     $trade = $tradeRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
        //     try {
        //         $memberService = new MemberService();
        //         $memberService->updateMemberConsumption($params['user_id'], $params['company_id'], $trade['pay_fee']);
        //     } catch (\Exception $e) {
        //         app('log')->debug('会员等级更新错误,会员id：'.$params['user_id']. '，错误信息: '.$e->getMessage());
        //     }
        // }

        //OMS同步 订单完成同步到OMS
        // app('log')->debug('AbstractNormalOrder_confirmReceipt_res:'. var_export($res,1));
        // event(new TradeUpdateEvent($res));
        // SaasErp 订单完成同步 不需要
        // app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.",确认收货 埋点");
        // event(new SaasErpFinishEvent($res));

        //触发订单确认收货事件
        //$eventData = [
        //    'company_id' => $params['company_id'],
        //    'order_id' => $params['order_id'],
        //];
        //event(new NormalOrderConfirmReceiptEvent($eventData));

        return $res;
    }

    public function orderFinishBrokerage($companyId, $orderId)
    {
        //触发订单确认收货事件
        $eventData = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        event(new NormalOrderConfirmReceiptEvent($eventData));

        $brokerageService = new BrokerageService();
        $brokerageService->updatePlanCloseTime($companyId, $orderId);
        return true;
    }

    /**
     * 更新字订单状态
     * @param $companyId
     * @param $orderId
     * @return mixed
     */
    public function finishOrderItemsZiti($companyId, $orderId)
    {
        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
        ];
        $updateInfo = [
            'delivery_status' => 'DONE',
            'delivery_time' => time(),
        ];

        return $this->normalOrdersItemsRepository->updateBy($filter, $updateInfo);
    }

    /**
     * 根据订单支付金额，更新会员等级
     * @param string $companyId 企业Id
     * @param array $orderDetail 订单详情
     * @return bool
     */
    public function orderUpdateMemberGrade($companyId, $orderDetail)
    {
        $orderInfo = $orderDetail['orderInfo'];
        $tradeInfo = $orderDetail['tradeInfo'];
        unset($orderDetail);
        //更新会员等级- 积分支付订单不需要
        if (!in_array($orderInfo['pay_type'], ['point'])) {
            //获取交易单信息
            try {
                $memberService = new MemberService();
                $memberService->updateMemberConsumption($orderInfo['user_id'], $companyId, $tradeInfo['payFee']);
            } catch (\Exception $e) {
                app('log')->debug('自提核销,会员等级更新错误,会员id：' . $orderInfo['user_id'] . '，错误信息: ' . $e->getMessage());
            }
        }
        return true;
    }

    // 修改已支付订单取消订单逻辑的时候
    // 注意社区团购订单，因为社区团购订单调用了该方法
    public function cancelOrder($params)
    {
        // 通用条件判断
        $rules = [
            'company_id' => ['required', '公司id错误'],
            'user_id' => ['required', '用户信息错误'],
            'order_id' => ['required', '订单号必填'],
            'cancel_from' => ['required', '取消渠道必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $order = $this->normalOrdersRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id'], 'user_id' => $params['user_id']]);
        if (!$order) {
            throw new ResourceException("订单号为{$params['order_id']}的订单不存在");
        }
        // if ((time() - $order['create_time']) <= 30) {
        //     throw new ResourceException('下单30秒后才能取消');
        // }
        if (!in_array($order['order_status'], ['NOTPAY', 'REVIEW_PASS', 'PAYED'])) {
            throw new ResourceException('订单状态已不能申请取消');
        }
        if ($order['cancel_status'] != 'NO_APPLY_CANCEL' && $order['cancel_status'] != 'FAILS') {
            throw new ResourceException("不能重复取消订单");
        }
        if ($order['delivery_status'] != 'PENDING') {
            throw new ResourceException("已发货订单不能取消");
        }

        //跨境订单，审核成功不允许取消
        if (isset($order['type']) && $order['type'] == 1 && $order['audit_status'] == 'approved') {
            throw new ResourceException('订单已审核成功，不能取消');
        }

        // 达达同城配订单，只允许 达达状态 0:待处理,1:待接单,2:待取货 取消订单
        $filter = [
            'company_id' => $order['company_id'],
            'order_id' => $order['order_id'],
        ];
        $dadaData = $this->normalOrdersRelDadaRepository->getInfo($filter);
        if ($order['receipt_type'] == 'dada' && !in_array($dadaData['dada_status'], [0,1])) {
            throw new ResourceException("骑手已接单，订单不能取消");
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($order['order_status'] == 'NOTPAY') {
                $res = $this->__noPayOrderCancel($order, $params);
                $updateInfo = [
                    'order_status' => 'CANCEL',
                    'cancel_status' => 'SUCCESS',
                ];
                $dadaUpdate = [
                    'dada_status' => '5',
                ];
                if ('shop' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => $params['operator_type'] ?? 'system',
                        'operator_id' => $params['operator_id'] ?? 0,
                        'remarks' => '商家取消订单',
                        'detail' => '订单号：' . $params['order_id'] . '，商家取消订单',
                        'params' => $params,
                    ];
                    $dadaUpdate['dada_cancel_from'] = '12';
                } elseif ('system' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'system',
                        'remarks' => '系统取消订单',
                        'detail' => '订单号：' . $params['order_id'] . '，系统取消订单',
                        'params' => $params,
                    ];
                    $dadaUpdate['dada_cancel_from'] = '11';
                } elseif ('buyer' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'user',
                        'operator_id' => $params['user_id'],
                        'remarks' => '订单取消',
                        'detail' => '订单号：' . $params['order_id'] . '，用户取消订单',
                        'params' => $params,
                    ];
                    $dadaUpdate['dada_cancel_from'] = '13';
                } elseif ('chief' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'chief',
                        'operator_id' => $params['chief_id'],
                        'remarks' => '订单取消',
                        'detail' => '订单号：' . $params['order_id'] . '，团长取消订单',
                        'params' => $params,
                    ];
                    $dadaUpdate['dada_cancel_from'] = '13';
                }

                event(new OrderProcessLogEvent($orderProcessLog));
                // 积分抵扣，升值，额度返回
                if ($order['uppoint_use'] > 0) {
                    $this->minusOrderUppoints($order['company_id'], $order['user_id'], $order['uppoint_use']);
                }
            } else {
                $validator = app('validator')->make($params, [
                    'cancel_reason' => 'required_without:other_reason',
                    'other_reason' => 'required_without:cancel_reason',
                ], [
                    'cancel_reason.*' => '取消原因必选',
                    'other_reason.*' => '其他取消原因必填',
                ]);
                if ($validator->fails()) {
                    $errorsMsg = $validator->errors()->toArray();
                    $errmsg = '';
                    foreach ($errorsMsg as $v) {
                        $msg = implode("，", $v);
                        $errmsg .= $msg . "，";
                    }
                    throw new ResourceException($errmsg);
                }

                $res = $this->__payedOrderCancel($order, $params);
                $updateInfo = [
                    'cancel_status' => 'WAIT_PROCESS',
                ];
                if ('shop' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => $params['operator_type'] ?? 'system',
                        'operator_id' => $params['operator_id'] ?? 0,
                        'remarks' => '申请取消订单',
                        'detail' => '订单号：' . $params['order_id'] . '，后台管理员申请取消订单，需要进行退款操作',
                        'params' => $params,
                    ];
                    $dadaUpdate = [
                        'dada_cancel_from' => '12',
                    ];
                } elseif ('system' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'system',
                        'remarks' => '申请取消订单',
                        'detail' => '订单号：' . $params['order_id'] . '，申请取消订单，需要进行退款操作',
                        'params' => $params,
                    ];
                    $dadaUpdate = [
                        'dada_cancel_from' => '11',
                    ];
                } elseif ('buyer' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'user',
                        'operator_id' => $params['user_id'],
                        'remarks' => '订单取消',
                        'detail' => '订单号：' . $params['order_id'] . '，用户申请取消订单，需要进行退款操作',
                        'params' => $params,
                    ];
                    $dadaUpdate = [
                        'dada_cancel_from' => '13',
                    ];
                } elseif ('chief' == $params['cancel_from']) {
                    $orderProcessLog = [
                        'order_id' => $params['order_id'],
                        'company_id' => $params['company_id'],
                        'operator_type' => 'chief',
                        'operator_id' => $params['chief_id'],
                        'remarks' => '订单取消',
                        'detail' => '订单号：' . $params['order_id'] . '，团长取消订单',
                        'params' => $params,
                    ];
                    $dadaUpdate = [
                        'dada_cancel_from' => '13',
                    ];
                }
                event(new OrderProcessLogEvent($orderProcessLog));
            }
            if ($res['cancel_id']) {
                $filter = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id']
                ];
                $this->update($filter, $updateInfo);
                // 变更达达同城配状态
                if ($order['receipt_type'] == 'dada') {
                    $this->normalOrdersRelDadaRepository->updateOneBy($filter, $dadaUpdate);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('取消订单失败 msg:'.$msg);
            $conn->rollback();
            throw $e;
        }
        //联通OME 取消订单创建退款申请单埋点
        event(new TradeRefundEvent($res));

        //联通SaasErp 取消订单 创建退款申请单 埋点
        app('log')->debug("saaserp " . __FUNCTION__ . "," . __LINE__ . ", 取消订单 创建退款申请单 埋点");
        event(new SaasErpRefundEvent($res));

        //触发订单取消事件
        $eventData = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
        ];
        event(new NormalOrderCancelEvent($eventData));

        return $res;
    }

    // 未支付订单取消
    public function __noPayOrderCancel($orderInfo, $params)
    {
        // 直接关闭订单
        $cancelData = $this->__preCancelData($orderInfo, $params);
        $cancelData['progress'] = 3;
        $cancelData['refund_status'] = 'SUCCESS';

        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $res = $cancelOrderRepository->create($cancelData);
        if (!$res['cancel_id']) {
            throw new ResourceException("订单取消失败！");
        }
        //退还积分
        (new PointMemberService())->cancelOrderReturnBackPoints($orderInfo);
        return $res;
    }

    // 已支付订单取消
    public function __payedOrderCancel($orderInfo, $params)
    {
        $cancelData = $this->__preCancelData($orderInfo, $params);

        $cancelData['refund_status'] = 'WAIT_CHECK';//退款状态 等待审核
        $cancelData['progress'] = 0;

        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelFilter = [
            'order_id' => $orderInfo['order_id'],
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
        ];
        $cancelOrder = $cancelOrderRepository->getInfo($cancelFilter);
        if ($cancelOrder) {
            $service = new TradeSettingService(new CancelService());
            $setting = $service->getSetting($params['company_id']);
            if ($setting['repeat_cancel'] ?? false) {
                $cancelOrder = $cancelOrderRepository->updateOneBy($cancelFilter, $cancelData);
            } else {
                throw new ResourceException("不能重复取消订单！");
            }
        } else {
            $cancelOrder = $cancelOrderRepository->create($cancelData);
        }
        // 生成退款单，不实际退款
        $aftersalesRefundService = new AftersalesRefundService();
        // $normalOrderService = new NormalOrderService();
        // $orderData = $normalOrderService->getOrderInfo($params['company_id'], $params['order_id']);
        $params['refund_type'] = 1;// 取消订单退款
        $tradeService = new TradeService();
        $trade_filter = [
            'company_id' => $params['company_id'],
            'order_id' => $orderInfo['order_id'],
            'trade_state' => 'SUCCESS',
        ];
        // 积分商城可能会有两条支付记录 积分支付+现金支付
        $trade_count = $tradeService->count($trade_filter);
        if ($trade_count > 1) {
            $trade_filter['pay_type|neq'] = 'point';
            $trade_lists = $tradeService->getTradeList($trade_filter);
            $trade_filter['trade_id'] = $trade_lists['list'][0]['tradeId'];
            unset($trade_filter['pay_type|neq']);
        }
        $trade = $tradeService->getInfo($trade_filter);
        $refundData = [
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
            'order_id' => $orderInfo['order_id'],
            'trade_id' => $trade['trade_id'],
            'shop_id' => $orderInfo['shop_id'] ?? 0,
            'distributor_id' => $orderInfo['distributor_id'] ?? 0,
            'refund_type' => 1, // 1:取消订单退款,
            'refund_channel' => 'original', // 默认取消订单原路返回
            'refund_status' => 'READY', // 售前取消订单退款默认审核成功
            'refund_fee' => $trade['total_fee'],
            'refund_point' => $orderInfo['point'],
            'return_freight' => 1, // 1:退运费,
            'pay_type' => $orderInfo['pay_type'], // 退款支付方式
            'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
            'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
            'cur_fee_rate' => $trade['cur_fee_rate'],
            'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
            'cur_pay_fee' => ($trade['pay_type'] == 'point') ? $orderInfo['point'] : $trade['cur_pay_fee'], // trade表没有单独积分字段，所以这样写
        ];
        $aftersalesRefundService->createRefund($refundData);
        // 达达同城配，取消订单
        $this->dadaCancelOrder($orderInfo, $cancelOrder);

        return $cancelOrder;
    }

    /**
     * 达达同城配，如果是商家或消费者取消，需要请求达达取消订单接口
     * @param array $orderInfo 订单详情数据
     * @param array $cancelData 取消数据
     * @return bool
     */
    public function dadaCancelOrder($orderInfo, $cancelOrder)
    {
        if ($orderInfo['receipt_type'] != 'dada') {
            return true;
        }
        $filter = [
            'company_id' => $orderInfo['company_id'],
            'order_id' => $orderInfo['order_id'],
        ];
        $dadaData = $this->normalOrdersRelDadaRepository->getInfo($filter);
        if ($dadaData['dada_status'] != '1') {
            return true;
        }
        if (!in_array($cancelOrder['cancel_from'], ['shop', 'buyer'])) {
            return true;
        }
        $dadaOrderService = new DadaOrderService();
        return $dadaOrderService->formalCancel($orderInfo['company_id'], $orderInfo['order_id'], $cancelOrder['cancel_reason']);
    }

    public function getCancelInfo($params)
    {
        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelFilter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'order_type' => $params['order_type'],
        ];
        $cancelOrder = $cancelOrderRepository->getInfo($cancelFilter);
        return $cancelOrder;
    }

    // 通用取消数据
    public function __preCancelData($order, $params)
    {
        if (!$order) {
            throw new ResourceException("订单信息错误");
        }

        if ($order['order_status'] == 'CANCEL' && ($params['cancel_from'] ?? '') != 'system') {
            throw new ResourceException("订单已取消");
        }

        if ($order['cancel_status'] != 'NO_APPLY_CANCEL' && $order['cancel_status'] != 'FAILS' && ($params['cancel_from'] ?? '') != 'system') {
            throw new ResourceException("不能重复取消订单");
        }

        if ($order['delivery_status'] != 'PENDING') {
            throw new ResourceException("已发货订单不能取消");
        }

        //获取交易单信息
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
        $cancelReason = '';
        if (isset($params['cancel_reason']) && trim($params['cancel_reason'])) {
            $cancelReason = $params['cancel_reason'];
        }
        if (isset($params['other_reason']) && trim($params['other_reason'])) {
            $cancelReason = $params['other_reason'];
        }

        $cancelData = [
            'order_id' => $order['order_id'],
            'company_id' => $order['company_id'],
            'user_id' => $order['user_id'],
            'distributor_id' => $order['distributor_id'],
            'order_type' => $order['order_type'],
            'total_fee' => $order['total_fee'] ?? 0,
            'point' => $order['point'] ?? 0,
            'pay_type' => $order['pay_type'] ?? 'wxpayh5',
            'cancel_from' => $params['cancel_from'],
            'cancel_reason' => trim($cancelReason),
            'payed_fee' => isset($trade['pay_fee']) ?: 0,
        ];

        return $cancelData;
    }


    public function autoConfirmCancelOrder($companyId, $orderId)
    {
        $autoAfterSalesSwitch = $this->getOrdersSetting($companyId, 'auto_aftersales');
        if ($autoAfterSalesSwitch !== true) {
            return false;
        }

        $refundFilter = [
            'company_id'    => $companyId,
            'order_id'      => $orderId,
            'refund_type'   => 1,
            'refund_status' => 'READY',
        ];
        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo($refundFilter);
        $params = [
            'order_id'      => $orderId,
            'company_id'    => $companyId,
            'remarks'       => '自动退款',
        ];
        try {
            return $this->passRefund($refundFilter, $refund, $params);
        }catch (Exception $exception) {
            app('log')->debug('自动确认退款失败' . $exception->getMessage() . " Line:" . $exception->getLine() . " File:" . $exception->getFile());
        }
        return true;
    }


    public function passRefund($refundFilter, $refund, $params)
    {
        if (empty($refund)) {
            throw new ResourceException('未发现退款单');
        }

        $aftersalesRefundService = new AftersalesRefundService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($refund['refund_status'] == 'SUCCESS') {
                throw new ResourceException('退款单状态 已退款，无法继续操作');
            }
            if ($refund['refund_status'] == 'REFUSE') {
                throw new ResourceException('退款单状态 已驳回，无法继续操作');
            }
            if ($refund['refund_status'] == 'AUDIT_SUCCESS') {
                throw new ResourceException('退款单状态 已审核通过，无法继续操作');
            }
            if ($refund['refund_status'] == 'CANCEL') {
                throw new ResourceException('退款单状态 已撤销，无法继续操作');
            }
            if ($refund['refund_status'] == 'PROCESSING') {
                throw new ResourceException('退款单状态 已发起退款等待到账，无法继续操作');
            }
            if ($refund['refund_status'] == 'CHANGE') {
                throw new ResourceException('退款单状态 退款异常，无法继续操作');
            }
            // 处理退款单状态
            $refundUpdate = [
                'refund_status' => 'AUDIT_SUCCESS', // 审核成功待退款
            ];
            $aftersalesRefundService->updateOneBy($refundFilter, $refundUpdate);

            // 处理取消订单表状态
            $cancelOrderFilter = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
            ];
            $cancelOrderUpdate = [
                'progress' => 2, // 处理中
                'refund_status' => 'AUDIT_SUCCESS',
            ];
            $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
            $result = $cancelOrderRepository->updateOneBy($cancelOrderFilter, $cancelOrderUpdate);

            // 处理订单状态
            // 订单状态直接取消成功，退款实际是异步执行
            $updateInfo = [
                'cancel_status' => 'SUCCESS',
                'order_status' => 'CANCEL',
            ];
            $filter = [
                'company_id' => $params['company_id'],
                'order_id' => $params['order_id']
            ];
            $this->update($filter, $updateInfo);
            //退还积分
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $params['operator_type'] ?? 'system',
                'operator_id' => $params['operator_id'] ?? 0,
                'remarks' => $params['remarks'] ?? '订单退款',
                'params' => $params,
            ];
            if ($orderProcessLog['operator_id'] == 0) {
                $orderProcessLog['detail'] = '订单号：' . $params['order_id'] . '，系统自动同意退款';
            } else {
                $orderProcessLog['detail'] = '订单号：' . $params['order_id'] . '，后台管理员同意退款';
            }
            event(new OrderProcessLogEvent($orderProcessLog));
            // 分销金额退款处理
            $brokerageService = new BrokerageService();
            $brokerageService->brokerageBycancelOrder($params['company_id'], $params['order_id']);

            $orderProfitService = new OrderProfitService();
            $orderProfitService->updateBy(['order_id' => $params['order_id'], 'company_id' => $params['company_id']], ['order_profit_status' => 0]);
            $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $params['order_id'], 'company_id' => $params['company_id']], ['order_profit_status' => 0]);
            $order = $this->normalOrdersRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
            // 积分抵扣，升值，额度返回
            if ($order['uppoint_use'] > 0) {
                $this->minusOrderUppoints($order['company_id'], $order['user_id'], $order['uppoint_use']);
            }
            // 处理达达状态
            if ($order['receipt_type'] == 'dada') {
                $dadaFilter = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id'],
                ];
                $updateDadaData = [
                    'dada_status' => '5',
                ];
                $this->normalOrdersRelDadaRepository->updateBy($dadaFilter, $updateDadaData);
            }

            $conn->commit();

            //联通SaasErp, 审核同意取消订单推erp
            event(new SaasErpUpdateEvent($order));

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // 已支付订单的取消订单并退款审核
    public function confirmCancelOrder($params)
    {
        if (!isset($params['refund_bn']) || !$params['refund_bn']) { // 没有传退款单
            $refundFilter = [
                'company_id' => $params['company_id'],
                'order_id' => $params['order_id'],
                'refund_type' => 1,
                'refund_status' => ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'CANCEL', 'REFUNDCLOSE', 'PROCESSING', 'CHANGE'],
            ];
        } else { // 传了退款单
            $refundFilter = [
                'company_id' => $params['company_id'],
                'refund_bn' => $params['refund_bn'],
                'order_id' => $params['order_id'],
            ];
        }
        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo($refundFilter);
        if (!$refund) {
            throw new ResourceException('没有查到退款单，无法同意取消订单');
        }
        // 同意退款
        if ($params['check_cancel'] == '1') {
            return $this->passRefund($refundFilter, $refund, $params);
        } else {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                if ($refund['refund_status'] != 'READY') {
                    throw new ResourceException('退款单状态不是待审核状态，无法拒绝');
                }
                // 处理退款单状态
                $refundUpdate = [
                    'refund_status' => 'REFUSE', // 审核拒绝
                ];
                $refund = $aftersalesRefundService->updateOneBy($refundFilter, $refundUpdate);

                $cancelOrderFilter = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                ];
                $cancelOrderUpdate = [
                    'shop_reject_reason' => $params['shop_reject_reason'],
                    'progress' => 4, // 已拒绝
                    'refund_status' => 'SHOP_CHECK_FAILS', // 审核拒绝
                ];
                $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
                $result = $cancelOrderRepository->updateOneBy($cancelOrderFilter, $cancelOrderUpdate);
                $updateInfo = [
                    'cancel_status' => 'FAILS',
                ];
                $filter = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id']
                ];
                $this->update($filter, $updateInfo);
                $orderProcessLog = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                    'operator_type' => $params['operator_type'] ?? 'system',
                    'operator_id' => $params['operator_id'] ?? 0,
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $params['order_id'] . '，后台管理员拒绝退款，拒绝原因：' . $params['shop_reject_reason'],
                    'params' => $params,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
                // 达达重发订单
                $this->dadaReOrder($params['company_id'], $params['order_id']);
                $conn->commit();

                event(new SaasErpRefundCancelEvent($refund));

                return $result;
            } catch (Exception $exception) {
                $conn->rollback();
                throw $exception;
            }
        }
    }

    /**
     * 骑士待接单状态下，商家取消订单，退款审核---拒绝后，重发订单
     * @param string $companyId 企业ID
     * @param string $orderId 订单号
     */
    public function dadaReOrder($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $dadaData = $this->normalOrdersRelDadaRepository->getInfo($filter);
        if (!$dadaData) {
            return true;
        }
        // 骑士待接单状态下，如果商家或消费者申请了取消，则重发订单
        // dada_cancel_from 1:达达回调配送员取消；2:达达回调商家主动取消；3:达达回调系统或客服取消；11:商城系统取消；12:商城商家主动取消；13:商城消费者主动取消；
        if ($dadaData['dada_status'] == '1' && in_array($dadaData['dada_cancel_from'], ['12', '13'])) {
            $dadaOrderService = new DadaOrderService();
            $dadaOrderService->reAddOrder($dadaData);
        }
        // 修改状态,只需要修改取消来源，dada_status不变，取消申请之前是什么，就还是什么
        $updateData = [
            'dada_cancel_from' => '0',
        ];
        $this->normalOrdersRelDadaRepository->updateBy($filter, $updateData);
        return true;
    }

    public function countOrderNum($filter)
    {
        $count = $this->normalOrdersRepository->count($filter);
        return intval($count);
    }

    public function setInvoiced($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
        ];
        $data = [
            'is_invoiced' => $params['status'] ? true : false
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo['invoice'] || !$orderInfo) {
            throw new ResourceException('此订单无发票信息');
        }
        $ordersResult = $this->normalOrdersRepository->updateOneBy($filter, $data);
        return $ordersResult;
    }

    public function updateInvoiceNumber($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id']
        ];
        $data = [
            'invoice_number' => $params['invoice_number']
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo['invoice'] || !$orderInfo) {
            throw new ResourceException('此订单无发票信息');
        }
        $ordersResult = $this->normalOrdersRepository->updateOneBy($filter, $data);
        return $ordersResult;
    }

    /**
     * 更新审核信息
     *
     * @param $params
     * @return mixed
     */
    public function updateAuditStatus($params)
    {
        $filter = [
            'order_id' => $params['order_id']
        ];
        $data = [
            'audit_status' => $params['audit_status'],
            'audit_msg' => $params['audit_msg']
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }
        $ordersResult = $this->normalOrdersRepository->updateOneBy($filter, $data);
        return $ordersResult;
    }

    public function bindUserOrder($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
            'bind_auth_code' => $params['bind_auth_code'],
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在或者验证码错误');
        }

        if ($orderInfo['user_id'] > 0) {
            throw new ResourceException('该订单已绑定用户');
        }

        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['user_id' => $params['user_id']]);
        if (!$memberInfo) {
            throw new ResourceException('绑定的用户不存在');
        }

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $aftersalesService = new AftersalesService();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            unset($filter['bind_auth_code']);
            $data = ['user_id' => $memberInfo['user_id'], 'mobile' => $memberInfo['mobile']];
            $this->normalOrdersRepository->update($filter, $data);
            $this->orderAssociationsRepository->update($filter, $data);
            $this->normalOrdersItemsRepository->updateBy($filter, $data);
            if ($tradeRepository->getInfo($filter)) {
                $tradeRepository->updateBy($filter, $data);
            }
            $aftersalesService->bindUserAftersales($params['company_id'], $params['order_id'], $params['user_id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 自提订单计算售后数量
     * @param $result
     * @return mixed
     */
    public function orderRecombine($result)
    {
        $orderInfo = $result['orderInfo'];
        $isZitiOrder = false;//不处理非自提订单
        if ($orderInfo['receipt_type'] == 'ziti') {
            //店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核
            if ($orderInfo['ziti_status'] == 'DONE' or $orderInfo['ziti_status'] == 'NOTZITI') {
                $isZitiOrder = true;//未核销的订单不允许申请售后
            }
        }

        $can_apply_aftersales = 0;
        $orderInfo['can_apply_aftersales'] = 0;
        foreach ($orderInfo['items'] as &$item) {
            $aftersalesService = new AftersalesService();
            $applied_num = $aftersalesService->getAppliedNum($item['company_id'], $item['order_id'], $item['id']); // 已申请数量
            //如果是自提订单发货数量等于子订单商品数量
            $item['delivery_item_num'] = $isZitiOrder ? $item['num'] : $item['delivery_item_num'];
            $item['left_aftersales_num'] = $item['delivery_item_num']  + $item['cancel_item_num'] - $applied_num; // 剩余申请数量
            $item['show_aftersales'] = $applied_num > $item['cancel_item_num'] ? 1 : 0;
            $can_apply_aftersales += $item['left_aftersales_num'];
            // 用于判断整个订单是否显示售后申请按钮，只有其中一个商品可以申请售后就显示
            if ($can_apply_aftersales) {
                if ($item['auto_close_aftersales_time'] > 0 && $item['auto_close_aftersales_time'] < time()) {
                    continue;
                }
                $orderInfo['can_apply_aftersales'] = 1;
            }
        }

        $result['orderInfo'] = $orderInfo;
        return $result;
    }


    /**
     * 增加积分升值，商家补贴积分累计增加
     * @param $orderData 订单数据
     */
    public function addOrderUppoints($orderData)
    {
        $orderData['uppoint_use'] = $orderData['uppoint_use'] ?? 0;
        if ($orderData['uppoint_use'] <= 0) {
            return true;
        }
        $pointupvaluationService = new PointupvaluationService();
        $result = $pointupvaluationService->setDeilyPoints($orderData['company_id'], $orderData['user_id'], $orderData['uppoint_use']);
        return $result;
    }

    /**
     * 减少积分升值，商家补贴积分累计减少
     * @param  [type] $company_id 企业Id
     * @param  [type] $user_id    会员Id
     * @param  [type] $uppoints   升值数
     * @return [type]             [description]
     */
    public function minusOrderUppoints($company_id, $user_id, $uppoints)
    {
        $pointupvaluationService = new PointupvaluationService();
        $result = $pointupvaluationService->setDeilyPoints($company_id, $user_id, -$uppoints);
        return $result;
    }

    /**
     * 已取消订单，支付成功回调后，直接生成已审核通过的退款单
     * @param  [type] $params
     * @return [type]
     */
    public function systemCreateRefund($params)
    {
        $orderInfo = $this->normalOrdersRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id'], 'user_id' => $params['user_id']]);
        // 生成退款单，不实际退款
        $aftersalesRefundService = new AftersalesRefundService();
        $params['refund_type'] = 1;// 取消订单退款
        $tradeService = new TradeService();
        $trade_filter = [
            'company_id' => $params['company_id'],
            'order_id' => $orderInfo['order_id'],
            'trade_state' => 'SUCCESS',
        ];
        // 积分商城可能会有两条支付记录 积分支付+现金支付
        $trade_count = $tradeService->count($trade_filter);
        if ($trade_count > 1) {
            $trade_filter['pay_type|neq'] = 'point';
            $trade_lists = $tradeService->getTradeList($trade_filter);
            $trade_filter['trade_id'] = $trade_lists['list'][0]['tradeId'];
            unset($trade_filter['pay_type|neq']);
        }
        $trade = $tradeService->getInfo($trade_filter);
        $refundData = [
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
            'order_id' => $orderInfo['order_id'],
            'trade_id' => $trade['trade_id'],
            'shop_id' => $orderInfo['shop_id'] ?? 0,
            'distributor_id' => $orderInfo['distributor_id'] ?? 0,
            'refund_type' => 1, // 1:取消订单退款,
            'refund_channel' => 'original', // 默认取消订单原路返回
            'refund_status' => 'AUDIT_SUCCESS', // 售前取消订单退款默认审核成功
            'refund_fee' => $trade['total_fee'],
            'refund_point' => $orderInfo['point'],
            'return_freight' => 1, // 1:退运费,
            'pay_type' => $orderInfo['pay_type'], // 退款支付方式
            'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
            'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
            'cur_fee_rate' => $trade['cur_fee_rate'],
            'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
            'cur_pay_fee' => ($trade['pay_type'] == 'point') ? $orderInfo['point'] : $trade['cur_pay_fee'], // trade表没有单独积分字段，所以这样写
        ];
        $aftersalesRefundService->createRefund($refundData);

        $orderProcessLog = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'operator_type' => $params['operator_type'] ?? 'system',
            'operator_id' => $params['operator_id'] ?? 0,
            'remarks' => '订单退款',
            'detail' => '订单号：' . $params['order_id'] . '，系统自动同意退款',
            'params' => $params,
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        // 分销金额退款处理
        $brokerageService = new BrokerageService();
        $brokerageService->brokerageBycancelOrder($params['company_id'], $params['order_id']);

        $orderProfitService = new OrderProfitService();
        $orderProfitService->updateOneBy(['order_id' => $params['order_id'], 'company_id' => $params['company_id']], ['order_profit_status' => 0]);
        $orderProfitService->orderItemsProfitRepository->updateBy(['order_id' => $params['order_id'], 'company_id' => $params['company_id']], ['order_profit_status' => 0]);
        return true;
    }

    /**
     * 获取达达的配送时长
     * @param array $dadaData 达达同城配数据
     */
    public function getDadaDeliveryLength($dadaData)
    {
        $delivery_time = bcsub($dadaData['delivered_time'], $dadaData['pickup_time']);
        return intval(bcdiv($delivery_time, 60)) . '分钟';
    }

    /**
     * 获取会员消费订单列表
     */
    public function getOrderItemLists($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $offset = ($page - 1) * $limit;
        $result['pager']['count'] = $this->normalOrdersRepository->count($filter);
        $result['pager']['page_no'] = $page;
        $result['pager']['page_size'] = $limit;
        $result['list'] = [];
        if (empty($result['pager']['count'])) {
            return $result;
        }
        $result['list'] = $this->normalOrdersRepository->getList($filter, $offset, $limit, $orderBy);
        if (empty($result['list'])) {
            return $result;
        }

        // 达达同城配数据
        $dada_filter = [
            'order_id' => array_column($result['list'], 'order_id'),
        ];
        $dadaOrderList = $this->normalOrdersRelDadaRepository->getLists($dada_filter);
        $dadaOrderList = array_column($dadaOrderList, null, 'order_id');

        foreach ($result['list'] as $k => $v) {
            if ($v['order_status'] == 'NOTPAY' && $v['auto_cancel_time'] - time() <= 0) {
                $v['order_status'] = 'CANCEL';
                $result['list'][$k]['order_status'] = 'CANCEL';
            }
            // 达达同城配数据
            $dadaData = $dadaOrderList[$v['order_id']] ?? [];
            $result['list'][$k]['order_status_msg'] = $this->getOrderStatusMsg($v, $dadaData);
            $result['list'][$k]['order_status_des'] = $v['order_status_des'] ?? null;
            $result['list'][$k]['create_date'] = date('Y-m-d H:i:s', $v['create_time']);
            $result['list'][$k]['items'] = $this->normalOrdersItemsRepository->getOrderFirstItem($v['order_id']);
        }
        return $result;
    }
    /**
     * 获取用户的总消费金额
     */
    public function getOrderTotalAmount($filter)
    {
        return $this->normalOrdersRepository->getTotalAmountByUserId($filter);
    }

    public function updatePayType($orderId, $payType) {
        $this->normalOrdersRepository->update(['order_id' => $orderId], ['pay_type' => $payType]);
        return true;
    }

    /**
     * 获取已完成订单的商品销售数量
     * @param int $companyId 企业id
     * @param array $distributorIds 店铺id
     * @return array
     */
    public function getDoneOrderTotalSalesCountByDistributorIds(int $companyId, array $distributorIds): array
    {
        return $this->normalOrdersRepository->getTotalSalesCountByDistributorIds([
            "company_id" => $companyId,
            "distributor_id" => $distributorIds,
            "order_status" => "DONE",
            "order_auto_close_aftersales_time|lt" => time()
        ]);
    }

    /**
     * 创建订单后的，后置操作
     * @param array $orderData
     * @return void
     */
    public function afterCreateOrder(array $orderData): void
    {
    }

    public function divisionOrderByDistribution($params)
    {
        $order = $this->normalOrdersRepository->getInfo(['order_id' => $params['order_id']]);
        if (!$order) {
            throw new ResourceException('订单不存在');
        }

        return $this->doDivision($order);
    }

    //退款需退哪个子订单，
    //三种场景
    //1售前取消
    //2售前部分取消
    //3售后部分取消与售后取消
    public function divisionRefundByDistribution($params)
    {
        $order = $this->normalOrdersRepository->getInfo(['order_id' => $params['order_id']]);
        if (!$order) {
            throw new ResourceException('订单不存在');
        }

        //有售后订号
        if ($params['aftersales_bn'] ?? 0) {
            //取售后订单详情
            $refund = $this->aftersalesRefundRepository->getInfo(['company_id' => $params['company_id'], 'aftersales_bn' => $params['aftersales_bn'] ]);

            return $this->doDivision($order, $refund);
        }

        //售前取消订单
        return $this->doDivision($order);
    }

    private function doDivision($order, $refund = [])
    {
        // 总部订单不分账
        if ($order['distributor_id'] == 0) {
            return [];
        }

        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoById($order['distributor_id']);
        if (!$distributorInfo) {
            throw new ResourceException('无效的店铺');
        }
        if (!$distributorInfo['split_ledger_info']) {
            throw new ResourceException('店铺未设置分账信息');
        }

        $splitLedgerInfo = json_decode($distributorInfo['split_ledger_info'], true);

        $umservice = new ChinaumsPayService();
        $paymentSetting = $umservice->getPaymentSetting($order['company_id']);
        if (!$paymentSetting) {
            throw new ResourceException('未设置支付信息');
        }

        $totalFee = $order['total_fee'];
        if ($refund) {
            $totalFee = $refund['refund_fee'];
        }
        $feeAmt = bcmul($totalFee, $paymentSetting['rate'] / 100);
        $divFee = $totalFee - $feeAmt;

        if ($distributorInfo['dealer_id'] == 0) {//未关联经销商两方分账
            $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
            $headquartersFee = bcmul($divFee, $headquartersProportion);
            $distributorFee = bcsub($totalFee, $headquartersFee);

            if ($refund) {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'refundOrderId' => config('ums.pre').$refund['refund_bn'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            } else {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            }

            $paymentSetting = $umservice->getPaymentSetting($order['company_id'], 'distributor_'.$order['distributor_id']);
            if (!$paymentSetting) {
                throw new ResourceException('未设置店铺支付信息');
            }
            if ($refund) {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//店铺子商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'refundOrderId' => config('ums.pre').$refund['refund_bn'].$paymentSetting['mid'],
                    'totalAmount' => $distributorFee,
                ];
            } else {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//店铺子商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'totalAmount' => $distributorFee,
                ];
            }
        } else {//关联经销商三方分账
            $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
            $dealerProportion = $splitLedgerInfo['dealer_proportion'] / 100;
            $headquartersFee = bcmul($divFee, $headquartersProportion);
            $dealerFee = bcmul($divFee, $dealerProportion);
            $distributorFee = bcsub($totalFee, $headquartersFee + $dealerFee);

            if ($refund) {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'refundOrderId' => config('ums.pre').$refund['refund_bn'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            } else {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            }

            $paymentSetting = $umservice->getPaymentSetting($order['company_id'], 'distributor_'.$order['distributor_id']);
            if (!$paymentSetting) {
                throw new ResourceException('未设置店铺支付信息');
            }
            if ($refund) {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'refundOrderId' => config('ums.pre').$refund['refund_bn'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            } else {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//店铺子商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'totalAmount' => $distributorFee,
                ];
            }

            $paymentSetting = $umservice->getPaymentSetting($order['company_id'], 'dealer_'.$distributorInfo['dealer_id']);
            if (!$paymentSetting) {
                throw new ResourceException('未设置经销商支付信息');
            }
            if ($refund) {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//主商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'refundOrderId' => config('ums.pre').$refund['refund_bn'].$paymentSetting['mid'],
                    'totalAmount' => $headquartersFee,
                ];
            } else {
                $result[] = [
                    'mid' => $paymentSetting['mid'],//经销商子商户
                    'merOrderId' => config('ums.pre').$order['order_id'].$paymentSetting['mid'],
                    'totalAmount' => $dealerFee,
                ];
            }
        }

        return $result;
    }

    public function markDown($orderInfo, $params) {
        $pointFreightFee = 0;
        if ($orderInfo['point_fee'] > 0 && $orderInfo['freight_fee'] > 0) {
            $pointFreightFee = bcsub($orderInfo['point_fee'], array_sum(array_column($orderInfo['items'], 'point_fee')));
        }
        $orderInfo['point_freight_fee'] = $pointFreightFee;

        // 需要现金支付的运费
        if (isset($params['freight_fee'])) {
            if ($params['freight_fee'] < 0) {
                throw new ResourceException('运费不能小于0');
            }
            $freightFee = $params['freight_fee'];
        } else {
            $freightFee = bcsub($orderInfo['freight_fee'], $pointFreightFee);
        }
        $orderInfo['total_fee'] += bcsub($freightFee + $pointFreightFee, $orderInfo['freight_fee']);
        $orderInfo['freight_fee'] = bcadd($freightFee, $pointFreightFee);

        if (isset($params['down_type']) && $params['down_type'] == 'total') {
            $orderInfo = $this->markDownTotal($orderInfo, $params['total_fee'], $freightFee);
        }

        if (isset($params['down_type']) && $params['down_type'] == 'items') {
            $orderInfo = $this->markDownItems($orderInfo, $params['items'], $freightFee);
        }

        $orderInfo['item_total_fee'] = $orderInfo['total_fee'] - $freightFee;
        return $orderInfo;
    }

    // 改总价
    private function markDownTotal($orderInfo, $totalFee, $freightFee) {
        $oldTotalFee = array_sum(array_column($orderInfo['items'], 'total_fee'));
        if (intval($oldTotalFee) == $totalFee) {
            return $orderInfo;
        }
        $leftTotalFee = $totalFee;
        foreach ($orderInfo['items'] as $key => $item) {
            if ($item == end($orderInfo['items'])) {
                $newItemTotalFee = $leftTotalFee;
            } else {
                $newItemTotalFee = bcmul($totalFee, $item['total_fee'] / $oldTotalFee);
            }
            if ($newItemTotalFee != $item['total_fee']) {
                $itemMarkDownFee = bcsub($item['total_fee'], $newItemTotalFee);
                if ($itemMarkDownFee < 0) {
                    throw new ResourceException('不能高于原订单金额');
                }
                $orderInfo['items'][$key]['discount_fee'] += $itemMarkDownFee;
                $orderInfo['items'][$key]['discount_info']['mark_down'] = [
                    'type' => 'mark_down',
                    'info' => '订单改价',
                    'rule' => '订单改价优惠',
                    'discount_fee' => $itemMarkDownFee,
                ];
                $orderInfo['items'][$key]['total_fee'] = $newItemTotalFee;
            }
            $leftTotalFee -= $newItemTotalFee;
        }

        $markDownFee = bcsub($oldTotalFee, $totalFee);
        if ($markDownFee < 0) {
            throw new ResourceException('不能高于原订单金额');
        }
        $orderInfo['discount_fee'] += $markDownFee;
        $orderInfo['discount_info']['mark_down'] = [
            'type' => 'mark_down',
            'info' => '订单改价',
            'rule' => '订单改价优惠',
            'discount_fee' => $markDownFee,
        ];

        $orderInfo['total_fee'] = $totalFee + $freightFee;
        return $orderInfo;
    }

    // 按件改
    private function markDownItems($orderInfo, $items, $freightFee) {
        $items = array_column($items, null, 'item_id');
        $oldTotalFee = 0;
        $totalFee = 0;
        foreach ($orderInfo['items'] as $key => $item) {
            if (isset($items[$item['item_id']])) {
                if (isset($items[$item['item_id']]['discount'])) {
                    $newItemTotalFee = bcmul($item['total_fee'], $items[$item['item_id']]['discount'] / 100);
                } else {
                    $newItemTotalFee = $items[$item['item_id']]['total_fee'];
                }

                if ($newItemTotalFee != $item['total_fee']) {
                    $itemMarkDownFee = bcsub($item['total_fee'], $newItemTotalFee);
                    if ($itemMarkDownFee < 0) {
                        throw new ResourceException('不能高于原订单金额');
                    }
                    $orderInfo['items'][$key]['discount_fee'] += $itemMarkDownFee;
                    $orderInfo['items'][$key]['discount_info']['mark_down'] = [
                        'type' => 'mark_down',
                        'info' => '订单改价',
                        'rule' => '订单改价优惠',
                        'discount_fee' => $itemMarkDownFee,
                    ];
                    $orderInfo['items'][$key]['total_fee'] = $newItemTotalFee;
                }
            }
            $oldTotalFee += $item['total_fee'];
            $totalFee += $orderInfo['items'][$key]['total_fee'];
        }

        if ($oldTotalFee != $totalFee) {
            $markDownFee = bcsub($oldTotalFee, $totalFee);
            if ($markDownFee < 0) {
                throw new ResourceException('不能高于原订单金额');
            }
            $orderInfo['discount_fee'] += $markDownFee;
            $orderInfo['discount_info']['mark_down'] = [
                'type' => 'mark_down',
                'info' => '订单改价',
                'rule' => '订单改价优惠',
                'discount_fee' => $markDownFee,
            ];
        }

        $orderInfo['total_fee'] = $totalFee + $freightFee;
        return $orderInfo;
    }

    public function saveMarkDown($orderInfo, $params) {
        $oldOrderInfo = $orderInfo;
        $orderInfo = $this->markDown($orderInfo, $params);

        if ($orderInfo['total_fee'] <= 0) {
            throw new ResourceException('订单实付金额必须大于0');
        }

        if ($orderInfo['user_id'] > 0) {
            $pointMemberService = new PointMemberService();
            $orderInfo = $pointMemberService->memberGetPoints($orderInfo['company_id'], $orderInfo);
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (isset($orderInfo['discount_info']['mark_down']) || $orderInfo['total_fee'] != $oldOrderInfo['total_fee']) {
                $updateInfo = [
                    'discount_info' => $orderInfo['discount_info'],
                    'total_fee' => $orderInfo['total_fee'],
                    'discount_fee' => $orderInfo['discount_fee'],
                    'freight_fee' => $orderInfo['freight_fee'],
                    'get_points' => $orderInfo['get_points'],
                    'extra_points' => $orderInfo['extra_points'],
                ];
                $filter = [
                    'company_id' => $orderInfo['company_id'],
                    'order_id' => $orderInfo['order_id'],
                ];
                $this->orderAssociationsRepository->update($filter, $updateInfo);
                $this->normalOrdersRepository->update($filter, $updateInfo);

                foreach ($orderInfo['items'] as $item) {
                    if (isset($item['discount_info']['mark_down'])) {
                        $updateInfo = [
                            'discount_info' => $item['discount_info'],
                            'total_fee' => $item['total_fee'],
                            'discount_fee' => $item['discount_fee'],
                            'get_points' => $item['get_points'],
                        ];
                        $filter['id'] = $item['id'];
                        $this->normalOrdersItemsRepository->update($filter, $updateInfo);
                    }
                }

                $orderProcessLog = [
                    'order_id' => $orderInfo['order_id'],
                    'company_id' => $orderInfo['company_id'],
                    'operator_type' => $params['operator_type'] ?? 'system',
                    'operator_id' => $params['operator_id'] ?? 0,
                    'remarks' => '订单改价',
                    'detail' => '订单号：' . $params['order_id'] . '，手动改价',
                    'params' => $params,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
            }
            $conn->commit();
            return $orderInfo;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    public function saveOrderRelZiti($params) {
        $filter = [
            'company_id' => $params['company_id'],
            'id' => $params['pickup_location'],
        ];
        $pickupLocationService = new PickupLocationService();
        $pickupLocation = $pickupLocationService->getInfo($filter);

        $data = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
            'name' => $pickupLocation['name'],
            'lng' => $pickupLocation['lng'],
            'lat' => $pickupLocation['lat'],
            'province' => $pickupLocation['province'],
            'city' => $pickupLocation['city'],
            'area' => $pickupLocation['area'],
            'address' => $pickupLocation['address'],
            'contract_phone' => $pickupLocation['contract_phone'],
            'pickup_date' => $params['pickup_date'],
            'pickup_time' => $params['pickup_time'],
        ];
        $normalOrdersRelZitiService = new NormalOrdersRelZitiService();
        $normalOrdersRelZitiService->create($data);
    }


    /**
     * 延长使用有效期
     */
    public function changeMultiExpireTime($company_id, $order_id, $is_check = true){
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id,
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if(empty($orderInfo) || $orderInfo['order_class'] != 'multi_buy'){
            throw new ResourceException('非團購訂單，操作失敗');
        }
        $macketingActivityService = new MarketingActivityService();
        $macketing_list = $macketingActivityService->getValidActivitys($company_id,$orderInfo['act_id'],null,null,['multi_buy']);
        if(empty($macketing_list)){
            throw new ResourceException('團購訂單活動不存在');
        }
        $macketingInfo = $macketing_list[0]??[];
        if ($macketingInfo['delayed_number'] != 0){
            throw new ResourceException('只能延期一次');
        }
        $prolong_month =$macketingInfo['prolong_month']??0;    // 延長多久
        $order_multi_expire_time = $macketingInfo['commodity_effective_end_time']; // 团购订单过期时间
        $new_multi_expire_time = strtotime('+'.$prolong_month.' month' ,$order_multi_expire_time);
        $updateInfo = [
            'commodity_effective_end_time' => $new_multi_expire_time,
            'delayed_number' => 1
        ];
//        if($is_check){
//            $updateInfo['multi_expire_date'] = date('Y-m-d H:i:s',$new_multi_expire_time);
//            return $updateInfo;
//        }
        $res = $macketingActivityService->updateExtension($orderInfo['act_id'], $updateInfo);
        if($res){
            return ['status'=>true];
        }
        return ['status'=>false];
    }

    /**
     * 核銷團購數量
     */
    public function verifyMultiOrder($company_id, $order_id, $num=1, $code='', $is_check = true, $isWriteOff = 0){
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id,
        ];
        $orderInfo = $this->normalOrdersRepository->getInfo($filter);
        if(empty($orderInfo) || $orderInfo['order_class'] != 'multi_buy'){
            throw new ResourceException('非團購訂單，操作失敗');
        }
        $itemList = $this->normalOrdersItemsRepository->getList(['order_id' => $orderInfo['order_id'], 'company_id' => $orderInfo['company_id']]);
        if(!isset($itemList['list'][0]) || empty($itemList['list'][0])){
            throw new ResourceException('订单信息错误');
        }
        $orderItemInfo = $itemList['list'][0];
        if($is_check && (empty($code) || $orderInfo['multi_check_code'] != $code)){
            return ['status'=>false,'msg'=>'核銷口令錯誤'];
        }
        if (!in_array($orderInfo['order_status'],['DONE'])){
//            DONE—订单完成;PAYED-已支付
            return ['status'=>false,'msg'=>'当前状态不可核销'];
        }
        //更换验证的日期
        $macketingActivityService = new MarketingActivityService();
        $macketing_list = $macketingActivityService->getValidActivitys($company_id,$orderInfo['act_id'],null,null,['multi_buy']);
        if(empty($macketing_list)){
            throw new ResourceException('團購訂單活動不存在');
        }
        $macketingInfo = $macketing_list[0]??[];
        if ($macketingInfo['delayed_number'] != 0){
            throw new ResourceException('只能延期一次');
        }
        if($macketingInfo['commodity_effective_end_time']<time()){
            throw new ResourceException('訂單已過期，核銷失敗');
        }
        //判断是否核销 如果为false的话不进行核销只是返回下核销数量
        if ($isWriteOff == 0) {
            return [
                'status' => true,
                'multiCheckNum' => $orderItemInfo['num'] - $orderInfo['multi_check_num']//剩余核销次数
            ];
        }
        if($orderInfo['multi_check_num']>=$orderItemInfo['num']){
            throw new ResourceException('已核销完成');
        }
        if($orderInfo['multi_check_num']+$num>$orderItemInfo['num']){
            throw new ResourceException('剩余核销数量不足');
        }
        $multi_check_num = $orderInfo['multi_check_num']<=0?$num:($orderInfo['multi_check_num']+$num);
        $updateInfo = [
            'multi_check_num' => $multi_check_num
        ];
        $res = $this->normalOrdersRepository->update($filter, $updateInfo);
        if($res){
            return ['status'=>true];
        }
        return ['status'=>false,'msg'=>'系統錯誤'];
    }
}
