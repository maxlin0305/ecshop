<?php

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Entities\EmployeePurchaseReluser;

use MembersBundle\Services\MemberService;
use PromotionsBundle\Services\EmployeePurchaseActivityService;
use MembersBundle\Services\MembersWhitelistService;

class EmployeePurchaseReluserService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(EmployeePurchaseReluser::class);
    }

    /**
     * 获取员工下的家属列表
     * @param  string $companyId      企业ID
     * @param  string $purchaseId     员工内购ID
     * @param  string $employeeUserId 员工会员ID
     * @return mixed
     */
    public function getReluserList($companyId, $purchaseId, $employeeUserId)
    {
        $filter = [
            'company_id' => $companyId,
            'purchase_id' => $purchaseId,
            'employee_user_id' => $employeeUserId
        ];
        $list = $this->getLists($filter);
        if (!$list) {
            return [];
        }
        $userIds = array_column($list, 'dependents_user_id');
        // 查询会员的头像和昵称
        $memberService = new MemberService();
        $memberFilter = [
            'company_id' => $companyId,
            'user_id' => $userIds,
        ];
        $memberInfoList = $memberService->getMemberInfoList($memberFilter, 1, -1);
        $infoList = array_column($memberInfoList['list'], null, 'user_id');
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        foreach ($list as $key => $value) {
            if (!isset($infoList[$value['dependents_user_id']])) {
                $list[$key]['is_delete'] = true;
            }
            $list[$key]['username'] = $infoList[$value['dependents_user_id']]['username'] ?? '';
            $list[$key]['avatar'] = $infoList[$value['dependents_user_id']]['avatar'] ?? '';
            $usedLimitData = $employeePurchaseActivityService->getUsedUserTotalLimitData($companyId, $purchaseId, $value['dependents_user_id']);
            $list[$key]['used_limitfee'] = $usedLimitData['user_total_buy_fee'];// 已使用额度
        }
        return $list;
    }

    public function create($data)
    {
        $reluserInfo = $this->entityRepository->getInfo($data);
        if ($reluserInfo) {
            return true;
        }
        $memberService = new MemberService();
        $membersWhitelistService = new MembersWhitelistService();
        // 检查员employee_user_id是否在白名单中
        $employeeMobile = $memberService->getMobileByUserId($data['employee_user_id'], $data['company_id']);
        $employeeWhitelistInfo = $membersWhitelistService->getInfo(['company_id' => $data['company_id'], 'mobile' => $employeeMobile]);
        if (!$employeeWhitelistInfo) {
            throw new ResourceException('员工信息已失效，无法成为亲友');
        }
        // 检查dependents_user_id是否在白名单中
        $dependentsMobile = $memberService->getMobileByUserId($data['dependents_user_id'], $data['company_id']);
        $dependentsWhitelistInfo = $membersWhitelistService->getInfo(['company_id' => $data['company_id'], 'mobile' => $dependentsMobile]);
        if ($dependentsWhitelistInfo) {
            throw new ResourceException('已经是员工，无需绑定');
        }

        $result = $this->entityRepository->create($data);
        return $result;
    }

    /**
     * 获取亲友数据
     * @param  array  $filter   查询条件
     */
    public function getDependentsLists($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $memberService = new MemberService();

        if (isset($filter['employee_user_mobile']) && $filter['employee_user_mobile']) {
            $employeeUserId = $memberService->getUserIdByMobile($filter['employee_user_mobile'], $filter['company_id']);
            $filter['employee_user_id'] = $employeeUserId ? $employeeUserId : -1;
            unset($filter['employee_user_mobile']);
        }
        if (isset($filter['dependents_user_mobile']) && $filter['dependents_user_mobile']) {
            $dependentsUserId = $memberService->getUserIdByMobile($filter['dependents_user_mobile'], $filter['company_id']);
            $filter['dependents_user_id'] = $dependentsUserId ? $dependentsUserId : -1;
            unset($filter['dependents_user_mobile']);
        }
        $lists = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if ($lists['total_count'] <= 0) {
            return [];
        }
        $userIds = [];
        foreach ($lists['list'] as $key => $value) {
            $userIds[] = $value['employee_user_id'];
            $userIds[] = $value['dependents_user_id'];
        }
        // 查询会员的手机号和昵称
        $memberFilter = [
            'company_id' => $filter['company_id'],
            'user_id' => $userIds,
        ];
        $memberInfoList = $memberService->getMemberList($memberFilter, 1, -1);
        $_memberInfoList = array_column($memberInfoList, null, 'user_id');
        $employeePurchaseActivityService = new EmployeePurchaseActivityService();
        foreach ($lists['list'] as $key => &$value) {
            $employeeUserId = $value['employee_user_id'];
            $dependentsUserId = $value['dependents_user_id'];
            $value['employee_user_mobile'] = $_memberInfoList[$employeeUserId]['mobile'] ?? $employeeUserId.'(已注销)';
            $value['employee_user_name'] = $_memberInfoList[$employeeUserId]['username'] ?? $employeeUserId.'(已注销)';
            $value['dependents_user_mobile'] = $_memberInfoList[$dependentsUserId]['mobile'] ?? $dependentsUserId.'(已注销)';
            $value['dependents_user_name'] = $_memberInfoList[$dependentsUserId]['username'] ?? $dependentsUserId.'(已注销)';
            // 会员的已使用额度
            $usedUserLimitData = $employeePurchaseActivityService->getUsedUserTotalLimitData($filter['company_id'], $filter['purchase_id'], $dependentsUserId);
            $value['dependents_used_limitfee'] = $usedUserLimitData['user_total_buy_fee'];
        }
        return $lists;

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
