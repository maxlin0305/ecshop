<?php

namespace OpenapiBundle\Services\Member;

use MembersBundle\Entities\MemberRelTags;
use OpenapiBundle\Services\BaseService;

class MemberRelTagService extends BaseService
{
    public function getEntityClass(): string
    {
        return MemberRelTags::class;
    }
}
