<?php

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ResourceLevelRelServiceRepository extends EntityRepository
{
    public $table = "reservation_level_rel_service";

    public function getList($filter)
    {
        $result = array();
        $dataList = $this->findBy($filter);
        foreach ($dataList as $data) {
            $result[] = normalize($data);
        }
        return $result;
    }
}
