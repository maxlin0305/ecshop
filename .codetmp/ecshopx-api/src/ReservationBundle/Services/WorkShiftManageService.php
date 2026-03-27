<?php

namespace ReservationBundle\Services;

use ReservationBundle\Interfaces\WorkShiftInterface;

class WorkShiftManageService
{
    /**
     * @var workShiftInterface
     */
    public $workShiftInterface;

    /**
     *
     */
    public function __construct(WorkShiftInterface $workShiftInterface)
    {
        $this->workShiftInterface = $workShiftInterface;
    }

    /**
     * Dynamically call the  instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->workShiftInterface->$method(...$parameters);
    }
}
