<?php

namespace SelfserviceBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;
use SelfserviceBundle\Services\FormTemplateService;

class RegistrationActivityController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationActivity",
     *     summary="获取指定报名活动",
     *     tags={"报名"},
     *     description="获取指定报名活动",
     *     operationId="getRegistrationActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="activeing", description="活动状态"),
     *                  @SWG\Property( property="activity_info", type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="28", description="活动ID"),
     *                          @SWG\Property( property="temp_id", type="string", example="15", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="苹果新品预售报名", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1586361600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1586620799", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="9", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1586495521", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1586495527", description="修改时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="formdata", type="object",
     *                                  @SWG\Property( property="id", type="string", example="15", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="tem_name", type="string", example="超全的模板", description="表单模板名称"),
     *                                  @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                  @SWG\Property( property="form_style", type="string", example="single", description="表单关键指数, single:单页问卷, multiple:多页问卷"),
     *                                  @SWG\Property( property="header_link_title", type="string", example="XX新品预售报名", description="头部文字"),
     *                                  @SWG\Property( property="header_title", type="string", example="帮助公众号获取用户信息，进行用户管理", description="头部文字内容"),
     *                                  @SWG\Property( property="bottom_title", type="string", example="苹果", description="表单关键指数"),
     *                                  @SWG\Property( property="key_index", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="key_index"),
     *                                  ),
     *                                  @SWG\Property( property="tem_type", type="string", example="ask_answer_paper", description="表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"),
     *                                  @SWG\Property( property="content", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="title", type="string", example="", description="标题"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="formdata", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                                  @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                                                  @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                                  @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                                  @SWG\Property( property="status", type="string", example="1", description="自行更改字段描述"),
     *                                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                                  @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                                  @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                                                  @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                               ),
     *                                          ),
     *                                       ),
     *                                  ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationActivity(Request $request)
    {
        $authInfo = $request->get('auth');
        if ($authInfo['user_id'] ?? 0) {
            $filter = [
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id'],
                'activity_id' => $request->get('activity_id'),
            ];
            $registrationRecordService = new RegistrationRecordService();
            $recordList = $registrationRecordService->getRocordList($filter);
            $result['status'] = 'already';
        }
        $result['activity_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'activity_id' => $request->get('activity_id'),
        ];
        $registrationActivityService = new RegistrationActivityService();
        $activityinfo = $registrationActivityService->getInfo($filter);
        if (empty($activityinfo)) {
            throw new ResourceException('活动不存在');
        }
        if ($activityinfo && $activityinfo['end_time'] <= time()) {
            $result['status'] = 'ended';
            return $this->response->array($result);
        }
        if (!($recordList['total_count'] ?? 0) || $activityinfo['join_limit'] > ($recordList['total_count'] ?? 0)) {
            $formTemplateService = new FormTemplateService();
            $temp = $formTemplateService->getInfo(['company_id' => $authInfo['company_id'], 'id' => $activityinfo['temp_id']]);
            $activityinfo['formdata'] = $temp;
            $result['activity_info'] = $activityinfo;
            $result['status'] = 'activeing';
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationRecordList",
     *     summary="获取指定报名日志",
     *     tags={"报名"},
     *     description="获取指定报名日志",
     *     operationId="getRegistrationRecordList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="11", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/RegistrationRecordInfo"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationRecordList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        if ($request->get('activity_id')) {
            $filter['activity_id'] = $request->get('activity_id');
        }
        $filter['user_id'] = $authInfo['user_id'];
        $registrationRecordService = new RegistrationRecordService();
        $result = $registrationRecordService->getRocordList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/registrationSubmit",
     *     summary="报名提交",
     *     tags={"报名"},
     *     description="报名提交",
     *     operationId="registrationSubmit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Parameter( name="formdata[content]", in="query", description="报名内容(json)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="record_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="手机号"),
     *                  @SWG\Property( property="wxapp_appid", type="string", example="", description="会员小程序appid"),
     *                  @SWG\Property( property="open_id", type="string", example="", description="用户open_id"),
     *                  @SWG\Property( property="status", type="string", example="pending", description="状态"),
     *                  @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="区块一标题", description="名称"),
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
     *                  @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
     *                  @SWG\Property( property="created", type="string", example="1612441632", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612441632", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function registrationSubmit(Request $request)
    {
        $allParams = $request->all('activity_id', 'formdata');

        $authInfo = $request->get('auth');
        if (!($authInfo['user_id'] ?? 0)) {
            throw new ResourceException('只有会员才可以参与报名');
        }
        $params['user_id'] = $authInfo['user_id'];
        $params['wxapp_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['open_id'] = $authInfo['open_id'] ?? '';
        $params['mobile'] = $authInfo['mobile'];
        $params['company_id'] = $authInfo['company_id'];
        $params['activity_id'] = $request->get('activity_id', 0);
        if (!$params['activity_id']) {
            throw new ResourceException('请指定报名活动');
        }

        $formdata = $request->get('formdata', null);
        $params['content'] = $formdata['content'];
        if (!$params['content']) {
            throw new ResourceException('报名数据不能为空');
        }
        $params['content'] = is_array($params['content']) ? $params['content'] : json_decode($params['content'], true);

        foreach ($params['content'] as $key => $card) {
            foreach ($card['formdata'] as $k => $value) {
                if (($value['is_required'] ?? false) == 'true' && !($value['answer'] ?? null)) {
                    throw new ResourceException((($card['title'] ?? 0) ? $card['title']."下的" : '').$value['field_title'].'必填');
                }
                if ($value['answer'] ?? null) {
                    $params['content'][$key]['formdata'][$k]['answer'] = is_array($value['answer']) ? implode(', ', $value['answer']) : $value['answer'];
                }
            }
        }
        $params['content'] = json_encode($params['content']);
        $registrationRecordService = new RegistrationRecordService();
        $result = $registrationRecordService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationRecordInfo",
     *     summary="获取指定报名日志",
     *     tags={"报名"},
     *     description="获取指定报名日志",
     *     operationId="getRegistrationRecordInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="record_id", in="query", description="记录ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationRecordInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationRecordInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $id = $request->get('record_id');
        $registrationRecordService = new RegistrationRecordService();
        $result = $registrationRecordService->getRocordInfo($id);
        if ($result['user_id'] != $authInfo['user_id']) {
            throw new ResourceException('信息有误');
        }
        return $this->response->array($result);
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
     *                          @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
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
