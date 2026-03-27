<?php

namespace WsugcBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WsugcBundle\Services\PostService;
use WsugcBundle\Services\SettingService;
use WsugcBundle\Services\PostTopicService;
use WsugcBundle\Services\MpsSftpService;
use WsugcBundle\Services\MpsFeedDownloadService;


class SettingController extends Controller
{
     /**
     * @SWG\Get(
     *     path="/h5app/wxapp/ugc/post/setting",
     *     summary="获取设置",
     *     tags={"笔记"},
     *     description="获取设置",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="类型，比如video。默认传video", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id']??1;

        $type = $request->input('type', '');
        $filter['type']=$type;
        $service = new SettingService();
        $result  = $service->getSettingList($filter,'*');
        $setting=[];
        if (empty($result)) {
            //return $this->response->noContent();

            return $this->response->array($setting);

        } else {
            if($result['list']??null){
                foreach($result['list'] as $k=>$v){
                    $setting[$v['keyname']]=$v['value'];
                }
            }
            return $this->response->array($setting);
        }
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/mps/pullfeed",
     *     summary="拉取feed",
     *     tags={"MPS-Feed"},
     *     description="拉取feed",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="类型，比如video。默认传video", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="post_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    function pullfeed(){
        set_time_limit(0);//不超时
        ini_set('memory_limit','2048M');
        $mpsFeedDownloadService=new MpsFeedDownloadService();
        $shouldQueue=false;
        $fromJob=1;
        $result=$mpsFeedDownloadService->schedulePullFeed($fromJob,$shouldQueue);
        return $this->response->array($result);
    }
}
