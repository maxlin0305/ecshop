<?php

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityChief;
use CommunityBundle\Entities\CommunityChiefZiti;
use CommunityBundle\Repositories\CommunityChiefRepository;
use CommunityBundle\Repositories\CommunityChiefZitiRepository;
use Dingo\Api\Exception\ResourceException;

class CommunityChiefZitiService
{
    /**
     * @var CommunityChiefZitiRepository
     */
    private $entityRepository;
    /**
     * @var CommunityChiefRepository
     */
    private $entityChiefRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityChiefZiti::class);
        $this->entityChiefRepository = app('registry')->getManager('default')->getRepository(CommunityChief::class);
    }

    /**
     * 获取用户的自提列表
     */
    public function getChiefZitiList($chief_id)
    {
        return $this->entityRepository->getLists(['chief_id' => $chief_id]);
    }

    /**
     * 添加团长自提点
     * @param $user_id
     * @param $params
     * @return array
     */
    public function createChiefZiti($chief_id, $params)
    {

        $params['chief_id'] = $chief_id;
        $geocode = $this->geocode("{$params['province']}{$params['city']}{$params['area']}");
        if (!$geocode) {
            throw new ResourceException('地區轉換錯誤,請換個地址！');
        }
        $location = $geocode[0]['geometry']['location']??[];
        if (!isset($location['lat'], $location['lng'])) {
            throw new ResourceException('地區轉換錯誤,請換個地址！');
        }
        $params['lat'] = $location['lat'];
        $params['lng'] = $location['lng'];
        return $this->entityRepository->create($params);
    }



    /**/

    /**
     * 修改自提点
     * @param $user_id
     * @param $ziti_id
     * @param $params
     * @return array
     */
    public function updateChiefZiti($ziti_id, $params)
    {
        $geocode = $this->geocode("{$params['province']}{$params['city']}{$params['area']}");
        if (!$geocode) {
            throw new ResourceException('地區轉換錯誤,請換個地址！'.json_encode($geocode));
        }
        $location = $geocode[0]['geometry']['location']??[];
        if (!isset($location['lat'], $location['lng'])) {
            throw new ResourceException('地區轉換錯誤,請換個地址！'.json_encode($geocode));
        }
        $params['lat'] = $location['lat'];
        $params['lng'] = $location['lng'];
        return $this->entityRepository->updateOneBy(['ziti_id' => $ziti_id], $params);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }


    /**
     * 地址转经纬度
     */
    public function geocode($address)
    {
        $key = config('GOOGLE_MAP_KEY',"AIzaSyBmSZouTYm8ViLN3MOrpFuCNvCJfBriaSs");
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if ($response['status'] !== 'OK') {
            return false;
        }
        return $response['results'];

    }
}
