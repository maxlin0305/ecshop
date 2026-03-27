<?php

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\EmployeePurchaseActivityService;

use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Services\PromotionSeckillActivityService;

class EmployeePurchaseActivity extends Controller
{

    public function __construct()
    {
        $this->service = new EmployeePurchaseActivityService();
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/employeepurchase/getinfo",
     *     summary="获取进行中员工内购详情",
     *     tags={"营销"},
     *     description="获取进行中员工内购详情",
     *     operationId="getOngoingActivityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Response(
     *         response="200", 
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="purchase_id", type="string", example="14", description="员工内购活动ID"),
     *               @SWG\Property(property="purchase_name", type="string", example="", description="活动名称"),
     *               @SWG\Property(property="ad_pic", type="string", example="", description="分享图片url"),
     *               @SWG\Property(property="user_type", type="string", example="employee", description="会员属性 employee:员工;dependents:家属;"),
     *               @SWG\Property(property="username", type="string", example="", description="会员名称"),
     *               @SWG\Property(property="avatar", type="string", example="", description="会员头像"),
     *               @SWG\Property(property="total_limitfee", type="integer", example="0", description="总额度,单位：分"),
     *               @SWG\Property(property="used_limitfee", type="integer", example="0", description="已使用额度,单位：分"),
     *               @SWG\Property(property="surplus_limitfee", type="integer", example="0", description="剩余额度,单位：分"),
     *               @SWG\Property(property="surplus_share_limitnum", type="string", example="9", description="剩余分享次数"),
     *               @SWG\Property(property="dependents_begin_time", type="string", example="1645804800", description="家属活动开始时间"),
     *               @SWG\Property(property="dependents_list", type="array", description="家属列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="1", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description=""),
     *                           @SWG\Property(property="purchase_id", type="string", example="14", description="员工内购活动ID"),
     *                           @SWG\Property(property="employee_user_id", type="string", example="20688", description="员工会员ID"),
     *                           @SWG\Property(property="dependents_user_id", type="string", example="20710", description="家属会员ID"),
     *                           @SWG\Property(property="created", type="string", example="1643192146", description="创建时间"),
     *                           @SWG\Property(property="updated", type="string", example="1643192146", description=""),
     *                           @SWG\Property(property="username", type="string", example="", description="会员名称"),
     *                           @SWG\Property(property="avatar", type="string", example="", description="会员头像"),
     *                           @SWG\Property(property="used_limitfee", type="string", example="", description="已使用额度,单位：分"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getOngoingActivityInfo(Request $request)
    {
        $authUser = $request->get('auth');
        $result = $this->service->getOngoingInfo($authUser['company_id'], $authUser['user_id'], $authUser['mobile']);
        if ($result['activity_data'] == false) {
            return $this->response->array([]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/employeepurchase/sharecode",
     *     summary="获取员工内购分享码",
     *     tags={"营销"},
     *     description="获取进行中的员工内购的分享码",
     *     operationId="getShareCode",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="code", type="string", example="3221937", description=""),
     *            ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getShareCode(Request $request)
    {
        $authUser = $request->get('auth');
        $code = $this->service->getShareCode($authUser['company_id'], $authUser['user_id'], $authUser['mobile']);
        return $this->response->array(['code' => $code]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/promotion/employeepurchase/dependents",
     *     summary="员工内购绑定成为家属",
     *     tags={"营销"},
     *     description="员工内购绑定成为家属",
     *     operationId="bindDependents",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Parameter( name="code", in="query", description="分享code", type="integer", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="boolean", example="true", description="状态"),
     *            ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function bindDependents(Request $request)
    {
        $authUser = $request->get('auth');
        $code = $request->input('code', '');
        if (!$code) {
            return $this->response->array(['status' => false]);
        }

        $this->service->lockShareCode($authUser['company_id'], $code);

        try {
            $status = $this->service->bindDependents($authUser['company_id'], $code, $authUser['user_id']);
            return $this->response->array(['status' => $status]);
        } catch (\Exception $e) {
            $this->service->unlockShareCode($authUser['company_id'], $code);
            throw new ResourceException($e->getMessage());
        }
    }

}
