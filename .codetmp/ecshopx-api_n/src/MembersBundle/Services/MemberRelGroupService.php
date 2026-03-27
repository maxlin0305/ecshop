<?php

namespace MembersBundle\Services;

use MembersBundle\Entities\MemberRelGroup;

/**
 *
 */
class MemberRelGroupService
{
    private $userInterface;

    /**
     * MemberGroupService 构造函数.
     */
    public function __construct()
    {
        $this->userInterface = app('registry')->getManager('default')->getRepository(MemberRelGroup::class);
    }

    /**
     * Dynamically call the usersservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->userInterface->$method(...$parameters);
    }
}
