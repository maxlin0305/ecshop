<?php

namespace CompanysBundle\Services;

use CompanysBundle\Entities\PushLogs;
use Illuminate\Support\Facades\Http;

class PushLogsService
{
    /**
     * @var pushLogsRepository
     */
    private $pushLogsRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->pushLogsRepository = app('registry')->getManager('default')->getRepository(PushLogs::class);
    }

    public function getPushLogList($filter, $page, $pageSize, $orderBy = ['created' => 'desc'])
    {
        $data = $this->pushLogsRepository->lists($filter, '*', $page, $pageSize,$orderBy);
        return $data;
    }

    //重新推送
    public function repush($id)
    {
        $data = $this->pushLogsRepository->getInfoById($id);
        if (empty($data)) {
            return ['status' => 1, 'errmsg' => 'id不存在'];
        }

        $method = strtolower($data['method']);
        $startTime = microtime(true);
        if ($method == 'get') {
            $response = Http::get($data['request_url'], $data['request_params']);
            $resp = $response->json();
            $status = $response->status();
        }
        //elseif ($method == 'post') {
        //$response = Http::post($data['request_url'], $data['request_params']);
        elseif ($method == 'post' || $method == 'put' ) {
            // 到货通知需要添加header头
            if($data['type'] == 1 ){
                $header_param = [
                    'Content-Type'  =>'application/json; charset=utf-8' ,
                    'Accept'        => "application/json",
                    'appKey'        => config('common.zgj_app_key'),
                    'appSecret'     => config('common.zgj_app_secret'),
                ];
            }
            $headers = [];
            if(!empty($header_param)){
                foreach ($header_param as $key => $value) {
                    $headers[]   =  $key . ": " . $value;
                }
            }
            $resp   = curl_post($data['request_url'],$data['request_params'],$headers,$method,'智管家');
            $status = $resp['code'] ?? 0 ;
            $resp['body'] = !is_array($resp['body']) ? json_decode($resp['body']) : $resp['body'] ;
            $resp   = json_encode($resp,256);
        } else {
            return ['status' => 1, 'errmsg' => '原数据请求方法无法识别(仅支持get|post)'];
        }
        $endTime = microtime(true);
        // 计算耗时
        $duration = ($endTime - $startTime) * 1000; // 转换为毫秒

        // 处理响应

        $updateData = [
            'response_data' => $resp,
            'http_status_code' => $status,
            'status' => $status == 200? 0 : 1,
            'push_time' => date('Y-m-d H:i:s'),
            'cost_time' => intval($duration),
            'retry_times' => $data['retry_times'] +1,
            'updated' => time()
        ];

        $this->pushLogsRepository->updateById($id, $updateData);

        return ['status' => 0, 'errmsg' => ''];
    }
}
