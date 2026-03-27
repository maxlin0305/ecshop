<?php

namespace PointBundle\Exception;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberRuleService;

class PointResourceException extends ResourceException
{
    public function __construct($message)
    {
        $pointName = (new PointMemberRuleService())->getPointName();
        $message = str_replace("{point}", $pointName, $message);
        parent::__construct($message);
    }
}
