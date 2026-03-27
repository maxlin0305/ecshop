<?php

namespace PromotionsBundle\Traits;

use Dingo\Api\Exception\ResourceException;

use MembersBundle\Services\MembersWhitelistService;
use CompanysBundle\Services\SettingService;
use GoodsBundle\Services\ItemsService;

use PromotionsBundle\Services\EmployeePurchaseActivityService;
use PromotionsBundle\Services\EmployeePurchaseItemsService;
use OrdersBundle\Services\CartService;
use MembersBundle\Services\MemberService;
use PromotionsBundle\Services\EmployeePurchaseReluserService;

/**
 * 购买，检查员工内购的限购、限额
 */
trait CheckEmployeePurchaseLimit
{

    public function checkOrderBuy(&$orderData, $isCheck)
    {
        // 未开启白名单不参与活动
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($orderData['company_id']);
        if ($setting['whitelist_status'] != true) {
            return false;
        }

        try {
            // 没有进行中活动，将不能购买
            $employeePurchaseActivityService = new EmployeePurchaseActivityService();
            $activityInfo = $employeePurchaseActivityService->getOngoingInfo($orderData['company_id'], $orderData['user_id'], $orderData['mobile']);
            if ($activityInfo['activity_data'] == false) {
                throw new ResourceException('活动暂时未开启，敬请期待~');
            }

            if ($activityInfo['minimum_amount'] > 0 && $orderData['total_fee'] - $orderData['freight_fee'] < $activityInfo['minimum_amount']) {
                throw new ResourceException('每笔订单至少买满'.bcdiv($activityInfo['minimum_amount'], 100).'元');
            }

            // 检查限购、限额
            $activityInfo['purchanse_items'] = $this->checkOrderLimit($activityInfo, $orderData);
            $orderData['employee_purchase'] = $activityInfo;
            return true;
        } catch (\Exception $e) {
            if ($isCheck) {
                throw new ResourceException($e->getMessage());
            } else {
                $orderData['extraTips'] = $e->getMessage();
                return false;
            }
        }
    }

    /**
     * 校验会员是否在白名单中
     * @param $companyId:企业ID
     * @param $mobile:会员手机号
     * @return mixed
     */
    public function checkWhitelistValid($companyId, $mobile)
    {
        // 查询白名单设置
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($companyId);
        $whitelistService = new MembersWhitelistService();
        $result = $whitelistService->checkWhitelistValid($companyId, $mobile, $tips);
        if (!$result) {
            throw new ResourceException($tips);
        }
        return $result;
    }

    public function checkIsEmployee($companyId, $mobile) {
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($companyId);
        if (!$setting['whitelist_status']) {
            return false;
        }
        
        $filter = [
            'company_id' => $companyId,
            'mobile' => $mobile,
        ];
        $whitelistService = new MembersWhitelistService();
        $count = $whitelistService->count($filter);
        return $count > 0;
    }

    public function checkIsDependent($companyId, $userId)
    {
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($companyId);
        if (!$setting['whitelist_status']) {
            return false;
        }

        // 检查是否有进行中的活动
        $filter = [
            'company_id' => $companyId,
            'begin_time|lte' => time(),
            'end_time|gt' => time(),
        ];
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $list = $employeePurchaseActivityService->getLists($filter);
        if (!$list) {
            return false;
        }
        $activityInfo = $list[0];

        $filter = [
            'company_id' => $companyId,
            'purchase_id' => $activityInfo['purchase_id'],
            'dependents_user_id' => $userId,
        ];
        $employeePurchaseReluserService = new EmployeePurchaseReluserService();
        $dependentInfo = $employeePurchaseReluserService->getInfo($filter);
        if (!$dependentInfo) {
            return false;
        }

        // 检查家属的员工，是否在白名单中
        $memberService = new MemberService();
        $employeeMobile = $memberService->getMobileByUserId($dependentInfo['employee_user_id'], $companyId);
        $filter = [
            'company_id' => $companyId,
            'mobile' => $employeeMobile,
        ];
        $whitelistService = new MembersWhitelistService();
        $count = $whitelistService->count($filter);
        return $count > 0;
    }

    public function checkOrderLimit($activityInfo, $orderData)
    {
        $itemIds = array_column($orderData['items'], 'item_id');
        $purchaseItemList = $this->getActivityItemLimit($orderData['company_id'], $activityInfo['purchase_id']);
        if (!$purchaseItemList) {
            throw new ResourceException('活动暂时未开启，敬请期待~');
        }

        $item_type = $purchaseItemList[0]['item_type'];
        $_purchaseItemList = array_column($purchaseItemList, null, 'item_id');
        $usedLimitData = [];
        // 全部商品,所有商品的购买总数，不能超过设置的限购数
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $purchanseInfo = $employeePurchaseActivityService->__checkOngoingInfo($orderData['company_id'], $orderData['user_id'], $orderData['mobile']);
        $purchanseUserData = $employeePurchaseActivityService->getUserData($purchanseInfo, $orderData['user_id']);
        if ($item_type == 'all') {
            $item_id = 0;
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($orderData['company_id'], $activityInfo['purchase_id'], $orderData['user_id'], $item_id);
            $total_num = array_sum(array_column($orderData['items'], 'num'));
            $this->compareLimitNum($total_num, $usedLimitData['user_buy_num'], $_purchaseItemList[$item_id]['limit_num']);
            $this->compareLimitFee($orderData['total_fee'], $purchanseUserData['total_used_limitfee'], $activityInfo['total_limitfee']);

            return ['item_type' => $item_type, 'items' => $_purchaseItemList];
        }
        if (in_array($item_type, ['brand', 'category'])) {
            // 获取商品详细信息
            $itemsService = new ItemsService();
            $itemsFilter = [
                'company_id' => $orderData['company_id'],
                'item_type' => 'normal',
                'item_id' => $itemIds,
            ];
            $itemsList = $itemsService->getSkuItemsList($itemsFilter, 1, -1 , []);
            if (!$itemsList['list']) {
                throw new ResourceException('获取商品详细信息失败，请稍后重试~');
            }
            $_itemsList = array_column($itemsList['list'], null, 'item_id');
        }
        switch ($item_type) {
            case 'item':// 指定商品
                $this->__checkItem($activityInfo, $_purchaseItemList, $orderData);
                app('log')->info('__checkItem succ');
                break;
            case 'brand':// 指定商品品牌
                $this->__checkItemBrand($activityInfo, $_purchaseItemList, $orderData, $_itemsList);
                app('log')->info('__checkItemBrand succ');
                break;
            case 'category':// 指定商品主类目
                $this->__checkItemCategory($activityInfo, $_purchaseItemList, $orderData, $_itemsList);
                app('log')->info('__checkItemCategory succ');
                break;
            case 'tag':// 指定商品标签
                $this->__checkItemTag($activityInfo, $_purchaseItemList, $orderData);
                app('log')->info('__checkItemTag succ');
                break;
        }
        // 检查会员的限额（员工、家属）
        app('log')->info('checkOrderTotalFee total_fee:'.$orderData['total_fee'].',total_used_limitfee:'.$purchanseUserData['total_used_limitfee'].',activity_total_limitfee:'.$activityInfo['total_limitfee']);
        $this->compareLimitFee($orderData['total_fee'], $purchanseUserData['total_used_limitfee'], $activityInfo['total_limitfee']);  
        app('log')->info('checkOrderTotalFee succ');
        return ['item_type' => $item_type, 'items' => $_purchaseItemList];
    }

    /**
     * 检查活动中，活动商品类型为 “指定商品适用”，限购数、限额
     * @param  array $activityInfo     进行中的活动详情
     * @param  array $purchaseItemList 设置的活动商品列表
     * @param  array $orderData        订单数据
     */
    public function __checkItem($activityInfo, $purchaseItemList, $orderData)
    {
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        foreach ($orderData['items'] as $items) {
            $item_id = $items['item_id'];
            if (isset($purchaseItemList[$item_id])) {
                $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($orderData['company_id'], $activityInfo['purchase_id'], $orderData['user_id'], $item_id);
                // 检查商品的限购、限额
                $this->compareLimitNum($items['num'], $usedLimitData['user_buy_num'], $purchaseItemList[$item_id]['limit_num']);
                $this->compareLimitFee($items['total_fee'], $usedLimitData['user_buy_fee'], $purchaseItemList[$item_id]['limit_fee']);
            }
        }
        return true;
    }

    /**
     * 检查活动中，活动商品类型为 “指定品牌适用”，限购数、限额
     * @param  array $activityInfo     进行中的活动详情
     * @param  array $purchaseItemList 设置的活动商品列表
     * @param  array $orderData        订单数据
     * @param  array $itemsList        订单中的商品列表
     */
    public function __checkItemBrand($activityInfo, $purchaseItemList, $orderData, $itemsList)
    {
        $brands = [];
        foreach ($orderData['items'] as $items) {
            $brand_id = $itemsList[$items['item_id']]['brand_id'];
            $brands[$brand_id]['num'] ??= 0;
            $brands[$brand_id]['fee'] ??= 0;
            $brands[$brand_id]['num'] += $items['num'];
            $brands[$brand_id]['fee'] += $items['total_fee'];
        }
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();

        foreach ($brands as $brand_id => $items) {
            if (isset($purchaseItemList[$brand_id])) {
                $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($orderData['company_id'], $activityInfo['purchase_id'], $orderData['user_id'], $brand_id);
                // 检查商品的限购、限额
                $this->compareLimitNum($items['num'], $usedLimitData['user_buy_num'], $purchaseItemList[$brand_id]['limit_num']);
                $this->compareLimitFee($items['fee'], $usedLimitData['user_buy_fee'], $purchaseItemList[$brand_id]['limit_fee']);
            }
                   
        }
        return true;
    }

    /**
     * 检查活动中，活动商品类型为 “指定分类适用”，限购数、限额
     * @param  array $activityInfo     进行中的活动详情
     * @param  array $purchaseItemList 设置的活动商品列表
     * @param  array $orderData        订单数据
     * @param  array $itemsList        订单中的商品列表
     */
    public function __checkItemCategory($activityInfo, $purchaseItemList, $orderData, $itemsList)
    {
        $mainCats = [];
        foreach ($orderData['items'] as $item) {
            $item_main_cat_id = $itemsList[$item['item_id']]['item_main_cat_id'];
            $mainCats[$item_main_cat_id]['num'] ??= 0;
            $mainCats[$item_main_cat_id]['fee'] ??= 0;
            $mainCats[$item_main_cat_id]['num'] += $item['num'];
            $mainCats[$item_main_cat_id]['fee'] += $item['total_fee'];
        }
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();

        foreach ($mainCats as $cat_id => $item) {
            if (isset($purchaseItemList[$cat_id])) {
                $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($orderData['company_id'], $activityInfo['purchase_id'], $orderData['user_id'], $cat_id);
                // 检查商品的限购、限额
                $this->compareLimitNum($item['num'], $usedLimitData['user_buy_num'], $purchaseItemList[$cat_id]['limit_num']);
                $this->compareLimitFee($item['fee'], $usedLimitData['user_buy_fee'], $purchaseItemList[$cat_id]['limit_fee']);
            }
                   
        }
        return true;
    }

    /**
     * 检查活动中，活动商品类型为 “指定商品标签适用”，限购数、限额
     * @param  array $activityInfo     进行中的活动详情
     * @param  array $purchaseItemList 设置的活动商品列表
     * @param  array $orderData        订单数据
     */
    public function __checkItemTag($activityInfo, $purchaseItemList, $orderData)
    {
        // 获取订单中商品的标签列表
        $itemsService = new ItemsService();
        $itemIds = array_column($orderData['items'], 'item_id');
        $itemsFilter = [
            'company_id' => $orderData['company_id'],
            'item_id' => $itemIds,
        ];
        $tagLists = $itemsService->getItemTagList($itemsFilter);
        if (empty($tagLists['select_tags_list'])) {
            return true;
        }
        $tagIds = [];
        foreach ($tagLists['select_tags_list'] as $tag) {
            if (isset($purchaseItemList[$tag['tag_id']])) {
                $tagIds[$tag['tag_id']][] = $tag['item_id'];
            }
            
        }
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $orderItems = array_column($orderData['items'], null, 'item_id');
        foreach ($tagIds as $tag_id => $item_ids) {
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($orderData['company_id'], $activityInfo['purchase_id'], $orderData['user_id'], $tag_id);
            $tag_item_total_num = 0;
            $tag_item_total_fee = 0;
            foreach ($item_ids as $item_id) {
                $tag_item_total_num += $orderItems[$item_id]['num'];
                $tag_item_total_fee += $orderItems[$item_id]['total_fee'];
            }
            app('log')->info('tag_id:'.$tag_id.',$tag_item_total_fee:'.$tag_item_total_num.',$tag_item_total_fee:'.$tag_item_total_fee);
            app('log')->info('item_ids:'.var_export($item_ids,1));
            $this->compareLimitNum($tag_item_total_num, $usedLimitData['user_buy_num'], $purchaseItemList[$tag_id]['limit_num']);
            $this->compareLimitFee($tag_item_total_fee, $usedLimitData['user_buy_fee'], $purchaseItemList[$tag_id]['limit_fee']);
        }
        return true;
    }

    /**
     * 比较限购数
     * @param  string $itemNum               购买数量
     * @param  string $userBuyNum            已购买数量
     * @param  string $purchanseItemLimitNum 员工内购活动，设置的限购数
     */
    public function compareLimitNum($itemNum, $userBuyNum, $purchanseItemLimitNum)
    {
        $nums = intval(bcadd($itemNum, $userBuyNum));
        if ($nums > $purchanseItemLimitNum) {
            throw new ResourceException('已超过限购数~');
        }
        return true;
    }

    /**
     * 比较限额
     * @param  string $itemFee               购买金额
     * @param  string $userBuyFee            已购买金额
     * @param  string $purchanseItemLimitFee 员工内购活动，设置的限额
     */
    public function compareLimitFee($itemFee, $userBuyFee, $purchanseItemLimitFee)
    {
        $fee = intval(bcadd($itemFee, $userBuyFee));
        if ($fee > $purchanseItemLimitFee) {
            throw new ResourceException('已超过限额~');
        }
        return true;
    }

    /**
     * 根据员工内购活动ID，获取活动商品数据
     * @param  string $companyId  企业ID
     * @param  string $purchaseId 员工内购活动ID
     * @return mixed  活动商品列表
     */
    public function getActivityItemLimit($companyId, $purchaseId)
    {
        $employeePurchaseItemsService = new EmployeePurchaseItemsService();
        $filter = [
            'company_id' => $companyId,
            'purchase_id' => $purchaseId,
        ];
        $purchaseItemList = $employeePurchaseItemsService->getLists($filter);
        if (!$purchaseItemList) {
            return [];
        }
        return $purchaseItemList;

        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $purchaseItemList = $employeePurchaseActivityService->getPurchaseItemList($companyId, $purchaseId);
        $item_type = $purchaseItemList['item_type'];
        if ($item_type == 'all') {
            return $purchaseItemList;
        }
        $items = [];
        $_skuList = array_column($purchaseItemList['items'], null, 'item_id');
        foreach ($itemIds as $item_id) {
            if (isset($_skuList[$item_id])) {
                $items[$item_id] = $_skuList[$item_id];
            }
        }
        $purchaseItemList['items'] = $items;
        return $purchaseItemList;
    }

    public function getItemLimit($itemInfo, $userId, $shopId)
    {
        if (!$userId) {
            return $itemInfo;
        }

        // 未开启白名单不参与活动
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($itemInfo['company_id']);
        if ($setting['whitelist_status'] != true) {
            return $itemInfo;
        }

        // 检查是否有进行中的活动
        $filter = [
            'company_id' => $itemInfo['company_id'],
            'begin_time|lte' => time(),
            'end_time|gt' => time(),
        ];
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        $activityList = $employeePurchaseActivityService->lists($filter);
        if (!$activityList['list']) {
            return $itemInfo;
        }
        $activityInfo = $activityList['list'][0];

        // 是否员工
        $memberService = new MemberService();
        $mobile = $memberService->getMobileByUserId($userId, $itemInfo['company_id']);
        $membersWhitelistService = new MembersWhitelistService();
        $whitelistInfo = $membersWhitelistService->getInfo(['company_id' => $itemInfo['company_id'], 'mobile' => $mobile]);
        $userType = 'visitor';
        if ($whitelistInfo) {
            $userType = 'employee';
        }

        // 不是员工检查是否家属
        if ($userType == 'visitor') {
            $employeePurchaseReluserService = new EmployeePurchaseReluserService();
            $reluserInfo = $employeePurchaseReluserService->getInfo(['company_id' => $itemInfo['company_id'], 'purchase_id' => $activityInfo['purchase_id'], 'dependents_user_id' => $userId]);
            if ($reluserInfo) {
                $employeeMobile = $memberService->getMobileByUserId($reluserInfo['employee_user_id'], $itemInfo['company_id']);
                $employeeWhitelistInfo = $membersWhitelistService->getInfo(['company_id' => $itemInfo['company_id'], 'mobile' => $employeeMobile]);
                if ($employeeWhitelistInfo) {
                    $userType = 'dependents';
                }
            }
        }

        // 当前角色不能参与内购活动
        $activityInfo['used_roles'] = json_decode($activityInfo['used_roles'], true);
        if (!in_array($userType, $activityInfo['used_roles'])) {
            $itemInfo['purchase_limit_num'] = 0;
            if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                foreach ($itemInfo['spec_items'] as $key => $item) {
                    $itemInfo['spec_items']['purchase_limit_num'] = 0;
                }
            }

            return $itemInfo;
        }

        // 没有设置限购
        $purchaseItemList = $this->getActivityItemLimit($itemInfo['company_id'], $activityInfo['purchase_id']);
        if (!$purchaseItemList) {
            return $itemInfo;
        }

        $filter = [
            'company_id' => $itemInfo['company_id'],
            'user_id' => $userId,
            'shop_type' => 'distributor',
            'shop_id' => $shopId,
        ];
        $cartService = new CartService();
        $itemsService = new ItemsService();
        $cartList = $cartService->lists($filter, 1, 1000);
        $cartList = array_column($cartList['list'], null, 'item_id');

        $cartItemsList = [];
        if ($cartList) {
            $filter = [
                'company_id' => $itemInfo['company_id'],
                'item_type' => 'normal',
                'item_id' => array_column($cartList, 'item_id'),
            ];
            $cartItemsList = $itemsService->getSkuItemsList($filter, 1, -1 , []);
            $cartItemsList = array_column($cartItemsList['list'], 'item_id,brand_id,item_main_cat_id', 'item_id');
        }


        $itemType = $purchaseItemList[0]['item_type'];
        $purchaseItemList = array_column($purchaseItemList, null, 'item_id');

        if ($itemType == 'all') {
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, 0);
            $cartNum = array_sum(array_column($cartList, 'num'));
            $itemInfo['purchase_limit_num_by_cart'] = $purchaseItemList[0]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
            $itemInfo['purchase_limit_num_by_fastbuy'] = $purchaseItemList[0]['limit_num'] - $usedLimitData['user_buy_num'];

            if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                foreach ($itemInfo['spec_items'] as $key => $item) {
                    $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] = $purchaseItemList[0]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                    $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] = $purchaseItemList[0]['limit_num'] - $usedLimitData['user_buy_num'];
                }
            }

            return $itemInfo;
        }

        if ($itemType == 'brand') {
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, $itemInfo['brand_id']);

            $cartNum = 0;
            foreach ($cartList as $row) {
                if (isset($cartItemsList[$row['item_id']]) && $cartItemsList[$row['item_id']]['brand_id'] == $itemInfo['brand_id']) {
                    $cartNum += $row['num'];
                }
            }

            if (isset($purchaseItemList[$itemInfo['brand_id']])) {
                $itemInfo['purchase_limit_num_by_cart'] = $purchaseItemList[$itemInfo['brand_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                $itemInfo['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$itemInfo['brand_id']]['limit_num'] - $usedLimitData['user_buy_num'];

                if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                    foreach ($itemInfo['spec_items'] as $key => $item) {
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] = $purchaseItemList[$itemInfo['brand_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$itemInfo['brand_id']]['limit_num'] - $usedLimitData['user_buy_num'];
                    }
                }
            }

            return $itemInfo;
        }

        if ($itemType == 'category') {
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, $itemInfo['item_main_cat_id']);

            $cartNum = 0;
            foreach ($cartList as $row) {
                if (isset($cartItemsList[$row['item_id']]) && $cartItemsList[$row['item_id']]['item_main_cat_id'] == $itemInfo['item_main_cat_id']) {
                    $cartNum += $row['num'];
                }
            }

            if (isset($purchaseItemList[$itemInfo['item_main_cat_id']])) {
                $itemInfo['purchase_limit_num_by_cart'] = $purchaseItemList[$itemInfo['item_main_cat_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                $itemInfo['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$itemInfo['item_main_cat_id']]['limit_num'] - $usedLimitData['user_buy_num'];

                if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                    foreach ($itemInfo['spec_items'] as $key => $item) {
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] = $purchaseItemList[$itemInfo['item_main_cat_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$itemInfo['item_main_cat_id']]['limit_num'] - $usedLimitData['user_buy_num'];
                    }
                }
            }

            return $itemInfo;
        }

        if ($itemType == 'tag') {
            $filter = [
                'company_id' => $itemInfo['company_id'],
                'item_id' => $itemInfo['item_id'],
            ];

            $tagList = $itemsService->getItemTagList($filter);
            // 商品没有关联tag
            if (!$tagList['select_tags_list']) {
                return $itemInfo;
            }

            // 商品关联的tag不在限购范围内
            $itemTagIds = array_intersect(array_column($tagList['select_tags_list'], 'tag_id'), array_keys($purchaseItemList));
            if (!$itemTagIds) {
                return $itemInfo;
            }

            // 获取购物车商品关联的每个tag合计商品数量
            $tagCartNum = [];
            if ($cartItemsList) {
                // 获取购物车商品关联的tag
                $filter = [
                    'company_id' => $itemInfo['company_id'],
                    'item_id' => array_column($cartItemsList, 'item_id'),
                ];
                $cartTagList = $itemsService->getItemTagList($filter);
                // 如果购物车商品没有关联tag，或者购物车商品关联的tag没有设置限购，则tag合计商品数量为0
                if (empty($cartTagList['select_tags_list']) || empty(array_intersect(array_column($cartTagList['select_tags_list'], 'tag_id'), $itemTagIds))) {
                    $tagCartNum = [];
                } else {
                    // 一个商品可能关联多个tag
                    foreach ($cartTagList['select_tags_list'] as $row) {
                        $cartItemTag[$row['item_id']][] = $row['tag_id'];
                    }

                    // 迭代购物车的每个商品的每个tag，如果是当前页面的商品且活动设置了tag限购数量，则对应的tag数量增加
                    foreach ($cartList as $row) {
                        foreach ($cartItemTag[$row['item_id']] as $tagId) {
                            if (in_array($tagId, $itemTagIds)) {
                                if (isset($tagCartNum[$tagId])) {
                                    $tagCartNum[$tagId] += $row['num'];
                                } else {
                                    $tagCartNum[$tagId] = $row['num'];
                                }
                            }
                        }
                    }
                }
            }

            // 迭代当前商品的每个tag，限购数量取最小值
            foreach ($itemTagIds as $tagId) {
                $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, $tagId);
                $cartNum = $tagCartNum[$tagId] ?? 0;
                if (isset($purchaseItemList[$tagId])) {
                    $limitNumByCart = $purchaseItemList[$tagId]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                    $limitNumByFastbuy = $purchaseItemList[$tagId]['limit_num'] - $usedLimitData['user_buy_num'];
                    if (!isset($itemInfo['purchase_limit_num_by_cart']) || $itemInfo['purchase_limit_num_by_cart'] > $limitNumByCart) {
                        $itemInfo['purchase_limit_num_by_cart'] = $limitNumByCart;
                    }
                    if (!isset($itemInfo['purchase_limit_num_by_fastbuy']) || $itemInfo['purchase_limit_num_by_fastbuy'] > $limitNumByFastbuy) {
                        $itemInfo['purchase_limit_num_by_fastbuy'] = $limitNumByFastbuy;
                    }
                    if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                        foreach ($itemInfo['spec_items'] as $key => $item) {
                            if (!isset($itemInfo['spec_items'][$key]['purchase_limit_num_by_cart']) || $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] > $limitNumByCart) {
                                $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] = $limitNumByCart;
                            }
                            if (!isset($itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy']) || $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] > $limitNumByFastbuy) {
                                $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] = $limitNumByFastbuy;
                            }
                        }
                    }
                }
            }

            return $itemInfo;
        }


        if ($itemType == 'item') {
            $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, $itemInfo['item_id']);
            if (isset($cartList[$itemInfo['item_id']])) {
                $cartNum = $cartList[$itemInfo['item_id']]['num'];
            } else {
                $cartNum = 0;
            }

            if (isset($purchaseItemList[$itemInfo['item_id']])) {
                $itemInfo['purchase_limit_num_by_cart'] = $purchaseItemList[$itemInfo['item_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                $itemInfo['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$itemInfo['item_id']]['limit_num'] - $usedLimitData['user_buy_num'];

                if (!$itemInfo['nospec'] || $itemInfo['nospec'] === 'false') {
                    foreach ($itemInfo['spec_items'] as $key => $item) {
                        $usedLimitData = $employeePurchaseActivityService->getUsedLimitData($itemInfo['company_id'], $activityInfo['purchase_id'], $userId, $item['item_id']);
                        if (isset($cartList[$item['item_id']])) {
                            $cartNum = $cartList[$item['item_id']]['num'];
                        } else {
                            $cartNum = 0;
                        }
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_cart'] = $purchaseItemList[$item['item_id']]['limit_num'] - $usedLimitData['user_buy_num'] - $cartNum;
                        $itemInfo['spec_items'][$key]['purchase_limit_num_by_fastbuy'] = $purchaseItemList[$item['item_id']]['limit_num'] - $usedLimitData['user_buy_num'];
                    }
                }
            }

            return $itemInfo;
        }

        return $itemInfo;

    }

}
