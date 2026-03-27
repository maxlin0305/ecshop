<?php

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityChiefDistributor;

class CommunityChiefDistributorService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityChiefDistributor::class);
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
