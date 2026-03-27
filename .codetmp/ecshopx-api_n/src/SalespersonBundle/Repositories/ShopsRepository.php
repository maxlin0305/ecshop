<?php

namespace SalespersonBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ShopsRepository extends EntityRepository
{
    public function getAllShops()
    {
        return app('registry')->getConnection('default')->fetchAssoc("select * from shops");
    }
}
