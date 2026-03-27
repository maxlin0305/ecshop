<?php

namespace CommunityBundle\Services;

class CommunitySettingService
{
    private $companyId;
    private $distributorId;
    public function __construct($companyId, $distributorId)
    {
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
    }

    public function getSetting()
    {
        $config = [
            'condition_type' => 'num',
            'condition_money' => 0,
            'aggrement' => '',
            'explanation' => '',
            'rebate_ratio' => 0,
            'distance_limit' => 10000,//距离限制
        ];
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId());
        if ($result) {
            $result = json_decode($result, true);
        }
        $result = array_merge($config, $result ?: []);
        //返回h5的分享链接subpages/community/apply-chief
        $result['regimental_commander_address'] = env('H5_URL', 'https://th5.smtengo.com') . "/subpages/community/apply-chief";
        return $result;
    }


    public function saveSetting($data)
    {
        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId(), json_encode($data));

        return $this->getSetting();
    }

    public function getRedisId()
    {
        return 'community_setting:'.$this->companyId.'_'.$this->distributorId;
    }
}
