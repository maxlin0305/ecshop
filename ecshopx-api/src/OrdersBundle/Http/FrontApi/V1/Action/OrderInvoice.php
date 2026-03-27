<?php

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use OrdersBundle\Services\UserOrderInvoiceService;

class OrderInvoice extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/orders/invoice",
     *     summary="获取用户订单发票列表",
     *     tags={"订单"},
     *     description="获取用户订单发票列表",
     *     operationId="getInvoiceList",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="2", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据集合",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="2", description="自增id"),
     *                           @SWG\Property(property="order_id", type="string", example="3127705000030150", description="订单号"),
     *                           @SWG\Property(property="user_id", type="string", example="20150", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="status", type="integer", example="2", description="是否开票"),
     *                           @SWG\Property(property="invoice", type="string", example="", description="发票信息"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getInvoiceList(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $userOrderInvoiceService = new UserOrderInvoiceService();
        $result = $userOrderInvoiceService->getDataList($filter, $page, $pageSize);
        return $this->response->array($result);
    }
}
