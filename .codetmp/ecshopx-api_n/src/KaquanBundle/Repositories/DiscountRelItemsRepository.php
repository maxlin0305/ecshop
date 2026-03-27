<?php

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use KaquanBundle\Entities\RelItems;

use Dingo\Api\Exception\ResourceException;

class DiscountRelItemsRepository extends EntityRepository
{
    public $table = 'kaquan_rel_items';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new RelItems();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return true;
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
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
     * @param array $insert_data
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createQuick($insert_data = [])
    {
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
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
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

        return intval($total);
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

        $lists = [];
        if ($pageSize > 0) {
            $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                      ->setMaxResults($pageSize);
        }

        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }

        return $lists;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param RelItems $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["item_id"])) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["card_id"]) && $data["card_id"]) {
            $entity->setCardId($data["card_id"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        //当前字段非必填
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        //当前字段非必填
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data['use_limit'])) {
            $entity->setUseLimit($data['use_limit']);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param RelItems $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'item_id' => $entity->getItemId(),
            'card_id' => $entity->getCardId(),
            'is_show' => $entity->getIsShow(),
            'item_type' => $entity->getItemType(),
            'company_id' => $entity->getCompanyId(),
            'use_limit' => $entity->getUseLimit(),
        ];
    }
}
