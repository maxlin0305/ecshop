<?php

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\CashWithdrawalService;

use Dingo\Api\Exception\ResourceException;

class CashWithdrawal extends Controller
{
    /**
     * @SWG\Put(
     *     path="/distribution/cash_withdrawals/{id}",
     *     summary="处理佣金提现申请",
     *     tags={"店铺"},
     *     description="处理佣金提现申请",
     *     operationId="processCashWithdrawal",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="提现id", required=true, type="string"),
     *     @SWG\Parameter( name="process_type", in="query", description="处理类型(reject 拒绝 argee 同意)", required=true, type="string"),
     *     @SWG\Parameter( name="remarks", in="query", description="拒绝描述", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function processCashWithdrawal($id, Request $request)
    {
        $processType = $request->input('process_type');
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        if ($processType == 'argee') {
            $status = $cashWithdrawalService->processCashWithdrawal($companyId, $id);
        } elseif ($processType == 'reject') {
            $remarks = $request->input('remarks', null);
            $status = $cashWithdrawalService->rejectCashWithdrawal($companyId, $id, $processType, $remarks);
        } else {
            throw new ResourceException('参数错误');
        }

        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/cashWithdrawals",
     *     summary="获取佣金提现列表",
     *     tags={"店铺"},
     *     description="获取佣金提现列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                     @SWG\Property(property="total_count", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        if ($request->input('mobile', false)) {
            $filter['distributor_mobile'] = $request->input('mobile');
        }
        if ($request->input('status', false)) {
            $filter['status'] = $request->input('status');
        }

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/cashWithdrawal/payinfo/{cash_withdrawal_id}",
     *     summary="获取佣金提现支付信息",
     *     tags={"店铺"},
     *     description="获取佣金提现支付信息",
     *     operationId="getMerchantTradeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="list", type="stirng"),
     *                     @SWG\Property(property="total_count", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getMerchantTradeList($cash_withdrawal_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->getMerchantTradeList($companyId, $cash_withdrawal_id);

        return $this->response->array($data);
    }
}
