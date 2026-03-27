<?php

namespace SelfserviceBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use SelfserviceBundle\Services\RegistrationRecordService;

use EspierBundle\Jobs\ExportFileJob;

class RegistrationRecordController extends Controller
{
    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new RegistrationRecordService();
        $this->limit = 20;
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationRecord/list",
     *     summary="报名活动列表",
     *     tags={"报名"},
     *     description="报名活动列表",
     *     operationId="getDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=false, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="会员手机号", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="48", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/RegistrationRecordInfo"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $params = $request->all('activity_id', 'start_time', 'mobile', 'end_time', 'status');

        $page = $request->get('page', 0);
        $size = $request->get('pageSize', 0);

        $orderBy = ['record_id' => 'DESC'];

        if ($params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }

        if ($params['start_time'] && $params['end_time']) {
            $filter['created|gte'] = $params['start_time'];
            $filter['created|lte'] = $params['end_time'];
        }

        if ($params['activity_id']) {
            $filter['activity_id'] = $params['activity_id'];
        }

        if ($params['status']) {
            $filter['status'] = $params['status'];
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $this->service->getRocordList($filter, $page, $size, $orderBy);
        if (isset($result['list']) && $result['list']) {
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($result['list'] as $key => $value) {
                if ($datapassBlock) {
                    $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationRecord/get",
     *     summary="获取指定详情",
     *     tags={"报名"},
     *     description="获取指定详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="record_id", in="query", description="记录ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationRecordInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDataInfo(Request $request)
    {
        $result = [];
        $id = $request->get('record_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->getRocordInfo($id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        $result['content'] = $this->service->fixeddecryptRocordContent($result['content'], $datapassBlock);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/selfhelp/registrationReview",
     *     summary="报名审批",
     *     tags={"报名"},
     *     description="报名审批",
     *     operationId="registrationReview",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="record_id", in="query", description="记录ID", required=true, type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="审核结果", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="拒绝原因", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="审批结果(0, 1)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function registrationReview(Request $request)
    {
        $params = $request->all('record_id', 'status', 'reason');
        $rules = [
            'record_id' => ['required', '报名记录id'],
            'status' => ['required', '审批结果必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['record_id'] = $params['record_id'];
        if ($params['status'] == 'false' && !$params['reason']) {
            throw new ResourceException('拒绝原因必填');
        }
        $params['status'] = $params['status'] == 'true' ? 'passed' : 'rejected';
        $result = $this->service->updateBy($filter, $params);
        $this->service->sendMassage($filter['company_id'], $filter['record_id']);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationRecord/export",
     *     summary="导出报名列表",
     *     tags={"报名"},
     *     description="导出报名列表",
     *     operationId="exportRegistrationRecord",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="activity_id",
     *         in="query",
     *         description="标题",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="会员手机号",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="导出结果(true, false)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function exportRegistrationRecord(Request $request)
    {
        $params = $request->all('activity_id', 'start_time', 'mobile', 'end_time');
        if ($params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }
        if ($params['start_time'] && $params['end_time']) {
            $filter['created|gte'] = $params['start_time'];
            $filter['created|lte'] = $params['end_time'];
        }
        if ($params['activity_id']) {
            $filter['activity_id'] = $params['activity_id'];
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $count = $this->service->count($filter);
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob('selform_registration_record', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Definition(
     *     definition="RegistrationRecordInfo",
     *     description="报名活动信息",
     *     type="object",
     *     @SWG\Property( property="record_id", type="string", example="48", description="记录id"),
     *                          @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                          @SWG\Property( property="user_id", type="string", example="20342", description="用户id"),
     *                          @SWG\Property( property="mobile", type="string", example="17621716237", description="用户手机号"),
     *                          @SWG\Property( property="status", type="string", example="pending", description="状态"),
     *                          @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="区块一标题", description="活动名称"),
     *                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                  @SWG\Property( property="formdata", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="36", description="ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                          @SWG\Property( property="field_title", type="string", example="团长姓名", description="表单项标题(中文描述)"),
     *                                          @SWG\Property( property="field_name", type="string", example="username", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                          @SWG\Property( property="form_element", type="string", example="text", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                          @SWG\Property( property="answer", type="string", example="吴琼", description="回答内容"),
     *                                       ),
     *                                  ),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="reason", type="string", example="null", description="申请售后原因 | 审核拒绝原因 | 审核不通过原因 | 拒绝原因 | 审核失败原因"),
     *                          @SWG\Property( property="created", type="string", example="1608272078", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1608272078", description="修改时间"),
     *                          @SWG\Property( property="wxapp_appid", type="string", example="wx912913df9fef6ddd", description="会员小程序appid"),
     *                          @SWG\Property( property="open_id", type="string", example="oHxgH0eB5RArTLq6ZCsh8DnQc4KY", description="用户open_id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="created_date", type="string", example="2020-12-18 14:14:38", description="创建时间"),
     *                          @SWG\Property( property="activity_name", type="string", example="qqqq", description="活动名称 "),
     *                          @SWG\Property( property="start_time", type="string", example="1607961600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1609430399", description="活动结束时间"),
     *                          @SWG\Property( property="start_date", type="string", example="2020-12-15 00:00:00", description="开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2020-12-31 23:59:59", description="有效期结束时间 "),
     *                          @SWG\Property( property="join_limit", type="string", example="111", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="1", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="1", description="是否小程序模板通知"),
     *                          @SWG\Property( property="create_date", type="string", example="2020-12-18 14:14:38", description="创建时间"),
     * )
     */
}
