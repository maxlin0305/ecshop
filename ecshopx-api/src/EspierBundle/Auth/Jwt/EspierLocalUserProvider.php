<?php

namespace EspierBundle\Auth\Jwt;

use AliBundle\Factory\MiniAppFactory;
use CompanysBundle\Services\PushMessageService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Exception;
use Illuminate\Support\Facades\Http;
use MembersBundle\Entities\MembersAddress;
use MembersBundle\Repositories\MembersInfoRepository;
use MembersBundle\Repositories\MembersRepository;
use MembersBundle\Services\MemberService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

use CompanysBundle\Ego\GenericUser as GenericUser;
use CompanysBundle\Services\CompanysService;

use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberRegSettingService;
use WechatBundle\Services\OfficialAccountService;
use WechatBundle\Services\OpenPlatform;

use Overtrue\Socialite\SocialiteManager;
use CompanysBundle\Services\Shops\ProtocolService;
use MembersBundle\Services\MembersProtocolLogService;

class EspierLocalUserProvider implements UserProvider
{
    protected $openPlatform;

    /**
     * 会员服务
     * @var MemberService
     */
    public $memberService;

    /**
     * undocumented function
     *
     * @return void
     */
    public function __construct($app, $config)
    {
        $this->openPlatform = new OpenPlatform();
        $this->memberService = new MemberService();
    }

    /**
     * 中间件token认证。通过用户的唯一标识符检索用户
     *
     * @param  mixed  $identifier 用户唯一标识id
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = $this->getUserLoginInfo($identifier);

        return $this->getGenericUser($user);
    }

    /**
     * 登陆。通过给定凭据检索用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $this->getCompanyId($credentials);

        $authType = $credentials['auth_type'] ?? 'local';

        switch ($authType) {
            // 用户名密码登陆
            case "local":
                if (!config('common.system_is_saas')) {
                    $companyId = config('common.system_companys_id');
                } elseif (config('common.system_main_companys_id')) {
                    $companyId = config('common.system_main_companys_id');
                } else {
                    $companyId = $credentials['company_id'];
                }
                $mobile = $credentials['username'];
                $password = isset($credentials['password']) ? $credentials['password'] : '';
                $check_type = (isset($credentials['check_type']) && $credentials['check_type']) ? $credentials['check_type'] : 'password';
                $vcode = isset($credentials['vcode']) ? $credentials['vcode'] : '';

                $autoRegister = (bool)($credentials["auto_register"] ?? false);
                $silent = (bool)($credentials["silent"] ?? false);
                // 验证密码，返回会员部分信息
                $user = $this->checkUser($companyId, $mobile, $password, $check_type, $vcode, $autoRegister, $silent);
                break;
            // 小程序授权登陆方式
            case "wxapp":
                $user = $this->preLogin($credentials);
                break;
            // 小程序授权登陆方式 (微信，pc授权登录)
            case "oauth":
                $user = $this->preOauthLogin($credentials);
                break;
            // 微信内服务号网页授权登陆方式
            case "wx_offiaccount":
                $user = $this->preOffiaccountLogin($credentials);
                break;
            // pc微信扫码登录
            case "pc_wxqrcode":
                $user = $this->prewxQrcodeLogin($credentials);
                break;
            case "aliapp":
                $user = $this->preAliMiniAppLogin($credentials);
                break;
            case "thirdapp":
                $user = $this->preThirdAppLogin($credentials);
                break;
            default:
                $user = [];
        }


       if (!empty($user['app_member_id']) && $user['app_member_id'] != ''){
           $appMemberId = $user['app_member_id'];
           $request_url = "https://tinside.smtengo.com"."/v1/api/member/info/{$appMemberId}";
//            $request_url = config('common.zgj_app_url')."/v1/api/member/info/{$appMemberId}";
           $header_param = [
               'Content-Type'  =>'application/json;charset=utf-8' ,
               'Accept'        => "application/json",
               'appKey'        => config('common.zgj_app_key'),
               'appSecret'     => config('common.zgj_app_secret'),
           ];
           try{
               app('log')->debug("request_url=>$request_url");
               app('log')->debug("header_param=>",$header_param);
               $response = $this->getCurl($request_url, $header_param);
//                app('log')->debug("response=>{$user['user_id']}", json_encode($response));
               if ($response && $response['status'] == 200){
                   $model = $response['model'];
                   //更新用户信息
                   $memberRepository = app('registry')->getManager('default')->getRepository(Members::class);
                   $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
                   $membersAddressRepository = app('registry')->getManager('default')->getRepository(MembersAddress::class);


//            [faceUrl] => 头像
//            [nickName] =昵称
//            [email] => 邮箱
//            [phone] => 手机号
//            [annualIncome] => 年收入

//            [firstName] => 名字
//            [lastName] => 姓

//            [gender] => 性别[PREFER_NOT_ANSWER]
//            [vocation] => 职业
//            [birthday] => 生日
//            [maritalStatus] => 用户状态[MARRIED]
//            [gender] => 性别[PREFER_NOT_ANSWER]

                   $updateMembers = [
                       'mobile' => $model['phone'],
                       'email' => $model['email'],
                   ];
                   $updateMembersInfo = [
                       'username' => $model['nickName'],//昵称
                       'mobile' => $model['phone'],//手机号
                       'email' => $model['email'],//年收入
                       'income' => $model['annualIncome'],//年收入
                       'industry' => $model['vocation'],//职业
                       'avatar' => $model['faceUrl'],
                       'sex' => ['MALE' => 1, 'FEMALE' => 2, 'PREFER_NOT_ANSWER' => 0][$model['gender']] ?? 0
                   ];
                   $addUserAddressList = [];
                   //获取用户列表进行
                   $userAddressList = $membersAddressRepository->lists(['user_id'=>$user['user_id']]);
                   $smartButlerAddressList = [];
                   foreach ($userAddressList['list'] as $v){
                       if ($v['smart_butler_address'] != ''){
                           $smartButlerAddressList[] = $v['smart_butler_address'];
                       }
                   }
                   foreach ($model['address'] as $key => $v) {
                       $addressInfo = "{$v['city']}{$v['district']}{$v['street']}{$v['address']}";
                       if (!in_array($addressInfo, $smartButlerAddressList)) {
//                            $geocode = $this->getLngAndLat($addressInfo);
//                            if (!$geocode) {
//                                $location = $geocode[0]['geometry']['location']??[];
//                                if (!isset($location['lat'], $location['lng'])) {
//                                    throw new Exception('地區轉換錯誤,請換個地址！'.json_encode($geocode));
//                                }
//                            }

                           if ($key === 0) {
                               //更新用户地址表
                               $updateMembersInfo['address'] = $addressInfo;
                               $updateMembersInfo['lng'] = $location['lng']??0;
                               $updateMembersInfo['lat'] = $location['lat']??0;
                           }
                           //获取经纬度
                           $membersAddressRepository->create([
                               'company_id' => 1,
                               'user_id' => $user['user_id'],
                               'username' =>  $model['nickName'] == ''?'-':$model['nickName'],//$model['nickName'] || '-',//姓名
                               'telephone' => $model['phone'],//手机号码
                               'area' => null,//手机号码
                               'province' => $v['city']??'-',//
                               'city' => $v['district']??'-',//
                               'county' => $v['street']??'-',//
                               'adrdetail' => $v['address'] == ''?'-':$v['address'],//
                               'smart_butler_address' => $addressInfo,
                               'lng' => $location['lng']??0,
                               'lat' => $location['lat']??0,
                           ]);
                       }
                   }

                   $memberRepository->updateBy(['user_id' => $user['user_id']],$updateMembers);
                   $membersInfoRepository->updateBy(['user_id' => $user['user_id']],$updateMembersInfo);


                   app('log')->debug('自动更新用户信息$updateMembers user_id ==> '.$user['user_id'],$updateMembers);
                   app('log')->debug('自动更新用户信息$updateMembersInfo user_id ==> '.$user['user_id'],$updateMembersInfo);
               }
           }catch (\Exception $exception){
               app('log')->debug('自动更新用户信息错误'.$exception->getMessage().$exception->getLine());
           }
       }
        if ($user && $user['user_id'] > 0) {
            // 登录成功代表用户接收隐私隐私协议
            $protocols = (new ProtocolService($user['company_id']))->get([ProtocolService::TYPE_MEMBER_REGISTER, ProtocolService::TYPE_PRIVACY]);
            $membersProtocolLogService = new MembersProtocolLogService();
            foreach ($protocols as $protocol) {
                if (!isset($protocol['digest'])) {
                    continue;
                }
                $acceptLog = [
                    'company_id' => $user['company_id'],
                    'user_id' => $user['user_id'],
                    'digest' => $protocol['digest'],
                ];
               app('log')->debug('登录成功代表用户接收隐私隐私协议参数'.var_export($acceptLog,1));
               $membersProtocolLogService->create($acceptLog);
            }
        }

        return $this->getGenericUser($user);
    }

    /**
     * 根据 origin 识别 company_id .
     *
     * @param  array  $credentials
     * @return boolean
     */
    private function getCompanyId(&$credentials)
    {
        if (isset($credentials['company_id']) && $credentials['company_id']) {
            return true;
        }

        if (!isset($credentials['origin']) or !$credentials['origin']) {
            return false;
        }

        $originDomain = str_replace(['http://', 'https://'], '', $credentials['origin']);
        $companyService = new CompanysService();
        $companyInfo = $companyService->getCompanyInfoByDomain($originDomain);
        if ($companyInfo) {
            $credentials['company_id'] = $companyInfo['company_id'] ?? '';
        }

        return true;
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (!empty($user)) {
            return new GenericUser($user);
        }
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return true;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
    }

    // 小程序预登陆，如果登陆成功则成功，否则是需要注册
    private function preLogin($inputData)
    {
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少小程序ID');
        }

        // 判断inputData里是否给了openid，如果给了，则外部已经根据微信code获取到了openid,防止两次传入code，解析失败
        if (isset($inputData['openid']) && $inputData['openid']) {
            $res = [
                'openid' => $inputData['openid'],
                'unionid' => $inputData['unionid'] ?? '',
            ];
        } else {
            //调用微信获取sessionkey接口，返回session_key,openid,unionid
            $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
            $res = $app->auth->session($inputData['code']);
        }

        if (!isset($res['openid']) || !$res['openid']) {
            throw new ResourceException('小程序登陆获取信息失败，请重试！');
        }
        // 如果小程序没有绑定过开放平台，则将unionid的值改成openid同样的值
        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        $userService = new UserService(new WechatUserService());
        $companyId = $inputData['company_id'] ? $inputData['company_id'] : $this->openPlatform->getCompanyId($inputData['appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        $wechatuser = $userService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid'], 'company_id' => $companyId]);
        if (!$wechatuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $res['unionid'], 'company_id' => $companyId]);
        if (!$user) {
            throw new ResourceException('登录出错，请联系服务商！');
        }

        if (!isset($user['user_id']) || (isset($user['user_id']) && !$user['user_id'])) {
            throw new ResourceException('请注册！');
        }

        // 迁移模式，让跳转到授权用户信息页
        if (config('common.transfer_mode') && ($wechatuser['need_transfer'] == 1)) {
            throw new ResourceException('请您重新授权用户信息！');
        }

        // $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);

        $result = [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'],
            'app_member_id' => $user['app_member_id'],
            // 'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            // 'wxapp_appid' => $user['authorizer_appid'],
            // 'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $res['openid'],
            // 'nickname' => $user['nickname'] ?? '',
            // 'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            // 'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            // 'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : ($user['sex'] ?? 0),
            // 'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 微信，pc授权登录
    private function preOauthLogin($inputData)
    {
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少参数，获取用户信息失败！');
        }
        if (!isset($inputData['openid'])) {
            throw new ResourceException('小程序信息错误，请联系服务商！');
        }
        $userService = new UserService(new WechatUserService());
        $wechatuser = $userService->getSimpleUser(['open_id' => $inputData['openid'], 'authorizer_appid' => $inputData['appid']]);
        $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        if (isset($wechatuser['unionid']) && $wechatuser['unionid']) {
            $user = $userService->getUserInfo(['open_id' => $inputData['openid'], 'unionid' => $wechatuser['unionid']]);
        } else {
            throw new ResourceException('请授权！');
        }

        $redis = app('redis')->connection('members');
        $key = 'member:oauth:login:' . $inputData['token'];
        $info = json_decode($redis->get($key), true);
        if (!$info) {
            throw new ResourceException('登录失败');
        }
        if ($info['union_id'] != $wechatuser['unionid']) {
            throw new ResourceException('用户错误');
        }

        // $companyId = $this->openPlatform->getCompanyId($user['authorizer_appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);

        $memberInfo = [];
        if (!isset($user['user_id']) || (isset($user['user_id']) && !$user['user_id'])) {
            throw new ResourceException('请注册！');
        }
        $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);

        return [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'],
            'app_member_id' => $user['app_member_id'] ?? 0,
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            'wxapp_appid' => $user['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'openid' => $inputData['openid'],
            'nickname' => $user['nickname'] ?? '',
            'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : $user['sex'],
            'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'offline_card_code' => $memberInfo['offline_card_code'] ?? '',
            'operator_type' => 'user',
        ];
    }
    public function prewxQrcodeLogin($inputData)
    {
        if (empty($inputData['company_id'])) {
            throw new ResourceException('缺少company_id参数，获取用户信息失败！');
        }
        if (empty($inputData['appid'])) {
            throw new ResourceException('缺少appid参数，获取用户信息失败！');
        }
        $config = [
            'wechat' => [
                'client_id' => $inputData['appid'],
                'client_secret' => $inputData['secret'],
                'redirect' => $inputData['url']
            ],
        ];

        $socialite = new SocialiteManager($config);
        // $oauth = $socialite->driver('wechat')->getAccessToken($inputData['code']);
        $oauth = $socialite->driver('wechat')->user();
        $res = $oauth->getOriginal();
        if (!isset($res['openid'])) {
            throw new ResourceException('公众号授权信息错误，请联系服务商！');
        }
        // 记录千人千码参数
        $res['source_id'] = isset($inputData['source_id']) ? trim($inputData['source_id']) : 0;
        $res['monitor_id'] = isset($inputData['monitor_id']) ? trim($inputData['monitor_id']) : 0;
        $res['inviter_id'] = isset($inputData['inviter_id']) ? trim($inputData['inviter_id']) : 0;
        $res['source_from'] = isset($inputData['source_from']) ? trim($inputData['source_from']) : 'default';

        $userService = new UserService(new WechatUserService());
        // $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        // $woaAppid = $this->openPlatform->getWoaAppidByCompanyId($companyId);
        $companyId = $inputData['company_id'];
        $woaAppid = $inputData['appid'];

        if (!$userInfo = $this->createFans($companyId, $inputData['appid'], $res)) {
            throw new ResourceException('Invalid store wxapp user.');
        }
        $wechatuser = $userService->getSimpleUser(['open_id' => $res['openid'], 'authorizer_appid' => $inputData['appid'], 'company_id' => $companyId]);
        if (!$wechatuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $user = $userService->getUserInfo(['open_id' => $res['openid'], 'unionid' => $wechatuser['unionid'], 'company_id' => $companyId]);
        if (!$user) {
            throw new ResourceException('登录出错，请联系服务商！');
        }

        if (isset($user['user_id']) && $user['user_id']) {
            $memberInfo = $this->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);
        } else {
            $user['user_id'] = 0;
        }

        $result = [
            'id' => $user['user_id']."_espier_".$user['open_id']."_espier_".$user['unionid'],
            'user_id' => $user['user_id'] ?? 0,
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            'wxapp_appid' => $user['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $user['unionid'],
            'app_member_id' => $user['app_member_id'],
            'openid' => $res['openid'],
            'nickname' => $user['nickname'] ?? '',
            'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : ($user['sex'] ?? 0),
            'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'offline_card_code' => $memberInfo['offline_card_code'] ?? '',
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 微信内服务号网页授权登录
    private function preOffiaccountLogin($inputData)
    {
        /** 根据code去获取用户信息 **/

        if (empty($inputData["company_id"])) {
            throw new ResourceException("缺少参数！");
        }
        if (empty($inputData["code"])) {
            throw new ResourceException("code error");
        }

        // 获取微信公众号的对象
        $app = (new OpenPlatform())->getWoaApp([
            "company_id" => $inputData["company_id"],
            "trustlogin_tag" => $inputData["trustlogin_tag"] ?? "weixin", // weixin
            "version_tag" => $inputData["version_tag"] ?? "touch" // touch
        ]);

        // 获取公众号的appid
        $woaAppid = $app->oauth->getClientId();

        // 根据code获取微信里的用户信息
        $res = (new OfficialAccountService($app))->getUserInfoByCode($inputData["code"]);

        if (!isset($res['openid']) || !$res['openid']) {
            throw new ResourceException('小程序登陆获取信息失败，请重试！');
        }
        // 如果小程序没有绑定过开放平台，则将unionid的值改成openid同样的值
        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        /** 将微信的用户信息存入表中 **/

        // 头像
        $res["headimgurl"] = $res["avatar"] ?? "";
        // 记录千人千码参数
        $res['source_id'] = isset($inputData['source_id']) ? trim($inputData['source_id']) : 0;
        $res['monitor_id'] = isset($inputData['monitor_id']) ? trim($inputData['monitor_id']) : 0;
        $res['inviter_id'] = isset($inputData['inviter_id']) ? trim($inputData['inviter_id']) : 0;
        $res['source_from'] = isset($inputData['source_from']) ? trim($inputData['source_from']) : 'default';
        // 创建微信用户信息
        $info = $this->createFans($inputData["company_id"], $woaAppid, $res);
        if (!$info) {
            throw new ResourceException('Invalid store wxapp user.');
        }

        // 微信用户的基本信息
        $wechatUser = $info["wechatuser"] ?? [];

        // 整理token内的数据
        $result = [
            'id' => 0 . "_espier_" . $wechatUser['open_id'] . "_espier_" . $wechatUser['unionid'],
            'user_id' => 0,
            'disabled' => 0,
            'company_id' => $inputData["company_id"],
            'wxapp_appid' => $wechatUser['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'unionid' => $wechatUser['unionid'],
            'openid' => $wechatUser['open_id'],
            'nickname' => $res['nickname'] ?? '',
            'mobile' => '',
            'username' => $res['username'] ?? '',
            'sex' => $res['sex'] ?? 0,
            'user_card_code' => '',
            'offline_card_code' => '',
            'operator_type' => 'user',
            "is_new" => (int)($info["is_new"] ?? 1),
        ];
        // 如果是老用户，则获取用户信息
        if (!$result["is_new"]) {
            $user = $info["memberInfo"] ?? [];
            $result = array_merge($result, [
                'id' => $user['user_id'] . "_espier_" . $wechatUser['open_id'] . "_espier_" . $wechatUser['unionid'],
                'user_id' => $user['user_id'] ?? 0,
                'app_member_id' => $user['app_member_id'] ?? 0,
                'disabled' => $user['disabled'] ?? 0,
                'mobile' => $user['mobile'] ?? '',
                'user_card_code' => $user['user_card_code'] ?? '',
                'offline_card_code' => $user['offline_card_code'] ?? '',
            ]);
        }

        return $result;
    }

    private function preThirdAppLogin($inputData)
    {
        if (empty($inputData['company_id'])) {
            throw new ResourceException('缺少参数！');
        }
        $params = [
            'email'	=> urldecode($inputData['email']??''),
            'mobile'	=> $inputData['mobile']??'',
            'memberId'	=> $inputData['memberId']??'',
            'timestamp'	=> $inputData['timestamp']??'',
            'appCode'	=>	$inputData['appCode']??'',
            'sign'	=>	$inputData['sign']??''
        ];
        app('log')->debug('thirdapp：'.var_export($params,1));
        if(empty($params['email']) && empty($params['mobile'])){
            throw new ResourceException('请注册！');
        }
//        $this->__thirdAppLoginCheck($params);
        $companyId = $inputData['company_id'];
        $user_type = 'zgjapp';

        /** @var MembersRepository $membersRepository */
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $userEntity = $membersRepository->findOneBy(['company_id' => $companyId, 'app_member_id' => $params['memberId']]);
        //memberId作为唯一
        if (empty($userEntity)){
            $member_info = [
                'mobile'    => $inputData['mobile']??null,
                'email'    => $params['email']??null,
//                'username'    => $inputData['mobile']??null,
                'company_id'    => $companyId,
                'source_from'    => 'openapi',
                'unionid'    => $params['memberId'],
                'user_type'    => $user_type,
                'auth_type'    => $inputData['auth_type'],
                'api_from'    => $user_type,
                'open_id'    => $params['memberId'],
                'app_member_id'    => $params['memberId'],
            ];
            $membersService = new MemberService();
            $appUser = $membersService->createMember($member_info,false);
            $appUser['unionid'] = $params['memberId'];
            $appUser["is_new"] = 1;
        }else{
            $appUser = $this->memberService->membersRepository->getDataByEntity($userEntity);
            $appUser['unionid'] = $appUser['app_member_id']??null;
            $appUser["is_new"] = 0;
        }
        $memberRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $memberInfo = $memberRepository->get(['user_id' => $appUser['user_id'], 'company_id' => $companyId]);

        app('log')->debug('thirdapp-1：'.var_export($memberInfo,1));
        $update = [];
        if($params['mobile'] && $params['mobile'] != $memberInfo['mobile']){
            // 自动更新手机号
            $update[ 'mobile' ] = $params['mobile'];
        }
        if($params['email'] && $params['email'] != $memberInfo['email']){
            // 自动更新手机号
            $update[ 'email' ] = $params['email'];
        }
        if ($update){
            try{
                $memberRepository->updateBy(['user_id' => $appUser['user_id'], 'company_id' => $companyId],$update);
            }catch (\Exception $exception){
                app('log')->debug('自动更新用户信息错误');
            }
        }
//
        $result = [
            'id' => $memberInfo['user_id'] . "__espier_companyid_espier_" . $companyId,
            "user_id" => $memberInfo["user_id"],
            "company_id" => $memberInfo["company_id"],
            "app_member_id" => $memberInfo["app_member_id"],
            "operator_type" => "user",
            "unionid" => $appUser["unionid"] ?? null,
            "openid" => $appUser["open_id"] ?? null,
            "is_new" => (int)($appUser["is_new"] ?? 0), // 是否为新用户 【0 老用户】【1 新用户】
        ];
        return $result;
    }

    private function preAliMiniAppLogin($inputData)
    {
        if (empty($inputData['company_id'])) {
            throw new ResourceException('缺少参数！');
        }

        if (isset($inputData['alipay_user_id']) && $inputData['alipay_user_id']) {
            $alipayUserId = $inputData['alipay_user_id'];
        } else {
            if (empty($inputData['code'])) {
                throw new ResourceException('缺少参数！');
            }
            $app = (new MiniAppFactory())->getApp($inputData['company_id']);
            $oauthData = $app->getFactory()->base()->oauth()->getToken($inputData['code'])->toMap();
            if (!isset($oauthData['user_id'])) {
                throw new ResourceException('小程序授权信息错误，请联系服务商！');
            }
            $alipayUserId = $oauthData['user_id'];
        }
        $companyId = $inputData['company_id'];

        $membersAssoRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $alipayuser = $membersAssoRepository->get(['company_id' => $companyId, 'user_type' => 'ali', 'unionid' => $alipayUserId]);
        if (!$alipayuser) {
            throw new ResourceException('请您检查是否已经授权！');
        }

        $memberRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $memberInfo = $memberRepository->get(['user_id' => $alipayuser['user_id'], 'company_id' => $companyId]);
        if (empty($memberInfo['user_id'])) {
            throw new ResourceException('请注册！');
        }

        $result = [
            'id' => $memberInfo['user_id'] . "_espier_alipay" . "_espier_" . $alipayuser['unionid'],
            'user_id' => $memberInfo['user_id'],
            'app_member_id' => $memberInfo['app_member_id'],
            'company_id' => $companyId,
            'alipay_appid' => $memberInfo['alipay_appid'],
            'alipay_user_id' => $alipayuser['unionid'],
            'operator_type' => 'user',
        ];
        return $result;
    }

    // 创建粉丝，已有则直接返回粉丝信息
    private function createFans($companyId, $appId, $userInfo)
    {
        $params = ['open_id' => $userInfo['openid'], 'unionid' => $userInfo['unionid']];
        if (!$params['open_id'] || !$params['unionid']) {
            throw new ResourceException('用户登录失败！');
        }

        $params['company_id'] = $companyId;
        $params['headimgurl'] = $userInfo['headimgurl'];
        $params['country'] = $userInfo['country'];
        $params['province'] = $userInfo['province'];
        $params['city'] = $userInfo['city'];
        $params['sex'] = $userInfo['sex'];
        $params['language'] = $userInfo['language'];
        $params['nickname'] = $userInfo['nickname'];

        $params['inviter_id'] = $userInfo['inviter_id'];
        $params['source_from'] = $userInfo['source_from'];
        $params['source_id'] = $userInfo['source_id'];
        $params['monitor_id'] = $userInfo['monitor_id'];

        $userService = new UserService(new WechatUserService());
        return $userService->createWxappFans($appId, $params);
    }

    /**
     * 认证获取用户
     * @$identifier  user_id."_espier_".open_id."_espier_".unionid
     */
    private function getUserLoginInfo($identifier)
    {
        if (!strpos($identifier, '_espier_')) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }
        list($userId, $openid, $unionid) = explode('_espier_', $identifier);

        $companyId = 0;
        if ($openid && $unionid && $openid != 'companyid') {
            if ($openid == 'alipay') {
                $membersAssoRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
                $alipayuser = $membersAssoRepository->get(['user_id' => $userId, 'user_type' => 'ali', 'unionid' => $unionid]);
                if ($alipayuser) {
                    $companyId = $alipayuser['company_id'];
                    $alipayUserId = $alipayuser['unionid'];
                }
            } else {
                $userService = new UserService(new WechatUserService());
                $user = $userService->getUserInfo(['unionid' => $unionid, 'open_id' => $openid]);
                $openPlatform = new OpenPlatform();
                if (isset($user['authorizer_appid']) && $user['authorizer_appid']) {
                    $companyId = $openPlatform->getCompanyId($user['authorizer_appid']);
                    $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
                }
            }
        } else {
            $companyId = $unionid;
        }

        if (!$companyId) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }

        $memberInfo = $this->getMemberInfo(['user_id' => $userId, 'company_id' => $companyId]);
        if (!$memberInfo) {
            throw new UnauthorizedHttpException('', '获取用户信息出错');
        }

        $result = [
            'id' => $memberInfo['user_id'],
            'user_id' => $memberInfo['user_id'],
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $memberInfo['company_id'],
            'wxapp_appid' => $user['authorizer_appid'] ?? '',
            'woa_appid' => $woaAppid ?? '',
            'open_id' => $user['open_id'] ?? '',
            'unionid' => $user['unionid'] ?? '',
            'nickname' => $user['nickname'] ?? '',
            'headimgurl' => $user['headimgurl'] ?? '',
            'grade_id' => $memberInfo['grade_id'],
            'mobile' => $memberInfo['mobile'],
            'username' => $memberInfo['username'] ?? '',
            'user_card_code' => $memberInfo['user_card_code'],
            'offline_card_code' => $memberInfo['offline_card_code'],
            'operator_type' => 'user',
            'inviter_id' => $memberInfo['inviter_id'],
            'source_id' => $memberInfo['source_id'],
            'monitor_id' => $memberInfo['monitor_id'],
            'latest_source_id' => $memberInfo['latest_source_id'],
            'latest_monitor_id' => $memberInfo['latest_monitor_id'],
            'chief_id' => 0,
            'alipay_appid' => $memberInfo['alipay_appid'] ?? '',
            'alipay_user_id' => $alipayUserId ?? '',
        ];
        if ((config('common.product_model') != 'in_purchase') && !empty($result['user_id'])) {
            $chief = (new \CommunityBundle\Services\CommunityChiefService())->getChiefInfoByUserID($result['user_id']);
            $result['chief_id'] = $chief['chief_id'] ?? 0;
        }
        return $result;
    }

    /**
     * 获取会员信息
     * @param  array $filter 查询条件
     * @return array         会员数据
     */
    private function getMemberInfo($filter)
    {
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $member = $membersRepository->get($filter);
        $result = $member;
        if ($member && isset($member['user_id']) && $member['user_id']) {
            $memberFilter = [
                'company_id' => $member['company_id'],
                'user_id' => $member['user_id']
            ];
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
            $info = $membersInfoRepository->getInfo($memberFilter);
            // 如果存在用户相信信息
            if (!empty($info)) {
                $info["other_params"] = (array)jsonDecode($info["other_params"] ?? null);
                // 前端透传的参数
                $info["isGetWxInfo"] = (bool)($info["other_params"]["isGetWxInfo"] ?? false);
            }
            $result = array_merge($member, $info);
        }
        return $result;
    }

    /**
     * 验证用户名密码
     * @param int $company_id 公司的企业id
     * @param string $mobile 手机号
     * @param string $password 密码
     * @param string $check_type 短信验证码的验证码类型
     * @param string $vcode 短信验证码
     * @param bool $autoRegister 是否自动注册【true 自动注册】【false 不自动注册并抛出异常】
     * @return array
     * @throws \Exception
     */
    private function checkUser($company_id, $mobile, $password, $check_type = 'password', $vcode = '', bool $autoRegister = false, bool $silent = false)
    {
        /** @var MembersRepository $membersRepository */
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
//        if (filter_var($mobile, FILTER_VALIDATE_EMAIL)) {
//            $userEntity = $membersRepository->findOneBy(['company_id' => $company_id, 'email' => fixedencrypt($mobile)]);
//        } else {
//            $userEntity = $membersRepository->findOneBy(['company_id' => $company_id, 'mobile' => fixedencrypt($mobile)]);
//        }
        $userEntity = $membersRepository->findOneBy(['company_id' => $company_id, 'email' => fixedencrypt($mobile)]);
        if (empty($userEntity)){
            $userEntity = $membersRepository->findOneBy(['company_id' => $company_id, 'mobile' => fixedencrypt($mobile)]);
        }

        // 表单验证最优先
        switch ($check_type) {
            case "mobile":
                if (!(new MemberRegSettingService())->checkSmsVcode($mobile, $company_id, $vcode, 'login')) {
                    throw new ResourceException('短信驗證碼錯誤');
                }
                break;
            case "password":
                if ($userEntity && !$this->checkPassword($password, $userEntity->getPassword())) {
                    throw new ResourceException('用戶名或密碼錯誤');
                }
                break;
            default:
                throw new ResourceException("验证类型有误！");
        }

        // 判断手机是否存在
        if (empty($userEntity)) {
            // 如果是自动创建，则直接创建用户
            if ($autoRegister) {
                $userInfo = (new MemberService())->createMember([
                    "mobile" => $mobile,
                    "region_mobile" => $mobile,
                    "mobile_country_code" => "86",
                    "company_id" => $company_id,
                    "wxa_appid" => "", // 小程序appid
                    "authorizer_appid" => "", // 公众号id
                    "sex" => 0,
                    "username" => randValue(8),
                    "avatar" => "",
                    "email" => "",
                    "password" => $password,
                    "api_from" => "h5app",
                    "auth_type" => "local",
                    "user_type" => "local",
                    "unionid" => "",
                    "open_id" => "",
                    "force_password" => 1
                ]);
            } else {
                if (!$silent) {
                    throw new ResourceException('手机号码未注册，请注册后登陆');
                }
                $userInfo = [
                    "user_id" => null,
                    "company_id" => $company_id,
                    "grade_id" => null,
                    "mobile" => null,
                    "user_card_code" => null,
                    "offline_card_code" => null,
                    "disabled" => null,
                    "inviter_id" => null,
                    "source_id" => null,
                    "monitor_id" => null,
                    "latest_source_id" => null,
                    "latest_monitor_id" => null,
                ];
            }
            $userInfo["is_new"] = 1;
        } else {
            $userInfo = $this->memberService->membersRepository->getDataByEntity($userEntity);
            $userInfo["is_new"] = 0;
        }

        // 生成token中的数据
        return $this->memberService->getTokenData($userInfo);
    }

    private function checkPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    private function __thirdAppLoginCheck($params){

        if (!isset($params['timestamp'])) {
            throw new ResourceException("timestamp error！");
        }
        $now = intval(microtime(true)*1000);
        //判断timestamp是否在合法时间范围内 允许最大时间误差10分钟
        if ($params['timestamp'] > $now) {
            throw new ResourceException("timestamp error！");
        }

        if ($now - $params['timestamp'] > 60 * 10 * 1000) {
            throw new ResourceException("timestamp error！");
        }
        if (!isset($params['sign']) || !$params['sign']) {
            throw new ResourceException("sign error！");
        }

        $sign = trim($params['sign']);

        unset($params['sign']);

        $token = config('common.zgj_app_sign_token');

        app('log')->debug('第三方app免密登陆校验token:' . $token);
        app('log')->debug('第三方app免密登陆sign:' . $sign);
        app('log')->debug('第三方app免密登陆，本地sign:' . self::gen_sign($params, $token));
        app('log')->debug('第三方app免密登陆request_params:' . var_export($params, 1));

        if (!$sign || $sign != self::gen_sign($params,$token) )
        {
            throw new ResourceException("sign error！");
        }
    }

    /**
     * 生成签名
     * -------------------------------------------------------------
     * @param   array $params 签名参数
     * @param   string $token 签名私钥
     * @return  string
     * @todo
     * -------------------------------------------------------------
     * 例如：将函数assemble得到的字符串md5加密，然后转为大写，尾部连接密钥$token组成新的字符串，再md5,结果再转为大写
     */
    static function gen_sign($params,$token){
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$token));
    }

    /**
     * 组合签名参数
     * -------------------------------------------------------------
     * @param   array $params 签名参数
     * @return  string
     * @todo
     * -------------------------------------------------------------
     * 根据参数名称将你的所有请求参数按照字母先后顺序排序:
     * key + value .... key + value 对除签名和图片外的所有请求参数按key做的升序排列, value无需编码。
     * 例如：
     * 将foo=1,bar=2,baz=3 排序为bar=2,baz=3,foo=1 参数名和参数值链接后，得到拼装字符串bar2baz3foo1
     * -------------------------------------------------------------
     */
    static function assemble($params)
    {
        if(!is_array($params)){
            return null;
        }

        ksort($params,SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }



    /**
     * get请求
    */
    public static function getCurl($url,$headers){
        $headers_str = "";
        foreach ($headers as $key => $value) {
            $headers_str .= "$key: $value\r\n";
        }
        $options = [
            'http' => [
                'header' => $headers_str,
                'method' => 'GET',
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        app('log')->debug("response=>--".$response);
        if ($response === false) {
            return false;
        } else {
            // 打印响应
            return json_decode($response,true);
        }

    }





    /**
     * 地址转经纬度
     */
    public function getLngAndLat($address)
    {
        $key = config('GOOGLE_MAP_KEY',"AIzaSyBmSZouTYm8ViLN3MOrpFuCNvCJfBriaSs");
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'
        ));
        $response = curl_exec($curl);
        curl_close($curl);
//        $response = '{ "results" : [ { "address_components" : [ { "long_name" : "411", "short_name" : "411", "types" : [ "postal_code" ] }, { "long_name" : "新福里", "short_name" : "新福里", "types" : [ "administrative_area_level_3", "political" ] }, { "long_name" : "太平區", "short_name" : "太平區", "types" : [ "administrative_area_level_2", "political" ] }, { "long_name" : "台中市", "short_name" : "台中市", "types" : [ "administrative_area_level_1", "political" ] }, { "long_name" : "Taiwan", "short_name" : "TW", "types" : [ "country", "political" ] } ], "formatted_address" : "411, Taiwan, 台中市太平區", "geometry" : { "bounds" : { "northeast" : { "lat" : 24.1693703, "lng" : 120.8378002 }, "southwest" : { "lat" : 24.0403117, "lng" : 120.6995378 } }, "location" : { "lat" : 24.119605, "lng" : 120.7809676 }, "location_type" : "APPROXIMATE", "viewport" : { "northeast" : { "lat" : 24.1693703, "lng" : 120.8378002 }, "southwest" : { "lat" : 24.0403117, "lng" : 120.6995378 } } }, "place_id" : "ChIJO-rJaVA9aTQRMRKY334H_Rs", "types" : [ "postal_code" ] } ], "status" : "OK" }';

        $response = json_decode($response, true);
        if ($response == null || $response['status'] !== 'OK') {
            return false;
        }
        return $response['results'];

    }
}
