<?php

namespace DistributionBundle\Services;

use DistributionBundle\Entities\CashWithdrawal;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\MerchantTradeService;

use Dingo\Api\Exception\ResourceException;

class CashWithdrawalService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CashWithdrawal::class);
    }

    /**
     * 获取提现支付记录
     */
    public function getMerchantTradeList($companyId, $cashWithdrawalId)
    {
        $filter = [
            'rel_scene_id' => $cashWithdrawalId,
            'company_id' => $companyId,
            'rel_scene_name' => 'rebate_cash_withdrawal'
        ];

        $merchantTradeService = new MerchantTradeService();
        return $merchantTradeService->lists($filter);
    }

    /**
     * 用户申请提现
     */
    public function applyCashWithdrawal($data)
    {
        // 根据手机号获取当前用户是否为分销商
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo(['mobile' => $data['mobile'], 'company_id' => $data['company_id']]);
        if (!$distributorInfo) {
            throw new ResourceException('当前用户不是分销商');
        }

        $basicConfigService = new BasicConfigService();
        $config = $basicConfigService->getInfoById($data['company_id']);
        if ($config && $data['money'] < $config['limit_rebate']) {
            throw new ResourceException('最少申请提现'. ($config['limit_rebate'] / 100) . '元');
        }

        $distributeCountService = new DistributeCountService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $insertData = [
                'company_id' => $data['company_id'],
                'open_id' => $data['open_id'],
                'user_id' => $data['user_id'],
                'wxa_appid' => $data['wxa_appid'],
                'money' => floor($data['money']),
                'distributor_id' => $distributorInfo['distributor_id'],
                'distributor_name' => $distributorInfo['name'],
                'distributor_mobile' => $distributorInfo['mobile'],
                'status' => 'apply',
            ];

            $return = $this->entityRepository->create($insertData);

            // 判断当前用户申请的提现金额是否合法
            $distributeCountService->applyCashWithdrawal($data['company_id'], $distributorInfo['distributor_id'], floor($data['money']));

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $return;
    }

    /**
     * 处理佣金提现
     */
    public function processCashWithdrawal($companyId, $cashWithdrawalId, $clientIp = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException('处理的佣金提现申请不存在');
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException('当前佣金提现正在处理或已完成');
        }

        //将提现状态设置为处理中
        $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'process']);

        //默认直接微信付款
        $paymentsService = new PaymentsService(new WechatPayService());
        //支付参数
        $paymentData = [
            'rel_scene_id' => $cashWithdrawalId,
            'rel_scene_name' => 'rebate_cash_withdrawal',
            're_user_name' => $info['distributor_name'],
            'mobile' => $info['distributor_mobile'],
            'amount' => $info['money'], //提现金额 （分）
            'user_id' => $info['user_id'],
            'open_id' => $info['open_id'],
            'payment_desc' => '佣金提现',
            'spbill_create_ip' => $clientIp ?: '127.0.0.1'
        ];

        $data = $paymentsService->merchantPayment($companyId, $info['wxa_appid'], $paymentData);
        //如果支付成功
        if ($data['status'] == 'SUCCESS') {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'success']);

                $distributeCountService = new DistributeCountService();
                $distributeCountService->agreeCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                // 如果一直为处理中，那么提供异常处理机制，通过申请单查询到最近一笔付款
                // 如果有付款则到微信查询是否已经付款成功，如果付款成功则进行后续处理
                // 否则改为待处理 apply
                throw new ResourceException('付款成功，服务器异常，请通过异常处理重试');
            }
        } elseif ($data['status'] == 'PROCESS') {
            //adapay提现T1类型有处理时间，回调时改提现状态
            $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'process']); 
        } else {
            $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'apply']);
            throw new ResourceException($data['error_desc']);
        }

        return true;
    }

    /**
     * 取消或拒绝提现申请
     */
    public function rejectCashWithdrawal($companyId, $cashWithdrawalId, $processType = 'reject', $remarks = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException('处理的佣金提现申请不存在');
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException('当前佣金提现正在处理或已完成');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $processType = ($processType == 'reject') ? 'reject' : 'cancel';
            $updateData['status'] = $processType;
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            $data = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'id' => $cashWithdrawalId], $updateData);

            $distributeCountService = new DistributeCountService();
            $distributeCountService->rejectCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException('系统错误，请稍后再试');
        }

        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
