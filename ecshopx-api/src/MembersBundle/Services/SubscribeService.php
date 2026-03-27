<?php

namespace MembersBundle\Services;

use MembersBundle\Interfaces\SubscribeInterface;

class SubscribeService
{
    public $subscribe;

    public function __construct(SubscribeInterface $subscribe)
    {
        $this->subscribe = $subscribe;
    }
}
