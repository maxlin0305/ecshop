<?php

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class CardRelatedRepository extends EntityRepository
{
    public $table = 'kaquan_card_related';

    /**
     * [get description]
     * @param  int $cardId
     * @return array
     */
    public function get($filter)
    {
        $detail = $this->findOneBy($filter);
        return $detail;
    }

    /**
     * [get description]
     * @param  int $cardId
     * @return array
     */
    public function getDetail($filter)
    {
        $result = [];
        $detail = $this->findOneBy($filter);
        if ($detail) {
            $result = [
                    'get_num' => ($detail->getGetNum()) ? $detail->getGetNum() : 0,
                    'use_num' => ($detail->getConsumeNum()) ? $detail->getConsumeNum() : 0,
                    'quantity' => ($detail->getQuantity()) ? $detail->getQuantity() : 0,
                ];
        }

        return $result;
    }


    /**
     * [getList description]
     * @param  array  $filter
     * @return [type]
     */
    public function getList($filter = array())
    {
        $result = [];
        $dataList = $this->findBy($filter);
        if ($dataList) {
            foreach ($dataList as $detail) {
                $cardId = $detail->getCardId();
                $result[$cardId] = [
                    'get_num' => ($detail->getGetNum()) ? $detail->getGetNum() : 0,
                    'use_num' => ($detail->getConsumeNum()) ? $detail->getConsumeNum() : 0,
                ];
            }
        }
        return $result;
    }
}
