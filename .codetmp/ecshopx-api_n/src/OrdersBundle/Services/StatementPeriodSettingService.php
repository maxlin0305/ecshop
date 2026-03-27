<?php

namespace OrdersBundle\Services;

use OrdersBundle\Entities\StatementPeriodSetting;

class StatementPeriodSettingService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(StatementPeriodSetting::class);
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
}
