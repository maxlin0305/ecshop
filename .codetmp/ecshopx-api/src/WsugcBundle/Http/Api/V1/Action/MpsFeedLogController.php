<?php
//话题
namespace WsugcBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\MpsFeedLogService;
use EspierBundle\Jobs\ExportFileJob;
use EspierBundle\Traits\GetExportServiceTraits;
use WsugcBundle\Services\MpsFeedDownloadService;
use WsugcBundle\Services\MpsFeedUploadService;
use YoushuBundle\Services\YoushuService;
use YoushuBundle\Services\TaskService;

class MpsFeedLogController extends Controller
{
    use GetExportServiceTraits;

    //public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new MpsFeedLogService();
        $this->limit = 20;
    }
    /**
     * @SWG\Get(
     *     path="/mps/feedlog/list",
     *     summary="获取商品Feed导入日志",
     *     tags={"MPS-feed商品"},
     *     description="获取商品Feed导入日志",
     *     operationId="getMpsFeedLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=false, type="string"),
     *     @SWG\Parameter( name="bn", in="query", description="货号", required=false, type="string"),
     *     @SWG\Parameter( name="unique_id", in="query", description="unique_id", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态。succ 成功 failed 失败", type="string"),
     *     @SWG\Parameter( name="file_name", in="query", description="文件名", type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getMpsFeedLogList(Request $request)
    {
        $params = $request->all();
        $page = $request->get('page', 1);
        $size = $request->get('pageSize', $this->limit);
        $orderBy = ['log_id' => 'DESC'];
        $filter = $this->_getFilter($request);
        $cols=$params['cols']??'*';
        $result = $this->service->getLogsList($filter, $cols, $page, $size, $orderBy);
        return $this->response->array($result);
    }
   private function _getFilter($request)
    {
        $params = $request->all('request_id','file_name','request_name', 'request_api', 'request_url','request_usetime','request_params','response_text','response_code','response_httpcode','request_type', 'request_from','request_to','cat_id','mobile','bn','order_id','rel_id','start_time','unique_id','end_time','status');
        foreach($params as $k=>$v){
            if($v){
                $filter[$k]=$v;
                if(in_array($k,['file_name','request_api','request_params','response_text','request_url'])){
                    $filter[$k.'|contains']=$v;
                    unset($filter[$k]);
                }
            }
        }
        //创建时间范围
        if (isset($params['start_time'],$params['start_time']) && $params['start_time']!='') {
            $filter['created|gte'] = $params['start_time'];
            unset($filter['start_time']);
        }
        if (isset($params['end_time'],$params['end_time']) && $params['end_time']!='') {
            $filter['created|lte'] = $params['end_time'];
            unset($filter['end_time']);
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        return $filter;
    }
    /**
     * @SWG\Get(
     *     path="/mps/feedlog/export",
     *     summary="导出feed商品日志",
     *     tags={"MPS-feed商品"},
     *     description="导出feed商品日志",
     *     operationId="exportMpsFeedLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="文件名",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="开始时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="结束时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="bn",
     *         in="query",
     *         description="bn",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="导出结果(true, false)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function exportMpsFeedLog(Request $request)
    {
        $filter = $this->_getFilter($request);
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

        $gotoJob = (new ExportFileJob('mpsfeed_apilogs', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
   /**
     * @SWG\Get(
     *     path="/mps/pullfeed",
     *     summary="手动拉取feed文件",
     *     tags={"MPS-feed商品"},
     *     description="手动拉取feed文件",
     *     operationId="pullfeed",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function pullFeed(){
        set_time_limit(0);//不超时
        $mpsFeedDownloadService=new MpsFeedDownloadService();
        
        $queue=1;
        $fromJob=0;
        $result=$mpsFeedDownloadService->schedulePullFeed($fromJob,$queue);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/mps/ordercsv/genPaidCsvFile",
     *     summary="生成支付csv文件",
     *     tags={"MPS-feed商品"},
     *     description="生成支付csv文件",
     *     operationId="getPointSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function genPaidCsvFile(){
        $mpsFeedDownloadService=new MpsFeedDownloadService();
        $mpsFeedDownloadService->generateOrderCsvFile();
        echo 'generateOrderCsvFile';
    }
   /**
     * @SWG\Get(
     *     path="/mps/feed/unmarketNotInMpsFeed",
     *     summary="下架/清空库存 不在feed里商品",
     *     tags={"MPS-feed商品"},
     *     description="下架/清空库存 不在feed里商品",
     *     operationId="unmarketNotInMpsFeed",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function unmarketNotInMpsFeed(){
        $mpsFeedUploadService=new MpsFeedUploadService();
        $mpsFeedUploadService->unmarketNotInMpsFeed();
        echo 'generateOrderCsvFile';
    }
      /**
     * @SWG\Get(
     *     path="/mps/feed/scheduleApproveStatusDefaultItem",
     *     summary="定时上下架商品根据库存",
     *     tags={"MPS-feed商品"},
     *     description="定时上下架商品根据库存",
     *     operationId="scheduleApproveStatusDefaultItem",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function scheduleApproveStatusDefaultItem(){
        $mpsFeedUploadService=new MpsFeedUploadService();
        $result=$mpsFeedUploadService->scheduleApproveStatusDefaultItem();
        //echo 'scheduleApproveStatusDefaultItem';
        return $this->response->array($result);

    }

    /**
     * @SWG\Get(
     *     path="/mps/feedlog/youshu/addGoods",
     *     summary="有数推送商品",
     *     tags={"MPS-feed商品"},
     *     description="有数推送商品",
     *     operationId="youshuaddGoods",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function YoushuAddGoods(){
        $youshuTaskService=new TaskService();
        $result=$youshuTaskService->addGoods();
        //echo 'scheduleApproveStatusDefaultItem';
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/mps/feedlog/youshu/addCategory",
     *     summary="有数推送商品类目",
     *     tags={"MPS-feed商品类目"},
     *     description="有数推送商品类目",
     *     operationId="youshuAddCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    function YoushuAddCategory(){
        $youshuTaskService=new TaskService();
        $result=$youshuTaskService->addCategory();
        //echo 'scheduleApproveStatusDefaultItem';
        return $this->response->array($result??[]);
    }
}
?>