<?php

namespace DistributionBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use DistributionBundle\Services\CashWithdrawalService;

class CashWithdrawal extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/cash_withdrawal",
     *     summary="佣金提现申请",
     *     tags={"店铺"},
     *     description="佣金提现申请",
     *     operationId="applyCashWithdrawal",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="money", in="query", description="提现金额", required=true, type="string"),
     *     @SWG\Response( response=200,description="成功返回结构",),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function applyCashWithdrawal(Request $request)
    {
        $authInfo = $request->get('auth');

        $data = [
            'mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'open_id' => $authInfo['open_id'] ?? '',
            'user_id' => $authInfo['user_id'],
            'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
            'money' => $request->input('money', 0),
        ];

        if ($data['money'] < 100) {
            throw new ResourceException('佣金提现最少为1元');
        }

        if ($data['money'] > 80000) {
            throw new ResourceException('佣金单次最多提现800元');
        }

        $cashWithdrawalService = new CashWithdrawalService();
        $result = $cashWithdrawalService->applyCashWithdrawal($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/cash_withdrawals",
     *     summary="获取提现申请列表",
     *     tags={"店铺"},
     *     description="获取提现申请列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
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
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="distributor_name", type="stirng"),
     *                     @SWG\Property(property="money", type="stirng"),
     *                     @SWG\Property(property="status", type="stirng"),
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

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        return $this->response->array($data);
    }
}
