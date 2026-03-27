<?php

namespace OpenapiBundle\Filter\Member;

use OpenapiBundle\Filter\BaseFilter;

class MemberCardVipGradeFilter extends BaseFilter
{
    protected function init()
    {
        // 判断是否有等级ID
        if (isset($this->requestData["vip_grade_id"])) {
            $this->filter["vip_grade_id"] = $this->requestData["vip_grade_id"];
        }
    }
}
