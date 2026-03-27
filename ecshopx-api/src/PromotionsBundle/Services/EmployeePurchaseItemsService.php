<?php

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Entities\EmployeePurchaseItems;

class EmployeePurchaseItemsService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(EmployeePurchaseItems::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
