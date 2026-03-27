<?php

namespace DistributionBundle\Services;

use DistributionBundle\Entities\Slider;

class SliderService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Slider::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function save($companyId, $params)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']]);
        if ($info) {
            $return = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], $params);
        } else {
            $return = $this->entityRepository->create($params);
        }

        return $return;
    }

    public function getSlider($filter)
    {
        $result = $this->getInfo($filter);
        if (!$result) {
            $filter['distributor_id'] = 0;
            $result = $this->getInfo($filter);
        }
        return $result;
    }
}
