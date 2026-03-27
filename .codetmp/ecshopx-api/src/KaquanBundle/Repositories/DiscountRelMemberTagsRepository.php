<?php

namespace KaquanBundle\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use KaquanBundle\Entities\RelMemberTags;

class DiscountRelMemberTagsRepository extends EntityRepository
{
    public $table = 'kaquan_rel_member_tags';

    /**
     * @param array $insert_data
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createQuick($insert_data = [])
    {
        if (!$insert_data) {
            return true;
        }
        $sql = '';
        foreach ($insert_data as $data) {
            if (!$sql) {
                $sql = 'insert into ' . $this->table . ' (`' . implode('`,`', array_keys($data)) . '`) values ';
            }
            $sql .= ' ("' . implode('","', $data) . '"),';
        }
        $sql = substr($sql, 0, -1) . ';';

        $em = $this->getEntityManager();
        $em->getConnection()->exec($sql);
        return true;
    }

    /**
     * @param $filter
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteQuick($filter)
    {
        $sql = 'delete from ' . $this->table;
        $sql .= ' where 1 ';
        foreach ($filter as $k => $v) {
            $sql .= " and {$k}='{$v}' ";
        }

        $em = $this->getEntityManager();
        $em->getConnection()->exec($sql);
        return true;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = -1, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }

            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        return $lists;
    }

    /**
     * 获取数据表字段数据
     *
     * @param RelMemberTags $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'card_id' => $entity->getCardId(),
            'tag_id' => $entity->getTagId(),
            'company_id' => $entity->getCompanyId(),
        ];
    }
}
