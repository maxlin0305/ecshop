<?php

namespace CompanysBundle\Services;

use CompanysBundle\Entities\PushLogs;
use CompanysBundle\Entities\PushMessage;
use Illuminate\Support\Facades\Http;
use Exception;

class PushMessageService
{
    /**
     * @var pushLogsRepository
     */
    private $pushLogsRepository;
    private $pushMessageRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->pushLogsRepository = app('registry')->getManager('default')->getRepository(PushLogs::class);
        $this->pushMessageRepository = app('registry')->getManager('default')->getRepository(PushMessage::class);
    }

    # 创建到货通知推送消息
    public function createPushMessage($method = 'get',$request_url = '',$request_params = [],$type = 0,$type_name = '',$company_id = 1 ,$merchant_id = 0,$distributor_id = 0,$user_id = 0,$header_param=[]){

        // type : 1到货通知
        $add_push_message = [
            'company_id'     => $company_id ,
            'merchant_id'    => $merchant_id ,
            'distributor_id' => $distributor_id ,
            'user_id'        => $user_id ,
            'msg_name'       => $type_name,
            'msg_type'       => $type,
            'content'        => '',
            'is_read'        => 0,
            'create_time'    => time()
        ];
        $add_message_res = $this->pushMessageRepository->create($add_push_message);
        if(!empty($add_message_res) && intval($add_message_res['id']) > 0 ){
            # 新增参数message_id
            $request_params['messageId'] = $add_message_res['id'];
            # 发起请求
            $method = strtolower($method);
            $startTime = microtime(true);
            if ($method == 'get') {
                $response = Http::get($request_url, $request_params);
                $resp = $response->json();
                $status = $response->status();
            } elseif ($method == 'post' || $method == 'put' ) {
                $headers = [];
                if(!empty($header_param)){
                    foreach ($header_param as $key => $value) {
                        $headers[]   =  $key . ": " . $value;
                    }
                }
                $resp   = curl_post($request_url,$request_params,$headers,$method,'智管家');
                $status = $resp['code'] ?? 0 ;
                $resp['body'] = !is_array($resp['body']) ? json_decode($resp['body']) : $resp['body'] ;
                $resp   = json_encode($resp,256);
            } else {
                throw new Exception("原数据请求方法无法识别(仅支持get|post)");
            }
            $endTime = microtime(true);
            // 计算耗时
            $duration = ($endTime - $startTime) * 1000; // 转换为毫秒
            // 处理响应
            $request_params = json_encode($request_params,256);
            $addLogData = [
                'response_data'    => $resp,
                'company_id'       => $company_id ,
                'request_url'      => $request_url,
                'request_params'   => $request_params,
                'http_status_code' => $status,
                'status'           => $status == 200? 0 : 1,
                'push_time'        => date('Y-m-d H:i:s'),
                'method'           => $method,
                'type'             => $type ,
                'cost_time'        => intval($duration),
                'retry_times'      => 0,
                'created'          => time()
            ];
            $this->pushLogsRepository->create($addLogData);
            $update_push_message = [
                'content'     => $request_params,
                'update_time' => time() ,
            ];
            $this->pushMessageRepository->updateById($add_message_res['id'],$update_push_message);
        }
        return true;
    }

    // 列表
    public function getPushMessageList($filter, $page = 1, $pageSize = -1, $orderBy = ['id' => 'DESC'])
    {
        $result = $this->pushMessageRepository->lists($filter,"*", $page, $pageSize,$orderBy);
        return $result;
    }

    // 标记为已读
    public function updatePushMessageBy($filter,$update)
    {
        $this->pushMessageRepository->updateBy($filter,$update);
        return true;
    }




}
