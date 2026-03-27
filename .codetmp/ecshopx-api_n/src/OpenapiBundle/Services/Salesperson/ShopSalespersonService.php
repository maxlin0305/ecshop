<?php

namespace OpenapiBundle\Services\Salesperson;

use OpenapiBundle\Services\BaseService;
use SalespersonBundle\Entities\ShopSalesperson;

class ShopSalespersonService extends BaseService
{
    public function getEntityClass(): string
    {
        return ShopSalesperson::class;
    }
}
