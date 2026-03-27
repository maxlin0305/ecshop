<?php

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\PushLogsService;
use Illuminate\Http\Request;

class PushLogs extends BaseController
{
    private $pushLogsService;

    public function __construct(PushLogsService $pushLogsService)
    {
        $this->pushLogsService = new $pushLogsService();
    }

    public function getCompanysPushLogs(Request $request)
    {
        $inputData = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $filter = ['company_id' => $companyId];
        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 20;
        $status = $inputData['status']?? null;
        if (isset($status)) {
            $filter['status'] = $status;
        }
        $result = $this->pushLogsService->getPushLogList($filter, $page, $pageSize);

        if(!empty($result['list'])){
            $type = config('order.pushMessageType');
            foreach ($result['list'] as &$v){
                $v['type']    = $type[$v['type']] ?? $v['type'];
            }
        }

        return $this->response->array($result);
    }

    public function repush(Request $request)
    {
        $inputData = $request->input();
        $id = $inputData['id']?? 0;
        $result = $this->pushLogsService->repush($id);

        return $this->response->array($result);
    }

}
