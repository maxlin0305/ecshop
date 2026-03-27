<?php

namespace OrdersBundle\Services;

use OrdersBundle\Interfaces\RightsInterface;

class RightsService
{
    /**
     * @var rightsInterface
     */
    public $rightsInterface;

    /**
     * KaquanService
     */
    public function __construct(RightsInterface $rightsInterface)
    {
        $this->rightsInterface = $rightsInterface;
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->rightsInterface->$method(...$parameters);
    }
}
