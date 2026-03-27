<?php

namespace OpenapiBundle\Services\Member;

use OpenapiBundle\Services\BaseService;
use PointBundle\Entities\PointMember;

class PointService extends BaseService
{
    public function getEntityClass(): string
    {
        return PointMember::class;
    }
}
