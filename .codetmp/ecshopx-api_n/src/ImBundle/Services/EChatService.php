<?php

namespace ImBundle\Services;

use Dingo\Api\Exception\ResourceException;

class EChatService
{
    public function getInfo($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        if ($result) {
            $result = json_decode($result, true);
        } else {
            $result = [
                'is_open' => false,
                'echat_url' => ''
            ];
        }
        return $result;
    }

    /**
     * 保存echat配置信息
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function saveInfo($companyId, $data)
    {
        $rules = [
            'is_open' => ['required', '开启状态必填'],
            'echat_url' => ['required', '一洽客服链接地址必填'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId($companyId), json_encode($data));
        $result = $this->getInfo($companyId);
        return $result;
    }

    /**
     * im配置信息
     * @param $companyId
     * @return string
     */
    private function getRedisId($companyId)
    {
        return 'im:echat:' . $companyId;
    }
}
