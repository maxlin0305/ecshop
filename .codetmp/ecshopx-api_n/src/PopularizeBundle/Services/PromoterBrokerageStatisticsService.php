<?php

namespace PopularizeBundle\Services;

use PopularizeBundle\Entities\PromoterBrokerageStatistics;

class PromoterBrokerageStatisticsService
{
    /**
     * promoterBrokerageStatistics数据库
     *
     * @var Object
     */
    public $promoterBrokerageStatisticsRepository;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->promoterBrokerageStatisticsRepository = app('registry')->getManager('default')->getRepository(PromoterBrokerageStatistics::class);
    }

    /**
     * Dynamically call the PromoterBrokerageStatisticsService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->promoterBrokerageStatisticsRepository->$method(...$parameters);
    }
}
