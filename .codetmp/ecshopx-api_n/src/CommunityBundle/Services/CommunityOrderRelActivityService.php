<?php

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityOrderRelActivity;

class CommunityOrderRelActivityService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityOrderRelActivity::class);
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
