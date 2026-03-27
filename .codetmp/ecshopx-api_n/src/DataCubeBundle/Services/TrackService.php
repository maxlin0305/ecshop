<?php

namespace DataCubeBundle\Services;

class TrackService
{
    private $prefix = 'datecube_tracklog';

    public function __construct()
    {
    }

    // 添加浏览人数日志
    public function addViewNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 添加粉丝人数日志
    public function addFansNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':fansnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':fansnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 添加购买人数日志
    public function addEntriesNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 添加注册人数日志
    public function addRegisterNum($params)
    {
        if (!$params['company_id'] || !$params['monitor_id'] || !$params['source_id']) {
            return false;
        }

        $date = date('Ymd');

        $list_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];
        $total_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        app('redis')->connection('datacube')->hincrby($list_key, $date, 1);
        app('redis')->connection('datacube')->hincrby($total_key, $date, 1);
    }

    // 获取浏览人数数量
    public function getViewNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取粉丝数数量
    public function getFansNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':fansnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取购买人数数量
    public function getEntriesNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取注册人数数量
    public function getRegisterNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $list_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':list:' . $params['source_id'];

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($list_key, $fields));

        return $total ?: 0;
    }

    // 获取总的浏览人数
    public function getTotalViewNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':viewnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取总的粉丝人数
    public function getTotalFansNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':fansnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取总的购买人数
    public function getTotalEntriesNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':entriesnum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    // 获取总的注册人数
    public function getTotalRegisterNum($params)
    {
        $selectDate = $this->__checkTime($params['date_type'], $params['date_range']);

        $total_key = $this->prefix . ':registernum:' . $params['company_id'] . '|' . $params['monitor_id'] . ':total';

        $_time = range(strtotime($selectDate['start']), strtotime($selectDate['stop']), 24 * 60 * 60);

        $fields = array_map(
            function ($v) {
                return date("Ymd", $v);
            },
            $_time
        );

        $total = array_sum(app('redis')->connection('datacube')->hmget($total_key, $fields));

        return $total ?: 0;
    }

    public function __checkTime($date_type, $filter = null)
    {
        switch ($date_type) {
            case 'today':
                return [
                    'start' => date('Ymd'),
                    'stop' => date('Ymd'),
                ];
                break;
            case 'yesterday':
                return [
                    'start' => date('Ymd', strtotime('-1 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'before7days':
                return [
                    'start' => date('Ymd', strtotime('-7 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'before30days':
                return [
                    'start' => date('Ymd', strtotime('-30 day')),
                    'stop' => date('Ymd', strtotime('-1 day')),
                ];
                break;
            case 'beforemonth':
                return [
                    'start' => date('Ymd', strtotime(date('Y-m-01') . ' -1 month')),
                    'stop' => date('Ymd', strtotime(date('Y-m-01') . ' -1 day')),
                ];
                break;
            case 'custom':
                return [
                    'start' => date('Ymd', strtotime($filter['start'])),
                    'stop' => date('Ymd', strtotime($filter['stop'])),
                ];
                break;
        }
    }
}
