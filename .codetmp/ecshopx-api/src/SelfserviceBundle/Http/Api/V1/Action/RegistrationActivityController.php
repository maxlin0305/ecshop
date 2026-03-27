<?php

namespace SelfserviceBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;

use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;

class RegistrationActivityController extends Controller
{
    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new RegistrationActivityService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/create",
     *     summary="添加报名活动",
     *     tags={"报名"},
     *     description="添加报名活动",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="temp_id", in="query", description="问卷调查模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="join_limit", in="query", description="每个会员每个活动可参与次数，默认1", required=false, type="integer"),
     *     @SWG\Parameter( name="is_sms_notice", in="query", description="是否发送短信通知", required=false, type="boolean"),
     *     @SWG\Parameter( name="is_wxapp_notice", in="query", description="是否发送小程序模板通知", required=false, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */

    public function createData(Request $request)
    {
        $params = $request->all('temp_id', 'activity_name', 'start_time', 'end_time', 'join_limit', 'is_sms_notice', 'is_wxapp_notice');
        $rules = [
            'temp_id' => ['required', '模板必选'],
            'activity_name' => ['required', '活动名称必填'],
            'start_time' => ['required', '开始时间必填'],
            'end_time' => ['required', '结束时间必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $result = $this->service->saveData($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/selfhelp/registrationActivity/update",
     *     summary="编辑报名活动",
     *     tags={"报名"},
     *     description="编辑报名活动",
     *     operationId="updateData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Parameter( name="temp_id", in="query", description="问卷调查模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="join_limit", in="query", description="每个会员每个活动可参与次数，默认1", required=false, type="integer"),
     *     @SWG\Parameter( name="is_sms_notice", in="query", description="是否发送短信通知", required=false, type="boolean"),
     *     @SWG\Parameter( name="is_wxapp_notice", in="query", description="是否发送小程序模板通知", required=false, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function updateData(Request $request)
    {
        $params = $request->all('activity_id', 'temp_id', 'activity_name', 'start_time', 'end_time', 'join_limit', 'is_sms_notice', 'is_wxapp_notice');
        $rules = [
            'activity_id' => ['required', '活动id必选'],
            'temp_id' => ['required', '模板必选'],
            'activity_name' => ['required', '活动名称必填'],
            'start_time' => ['required', '开始时间必填'],
            'end_time' => ['required', '结束时间必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['activity_id'] = $params['activity_id'];
        $filter['company_id'] = $companyId;
        $params['company_id'] = $companyId;
        $result = $this->service->saveData($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/list",
     *     summary="报名活动列表",
     *     tags={"报名"},
     *     description="报名活动列表",
     *     operationId="getDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否在有效期内", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="34", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="38", description="活动ID "),
     *                          @SWG\Property( property="temp_id", type="string", example="23", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="cesss", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1609430400", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1610726399", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="3", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="0", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="0", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1610443180", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1610443180", description="更新时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="start_date", type="string", example="2021-01-01 00:00:00", description="开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2021-01-15 23:59:59", description="结束时间"),
     *                          @SWG\Property( property="total_join_num", type="string", example="0", description="总参与人数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'start_time', 'end_time', 'status', 'is_valid');
        $page = $request->get('page', 1);
        $size = $request->get('pageSize', $this->limit);
        $orderBy = ['activity_id' => 'DESC'];
        $filter = $this->_getFilter($request);

        $fieldTitle = $request->get('field_title');
        if (!is_null($fieldTitle) && $fieldTitle !== '') {
            $filter['activity_name|contains'] = $fieldTitle;
        }

        $result = $this->service->lists($filter, '*', $page, $size, $orderBy);

        if ($result['list'] ?? null) {
            $registrationRecordService = new RegistrationRecordService();
            $activityIds = array_column($result['list'], 'activity_id');
            $datalist = $registrationRecordService->getJoinActivityUserNum($filter['company_id'], $activityIds);
            if ($datalist) {
                foreach ($result['list'] as &$v) {
                    $v['total_join_num'] = $datalist[$v['activity_id']] ?? 0;
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/get",
     *     summary="获取指定详情",
     *     tags={"报名"},
     *     description="获取指定详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDataInfo(Request $request)
    {
        $result = [];
        $id = $request->get('activity_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->getInfoById($id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/del",
     *     summary="删除活动",
     *     tags={"报名"},
     *     description="删除活动",
     *     operationId="deleteData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function deleteData(Request $request)
    {
        $result = [];
        $id = $request->get('activity_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->deleteById($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/invalid",
     *     summary="废弃指定项",
     *     tags={"报名"},
     *     description="废弃指定项",
     *     operationId="deleteData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="操作结果(0,1)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function restoreData(Request $request)
    {
        $result = [];
        $id = $request->get('activity_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $filter['activity_id'] = $id;
        $params['end_time'] = time() - 3600;
        $result = $this->service->updateBy($filter, $params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/easylist",
     *     summary="报名活动列表",
     *     tags={"报名"},
     *     description="报名活动列表",
     *     operationId="getEasyDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态(ongoing)", required=true, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否有效(0, 1)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="34", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="38", description="活动ID "),
     *                          @SWG\Property( property="activity_name", type="string", example="cesss", description="活动名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getEasyDatalist(Request $request)
    {
        $page = $request->get('page', 1);
        $size = $request->get('pageSize', $this->limit);
        $orderBy = ['activity_id' => 'DESC'];
        $filter = $this->_getFilter($request);
        $result = $this->service->lists($filter, 'activity_id,activity_name', $page, $size, $orderBy);
        return $this->response->array($result);
    }

    private function _getFilter($request)
    {
        $params = $request->all('temp_id', 'activity_name', 'start_time', 'end_time', 'status', 'is_valid');
        if ($params['status']) {
            switch ($params['status']) {
                case "waiting":
                    $filter['start_time|gte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "ongoing":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "end":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|lte'] = time();
                    break;
            }
        }
        if ($request->get('is_valid')) {
            $filter['start_time|lte'] = time();
            $filter['end_time|gte'] = time();
        }

        if (isset($params['start_time'],$params['end_time']) && $params['start_time'] && $params['end_time']) {
            $filter['created|gte'] = $params['start_time'];
            $filter['created|lte'] = $params['end_time'];
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        return $filter;
    }

    /**
     * @SWG\Definition(
     *     definition="RegistrationActivity",
     *     description="报名活动信息",
     *     type="object",
     *     @SWG\Property( property="activity_id", type="string", example="39", description="活动ID  "),
     *                  @SWG\Property( property="temp_id", type="string", example="23", description="表单模板id "),
     *                  @SWG\Property( property="activity_name", type="string", example="免费美家设计", description="活动名称 "),
     *                  @SWG\Property( property="start_time", type="string", example="2021-01-01", description="活动开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="2021-11-01", description="活动结束时间"),
     *                  @SWG\Property( property="join_limit", type="string", example="1", description="可参与次数"),
     *                  @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                  @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                  @SWG\Property( property="created", type="string", example="1612410464", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612410464", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     * )
     */
}
