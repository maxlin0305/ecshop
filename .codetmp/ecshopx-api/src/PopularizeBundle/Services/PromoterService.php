<?php

namespace PopularizeBundle\Services;

// use PopularizeBundle\Neo4jLabels\Promoter;
use PopularizeBundle\MysqlDatabase\Promoter;
use PopularizeBundle\Services\PromoterGradeService;
use PopularizeBundle\Services\SettingService;
use KaquanBundle\Services\VipGradeOrderService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Services\OrderAssociationService;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;

class PromoterService
{
    public $promoterNeoEloquent;
    public function __construct()
    {
        $this->promoterNeoEloquent = new Promoter();
    }

    public function __call($method, $parameters)
    {
        return $this->promoterNeoEloquent->$method(...$parameters);
    }

    /**
     * 保存推广员信息
     */
    public function create($data)
    {
        // 判断是否有推荐人
        $pid = null;
        $pmobile = 0;
        if (isset($data['inviter_id']) && $data['inviter_id']) {
            // 判断推荐人是否为推广员
            $inviterInfo = $this->promoterNeoEloquent->getInfoByUserId($data['inviter_id']);
            if ($inviterInfo && $inviterInfo['is_promoter']) {
                $pid = intval($inviterInfo['promoter_id']);
                $memberService = new MemberService();
                $pmobile = $memberService->getMobileByUserId($data['inviter_id'], $data['company_id']);
            }
        }

        $isPromoter = $this->userIsChangePromoter(intval($data['company_id']), intval($data['user_id']));

        $createData = [
            'user_id' => intval($data['user_id']),
            'company_id' => intval($data['company_id']),
            'pid' => $pid,
            'pmobile' => $pmobile,
            'grade_level' => 1,
            'is_promoter' => $isPromoter ? 1 : 0,
            'disabled' => 0,
            'shop_status' => 0,
            'is_buy' => 0,
            'created' => time()
        ];
        $this->promoterNeoEloquent->create($createData, $pid);

        // 如果已经成为推广员，并且有上级，那么对上级进行升级判断
        // if ($pid && $createData['is_promoter']) {
        // 如果会员由上级则会自动升级
        if ($pid) {
            $promoterGradeService = new PromoterGradeService();
            $promoterGradeService->upgradeGrade($data['company_id'], $data['inviter_id']);
        }

        // 如果成为推广员，对当前推广员进行升级
        if ($isPromoter) {
            $promoterGradeService = new PromoterGradeService();
            $promoterGradeService->upgradeGrade(intval($data['company_id']), intval($data['user_id']));
        }

        return $createData;
    }

    public function getInfo($filter)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        return $promoterRepository->getInfo($filter);
    }

    /**
     * 获取推广员列表
     */
    public function getPromoterList($filter = array(), $page = 1, $limit = 20)
    {
        $filter['user_id'] = $filter['user_id'] ?? [];

        if (!is_array($filter['user_id'])) {
            $filter['user_id'] = [$filter['user_id']];
        }

        if (isset($filter['mobile']) && $filter['mobile']) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($filter['mobile'], $filter['company_id']);
            if (empty($userId)) {
                $filter['user_id'] = '-1';
            } else {
                if (!empty($filter['user_id'])) {
                    $filter['user_id'] = array_intersect($filter['user_id'], [$userId]);
                    if (empty($filter['user_id'])) {
                        $filter['user_id'] = '-1';
                    }
                } else {
                    $filter['user_id'] = [$userId];
                }
            }
            unset($filter['mobile']);
        }

        if (isset($filter['username']) && $filter['username'] && $filter['user_id'] != '-1') {
            $memberService = new MemberService();
            $userIdList = $memberService->getUserIdByUsername($filter['username'], $filter['company_id']);
            if (empty($userIdList)) {
                $filter['user_id'] = '-1';
            } else {
                if (!empty($filter['user_id'])) {
                    $filter['user_id'] = array_intersect($filter['user_id'], $userIdList);
                    if (empty($filter['user_id'])) {
                        $filter['user_id'] = '-1';
                    }
                } else {
                    $filter['user_id'] = $userIdList;
                }
            }
            unset($filter['username']);
        }
        if (!empty($filter['username'])) {
            unset($filter['username']);
        }
        if (empty($filter['user_id'])) {
            unset($filter['user_id']);
        }

        $filter['is_promoter'] = 1;
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterList = $promoterRepository->getLists($filter, $page, $limit, ['created' => 'DESC']);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($filter['company_id'], $promoterList, $limit);

        return $promoterList;
    }

    // 推广员详情
    public function getPromoterInfo($companyId, $userId)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $data = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId]);
        if (!$data) {
            return [];
        }

        $promoterList['list'][] = $data;
        if (isset($data['pid']) && $data['pid']) {
            $promoterList['list'][] = $promoterRepository->getInfo(['id' => $data['pid']]);
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, 2);
        $return = $promoterList['list'][0];
        if (isset($promoterList['list'][1])) {
            $return['parent_info'] = $promoterList['list'][1];
        }
        return $return;
    }

    // 将推广员移动到指定推广员上
    public function relRemove($companyId, $userId, $newUserId)
    {
        if ($userId == $newUserId) {
            throw new ResourceException('自己不能调到自己');
        }

        $userInfo = $this->getInfoByUserId($userId);
        if (!$userInfo || !$userInfo['is_promoter']) {
            throw new ResourceException('当前不是推广员');
        }

        if ($userInfo && $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        if ($newUserId) {

            // 判断当前新的推广员是否在 以前推广员的下线
            // 我 不可 移动 到 我的 下级
            $relData = $this->promoterNeoEloquent->getRelationParentBy(['user_id' => $newUserId]);
            if ($relData['total_count'] > 0) {
                if (in_array($userId, array_column($relData['list'], 'user_id'))) { //
                    throw new ResourceException('不能移动到下级');
                }
            }

            $pdata = $this->getInfoByUserId($newUserId);

            if (!$pdata) {
                throw new ResourceException('无效的上级');
            }

            if ($pdata['company_id'] != $companyId) {
                throw new ResourceException('无效的上级');
            }

            if ($pdata && (!$userInfo['is_promoter'] || $userInfo['disabled'])) {
                throw new ResourceException('无效的上级');
            }

            $pid = $pdata['promoter_id']; //从Neo4j里取出来后把记录的id 赋给promoter_id

            $memberService = new MemberService();
            $pmobile = $memberService->getMobileByUserId($newUserId, $pdata['company_id']);
        } else {
            if (!isset($userInfo['pid']) || $userInfo['pid'] === null) {
                throw new ResourceException('当前推广员已是顶级');
            }
            $pid = null;
            $pmobile = null;
        }

        $this->updateByUserId($userId, ['pid' => $pid, 'pmobile' => $pmobile]);

        $promoterGradeService = new PromoterGradeService();
        if($newUserId) {
            $promoterGradeService->upgradeGrade($companyId, $newUserId);
        }
        if($userId) {
            $promoterGradeService->upgradeGrade($companyId, $userId);
        }
        if (isset($userInfo['pid']) && $userInfo['pid']) {
            $promoterGradeService->upgradeGrade($companyId, $userInfo['pid']);
        }

        return true;
    }

    // 获取指定会员的上级的推广员
    public function getPromoter($companyId, $userId)
    {
        $settingService = new SettingService();

        // 如果没有开启推广员 则不返回
        $isOpen = $settingService->getOpenPopularize($companyId);
        if ($isOpen == 'false') {
            return null;
        }

        $data = $this->getInfoByUserId($userId);

        if ($data && isset($data['company_id']) && $data['company_id'] != $companyId) {
            return null;
            // throw new ResourceException('参数错误');
        }

        if ($data && isset($data['pid']) && $data['pid']) {
            $pdata = $this->getInfoById($data['pid']);
            if (!$pdata) {
                return null;
            }
            return $pdata['user_id'];
        } else {
            return null;
        }
    }

    /**
     * 获取推广员下级列表
     */
    public function getPromoterchildrenList($filter, $depth = null, $page = 1, $limit = 20, $secrecy = 0)
    {
        $offset = ($page - 1) * $limit;

        // company_id id 必填
        $companyId = $filter['company_id'];
        if (!isset($filter['user_id']) && !isset($filter['promoter_id'])) {
            throw new ResourceException('参数错误');
        }

        $promoterList = $this->getRelationChildrenBy($filter, $depth, $offset, $limit);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, $limit, $secrecy);

        return $promoterList;
    }

    /**
     * 获取推广员上级列表
     */
    public function getPromoterParentList($filter, $depth, $page = 1, $limit = 20, $secrecy = 0)
    {
        $offset = ($page - 1) * $limit;

        // company_id id 必填
        $companyId = $filter['company_id'];
        if (!isset($filter['user_id']) && !isset($filter['promoter_id'])) {
            throw new ResourceException('参数错误');
        }

        $promoterList = $this->getRelationParentBy($filter, $depth, $offset, $limit);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, $limit, $secrecy);

        return $promoterList;
    }

    private function __formatPromoterData($companyId, $promoterList, $limit, $secrecy = 0)
    {
        $promoterGradeService = new PromoterGradeService();
        $isOpenPromoterGrade = $promoterGradeService->getOpenPromoterGrade($companyId);
        $config = $promoterGradeService->getPromoterGradeConfig($companyId);
        if (isset($config['grade'])) {
            foreach ($config['grade'] as $key => $row) {
                $gradeCustom[$row['grade_level']] = $row['custom_name'];
            }
        } else {
            $gradeCustom = array_column($promoterGradeService->promoterGradeDefault, 'name', 'grade_level');
        }

        $promoterData = $promoterList['list']['list'] ?? $promoterList['list'];
        $userIds = array_column($promoterData, 'user_id');
        $memberService = new MemberService();
        $page = 1;
        $memberList = $memberService->getList($page, $limit, array('user_id|in' => $userIds));
        $memberList = array_column($memberList['list'], null, 'user_id');

        $wechatUserService = new WechatUserService();
        $wechatUserList = $wechatUserService->getWechatUserList(['company_id' => $companyId, 'user_id' => $userIds]);
        $wechatUserList = array_column($wechatUserList, null, 'user_id');
        $pidList = array_column($promoterData, 'id');
        $childrenCountList = $this->relationChildrenCountByPidList($pidList);
        foreach ($promoterList['list'] as $k => $row) {
            if (empty($row)) {
                continue;
            }
            $promoterList['list'][$k]['children_count'] = $childrenCountList[$row['id']]['count'] ?? 0;
            // $promoterList['list'][$k]['children_count'] = $this->relationChildrenCountByUserId($row['user_id'], 1);
            $promoterList['list'][$k]['bind_date'] = date('Y-m-d', $row['created']);
            if (isset($memberList[$row['user_id']])) {
                // if ($secrecy) {
                //     $memberList[$row['user_id']]['mobile'] = substr_replace($memberList[$row['user_id']]['mobile'], '*****', 3, 5);
                // }
                $promoterList['list'][$k] = array_merge($memberList[$row['user_id']], $promoterList['list'][$k]);
                $promoterList['list'][$k]['promoter_grade_name'] = $gradeCustom[$row['grade_level']] ?? '';
                $promoterList['list'][$k]['is_open_promoter_grade'] = $isOpenPromoterGrade;
            }
            if (isset($wechatUserList[$row['user_id']])) {
                $promoterList['list'][$k]['nickname'] = (string)$wechatUserList[$row['user_id']]['nickname'];
                $promoterList['list'][$k]['headimgurl'] = $wechatUserList[$row['user_id']]['headimgurl'];
            }
        }

        return $promoterList;
    }

    /**
     * 指定会员成为推广员
     *
     * @param int $companyId 企业ID
     * @param int $userId 会员ID
     * @param boolean $force 是否强制成为推广员
     */
    public function changePromoter($companyId, $userId, $force = false)
    {
        // 如果不是强制当前用户成为推广员，
        // 那么对当前用户进行检查，是否满足成为推广员的条件
        if (!$force) {
            $isPromoter = $this->userIsChangePromoter($companyId, $userId);
            // 当前用户未达到成为推广员条件 那么则不进行更新
            if (!$isPromoter) {
                throw new ResourceException('不满足条件');
            }
        }

        $info = $this->promoterNeoEloquent->getInfoByUserId($userId);
        if (!$info) {
            $insertData['user_id'] = $userId;
            $insertData['company_id'] = $companyId;
            $memberService = new MemberService();
            $inviterId = $memberService->getinviterByUserId($userId, $companyId);
            if ($inviterId) {
                $insertData['inviter_id'] = $inviterId;
            }
            $info = $this->create($insertData);
        } else {
            if ($info['company_id'] != $companyId) {
                throw new ResourceException('数据异常');
            }
        }

        if (1 == $info['is_promoter']) {
            throw new ResourceException('该用户已经是推广员');
        }

        $data = $this->promoterNeoEloquent->updateByUserId($userId, ['is_promoter'=>1, 'disabled' => 0]);
        $promoterGradeService = new PromoterGradeService();
        $promoterGradeService->upgradeGrade($companyId, $userId);

        return $data;
    }

    /**
     * 校验当前用户是否可以成为推广员
     * 即：当前会员满足商家设定成为推广员的条件
     */
    public function userIsChangePromoter($companyId, $userId)
    {
        $settingService = new SettingService();
        $isOpen = $settingService->getOpenPopularize($companyId);
        if ($isOpen == 'false') {
            return false;
        }

        $config = $settingService->getConfig($companyId);
        $isPromoter = false;
        switch ($config['change_promoter']['type']) {
        case 'no_threshold':
            $isPromoter = true;
            break;
        case 'internal':
            // 仅内部开启推广
            $isPromoter = false;
            break;
        case 'vip_grade':
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
            if (isset($vipgrade['is_vip']) && $vipgrade['is_vip'] && isset($vipgrade['vip_type']) && $vipgrade['vip_type'] == $config['change_promoter']['filter']['vip_grade']) {
                $isPromoter = true;
            }
            break;
        case 'consume_money':
            $memberService = new MemberService();
            $totalConsumption = $memberService->getTotalConsumption($userId);
            if (bcdiv($totalConsumption, 100, 2) >= $config['change_promoter']['filter']['consume_money']) {
                $isPromoter = true;
            }
            break;
        case 'order_num':
            $filter = [
                'user_id' => $userId,
                'company_id' => $companyId,
                'order_status' => 'DONE'
            ];
            $orderAssociationService = new OrderAssociationService();
            $orderTotal = $orderAssociationService->countOrderNum($filter);
            if ($orderTotal >= $config['change_promoter']['filter']['order_num']) {
                $isPromoter = true;
            }
            break;
        }
        return $isPromoter;
    }

    /**
     * 推广员虚拟店铺状态修改
     */
    public function updateShopStatus($companyId, $userId, $status, $reason = null)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterInfo = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId, 'is_promoter' => 1, 'disabled' => false]);
        if (!$promoterInfo) {
            throw new ResourceException('当前推广员无权限');
        }

        // 如果当前小店已经开通了，则不需要再次申请
        if ($status == 2 && $promoterInfo['shop_status'] == 1) {
            return true;
        }

        $updateData['shop_status'] = intval($status);
        if ($reason) {
            $updateData['reason'] = trim($reason);
        }

        $promoterRepository->updateOneBy(['id' => $promoterInfo['id']], $updateData);
        return true;
    }
}
