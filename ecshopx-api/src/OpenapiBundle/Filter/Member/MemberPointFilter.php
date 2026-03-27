<?php

namespace OpenapiBundle\Filter\Member;

use OpenapiBundle\Filter\BaseFilter;

class MemberPointFilter extends BaseFilter
{
    protected function init()
    {
        $this->setUserIdByMobile();
        $this->setTimeRange("created|gte", "created|lte");
    }
}
