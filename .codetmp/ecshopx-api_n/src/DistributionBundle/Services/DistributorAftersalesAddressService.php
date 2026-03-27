<?php

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorAftersalesAddress;
use ThirdPartyBundle\Services\Map\MapService;

class DistributorAftersalesAddressService
{
    private $entityRepository;
    private $distributorAfterSalesAddressRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->distributorAfterSalesAddressRepository = app('registry')->getManager('default')->getRepository(DistributorAftersalesAddress::class);
    }

    /**
     * 设置店铺售后地址
     * @param $data
     */
    public function setDistributorAfterSalesAddress($data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 获取经纬度
            $location = MapService::make($data['company_id'])->getLatAndLng($data['city'], $data['address']);
            if (empty($location->getLng()) || empty($location->getLat())) {
                throw new ResourceException('地址识别错误，请检查高德地图配置');
            }
            $data['lng'] = $location->getLng();
            $data['lat'] = $location->getLat();

            if (is_array($data['distributor_id']) && !empty($data['distributor_id'])) {
                foreach ($data['distributor_id'] as $distributor_id) {
                    $data_per = [
                        'distributor_id' => $distributor_id,
                        'name' => $data['name'] ?? '',
                        'province' => $data['province'],
                        'city' => $data['city'],
                        'area' => $data['area'],
                        'regions_id' => $data['regions_id'],
                        'regions' => $data['regions'],
                        'address' => $data['address'],
                        'lng' => $data['lng'],
                        'lat' => $data['lat'],
                        'company_id' => $data['company_id'],
                        'mobile' => $data['mobile'],
                        'contact' => $data['contact'] ?? null,
                        'hours' => $data['hours'] ?? '',
                        'merchant_id' => $data['merchant_id'],
                        'return_type' => $data['return_type'],
                    ];
                    $result = $this->distributorAfterSalesAddressRepository->create($data_per);
                }
                $conn->commit();
            } else {
                $data_per = [
                    'distributor_id' => $data['distributor_id'] ?? 0,
                    'name' => $data['name'] ?? '',
                    'province' => $data['province'],
                    'city' => $data['city'],
                    'area' => $data['area'],
                    'regions_id' => $data['regions_id'],
                    'regions' => $data['regions'],
                    'address' => $data['address'],
                    'lng' => $data['lng'],
                    'lat' => $data['lat'],
                    'company_id' => $data['company_id'],
                    'mobile' => $data['mobile'],
                    'contact' => $data['contact'] ?? null,
                    'hours' => $data['hours'] ?? '',
                    'merchant_id' => $data['merchant_id'],
                    'return_type' => $data['return_type'],
                ];
                $result = $this->distributorAfterSalesAddressRepository->create($data_per);
                $conn->commit();
            }
            return ['status' => true, 'result' => $result];
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
    }
    /**
     * 修改店铺售后地址
     * @param $filter
     * @param $data
     */
    public function updateDistributorAfterSalesAddress($filter, $data)
    {
        try {
            $result = $this->distributorAfterSalesAddressRepository->updateOneBy($filter, $data);
            return ['status' => true, 'result' => $result];
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * 获取店铺售后地址列表
     * @param $filter
     * @param $data
     */
    public function getDistributorAfterSalesAddress($filter, $page, $page_size, $orderBy = ['distributor_id' => 'DESC'])
    {
        $result = $this->distributorAfterSalesAddressRepository->lists($filter, '*', $page, $page_size, $orderBy);
        if (count($result['list']) == 0) {
            return $result;
        }
        $ids = [];
        foreach ($result['list'] as $value) {
            array_push($ids, $value['distributor_id']);
        }
        $filter = [
            'distributor_id' => $ids,
        ];
        $distributors = $this->entityRepository->getLists($filter);
        foreach ($distributors as $distributor) {
            foreach ($result['list'] as &$value) {
                if ($distributor['distributor_id'] == $value['distributor_id']) {
                    $value['name'] = $distributor['name'];
                    $value['logo'] = $distributor['logo'];
                }
            }
            unset($value);
        }

        return $result;
    }

    /**
     * 根据ID获取店铺售后地址详情
     * @param $filter
     * @return mixed
     */
    public function getDistributorAfterSalesAddressDetail($filter)
    {
        $address = $this->distributorAfterSalesAddressRepository->getInfo($filter);
        $filter = [
            'distributor_id' => $address['distributor_id']
        ];
        // 总部店铺是查不到信息的，distributor_id=0
        $distributors = $this->entityRepository->getInfo($filter);
        $address['name'] = $distributors['name'] ?? '';
        $address['logo'] = $distributors['logo'] ?? '';

        return $address;
    }

    /**
     * 根据店铺id获取店铺售后地址
     * @param $filter
     * @return array
     */
    public function getAftersalesAddressByDistributorId($filter)
    {
        $address = $this->distributorAfterSalesAddressRepository->lists($filter);
        $distributorInfo = $this->entityRepository->getInfo($filter);

        $result = [
            'address' => $address,
            'distributor_info' => $distributorInfo
        ];

        return $result;
    }

    public function getOneAftersaleAddressBy($filter)
    {
        $infodata = $this->distributorAfterSalesAddressRepository->getInfo($filter);
        if (!$infodata) {
            return [];
            //throw new ResourceException("未设置售后地址！");
        }
        $address = $infodata['province']. "" . $infodata['city']. "". $infodata['area']. "" .$infodata['address'];
        $result = [
            'contact' => $infodata['contact'] ?? '未知',
            'mobile' => $infodata['mobile'] ?? '未知',
            'address' => $address ?? '未知'
        ];
        return $result;
    }

    /*
     * 根据主键删除店铺售后地址
     */
    public function deleteDistributorAfterSalesAddress($filter)
    {
        $result = $this->distributorAfterSalesAddressRepository->deleteBy($filter);

        if ($result) {
            return ['status' => true];
        } else {
            return ['status' => false];
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->distributorAfterSalesAddressRepository->$method(...$parameters);
    }
}
