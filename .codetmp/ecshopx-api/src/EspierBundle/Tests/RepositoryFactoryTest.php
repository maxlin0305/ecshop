<?php

namespace EspierBundle\Tests;

use EspierBundle\Services\TestBaseService;
use Doctrine\ORM\EntityRepository;
use EspierBundle\Traits\RepositoryFactory;
use EspierBundle\Entities\Address;

class AddressRepositoryNew extends EntityRepository
{
    use RepositoryFactory;
    public static $entityClass = Address::class;
}

class RepositoryFactoryTest extends TestBaseService
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $addressRepository = AddressRepositoryNew::instance();
        $this->assertEquals('北京市', $addressRepository->findOneByLabel("北京市")->getLabel());
    }
}
