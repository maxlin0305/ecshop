<?php

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;

use Hashids\Hashids;

use PromotionsBundle\Entities\EmployeePurchase;

use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsService;

use MembersBundle\Services\MembersWhitelistService;
use MembersBundle\Services\MemberService;

class EmployeePurchaseActivityService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(EmployeePurchase::class);
    }

    /**
     * 创建活动
     * @param $params
     * @return mixed
     */
    public function create($params)
    {
        $this->checkActivity($params);
        $data = $this->__formatData($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->create($data['activity']);
            $employeePurchaseItemsService = new EmployeePurchaseItemsService();
            foreach ($data['items'] as $items) {
                $items['purchase_id'] = $result['purchase_id'];
                $employeePurchaseItemsService->create($items);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    /**
     * 根据条件，更新数据
     * @param $filter
     * @param $params
     * @return mixed
     */
    public function updateActivity($filter, $params)
    {
        $this->checkActivity($params, $filter);
        $data = $this->__formatData($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->updateOneBy($filter, $data['activity']);
            $employeePurchaseItemsService = new EmployeePurchaseItemsService();
            $employeePurchaseItemsService->deleteBy($filter);
            foreach ($data['items'] as $items) {
                $items['purchase_id'] = $filter['purchase_id'];
                $employeePurchaseItemsService->create($items);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return $result;
    }

    /**
     * 格式化活动数据
     * @param  array $params 活动数据
     */
    private function __formatData($params)
    {
        $data = [];
        $data['activity'] = [
            'purchase_id' => $params['purchase_id'] ?? false,
            'company_id' => $params['company_id'],
            'purchase_name' => $params['purchase_name'],
            'ad_pic' => $params['ad_pic'],
            'used_roles' => json_encode($params['used_roles']),
            'employee_limitfee' => bcmul($params['employee_limitfee'], 100),
            'is_share_limitfee' => $params['is_share_limitfee'] == 'false' ? 0 : 1,
            'dependents_limitfee' => bcmul($params['dependents_limitfee'], 100),
            'dependents_limit' => $params['dependents_limit'],
            'begin_time' => $params['begin_time'],
            'end_time' => $params['end_time'],
            'minimum_amount' => bcmul($params['minimum_amount'], 100),
        ];
        if ($params['item_type'] == 'all') {
            $data['items'][] = [
                'company_id' => $params['company_id'],
                'item_id' => '0',
                'item_type' => 'all',
                'limit_fee' => '0',
                'limit_num' => $params['item_limit'],
            ];
        } else {
            foreach ($params['item_limit'] as $key => $limit) {
                $data['items'][] = [
                    'company_id' => $params['company_id'],
                    'item_id' => $limit['id'],
                    'item_type' => $params['item_type'],
                    'limit_fee' => bcmul($limit['limit_fee'], 100),
                    'limit_num' => $limit['limit_num'],
                ];
            }

        }
        return $data;
    }

    /**
     * 检查活动
     * @param $params
     * @param array $filter
     */
    private function checkActivity(&$params, $filter = [])
    {
        $params['purchase_id'] ??= 0;
        $params['dependents_limit'] ??= 0;
        $params['is_share_limitfee'] ??= 0;
        $params['dependents_limitfee'] ??= 0;
        $params['begin_time'] = strtotime($params['begin_time']);
        $params['end_time'] = strtotime($params['end_time']);
        if ($params['purchase_id'] > 0) {
            $info = $this->getInfoById($params['purchase_id']);
            if ($info['begin_time'] <= time()) {
                throw new ResourceException('当前活动状态不能编辑，请确认后再操作');
            }
        }
        if ($params['begin_time'] >= $params['end_time']) {
            throw new ResourceException('活动开始时间不能大于结束时间');
        }
        $filter = [
            'company_id' => $params['company_id'],
            'begin_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['begin_time'], time()),//排除过期活动
        ];
        if ($params['purchase_id'] > 0) {
            $filter['purchase_id|neq'] = $params['purchase_id'];
        }
        $purchaseList = $this->lists($filter);
        if ($purchaseList['total_count'] > 0) {
            throw new ResourceException('当前活动时间已有活动，请确认后再进行添加');
        }
        // 适用角色 employee必填 dependents选填
        if (empty($params['used_roles']) || !in_array('employee', $params['used_roles'])) {
            throw new ResourceException('适用角色选择错误');
        }
        if ($params['employee_limitfee'] <= 0 || $params['employee_limitfee'] > 9999999) {
            throw new ResourceException('员工额度为0~9999999的数字');
        }

        // 适用角色如果选择了dependents:家属
        if (in_array('dependents', $params['used_roles'])) {
            if ($params['is_share_limitfee'] == 'false') {
                if ($params['dependents_limitfee'] == '') {
                    throw new ResourceException('请输入亲友额度');
                }
                if (!is_numeric($params['dependents_limitfee'])) {
                    throw new ResourceException('亲友额度为0~9999999的数字');
                }
                if ($params['dependents_limitfee'] <= 0 || $params['dependents_limitfee'] > 9999999) {
                    throw new ResourceException('亲友额度为0~9999999的数字');
                }
            } else {
                $params['dependents_limitfee'] = 0;
            }
            if ($params['dependents_limit'] == '') {
                throw new ResourceException('请输入员工邀请上限');
            }
            $params['dependents_limit'] = intval($params['dependents_limit']);
            if ($params['dependents_limit'] <= 0) {
                throw new ResourceException('员工邀请上限为大于0的整数');
            }
        }

        if ($params['item_type'] == 'all') {
            if ($params['item_limit'] == '') {
                throw new ResourceException('请输入每人限购');
            }
            $params['item_limit'] = intval($params['item_limit']);
            if ($params['item_limit'] <= 0) {
                throw new ResourceException('每人限购为大于0的整数');
            }
        } else {
            if (empty($params['item_limit'])) {
                throw new ResourceException('请选择活动商品');
            }
            foreach ($params['item_limit'] as $key => $item) {
                $item['limit_num'] ??= '';
                $item['limit_fee'] ??= '';
                if ($item['limit_num'] == '') {
                    throw new ResourceException('请输入每人限购');
                }
                $item['limit_num'] = intval($item['limit_num']);
                if ($item['limit_num'] <= 0) {
                    throw new ResourceException('每人限购为大于0的整数');
                }
                if ($item['limit_fee'] == '') {
                    throw new ResourceException('请输入每人限额');
                }
                if (!is_numeric($item['limit_fee'])) {
                    throw new ResourceException('每人限额为大于0的数字');
                }
                $item['limit_fee'] = floatval($item['limit_fee']);
                if ($item['limit_fee'] <= 0) {
                    throw new ResourceException('每人限额为大于0的数字');
                }
                $params['item_limit'][$key] = $item;
            }
        }
        return true;
    }

    /**
     * 根据条件，获取活动详情
     * @param $filter
     */
    public function getActivityInfo($filter)
    {
        $info = $this->entityRepository->getInfo($filter);
        if (!$info) {
            return [];
        }
        $info['item_limit'] = [];
        $employeePurchaseItemsService = new EmployeePurchaseItemsService();
        $itemList = $employeePurchaseItemsService->getLists($filter);
        if (!$itemList) {
            return $info;
        }
        $info['item_type'] = $itemList[0]['item_type'] ?? 'all';
        if ($info['item_type'] == 'all') {
            $info['item_limit'] = $itemList[0]['limit_num'];
            return $info;
        }
        // 处理一下活动商品的数据
        $this->getPurchaseItemLimit($filter['company_id'], $info['item_type'], $itemList, $info);
        return $info;
    }

    /**
     * 根据活动商品类型，获取商品列表的数据。用于管理后台，展示已设置的活动商品数据。
     * @param  string $companyId 企业ID
     * @param  string $itemType  活动商品类型  all:全部商品;item:指定商品适用;category:指定分类适用;tag:指定商品标签适用;brand:指定品牌适用
     * @param  array $itemList  已设置的活动商品列表
     * @return mixed
     */
    public function getPurchaseItemLimit($companyId, $itemType, $itemList, &$info)
    {
        $item_ids = array_column($itemList, 'item_id');
        switch ($itemType) {
            case 'item':// 指定商品
                $itemsService = new ItemsService();
                $itemsFilter = [
                    'company_id' => $companyId,
                    'item_id' => $item_ids,
                ];
                $itemsList = $itemsService->getSkuItemsList($itemsFilter, 1, -1 , []);
                $itemTreeLists = $itemsList['list'] ?? [];
                if (empty($itemTreeLists)) {
                    return false;
                }
                $info['itemTreeLists'] = $itemsService->formatItemsList($itemTreeLists);
                $_itemsList = array_column($itemsList['list'], null, 'item_id');
                foreach ($itemList as $key => $items) {
                    if (!isset($_itemsList[$items['item_id']])) {
                        unset($itemList[$key]);
                        continue;
                    }
                    $items['id'] = $items['item_id'];
                    $items['name'] = $_itemsList[$items['item_id']]['item_name'];
                    $items['item_spec_desc'] = $_itemsList[$items['item_id']]['item_spec_desc'] ?? '';
                    $itemList[$key] = $items;
                }
                break;
            case 'category':// 指定商品主类目
                $itemsCategoryService = new ItemsCategoryService();
                $categoryFilter = [
                    'company_id' => $companyId,
                    'category_id' => $item_ids,
                    'is_main_category' => 'true',
                ];
                $itemsCategoryList = $itemsCategoryService->listsCopy($categoryFilter, [], -1, 1);
                $_itemsCategoryList = array_column($itemsCategoryList['list'], null, 'category_id');
                foreach ($itemList as $key => $items) {
                    if (!isset($_itemsCategoryList[$items['item_id']])) {
                        unset($itemList[$key]);
                        continue;
                    }
                    $items['id'] = $items['item_id'];
                    $items['name'] = $_itemsCategoryList[$items['item_id']]['category_name'];
                    $itemList[$key] = $items;
                }
                break;
            case 'tag':// 指定商品标签
                $itemsTagsService = new ItemsTagsService();
                $tagFilter = [
                    'company_id' => $companyId,
                    'tag_id' => $item_ids,
                ];
                $itemsTagsList = $itemsTagsService->getListTags($tagFilter, 1, -1, []);
                $_itemsTagsList = array_column($itemsTagsList['list'], null, 'tag_id');
                foreach ($itemList as $key => $items) {
                    if (!isset($_itemsTagsList[$items['item_id']])) {
                        unset($itemList[$key]);
                        continue;
                    }
                    $items['id'] = $items['item_id'];
                    $items['name'] = $_itemsTagsList[$items['item_id']]['tag_name'];
                    $itemList[$key] = $items;
                }
                break;
            case 'brand':// 指定商品品牌
                $itemsAttributesService = new ItemsAttributesService();
                $brandFilter = [
                    'company_id' => $companyId,
                    'attribute_type' => 'brand',
                    'attribute_id' => $item_ids,
                ];
                $brandList = $itemsAttributesService->getAttrList($brandFilter, 1, -1);
                $_brandList = array_column($brandList['list'], null, 'attribute_id');
                foreach ($itemList as $key => $items) {
                    if (!isset($_brandList[$items['item_id']])) {
                        unset($itemList[$key]);
                        continue;
                    }
                    $items['id'] = $items['item_id'];
                    $items['name'] = $_brandList[$items['item_id']]['attribute_name'];
                    $itemList[$key] = $items;
                }
                break;
            default:
                break;
        }
        $info['item_limit'] = $itemList;
        return true;
    }

    /**
     * 手动结束活动
     * @param $companyId
     * @param $purchaseId
     * @return mixed
     */
    public function endActivity($companyId, $purchaseId)
    {
        $filter = [
            'company_id' => $companyId,
            'purchase_id' => $purchaseId
        ];
        $params['end_time'] = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));
        $params['begin_time'] = $params['end_time'];
        $result = $this->entityRepository->updateOneBy($filter, $params);
        return $result;
    }

    /**
     * 根据会员，获取正在进行中的，员工内购活动数据
     * @param  string $companyId 企业ID
     * @param  string $userId    会员ID
     * @param  string $mobile    会员手机号
     * @return mixed
     */
    public function getOngoingInfo($companyId, $userId, $mobile = null)
    {
        if (!$mobile && $userId) {
            $memberService = new MemberService();
            $mobile = $memberService->getMobileByUserId($userId, $companyId);
        }

        // 检查
        $activityInfo = $this->__checkOngoingInfo($companyId, $userId, $mobile);
        if ($activityInfo['activity_data'] == false && $activityInfo['user_type'] == 'employee') {
            return $activityInfo;
        }
        $userData = $this->getUserData($activityInfo, $userId);
        $activityInfo = array_merge($activityInfo, $userData);
        $result = [
            'activity_data' => $activityInfo['activity_data'],
            'purchase_id' => $activityInfo['purchase_id'],
            'purchase_name' => $activityInfo['purchase_name'],
            'ad_pic' => $activityInfo['ad_pic'],
            'is_share_limitfee' => $activityInfo['is_share_limitfee'],
            'user_type' => $activityInfo['user_type'],
            'employee_user_id' => $activityInfo['employee_user_id'] ?? 0,// 员工userId
            'username' => $activityInfo['username'] ?? '',// 昵称
            'avatar' => $activityInfo['avatar'] ?? '', // 头像
            'total_limitfee' => $activityInfo['total_limitfee'] ?? 0, // 总额度
            'used_limitfee' => $activityInfo['used_limitfee'] ?? 0, // 会员已使用额度
            'total_used_limitfee' => $activityInfo['total_used_limitfee'] ?? 0, // 员工已使用额度
            'surplus_limitfee' => $activityInfo['surplus_limitfee'] ?? 0, // 剩余额度
            'surplus_share_limitnum' => $activityInfo['surplus_share_limitnum'] ?? 0, // 剩余分享次数
            'dependents_limit' => $activityInfo['dependents_limit'] ?? 0, // 分享总次数
            'dependents_list' => $activityInfo['dependents_list'] ?? [],// 家属列表
            'minimum_amount' => $activityInfo['minimum_amount'] ?? 0,
            'used_roles' => $activityInfo['used_roles'],
            'dependents_begin_time' => $activityInfo['begin_time'] + 3600 * config('common.employee_purchanse_dependents_hour'),
        ];
        return $result;
    }

    /**
     * 根据会员，检查是否有进行中的员工内购活动
     * @param  string $companyId 企业ID
     * @param  string $userId    会员ID
     * @param  string $mobile    会员手机号
     * @return mixed
     */
    public function __checkOngoingInfo($companyId, $userId, $mobile)
    {
        // 获取当前会员的属性 员工、家属
        $user_type = '';
        // 检查是否为员工
        $membersWhitelistService = new MembersWhitelistService();
        $employeePurchaseReluserService = new EmployeePurchaseReluserService();
        $whitelistInfo = $membersWhitelistService->getInfo(['company_id' => $companyId, 'mobile' => $mobile]);
        $whitelistInfo and $user_type = 'employee';
        // 检查是否有进行中的活动
        $filter = [
            'company_id' => $companyId,
            'begin_time|lte' => time(),
            'end_time|gt' => time(),
        ];
        $list = $this->getLists($filter);
        // 员工，没有内购活动时，可以购买
        if ($user_type == 'employee') {
            if (!$list) {
                return ['user_type' => $user_type, 'activity_data' => false];
            }
            $employee_user_id = $userId;

        }
        if (!$list) {
            throw new ResourceException('活动暂时未开启，敬请期待~');
        }
        $info = $list[0];
        $info['used_roles'] = json_decode($info['used_roles'], true);
        if (!in_array('dependents', $info['used_roles']) && !$user_type) {
            throw new ResourceException('当前活动没有开放亲友购买权限~');
        }
        // 检查是否为家属,如果家属的分享员工已经在白名单删除，家属也不能再查看数据
        $reluserInfo = $employeePurchaseReluserService->getInfo(['company_id' => $companyId, 'purchase_id' => $info['purchase_id'], 'dependents_user_id' => $userId]);
        if ($reluserInfo) {
            // 检查家属的员工，是否在白名单中
            $memberService = new MemberService();
            $employeeMobile = $memberService->getMobileByUserId($reluserInfo['employee_user_id'], $companyId);
            $employeeWhitelistInfo = $membersWhitelistService->getInfo(['company_id' => $companyId, 'mobile' => $employeeMobile]);
            if ($employeeWhitelistInfo) {
                $user_type = 'dependents';
            }
            $employee_user_id = $reluserInfo['employee_user_id'];
        }
        if (!$user_type) {
            throw new ResourceException('请通过新的分享重新绑定成为亲友~');
        }
        $info['user_type'] = $user_type;
        $info['activity_data'] = true;
        $info['employee_user_id'] = $employee_user_id;
        return $info;
    }

    /**
     * 获取会员的额度数据
     * @param  array $activityInfo 员工内购活动详情
     * @param  string $userId      会员ID
     * @return mixed
     */
    public function getUserData($activityInfo, $userId)
    {
        $employeePurchaseReluserService = new EmployeePurchaseReluserService();
        // 会员的已使用额度
        $usedUserLimitData = $this->getUsedUserTotalLimitData($activityInfo['company_id'], $activityInfo['purchase_id'], $userId);
        $activityInfo['used_limitfee'] = $usedUserLimitData['user_total_buy_fee'];
        // 员工的已使用额度
        if ($activityInfo['is_share_limitfee'] == 1) {// 家属共享员工额度
            $usedLimitData = $this->getUsedTotalLimitData($activityInfo['company_id'], $activityInfo['purchase_id'], $activityInfo['employee_user_id']);
            $activityInfo['total_used_limitfee'] = $usedLimitData['total_buy_fee'];
        } else {
            $activityInfo['total_used_limitfee'] = $activityInfo['used_limitfee'];
        }

        if ($activityInfo['user_type'] == 'employee') {
            // 员工
            $activityInfo['dependents_list'] = $employeePurchaseReluserService->getReluserList($activityInfo['company_id'], $activityInfo['purchase_id'], $userId);
            $activityInfo['surplus_share_limitnum'] = $this->getSurplusShareLimitnum($activityInfo['company_id'], $activityInfo['purchase_id'], $userId, $activityInfo['dependents_limit']);
            $activityInfo['total_limitfee'] = $activityInfo['employee_limitfee'];
            $activityInfo['surplus_limitfee'] = bcsub($activityInfo['total_limitfee'], $activityInfo['used_limitfee']);
            if ($activityInfo['is_share_limitfee'] == 1) {
                $activityInfo['surplus_limitfee'] = bcsub($activityInfo['total_limitfee'], $activityInfo['total_used_limitfee']);
            }
        } else {
            // 家属
            $activityInfo['surplus_share_limitnum'] = $this->getSurplusShareLimitnum($activityInfo['company_id'], $activityInfo['purchase_id'], $activityInfo['employee_user_id'], $activityInfo['dependents_limit']);
            if ($activityInfo['is_share_limitfee'] == 1) {// 家属共享员工额度
                // 如果是共享，查询员工下已使用额度
                $activityInfo['total_limitfee'] = $activityInfo['employee_limitfee'];
                $activityInfo['surplus_limitfee'] = bcsub($activityInfo['total_limitfee'], $activityInfo['total_used_limitfee']);
            } else {// 家属不共享员工额度
                $activityInfo['total_limitfee'] = $activityInfo['dependents_limitfee'];
                $activityInfo['surplus_limitfee'] = bcsub($activityInfo['total_limitfee'], $activityInfo['used_limitfee']);
            }
        }
        // 查询会员信息
        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['company_id' => $activityInfo['company_id'], 'user_id' => $userId], false);
        $activityInfo['username'] = $memberInfo['username'] ?? '';
        $activityInfo['avatar'] = $memberInfo['avatar'] ?? '';
        return $activityInfo;
    }

    /**
     * 设置订单关联数据，用于取消订单时，需要减少的限购数和限额
     * @param array $params 要保存的数据
     */
    public function setRelOrderData($params)
    {
        $employeePurchaseRelorderService = new EmployeePurchaseRelorderService();
        return $employeePurchaseRelorderService->create($params);
    }

    /**
     * 设置员工或家属购买数据（数量、金额）
     * @param  string $companyId       企业Id
     * @param  string $purchaseId      员工内购活动ID
     * @param  string $userId  会员ID
     * @param  string $itemId  商品Id、主类目Id、商品标签Id、商品品牌Id
     * @param  string $fee 金额  增加时正数、减少时负数
     * @param  string $num 数量  增加时正数，减少时负数
     */
    public function setUsedLimitData($relOrderParams, $companyId, $purchaseId, $userId, $itemId, $fee, $num)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);

        $buyFeeKey = 'user_buy_fee:'.$purchaseId.':'. $userId.':'.$itemId;
        $buyNumKey = 'user_buy_num:'.$purchaseId.':'. $userId.':'.$itemId;
        if ($num > 0 || $fee > 0) {
            $relOrderParams['user_id'] = $userId;
            $relOrderParams['purchase_item_id'] = $itemId;
            $relOrderParams['fee'] = $fee;
            $relOrderParams['num'] = $num;
            $relOrderParams['redis_key'] = 'user_buy';
            $this->setRelOrderData($relOrderParams);
            app('redis')->hincrby($key, $buyFeeKey, $fee);
            app('redis')->hincrby($key, $buyNumKey, $num);
            return true;
        }
        if (app('redis')->hexists($key, $buyFeeKey)) {
            app('redis')->hincrby($key, $buyFeeKey, $fee);
        }
        if (app('redis')->hexists($key, $buyNumKey)) {
            app('redis')->hincrby($key, $buyNumKey, $num);
        }
        return true;
    }


    /**
     * 设置员工或家属购买的总计数据（额度），用于计算剩余额度
     * @param string $companyId  企业Id
     * @param string $purchaseId 员工内购活动ID
     * @param string $userId     会员ID
     * @param string $fee        金额（单位：分） 增加时正数、减少时负数
     */
    public function setUsedTotalLimitData($relOrderParams, $companyId, $purchaseId, $userId, $fee)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $totalBuyFeeKey = 'total_buy_fee:'.$purchaseId.':'. $userId;
        if ($fee > 0) {
            $relOrderParams['user_id'] = $userId;
            $relOrderParams['order_item_id'] = 0;
            $relOrderParams['purchase_item_id'] = 0;
            $relOrderParams['fee'] = $fee;
            $relOrderParams['num'] = 0;
            $relOrderParams['redis_key'] = 'total_buy_fee';
            $this->setRelOrderData($relOrderParams);
            app('redis')->hincrby($key, $totalBuyFeeKey, $fee);
            return true;
        }
        if (app('redis')->hexists($key ,$totalBuyFeeKey)) {
            app('redis')->hincrby($key, $totalBuyFeeKey, $fee);
        }
        return true;
    }

    /**
     * 设置会员购买的总计数据（额度），用于显示已使用额度
     * @param string $companyId  企业Id
     * @param string $purchaseId 员工内购活动ID
     * @param string $userId     会员ID
     * @param string $fee        金额（单位：分） 增加时正数、减少时负数
     */
    public function setUsedUserTotalLimitData($relOrderParams, $companyId, $purchaseId, $userId, $fee)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $totalBuyFeeKey = 'user_total_buy_fee:'.$purchaseId.':'. $userId;
        if ($fee > 0) {
            $relOrderParams['user_id'] = $userId;
            $relOrderParams['order_item_id'] = 0;
            $relOrderParams['purchase_item_id'] = 0;
            $relOrderParams['fee'] = $fee;
            $relOrderParams['num'] = 0;
            $relOrderParams['redis_key'] = 'user_total_buy_fee';
            $this->setRelOrderData($relOrderParams);
            app('redis')->hincrby($key, $totalBuyFeeKey, $fee);
            return true;
        }
        if (app('redis')->hexists($key ,$totalBuyFeeKey)) {
            app('redis')->hincrby($key, $totalBuyFeeKey, $fee);
        }
        return true;
    }

    /**
     * 获取员工或家属购买数据（数量、金额）
     * @param  string $companyId  企业Id
     * @param  string $purchaseId 员工内购活动ID
     * @param  string $userId     员工会员ID
     * @param  string $itemId  商品Id、主类目Id、商品标签Id、商品品牌Id
     */
    public function getUsedLimitData($companyId, $purchaseId, $userId, $itemId)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $buyFeeKey = 'user_buy_fee:'.$purchaseId.':'. $userId.':'.$itemId;
        $buyNumKey = 'user_buy_num:'.$purchaseId.':'. $userId.':'.$itemId;

        $result = app('redis')->hmget($key, [$buyFeeKey, $buyNumKey]);

        //不确定返回值是数字索引的原因？
        if (!isset($result[$buyFeeKey])) {
            $result[$buyFeeKey] = $result[0] ?? 0;
        }
        if (!isset($result[$buyNumKey])) {
            $result[$buyNumKey] = $result[1] ?? 0;
        }
        $data['user_buy_fee'] = $result[$buyFeeKey] ?? 0;
        $data['user_buy_num'] = $result[$buyNumKey] ?? 0;
        return $data;
    }

    /**
     * 获取员工或家属活动总计购买数据（数量、金额）
     * @param  string $companyId  企业Id
     * @param  string $purchaseId 员工内购活动ID
     * @param  string $userId     员工会员ID
     */
    public function getUsedTotalLimitData($companyId, $purchaseId, $userId)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $totalBuyFeeKey = 'total_buy_fee:'.$purchaseId.':'. $userId;

        $result = app('redis')->hmget($key, [$totalBuyFeeKey]);

        //不确定返回值是数字索引的原因？
        if (!isset($result[$totalBuyFeeKey])) {
            $result[$totalBuyFeeKey] = $result[0] ?? 0;
        }
        $data['total_buy_fee'] = $result[$totalBuyFeeKey] ?? 0;
        return $data;
    }

    /**
     * 获取员工或家属活动总计购买数据（数量、金额）,用于显示会员的已使用额度
     * @param  string $companyId  企业Id
     * @param  string $purchaseId 员工内购活动ID
     * @param  string $userId     员工会员ID
     */
    public function getUsedUserTotalLimitData($companyId, $purchaseId, $userId)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $totalBuyFeeKey = 'user_total_buy_fee:'.$purchaseId.':'. $userId;

        $result = app('redis')->hmget($key, [$totalBuyFeeKey]);

        //不确定返回值是数字索引的原因？
        if (!isset($result[$totalBuyFeeKey])) {
            $result[$totalBuyFeeKey] = $result[0] ?? 0;
        }
        $data['user_total_buy_fee'] = $result[$totalBuyFeeKey] ?? 0;
        return $data;
    }

    /**
     * 获取用户所有活动的数据
     *
     * @param $companyId
     * @param $userId
     * @return array
     */
    public function getUserAllActivityData($companyId, $userId): array
    {
        // 当前正在进行的活动
        $filter = [
            'company_id'     => $companyId,
            'begin_time|lte' => time(),
            'end_time|gt'    => time(),
        ];
        $employeePurchaseList = $this->entityRepository->getLists($filter);
        if (empty($employeePurchaseList)) {
            return [];
        }

        $result = [];

        foreach ($employeePurchaseList as $value) {
            $usedUserLimitData = $this->getUsedUserTotalLimitData($companyId, $value['purchase_id'], $userId);
            if (isset($usedUserLimitData['user_total_buy_fee']) && $usedUserLimitData['user_total_buy_fee']) {
                $result[] = [
                    'purchase_id'        => $value['purchase_id'],
                    'user_total_buy_fee' => $usedUserLimitData['user_total_buy_fee'],
                ];
            }
        }

        return $result;
    }

    /**
     * 获取员工的剩余分享次数
     * @param  string $companyId       企业Id
     * @param  string $purchaseId      员工内购活动ID
     * @param  string $employeeUserId  员工会员ID
     * @param  string $dependentsLimit 员工邀请上限
     */
    public function getSurplusShareLimitnum($companyId, $purchaseId, $employeeUserId, $dependentsLimit)
    {
        if ($dependentsLimit <= 0) {
            return 0;
        }
        $result = $this->getUsedShareLimitnum($companyId, $purchaseId, $employeeUserId);
        return bcsub($dependentsLimit, $result['used_share_limitnum']);
    }

    /**
     * 获取员工内购数据存储的key
     * @param  string $companyId 企业ID
     * @return string
     */
    public function getEmployeePurchanseDataKey($companyId)
    {
        return 'employee_purchase_data:'.$companyId;
    }

    /**
     * 设置员工已分享数量
     * @param  string $companyId       企业Id
     * @param  string $purchaseId      员工内购活动ID
     * @param  string $userId  员工会员ID
     * @param  int $num  分享次数
     */
    public function setUsedShareLimitnum($companyId, $purchaseId, $userId, $num = 1)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);

        $shareLimitnum = 'share_limitnum:'.$purchaseId.':'. $userId;

        app('redis')->hincrby($key, $shareLimitnum, $num);
        return true;
    }

    /**
     * 获取员工已分享数量
     * @param  string $companyId  企业Id
     * @param  string $purchaseId 员工内购活动ID
     * @param  string $userId     员工会员ID
     */
    public function getUsedShareLimitnum($companyId, $purchaseId, $userId)
    {
        $shareLimitnum = 'share_limitnum:'.$purchaseId.':'. $userId;

        $key = $this->getEmployeePurchanseDataKey($companyId);

        $result = app('redis')->hmget($key, [$shareLimitnum]);

        //不确定返回值是数字索引的原因？
        if (!isset($result[$shareLimitnum])) {
            $result[$shareLimitnum] = $result[0] ?? 0;
        }
        $data['used_share_limitnum'] = $result[$shareLimitnum] ?? 0;
        return $data;
    }

    /**
     * 获取进行中活动员工的分享码
     * @param  string $companyId 企业ID
     * @param  string $userId    员工会员ID
     * @param  string $mobile    员工会员手机号
     */
    public function getShareCode($companyId, $userId, $mobile)
    {
        $activityInfo = $this->__checkOngoingInfo($companyId, $userId, $mobile);
        if ($activityInfo['user_type'] != 'employee') {
            throw new ResourceException('暂时没有权限邀请员工，请确认后重试~');
        }
        // 检查是否还有剩余分享次数
        $surplus_share_limitnum = $this->getSurplusShareLimitnum($companyId, $activityInfo['purchase_id'], $userId, $activityInfo['dependents_limit']);
        if ($surplus_share_limitnum <= 0) {
            throw new ResourceException('已达到邀请上限');
        }
        // 生成code
        $code = $this->genShareCode($companyId);
        $encodeData = [$activityInfo['purchase_id'], $userId];
        $hashids = new Hashids();
        $ticket = $hashids->encode($encodeData);
        $key = $this->getEmployeePurchanseDataKey($companyId);
        app('redis')->hset($key, $code, $ticket);
        // 设置code的过期时间
        $time_expire = config('common.employee_purchanse_sharecode_expire');
        $codeValue = (time() + $time_expire) . '_'. $companyId;
        app('redis')->hset('employee_purchanse_code',$code, $codeValue);

        return $code;
    }

    /**
     * 删除已过期的分享码
     * @return [type]
     */
    /*public function scheduleExpireSharecode()
    {
        $allCode = app('redis')->hgetall('employee_purchanse_code');
        if (empty($allCode)) {
            return true;
        }
        foreach ($allCode as $code => $expire_data) {
            list($expire_time, $company_id) = explode('_', $expire_data);
            if (intval($expire_time) >= time()) {
                continue;
            }
            // code过期，删除数据
            $this->doExpireShareCode($company_id, $code);
        }
        return true;
    }*/

    /**
     * 处理已过期的分享码
     * @param  string $companyId 企业ID
     * @param  string $code      分享码
     */
    /*public function doExpireShareCode($companyId, $code)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $ticket = app('redis')->hget($key, $code);
        if (!$ticket) {
            app('redis')->hdel('employee_purchanse_code', $code);
            return true;
        }
        $hashids = new Hashids();
        $ticketData = $hashids->decode($ticket);
        list($purchaseId, $employeeUserId) = $ticketData;
        // $encodeData = [$purchase_id, $userId];
        // 减少分享次数
        $this->setUsedShareLimitnum($companyId, $purchaseId, $employeeUserId, -1);
        app('redis')->hdel('employee_purchanse_code', $code);
        app('redis')->hdel($key, $code);
        return true;
    }*/

    /**
     * 生成分享吗
     * @param  string $companyId 企业ID
     */
    private function genShareCode($companyId)
    {
        $code = (string)rand(1000000, 9999999);
        $key = $this->getEmployeePurchanseDataKey($companyId);
        if (app('redis')->hget($key, $code)) {
            $code = $this->genShareCode($companyId);
        }
        return $code;
    }

    /**
     * 验证分享码是否存在
     * @param  string $companyId 企业ID
     * @param  string $code      分享码
     */
    public function checkShareCode($companyId, $code)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        return app('redis')->hexists($key, $code);
    }

    /**
     * 验证分享码是否存在
     * @param  string $companyId 企业ID
     * @param  string $code      分享码
     */
    public function lockShareCode($companyId, $code)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $ticket = app('redis')->hget($key, $code);
        if (app('redis')->hdel($key, $code)) {
            app('redis')->hset($key, $code.'_', $ticket);
            return true;
        }
        throw new ResourceException('邀请码已被使用');
    }

    public function unlockShareCode($companyId, $code)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $ticket = app('redis')->hget($key, $code.'_');
        if (app('redis')->hdel($key, $code.'_')) {
            app('redis')->hset($key, $code, $ticket);
        }
        return true;
    }

    /**
     * 绑定成为员工的家属
     * @param  string $companyId 企业ID
     * @param  string $code      分享码
     * @param  string $dependentsUserId      家属的会员ID
     */
    public function bindDependents($companyId, $code, $dependentsUserId)
    {
        $key = $this->getEmployeePurchanseDataKey($companyId);
        $ticket = app('redis')->hget($key, $code.'_');
        if (!$ticket) {
            throw new ResourceException('分享链接已失效');
        }
        $hashids = new Hashids();
        $ticketData = $hashids->decode($ticket);
        $purchaseId = $ticketData[0] ?? false;
        $employeeUserId = $ticketData[1] ?? false;
        // $encodeData = [$purchase_id, $userId];
        if (!$purchaseId || !$employeeUserId) {
            throw new ResourceException('分享链接已失效');
        }

        $activityInfo = $this->entityRepository->getInfoById($purchaseId);
        $surplusShareLimitnum = $this->getSurplusShareLimitnum($companyId, $purchaseId, $employeeUserId, $activityInfo['dependents_limit']);
        if ($surplusShareLimitnum <= 0) {
            throw new ResourceException('已达到邀请上限');
        }

        // 去绑定，成为家属
        $employeePurchaseReluserService = new EmployeePurchaseReluserService();
        $info = $employeePurchaseReluserService->getInfo(['company_id' => $companyId, 'purchase_id' => $purchaseId, 'dependents_user_id' => $dependentsUserId]);
        if ($info) {
            throw new ResourceException('已经是亲友，不需要重复邀请');
        }

        $data = [
            'company_id' => $companyId,
            'purchase_id' => $purchaseId,
            'employee_user_id' => $employeeUserId,
            'dependents_user_id' => $dependentsUserId,
        ];
        $result = $employeePurchaseReluserService->create($data);
        if (!$result) {
            throw new ResourceException('绑定成为亲友失败');
        }
        // 删除code
        app('redis')->hdel($key, $code.'_');

        // 增加分享次数
        $this->setUsedShareLimitnum($companyId, $purchaseId, $employeeUserId, 1);

        return true;
    }

    public function restoreEmployeeShareLimitnum($companyId, $userId)
    {
        // 检查是否有进行中的活动
        $filter = [
            'company_id' => $companyId,
            'begin_time|lte' => time(),
            'end_time|gt' => time(),
        ];
        $activityList = $this->getLists($filter);
        if (!$activityList) {
            return true;
        }
        $activityInfo = $activityList[0];
        $employeePurchaseReluserService = new EmployeePurchaseReluserService();
        $info = $employeePurchaseReluserService->getInfo(['company_id' => $companyId, 'purchase_id' => $activityInfo['purchase_id'], 'dependents_user_id' => $userId]);
        if ($info) {
            // 家属注销账号且没有下过单，返还员工的分享额度
            $usedUserLimitData = $this->getUsedUserTotalLimitData($companyId, $activityInfo['purchase_id'], $userId);
            if ($usedUserLimitData['user_total_buy_fee'] == 0) {
                $this->setUsedShareLimitnum($companyId, $activityInfo['purchase_id'], $info['employee_user_id'], -1);
            }
        }
        return true;
    }

    /**
     * 员工内购，累计减少限购数、限额
     * @param  string $companyId 企业ID
     * @param  string $orderId   订单编号
     */
    public function minusEmployeePurchaseLimitData($companyId, $orderId)
    {
        $employeePurchaseRelorderService = new EmployeePurchaseRelorderService();
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $relOrderList = $employeePurchaseRelorderService->getLists($filter);
        if (!$relOrderList) {
            return true;
        }

        foreach ($relOrderList as $relOrder) {
            app('log')->debug('员工内购，累计减少限购数、限额，数据====》'.json_encode($relOrder));
            if ($relOrder['redis_key'] == 'user_buy') {
                $this->setUsedLimitData([], $companyId, $relOrder['purchase_id'], $relOrder['user_id'], $relOrder['purchase_item_id'], -intval($relOrder['fee']), -intval($relOrder['num']));
            } elseif ($relOrder['redis_key'] == 'total_buy_fee') {
                $this->setUsedTotalLimitData([], $companyId, $relOrder['purchase_id'], $relOrder['user_id'], -intval($relOrder['fee']));
            } elseif($relOrder['redis_key'] == 'user_total_buy_fee') {
                $this->setUsedUserTotalLimitData([], $companyId, $relOrder['purchase_id'], $relOrder['user_id'], -intval($relOrder['fee']));
            }
        }

        return true;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
