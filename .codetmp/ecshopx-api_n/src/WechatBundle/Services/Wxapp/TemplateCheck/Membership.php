<?php

namespace WechatBundle\Services\Wxapp\TemplateCheck;

/**
 * 源源客会员小程序
 * 参数配置类
 */
class Membership
{
    /**
     * 保存配置参数
     */
    public function check($authorizerAppId, $wxaAppId, $templateName, $wxaName)
    {
        return true;
    }

    public function checkPermission($authorizerAppId)
    {
        return true;
    }
}
