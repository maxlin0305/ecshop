<?php

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\EmployeePurchaseActivityService;
use PromotionsBundle\Services\EmployeePurchaseReluserService;

class EmployeePurchaseActivity extends Controller
{
    public function __construct()
    {
        $this->service = new EmployeePurchaseActivityService();
    }
    /**
     * @SWG\Definition(
     * definition="EmployeePurchaseList",
     * type="object",
     * @SWG\Property( property="purchase_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="purchase_name", type="string", example="测试1", description="活动名称"),
     * @SWG\Property( property="used_roles", type="array",
     *     @SWG\Items( type="string", example="1", description="适用角色集合 json_array employee:员工;dependents:家属;"),
     * ),
     * @SWG\Property( property="employee_limitfee", type="string", example="", description="员工额度，以分为单位"),
     * @SWG\Property( property="is_share_limitfee", type="string", example="", description="家属是否共有额度 false:否 true:是"),
     * @SWG\Property( property="dependents_limitfee", type="string", example="", description="家属额度，以分为单位"),
     * @SWG\Property( property="dependents_limit", type="string", example="", description="员工邀请家属上限"),
     * @SWG\Property( property="begin_time", type="string", example="1611158400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611331199", description="活动结束时间"),
     * @SWG\Property( property="created", type="string", example="1611216662", description=""),
     * @SWG\Property( property="updated", type="string", example="1611216662", description=""),
     * @SWG\Property( property="begin_date", type="string", example="2021-01-21", description="有效期开始时间"),
     * @SWG\Property( property="end_date", type="string", example="2021-01-22", description="有效期结束时间"),
     * @SWG\Property( property="activity_status", type="string", example="it_has_ended", description="活动状态 waiting:未开始 ongoing:进行中 it_has_ended:已结束"),
     * )
     */

    /**
     * @SWG\Definition(
     * definition="EmployeePurchaseDetail",
     * type="object",
     * @SWG\Property( property="purchase_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="purchase_name", type="string", example="测试1", description="活动名称"),
     * @SWG\Property( property="used_roles", type="string", example="", description="适用角色集合 json_array employee:员工;dependents:家属;"),
     * @SWG\Property( property="employee_limitfee", type="string", example="", description="员工额度，以分为单位"),
     * @SWG\Property( property="is_share_limitfee", type="boolean", example="", description="家属是否共有额度 false:否 true:是"),
     * @SWG\Property( property="dependents_limitfee", type="string", example="", description="家属额度，以分为单位"),
     * @SWG\Property( property="dependents_limit", type="string", example="", description="员工邀请家属上限"),
     * @SWG\Property( property="begin_time", type="string", example="1611158400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611331199", description="活动结束时间"),
     * @SWG\Property( property="created", type="string", example="1611216662", description=""),
     * @SWG\Property( property="updated", type="string", example="1611216662", description=""),
     * )
     */
    
    /**
     * @SWG\Definition(
     * definition="EmployeePurchaseInfo",
     * type="object",
     * @SWG\Property(property="purchase_id", type="string", example="15", description="员工内购活动ID"),
     * @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     * @SWG\Property(property="purchase_name", type="string", example="", description="活动名称"),
     * @SWG\Property(property="ad_pic", type="string", example="", description="活动分享图片"),
     * @SWG\Property(property="used_roles", type="string", example="", description="适用角色集合 array employee:员工;dependents:家属;"),
     * @SWG\Property(property="employee_limitfee", type="integer", example="2000000", description="员工额度，以分为单位"),
     * @SWG\Property(property="is_share_limitfee", type="string", example="1", description="家属是否共有额度 false:否 true:是"),
     * @SWG\Property(property="dependents_limitfee", type="integer", example="0", description="家属额度，以分为单位"),
     * @SWG\Property(property="dependents_limit", type="integer", example="6", description="员工邀请家属上限"),
     * @SWG\Property(property="begin_time", type="integer", example="1649260800", description="活动开始时间"),
     * @SWG\Property(property="end_time", type="integer", example="1649347199", description="活动结束时间"),
     * @SWG\Property(property="created", type="integer", example="1643081473", description="创建时间"),
     * @SWG\Property(property="updated", type="integer", example="1643081473", description="更新时间"),
     * @SWG\Property(property="item_limit", type="array", description="每人限购的数据。item_type=all时，此字段为字符串。",
     *     @SWG\Items(
     *         @SWG\Property(property="purchase_id", type="string", example="15", description=""),
     *         @SWG\Property(property="item_id", type="string", example="6135", description="关联商品ID(主类目id、商品标签id、品牌id等)"),
     *         @SWG\Property(property="item_type", type="string", example="item", description="活动商品类型: all:全部商品,item:指定商品,tag:标签,category:商品主类目,brand:品牌"),
     *         @SWG\Property(property="limit_fee", type="string", example="150001", description="每人额度，以分为单位"),
     *         @SWG\Property(property="limit_num", type="string", example="3", description="每人限购"),
     *         @SWG\Property(property="begin_time", type="string", example="1649260800", description="活动起始时间"),
     *         @SWG\Property(property="end_time", type="string", example="1649347199", description="活动截止时间"),
     *         @SWG\Property(property="created", type="string", example="1643081473", description="创建时间"),
     *         @SWG\Property(property="updated", type="string", example="1643081473", description="更新时间"),
     *         @SWG\Property(property="id", type="string", example="6135", description="关联商品ID(主类目id、商品标签id、品牌id等)"),
     *         @SWG\Property(property="name", type="string", example="情定上海（ShangHai Kiss）S3146 C22", description="名称（商品名称、主类目名称、商品标签名称、商品品牌名称等）"),
     *         @SWG\Property(property="item_spec_desc", type="string", example="折射率:带1.55超薄加膜片", description="商品规格描述。item_type=item时使用"),
     *         ),
     * ),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/promotions/employeepurchase/create",
     *     summary="创建员工内购活动",
     *     tags={"营销"},
     *     description="创建员工内购活动",
     *     operationId="createActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_name", in="query", description="活动名称", type="string", required=true),
     *     @SWG\Parameter( name="ad_pic", in="query", description="活动图片", type="string", required=true),
     *     @SWG\Parameter( name="used_roles", in="query", description="适用角色集合 array employee:员工;dependents:家属", type="string", required=true),
     *     @SWG\Parameter( name="employee_limitfee", in="query", description="员工额度，单位:元", type="string", required=true),
     *     @SWG\Parameter( name="is_share_limitfee", in="query", description="家属是否共有额度 false:否 true:是", type="string"),
     *     @SWG\Parameter( name="dependents_limitfee", in="query", description="家属额度，单位:元", type="string"),
     *     @SWG\Parameter( name="dependents_limit", in="query", description="员工邀请家属上限", type="string"),
     *     @SWG\Parameter( name="item_type", in="query", description="活动商品类型: all:全部商品,item:指定商品,tag:标签,category:商品主类目,brand:品牌", type="string", required=true),
     *     @SWG\Parameter( name="item_limit", in="query", description="商品限购数据。item_type=all时为数字。item_type=tag时，传数组 id:标签id,limit_num:每人限购,limit_fee:限额。其他类推", type="string", required=true),
     *     @SWG\Parameter( name="begin_time", in="query", description="活动开始时间", required=true, type="string", required=true),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/EmployeePurchaseDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        $rules = [
            'purchase_name' => ['required', '请输入活动名称'],
            'ad_pic' => ['required', '请上传活动封面'],
            'begin_time' => ['required', '活动开始时间必填'],
            'end_time' => ['required', '活动结束时间必填'],
            'used_roles' => ['required', '请选择适用角色'],
            'employee_limitfee' => ['required', '请输入员工额度'],
            // 'item_type' => ['required|in:all,item,tag,category,brand', '请选择正确的活动商品类型'],
            // 'item_limit' => ['required', '每人限购必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        

        $result = $this->service->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/employeepurchase/lists",
     *     summary="获取员工内购活动列表",
     *     tags={"营销"},
     *     description="获取员工内购活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_name", in="query", description="活动名称", required=false, type="string"),
     *     @SWG\Parameter( name="activity_status", in="query", description="活动状态 0:全部 waiting:未开始 ongoing:进行中 it_has_ended:已结束", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/EmployeePurchaseList"
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];

        $input = $request->all('purchase_name', 'activity_status');

        if ($input['purchase_name']) {
            $filter['purchase_name|contains'] = $input['purchase_name'];
        }
        if ($input['activity_status']) {
            switch ($input['activity_status']) {
                case "waiting":// 未开始
                    $filter['begin_time|gte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "ongoing":// 进行中
                    $filter['begin_time|lte'] = time();
                    $filter['end_time|gt'] = time();
                    break;
                case "it_has_ended":// 已结束
                    $filter['end_time|lte'] = time();
                    break;
            }
        }


        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['purchase_id' => 'desc'];

        $result = $this->service->lists($filter, '*', $page, $pageSize, $orderBy);
        if ($result['total_count'] > 0 && $result['list']) {
            foreach ($result['list'] as $key => $row) {
                $row['begin_date'] = date('Y-m-d H:i:s', $row['begin_time']);
                $row['end_date'] = date('Y-m-d H:i:s', $row['end_time']);
                if ($row['begin_time'] >= time() && $row['end_time'] >= time()) {
                    $row['activity_status'] = 'waiting';
                } elseif ($row['begin_time'] <= time() && $row['end_time'] > time()) {
                    $row['activity_status'] = 'ongoing';
                } else {
                    $row['activity_status'] = 'it_has_ended';
                }
                $result['list'][$key] = $row;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/employeepurchase/getinfo",
     *     summary="获取员工内购活动详情",
     *     tags={"营销"},
     *     description="获取员工内购活动详情",
     *     operationId="getActivityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_id", in="query", description="员工内购活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/EmployeePurchaseInfo"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityInfo(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['purchase_id'] = $request->input('purchase_id');
        $result = $this->service->getActivityInfo($filter);
        if (!$result) {
            return $this->response->array([]);
        }
        $result['begin_date'] = date('Y-m-d H:i:s', $result['begin_time']);
        $result['end_date'] = date('Y-m-d H:i:s', $result['end_time']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/employeepurchase/update",
     *     summary="编辑员工内购活动",
     *     tags={"营销"},
     *     description="编辑员工内购活动",
     *     operationId="updateActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_id", in="query", description="活动ID", type="string", required=true),
     *     @SWG\Parameter( name="purchase_name", in="query", description="活动名称", type="string", required=true),
     *     @SWG\Parameter( name="used_roles", in="query", description="适用角色集合 array employee:员工;dependents:家属", type="string", required=true),
     *     @SWG\Parameter( name="employee_limitfee", in="query", description="员工额度，单位:元", type="string", required=true),
     *     @SWG\Parameter( name="is_share_limitfee", in="query", description="家属是否共有额度 false:否 true:是", type="string"),
     *     @SWG\Parameter( name="dependents_limitfee", in="query", description="家属额度，单位:元", type="string"),
     *     @SWG\Parameter( name="dependents_limit", in="query", description="员工邀请家属上限", type="string"),
     *     @SWG\Parameter( name="item_type", in="query", description="活动商品类型: all:全部商品,item:指定商品,tag:标签,category:商品主类目,brand:品牌", type="string"),
     *     @SWG\Parameter( name="item_limit", in="query", description="商品限购数据。item_type=all时为数字。item_type=tag时，传数组 id:标签id,limit_num:每人限购,limit_fee:限额。其他类推", type="string"),
     *     @SWG\Parameter( name="begin_time", in="query", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/EmployeePurchaseDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        $rules = [
            'purchase_id' => ['required', '活动ID必填'],
            'purchase_name' => ['required', '活动名称必填'],
            'ad_pic' => ['required', '活动图片必填'],
            'used_roles' => ['required', '适用角色必填'],
            'employee_limitfee' => ['required|min:0', '员工额度必须大于0'],
            'begin_time' => ['required', '活动开始时间必填'],
            'end_time' => ['required', '活动结束时间必填'],
            'item_type' => ['required|in:all,item,tag,category,brand', '请选择正确的活动商品类型'],
            'item_limit' => ['required', '每人限购必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['company_id'] = $params['company_id'];
        $filter['purchase_id'] = $params['purchase_id'];
        $result = $this->service->updateActivity($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/employeepurchase/endactivity",
     *     summary="终止员工内购活动",
     *     tags={"营销"},
     *     description="终止员工内购活动",
     *     operationId="endActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_id", in="query", description="员工内购活动ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function endActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $result = $this->service->endActivity($authUser['company_id'], $request->input('purchase_id'));
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/employeepurchase/dependents/lists",
     *     summary="获取员工内购家属列表",
     *     tags={"营销"},
     *     description="获取员工内购活动的家属列表",
     *     operationId="getDependentsLists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="purchase_id", in="query", description="员工内购活动ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/EmployeePurchaseList"
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getDependentsLists(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->all('purchase_id', 'employee_user_mobile', 'dependents_user_mobile');
        $filter['purchase_id'] = $params['purchase_id'];
        $filter['company_id'] = $authUser['company_id'];
        if ($params['employee_user_mobile']) {
            $filter['employee_user_mobile'] = $params['employee_user_mobile'];
        }
        if ($params['dependents_user_mobile']) {
            $filter['dependents_user_mobile'] = $params['dependents_user_mobile'];
        }

        $purchaseReluserService = new EmployeePurchaseReluserService();
        $result = $purchaseReluserService->getDependentsLists($filter);
        return $this->response->array($result);
    }
}
