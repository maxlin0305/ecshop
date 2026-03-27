<?php

namespace TdksetBundle\Services;

use Dingo\Api\Exception\DeleteResourceFailedException;

class TdkGlobalService
{
    public $key = 'TdkGlobal_';

    public function __construct()
    {
    }

    /**
     * 获取信息
     */
    public function getInfo($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->key . $companyId);

        if (!empty($result) and $result != 'null') {
            return json_decode($result, true);
        } else {
            $data['title'] = '';
            $data['mate_description'] = '';
            $data['mate_keywords'] = '';
            return $data;
        }
    }

    /**
     * 保存
     */
    public function saveSet($companyId, $data)
    {
        $redis = app('redis')->connection('default');
        $info = $redis->set($this->key . $companyId, json_encode($data));
        if (!empty($info)) {
            return [];
        } else {
            throw new DeleteResourceFailedException("保存失败");
        }
    }
}
