<?php

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorSalesmanRole;
use SalespersonBundle\Services\SalespersonService;
use Dingo\Api\Exception\ResourceException;

class DistributorSalesmanRoleService
{
    private $distributorSalesmanRoleRepository;

    public function __construct()
    {
        $this->distributorSalesmanRoleRepository = app('registry')->getManager('default')->getRepository(DistributorSalesmanRole::class);
    }

    /**
     * 发货权限获取
     *
     * @param int $salespersonId
     * @return void
     */
    public function checkSalespersonRole($salespersonId, $route)
    {
        if ($route[1]['role'] ?? 0) {
            $salespersonService = new SalespersonService();
            $filter = [
                'salesperson_id' => $salespersonId
            ];
            $info = $salespersonService->salesperson->getInfo($filter);
            if ($info['role'] ?? 0) {
                $roleFilter = [
                'salesman_role_id' => $info['role']
            ];
                $roleInfo = $this->getInfo($roleFilter);
                if (($roleInfo['rule_ids'] ?? 0) && in_array($route[1]['role'], $roleInfo['rule_ids'])) {
                    return true;
                }
            }
            throw new ResourceException('暂无权限,请联系管理员');
        }
        return true;
    }

    public function __call($method, $parameters)
    {
        return $this->distributorSalesmanRoleRepository->$method(...$parameters);
    }
}
