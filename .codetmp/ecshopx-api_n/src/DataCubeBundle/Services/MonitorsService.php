<?php

namespace DataCubeBundle\Services;

use DataCubeBundle\Entities\Monitors;
use DataCubeBundle\Entities\RelSources;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\OpenPlatform;

class MonitorsService
{
    /** @var monitorsRepository */
    private $monitorsRepository;
    private $relSourcesRepository;

    /** @var openPlatform */
    private $openPlatform;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->monitorsRepository = app('registry')->getManager('default')->getRepository(Monitors::class);
        $this->relSourcesRepository = app('registry')->getManager('default')->getRepository(RelSources::class);
    }

    /**
     * 添加
     *
     * @param array params 跟踪链接数据
     * @return array
     */
    public function addMonitors(array $params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'wxappid' => $params['wxappid'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
            'page_name' => $params['page_name'],
        ];
        $filter = [
            'wxappid' => $params['wxappid'],
            'company_id' => $params['company_id'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
        ];
        $oldInfo = $this->monitorsRepository->findOneBy($filter);
        if ($oldInfo) {
            throw new ResourceException('此链接已经添加过，不能重复添加.');
        }

        $getAuthorizerInfo = $this->openPlatform->getAuthorizerInfo($params['wxappid']);
        $data['nick_name'] = $getAuthorizerInfo['nick_name'];

        $rs = $this->monitorsRepository->create($data);

        return $rs;
    }

    /**
     * 删除
     *
     * @param array filter
     * @return bool
     */
    public function deleteMonitors($filter)
    {
        $monitorsInfo = $this->monitorsRepository->get($filter['monitor_id']);

        if ($filter['company_id'] != $monitorsInfo['company_id']) {
            throw new ResourceException('删除跟踪链接信息有误.');
        }
        if (!$filter['monitor_id']) {
            throw new ResourceException('跟踪链接id不能为空.');
        }

        return $this->monitorsRepository->delete($filter['monitor_id']);
    }

    /**
     * 删除一条来源监控信息
     *
     * @param array filter
     * @return bool
     */
    public function deleteRelSources($filter)
    {
        if (!$filter['company_id']) {
            throw new ResourceException('删除跟踪链接信息有误.');
        }
        if (!$filter['monitor_id']) {
            throw new ResourceException('删除来源监控缺少参数.');
        }
        if (!$filter['source_id']) {
            throw new ResourceException('删除来源监控缺少参数.');
        }

        return $this->relSourcesRepository->deleteOneRelSource($filter['monitor_id'], $filter['source_id'], $filter['company_id']);
    }

    /**
     * 获取
     *
     * @param inteter monitors_id 跟踪链接id
     * @return array
     */
    public function getMonitorsDetail($monitor_id)
    {
        $monitorInfo = $this->monitorsRepository->get($monitor_id);

        return $monitorInfo;
    }

    /**
     * 获取
     *
     * @param array filter
     * @return array
     */
    public function getMonitorsList($filter, $page, $pageSize, $orderBy = ['monitor_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $monitorsList = $this->monitorsRepository->list($filter, $orderBy, $pageSize, $page);

        return $monitorsList;
    }

    /**
     * 修改
     *
     * @param array params 提交的
     * @return array
     */
    public function updateMonitors($params)
    {
        $monitorsInfo = $this->monitorsRepository->get($params['monitor_id']);

        if ($params['company_id'] != $monitorsInfo['company_id']) {
            throw new ResourceException('请确认您的门店信息后再提交.');
        }
        $data = [
            'company_id' => $params['company_id'],
            'wxappid' => $params['wxappid'],
            'nick_name' => $params['nick_name'],
            'monitor_path' => $params['monitor_path'],
            'monitor_path_params' => $params['monitor_path_params'],
        ];

        $rs = $this->monitorsRepository->update($params['monitor_id'], $data);

        return $rs;
    }

    public function relSources($params)
    {
        $conn = app('registry')->getConnection('default');

        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $params['company_id'],
                'monitor_id' => $params['monitor_id'],
            ];
            $conn->delete($this->relSourcesRepository->table, $filter);
            if ($params['sourceIds']) {
                foreach ($params['sourceIds'] as $source_id) {
                    $data = [
                        'company_id' => $params['company_id'],
                        'monitor_id' => $params['monitor_id'],
                        'source_id' => $source_id,
                    ];
                    $conn->insert($this->relSourcesRepository->table, $data);
                }
            }
            $conn->commit();
            return $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getRelSources($params)
    {
        $relMonitorsList = $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        $sourcesService = new SourcesService();
        foreach ($relMonitorsList as &$v) {
            $sourceInfo = $sourcesService->getSourcesDetail($v['source_id']);
            $v['source_name'] = $sourceInfo['source_name'];
        }
        return $relMonitorsList;
    }

    public function getStats($params)
    {
        $dateFilter['date_type'] = $params['date_type'];
        $dateFilter['date_range'] = [];
        if ($params['date_type'] == 'custom') {
            $dateFilter['date_range']['start'] = $params['begin_date'];
            $dateFilter['date_range']['stop'] = $params['end_date'];
        }

        $sourcesService = new SourcesService();
        $relMonitorsList = $this->relSourcesRepository->getListByMonitorId($params['monitor_id'], $params['company_id']);
        $trackService = new TrackService();
        foreach ($relMonitorsList as &$v) {
            $sourceInfo = $sourcesService->getSourcesDetail($v['source_id']);
            $v['source_name'] = $sourceInfo['source_name'];
            $oneFilter = [
                'company_id' => $v['company_id'],
                'monitor_id' => $v['monitor_id'],
                'source_id' => $v['source_id'],
            ];
            // $v['view_num'] = $trackService->getViewNum(array_merge($dateFilter, $oneFilter));
            $v['fans_num'] = $trackService->getFansNum(array_merge($dateFilter, $oneFilter));
            $v['register_num'] = $trackService->getRegisterNum(array_merge($dateFilter, $oneFilter));
            $v['entries_num'] = $trackService->getEntriesNum(array_merge($dateFilter, $oneFilter));

            // $v['conversion_rate'] = ($v['view_num'] > 0) ? round( $v['entries_num'] / $v['view_num'] * 100 , 2) . "％" : '0%';
            $v['register_entries_rate'] = ($v['register_num'] > 0) ? round($v['entries_num'] / $v['register_num'] * 100, 2) . "％" : '0%';
        }

        $totalFilter = [
            'company_id' => $params['company_id'],
            'monitor_id' => $params['monitor_id'],
        ];
        $statsTotal = [
            // 'total_view_num' => $trackService->getTotalViewNum(array_merge($dateFilter, $totalFilter)),
            'total_fans_num' => $trackService->getTotalFansNum(array_merge($dateFilter, $totalFilter)),
            'total_register_num' => $trackService->getTotalRegisterNum(array_merge($dateFilter, $totalFilter)),
            'total_entries_num' => $trackService->getTotalEntriesNum(array_merge($dateFilter, $totalFilter)),
        ];
        $statsTotal['total_register_entries_rate'] = ($statsTotal['total_register_num'] > 0) ? round($statsTotal['total_entries_num'] / $statsTotal['total_register_num'] * 100, 2) . "％" : '0%';
        // $statsTotal['total_conversion_rate'] = ($statsTotal['total_view_num'] > 0) ? round( $statsTotal['total_entries_num'] / $statsTotal['total_view_num'] * 100 , 2) . "％" : '0%';

        $result = [
            'stats_total' => $statsTotal,
            'stats_list' => $relMonitorsList,
        ];
        return $result;
    }

    public function getMonitorWxaCode($monitorId, $sourceId, $isBase64 = 0)
    {
        $monitorInfo = $this->monitorsRepository->get($monitorId);
        $app = $this->openPlatform->getAuthorizerApplication($monitorInfo['wxappid']);
        $data['page'] = $monitorInfo['monitor_path'];
        $paramsStr = $monitorInfo['monitor_path_params'] . '&s='.$sourceId . '&m=' . $monitorId;
        // $paramsStr = $monitorInfo['monitor_path_params'] . '&ms='.$monitorId .'_'.$sourceId;
        // $paramsStr = trim($paramsStr, '&');
        // parse_str($paramsStr, $scene);
        // $data['scene'] = urlencode($paramsStr);
        if (!$isBase64) {
            $data['width'] = 1280;
        }
        $scene = trim($paramsStr, '&');
        $wxaCode = $app->app_code->getUnlimit($scene, $data);
        if ($isBase64) {
            $base64 = 'data:image/jpg;base64,' . base64_encode($wxaCode);
            return ['base64Image' => $base64];
        } else {
            return $wxaCode;
        }
    }
}
