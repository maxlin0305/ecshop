<?php

namespace MerchantBundle\Services;

use Dingo\Api\Exception\ResourceException;

use MerchantBundle\Entities\MerchantSettlementApply;

use MembersBundle\Services\MemberRegSettingService;

use CompanysBundle\Services\OperatorsService;

class MerchantSettlementApplyService
{
    public const APPLY_STEP = [
        'sign' => 1, // 已注册
        'apply_1' => 2, // 已填写第一步入驻信息
        'apply_2' => 3, // 已填写第二步商户信息
        'apply_3' => 4, // 已填写第三步证照信息
    ];

    public const AUDIT_STATUS = [
        'ongoing' => 1, // 审核中
        'succ' => 2, // 审核成功
        'fail' => 3, // 审核驳回
    ];

    /**
     * @var settlementApplyRepository
     */
    private $settlementApplyRepository;


    public function __construct()
    {
        $this->settlementApplyRepository = app('registry')->getManager('default')->getRepository(MerchantSettlementApply::class);
    }

    /**
     * 根据商户入驻id获取入驻信息
     * @param  string $account_id 商户入驻ID
     */
    public function getAccountInfo($account_id)
    {
        $accountInfo = $this->getInfoById($account_id);
        if ($accountInfo) {
            $result = [
                'account_id' => $accountInfo['id'],
                'company_id' => $accountInfo['company_id'],
                'mobile' => $accountInfo['mobile'],
                'operator_type' => 'user',
            ];
            return $result;
        }

        throw new \LogicException("获取登录信息出错!");
    }

    /**
     * 商户入驻，登录，如果没有账号，则进行注册后再登录
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function accountLogin($params)
    {
        $this->__checkParams($params);
        // 验证vcode是否正确
        $regSettinService = new MemberRegSettingService();
        if (!$regSettinService->checkSmsVcode($params['mobile'], $params['company_id'], $params['vcode'], 'merchant_login')) {
            throw new ResourceException('验证码错误');
        }

        $filter = [
            'company_id' => $params['company_id'],
            'mobile' => $params['mobile'],
        ];
        $accountInfo = $this->getInfo($filter);
        if (!$accountInfo) {
            $settingService = new MerchantSettingService();
            $setting = $settingService->getBaseSetting($params['company_id']);
            if ($setting['status'] == false) {
                throw new ResourceException('该平台现不支持商户入驻，请核实后再试');
            }
            $createData = [
                'company_id' => $params['company_id'],
                'mobile' => $params['mobile'],
                'is_agree_agreement' => true,
                'merchant_type_id' => 0,
                'audit_status' => self::AUDIT_STATUS['ongoing'],
                'source' => 'h5',
                'disabled' => true,
            ];
            $accountInfo = $this->create($createData);
        }

        $result = [
            'account_id' => $accountInfo['id'],
            'company_id' => $accountInfo['company_id'],
            'mobile' => $accountInfo['mobile'],
            'operator_type' => 'user',
        ];
        return $result;
    }

    /**
     * 商户入驻，登录，检查
     */
    private function __checkParams($params)
    {
        if (!$params['company_id'] ?? '') {
            throw new ResourceException('企业ID不能为空');
        }
        if (!$params['mobile'] ?? '') {
            throw new ResourceException('手机号不能为空');
        }
        if (!ismobile($params['mobile'])) {
            throw new ResourceException('请填写正确的手机号码');
        }
        if (!$params['vcode'] ?? '') {
            throw new ResourceException('短信验证码不能为空');
        }
    }

    /**
     * 根据商户入驻ID，获取当前步骤
     * @param  string $companyId 企业ID
     * @param  string $accountId 商户入驻ID
     * @return array            当前步骤数据
     */
    public function getSettlementApplyStep($companyId, $accountId)
    {
        $info = $this->getInfo(['company_id' => $companyId, 'id' => $accountId]);
        if (!$info) {
            throw new ResourceException('获取账号信息失败');
        }
        $result['step'] = self::APPLY_STEP['sign'];
        if ($info['license_url']) {
            $result['step'] = self::APPLY_STEP['apply_3'];
            if ($info['is_agree_agreement'] == false) {
                $this->agreeAgreement($info);
            }
        } elseif ($info['merchant_name']) {
            $result['step'] = self::APPLY_STEP['apply_2'];
        } elseif ($info['settled_type']) {
            $result['step'] = self::APPLY_STEP['apply_1'];
        }
        return $result;
    }

    /**
     * 修改入驻协议状态，创建商户管理员账号，并去发送入驻成功短信
     * @param  [type] $applyInfo [description]
     * @return [type]            [description]
     */
    public function agreeAgreement($applyInfo)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->updateOneBy(['id' => $applyInfo['id']], ['is_agree_agreement' => true]);
            // 查询商户数据
            $merchantService = new MerchantService();
            $merchantInfo = $merchantService->getInfo(['settlement_apply_id' => $applyInfo['id']]);
            if (!$merchantInfo) {
                throw new ResourceException('商户信息获取失败');
            }
            // 创建商户管理员数据
            $operatorsService = new OperatorsService();
            $operatorsData = [
                'company_id' => $applyInfo['company_id'],
                'mobile' => $applyInfo['mobile'],
                'login_name' => $applyInfo['mobile'],
                'operator_type' => 'merchant',
                // 'password' => (string)rand(100000, 999999),
                'is_disable' => 0,
                'is_dealer_main' => 0,
                'merchant_id' => $merchantInfo['id'],
                'is_merchant_main' => 1,
            ];

            $operatorsService->createOperator($operatorsData);
            // 发送短信（入驻成功）
            // $merchantService = new MerchantService();
            // $merchantService->scheduleEnterSuccessNotice($operatorsData['company_id'], $operatorsData['mobile'], $operatorsData['password']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            // app('log')->debug('商户入驻成功发送短信失败: '.$e->getMessage());
        }

        return true;
    }

    /**
     * 保存商户入驻信息
     * @param  string $accountId 商户入驻ID
     * @param  string $step 入驻信息填写步骤 1:入驻信息;2:商户信息;3:证照信息
     * @param  array $params    要保存的数据
     */
    public function saveSettlementApply($accountId, $step, $params)
    {
        $info = $this->getInfoById($accountId);
        if (!$info) {
            throw new ResourceException('未查询到相关数据');
        }
        if ($info['audit_status'] == self::AUDIT_STATUS['succ']) {
            throw new ResourceException('入驻申请已经审核通过，不能再修改');
        }
        $settingService = new MerchantSettingService();
        $setting = $settingService->getBaseSetting($params['company_id']);
        if (!$setting['status']) {
            throw new ResourceException('该平台现不支持商户入驻，请核实后再试');
        }

        $curStep = $this->getSettlementApplyStep($params['company_id'], $accountId);
        if (intval($step) > intval($curStep['step'])) {
            throw new ResourceException('入驻信息填写步骤错误');
        }
        switch ($step) {
            case '1':
                // 检查入驻类型
                if (!in_array($params['settled_type'], $setting['settled_type'])) {
                    throw new ResourceException('入驻类型错误，请确认后重新提交');
                }
                // 检查商户类型
                $settingService->__checkMerchantType($params['company_id'], $params['merchant_type_id']);
                break;
            case '2':
                if (!ismobile($params['legal_mobile'])) {
                    throw new ResourceException('请填写正确的手机号码');
                }
                // if ($params['bank_acct_type'] == '2' && !ismobile($params['bank_mobile'])) {
                //     throw new ResourceException('请填写正确的绑定手机号');
                // }
                // 检查统一社会信用代码是否重复
                $lists = $this->lists(['company_id' => $params['company_id'], 'id|neq' => $accountId, 'social_credit_code_id' => $params['social_credit_code_id']]);
                if ($lists['total_count'] > 0) {
                    throw new ResourceException('统一社会信用代码已存在，请检查后再重新提交');
                }
                break;
            case '3':
                $params['disabled'] = false;
                if ($info['audit_status'] == self::AUDIT_STATUS['fail']) {
                    $params['audit_status'] = self::AUDIT_STATUS['ongoing'];
                }
                break;
            default:
                throw new ResourceException('入驻信息填写步骤错误');
                break;
        }
        if ($info['audit_status'] == self::AUDIT_STATUS['ongoing'] && in_array($step, ['1','2'])) {
            $params['disabled'] = true;
        }

        return $this->updateOneBy(['id' => $accountId], $params);
    }

    /**
     * 根据入驻申请ID，查询入驻申请详情
     * @param  string $accountId 入驻申请ID
     * @return array            入驻申请详情
     */
    public function getSettlementApplyDetail($accountId)
    {
        $info = $this->getInfoById($accountId);
        if (!$info) {
            throw new ResourceException('入驻申请查询失败');
        }
        $settingService = new MerchantSettingService();
        $typeName = $settingService->getTypeNameById($info['company_id'], $info['merchant_type_id']);
        return array_merge($info, $typeName);
    }

    /**
     * 商户入驻申请，数据脱敏
     * @param  array $result        入驻申请数据
     * @param  boolean $datapassBlock 是否需要脱敏
     * @return array                入驻申请数据
     */
    public function settlementApplyDataMasking($result, $datapassBlock)
    {
        if (!$datapassBlock) {
            return $result;
        }
        $result['legal_cert_id'] = data_masking('idcard', (string) $result['legal_cert_id']);
        $result['legal_mobile'] = data_masking('mobile', (string) $result['legal_mobile']);
        $result['card_id_mask'] = data_masking('bankcard', (string) $result['card_id_mask']);
        $result['bank_mobile'] = data_masking('mobile', (string) $result['bank_mobile']);
        $result['legal_certid_front_url'] = data_masking('image', (string) $result['legal_certid_front_url']);
        $result['legal_cert_id_back_url'] = data_masking('image', (string) $result['legal_cert_id_back_url']);
        $result['bank_card_front_url'] = data_masking('image', (string) $result['bank_card_front_url']);
        return $result;
    }

    /**
     * 审核商家入驻申请
     * @param  array $params
     */
    public function settlementApplyAudit($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 查询入驻申请详情
            $info = $this->getInfoById($params['id']);
            if (!$info) {
                throw new ResourceException('入驻申请查询失败');
            }
            if ($info['audit_status'] != self::AUDIT_STATUS['ongoing']) {
                throw new ResourceException('入驻申请当前无需审核');
            }
            switch ($params['audit_status']) {
                case self::AUDIT_STATUS['succ']:
                    $this->auditSucc($info);
                    break;
                case self::AUDIT_STATUS['fail']:
                    $this->auditFail($info);
                    break;
                default:
                    throw new ResourceException('入驻申请审核失败,请核实后再试');
                    break;
            }
            $updateData = [
                'audit_status' => $params['audit_status'],
                'audit_memo' => $params['audit_memo'],
                'audit_goods' => $params['audit_goods'],
            ];
            $this->updateOneBy(['company_id' => $params['company_id'], 'id' => $params['id']], $updateData);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 审核成功
     * @param  array $info 入驻申请详情
     */
    public function auditSucc($info)
    {
        // 创建商户数据
        $merchantService = new MerchantService();
        $merchantData = $info;
        $merchantData['settlement_apply_id'] = $info['id'];
        $merchantData['settled_succ_sendsms'] = 1;
        $merchantData['disabled'] = 0;
        $merchantResult = $merchantService->create($merchantData);
        // 创建商户管理员数据
        $operatorsService = new OperatorsService();
        $operatorsData = [
            'company_id' => $info['company_id'],
            'mobile' => $info['mobile'],
            'login_name' => $info['mobile'],
            'operator_type' => 'merchant',
            // 'password' => (string)rand(100000, 999999),
            'is_disable' => 0,
            'is_dealer_main' => 0,
            'is_merchant_main' => 1,
            'merchant_id' => $merchantResult['id'],
        ];

        $operatorsService->createOperator($operatorsData);

        $merchantService = new MerchantService();
        // 发送审核完成通知
        $merchantService->scheduleAuditSuccessNotice($operatorsData['company_id'], $operatorsData['mobile']);
        return true;
    }

    /**
     * 审核失败
     * @param  array $info 入驻申请详情
     */
    public function auditFail($info)
    {
        $merchantService = new MerchantService();
        // 发送审核完成通知
        $merchantService->scheduleAuditFailNotice($info['company_id'], $info['mobile']);
        return true;
    }

    public function getLoginInfo($companyId, $accountId) {
        $info = $this->getInfoById($accountId);
        if ($info['audit_status'] != self::AUDIT_STATUS['succ']) {
            return [];
        }

        $merchantService = new MerchantService();
        $merchant = $merchantService->getInfo(['company_id' => $companyId, 'settlement_apply_id' => $accountId]);

        $filter = [
            'company_id' => $companyId,
            'operator_type' => 'merchant',
            'merchant_id' => $merchant['id'],
        ];
        $operatorsService = new OperatorsService();
        $operator = $operatorsService->getInfo($filter);
        $result = [
            'mobile' => $operator['mobile'],
        ];

        if (!$operator['password']) {
            $password = (string)rand(100000, 999999);
            $operatorsService->updateOperator($operator['operator_id'], ['password' => $password]);
            $result['password'] = $password;
        }
        return $result;
    }

    public function resetPassword($companyId, $accountId) {
        $info = $this->getInfoById($accountId);
        if ($info['audit_status'] != self::AUDIT_STATUS['succ']) {
            return [];
        }

        $merchantService = new MerchantService();
        $merchant = $merchantService->getInfo(['company_id' => $companyId, 'settlement_apply_id' => $accountId]);

        $filter = [
            'company_id' => $companyId,
            'operator_type' => 'merchant',
            'merchant_id' => $merchant['id'],
        ];
        $operatorsService = new OperatorsService();
        $operator = $operatorsService->getInfo($filter);
        $result = [
            'mobile' => $operator['mobile'],
        ];

        $password = (string)rand(100000, 999999);
        $operatorsService->updateOperator($operator['operator_id'], ['password' => $password]);
        $result['password'] = $password;
        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->settlementApplyRepository->$method(...$parameters);
    }
}
