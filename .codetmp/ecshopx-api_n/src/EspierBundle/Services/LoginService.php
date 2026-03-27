<?php

namespace EspierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use MembersBundle\Http\FrontApi\V1\Action\Members;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Http\FrontApi\V1\Action\SalespersonController;
use Symfony\Component\HttpFoundation\ParameterBag;
use WechatBundle\Http\FrontApi\V1\Action\Wxapp;
use WechatBundle\Services\OpenPlatform;
use WorkWechatBundle\Entities\WorkWechatRel;
use PromotionsBundle\Services\EmployeePurchaseActivityService;
use MembersBundle\Services\MembersWhitelistService;
use AliBundle\Factory\MiniAppFactory;

class LoginService
{
    /**
     * 微信小程序的预登录
     * @param array $requestData
     * @param array $authData
     * @param int $defaultDistributorId
     * @return array
     * @throws ResourceException
     */
    public function wxappPreLogin($params): array
    {
        $errorMessage = validator_params($params, [
            'appid' => ['required', '缺少参数，登录失败！'],
            'code' => ['required', '缺少参数，登录失败！'],
            'iv' => ['required', '缺少参数，登录失败！'],
            'encryptedData' => ['required', '缺少参数，登录失败！'],
        ]);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $openPlatformService = new OpenPlatform();
        // 获取app
        $app = $openPlatformService->getAuthorizerApplication($params['appid']);
        // 获取session key，并且会返回unionid和openid
        $res = $app->auth->session($params['code']);
        $sessionKey = $res['session_key'] ?? null;
        $unionId = $res['unionid'] ?? null;
        $openId = $res['openid'] ?? null;
        empty($unionId) ? $unionId = $openId : null;

        // 验证参数
        if (empty($sessionKey)) {
            throw new ResourceException('用户登录失败！');
        }
        if (empty($openId)) {
            throw new ResourceException('小程序授权错误，请联系供应商！');
        }
        if (empty($unionId)) {
            throw new ResourceException('此小程序未关联开放平台，请联系供应商！');
        }

        // 获取手机号
        $mobileData = $app->encryptor->decryptData($sessionKey, $params['iv'], $params['encryptedData']);
        $regionMobile = $mobileData['phoneNumber'] ?? ''; // 带区号的手机号
        $mobile = $mobileData['purePhoneNumber'] ?? ''; // 不带区号的纯手机号
        $countryCode = $mobileData['countryCode'] ?? ''; // 区号
        // 获取手机号
        if (!$mobile) {
            throw new ResourceException('授权手机号失败');
        }

        $wechatUserService = new WechatUserService();

        // 迁移模式，刷新旧的unionid为新的unionid
        if (config('common.transfer_mode')) {
            $wechatUser = $wechatUserService->getSimpleUser(['open_id' => $openId, 'authorizer_appid' => $params['appid'], 'company_id' => $params['company_id']]);
            if ($wechatUser && ($wechatUser['unionid'] != $unionId)) {
                $filter = [
                    'company_id' => $params['company_id'],
                    'authorizer_appid' => $params['appid'],
                    'open_id' => $openId,
                ];
                $wechatUserService->updateUnionId($filter, $wechatUser['unionid'], $unionId);
            }
        }

        // 创建/更新微信用户
        $weChatUserData = [
            'company_id' => $params['company_id'],
            'company_id' => $params['company_id'],
            'open_id' => $openId,
            'unionid' => $unionId,
            // 记录千人千码参数
            'source_id' => $params['source_id'] ?? 0,
            'monitor_id' => $params['monitor_id'] ?? 0,
            'inviter_id' => $params['source_id'] ?? 0,
            'source_from' => $params['source_from'] ?? 'default',
        ];
        $wechatUserInfo = $wechatUserService->createWxappFans($params['appid'], $weChatUserData);

        $userType = 'wechat';

        // 查询一次是否存在用户
        $memberService = new MemberService();
        $member = $memberService->getInfoByMobile($params['company_id'], $mobile);
        $params['open_id'] = $openId;
        $params['unionid'] = $unionId;
        $params['mobile'] = $mobile;
        $params['user_type'] = $userType;
        if (!$member) {
            $member = $this->register($params);
        } else {
            $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $unionId, $member['user_id']);
            if (!$membersAssociation) {
                $member = $this->register($params);
            }
        }

        if (isset($params['salesperson_id']) && $params['salesperson_id']) {
            // 用户和导购的关联绑定
            $this->bindWithSalesperson($params['company_id'], $params['salesperson_id'], $member['user_id']);
        }

        return [
            'user_id' => $member['user_id'],
            'open_id' => $openId,
            'unionid' => $unionId,
        ];
    }

    public function aliappPreLogin($params) {
        $errorMessage = validator_params($params, [
            'code' => ['required', '缺少参数，登录失败！'],
            'encryptedData' => ['required', '缺少参数，登录失败！'],
        ]);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 换取授权访问令牌
        $app = (new MiniAppFactory())->getApp($params['company_id']);
        $oauthData = $app->getFactory()->base()->oauth()->getToken($params['code'])->toMap();
        if (!isset($oauthData['user_id'])) {
            throw new ResourceException('小程序授权信息错误，请联系服务商！');
        }

        // 解密获取手机号
        $decryptResult = $app->getFactory()->util()->aes()->decrypt($params['encryptedData']);
        $decryptData = json_decode($decryptResult, true);
        if (empty($decryptData['mobile'])) {
            throw new ResourceException('授权手机号失败');
        }
        $mobile = $decryptData['mobile'];


        $userType = 'ali';

        // 查询一次是否存在用户
        $memberService = new MemberService();
        $member = $memberService->getInfoByMobile($params['company_id'], $mobile);
        $membersAssociation = $memberService->getMembersAssociation($params['company_id'], $userType, $oauthData['user_id']);
        if (!$member || !$membersAssociation) {
            // 创建会员, 将用户信息添加至会员主表（members）
            $params['open_id'] = $oauthData['user_id'];
            $params['unionid'] = $oauthData['user_id'];
            $params['mobile'] = $mobile;
            $params['user_type'] = $userType;
            $params['alipay_appid'] = $app->getConfig()->getAppId();
            $member = $this->register($params);
        }

        if (isset($params['salesperson_id']) && $params['salesperson_id']) {
            // 用户和导购的关联绑定
            $this->bindWithSalesperson($params['company_id'], $params['salesperson_id'], $member['user_id']);
        }

        return [
            'user_id' => $member['user_id'],
            'alipay_user_id' => $oauthData['user_id'],
        ];
    }

    /**
     * 注册用户
     * @return array
     * @throws \Exception
     */
    protected function register($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $employeePurchaseActivityService = new EmployeePurchaseActivityService();
            $purchanseShareCode = $params['purchanse_share_code'] ?? '';
            if ($purchanseShareCode) {
                // 如果有分享码且分享码有效，先给分享码加锁
                $employeePurchaseActivityService->lockShareCode($params['company_id'], $purchanseShareCode);
            } else {
                // 检查白名单;
                $inWhitelist = (new MembersWhitelistService())->checkWhitelistValid($params['company_id'], $params['mobile'], $tips);
                if (!$inWhitelist) {
                    throw new ResourceException($tips);
                }
            }

            $memberService = new MemberService();
            $params['inviter_id'] = $params['inviter_id'] ?? 0;
            if (isset($params['uid']) && $params['uid']) {
                $memberInfo = $memberService->getMemberInfo([
                    'user_id' => $params['uid'],
                    'company_id' => $params['company_id']
                ]);
                if ($memberInfo) {
                    $params['inviter_id'] = $params['uid'];
                }
            } elseif (!$params['inviter_id'] && $params['user_type'] == 'wechat') {
                $wechatUser = (new WechatUserService())->getSimpleUserInfo($params['company_id'], $params['unionid']);
                $params['inviter_id'] = $wechatUser['inviter_id'] ?? 0;
                $params['source_from'] = $wechatUser['source_from'] ?? 'default';
            }

            // 创建用户
            $result = $memberService->createMember($params);

            // 绑定家属
            if ($purchanseShareCode) {
                $employeePurchaseActivityService->bindDependents($params['company_id'], $purchanseShareCode, $result['user_id']);
            }
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            //解锁邀请码
            if ($purchanseShareCode) {
                $employeePurchaseActivityService->unlockShareCode($params['company_id'], $purchanseShareCode);
            }

            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 让用户和导购做一个关联绑定
     * @return array|bool[]
     * @throws \Exception
     */
    public function bindWithSalesperson($companyId, $salespersonId, $userId)
    {
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);

        //查找用户已绑定的导购员
        $bound = $workWechatRepositories->getInfo([
            'user_id' => $userId,
            'is_bind' => 1,
            'company_id' => $companyId,
        ]);
        if ($bound) {
            if ($bound['salesperson_id'] == $salespersonId) {
                return true;
            }
            return false;
        }

        $filter = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
        ];
        $data = $workWechatRepositories->getInfo($filter);
        if ($data) {
            $result = $workWechatRepositories->updateOneBy($filter, ['is_bind' => 1]); //修改
        } else {
            $data = [
                'user_id' => $userId,
                'salesperson_id' => $salespersonId,
                'company_id' => $companyId,
                'is_bind' => 1
            ];
            $result = $workWechatRepositories->create($data);
        }

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
