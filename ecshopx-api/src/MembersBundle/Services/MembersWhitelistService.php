<?php

namespace MembersBundle\Services;

use MembersBundle\Entities\MembersWhitelist;
use CompanysBundle\Services\SettingService;

use Dingo\Api\Exception\ResourceException;

class MembersWhitelistService
{
    public $entityRepository;

    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MembersWhitelist::class);
    }

    public function createData($data)
    {
        $info = $this->entityRepository->getInfo(['mobile' => $data['mobile'], 'company_id' => $data['company_id']]);
        if ($info) {
            throw new ResourceException("该手机号已被使用");
        }

        return $this->entityRepository->create($data);
    }

    /**
     * 校验会员是否在白名单中
     * @param $companyId:企业ID
     * @param $mobile:会员手机号
     * @return bool
     */
    public function checkWhitelistValid($companyId, $mobile, &$msg)
    {
        // 查询白名单设置
        $settingService = new SettingService();
        $setting = $settingService->getWhitelistSetting($companyId);
        // 开启后，验证白名单
        if ($setting['whitelist_status'] == true) {
            $filter = [
                'company_id' => $companyId,
                'mobile' => $mobile,
            ];
            $count = $this->count($filter);
            if ($count <= 0) {
                $msg = $setting['whitelist_tips'];
                return false;
            }
        }

        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
