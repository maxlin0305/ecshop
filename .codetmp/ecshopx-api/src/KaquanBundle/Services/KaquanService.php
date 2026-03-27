<?php

namespace KaquanBundle\Services;

use KaquanBundle\Interfaces\KaquanInterface;

class KaquanService
{
    /**
     * @var kaquanInterface
     */
    public $kaquanInterface;

    /**
     * KaquanService
     */
    public function __construct(KaquanInterface $kaquanInterface)
    {
        $this->kaquanInterface = $kaquanInterface;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->kaquanInterface->$method(...$parameters);
    }
}
