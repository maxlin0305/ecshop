<?php

namespace SelfserviceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use SelfserviceBundle\Entities\FormSetting;

use Dingo\Api\Exception\ResourceException;
use Exception;

class FormSettingRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new FormSetting();
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

    public function discard($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException("数据不存在");
        }
        $data['status'] = 2;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
    }

    public function restore($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException("数据不存在");
        }
        $data['status'] = 1;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
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
            throw new Exception("删除的数据不存在");
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
            throw new Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
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
    public function lists($filter, $orderBy = ["id" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (!is_null($value)) {
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
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }
        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["field_title"]) && $data["field_title"]) {
            $entity->setFieldTitle($data["field_title"]);
        }
        if (isset($data["field_name"]) && $data["field_name"]) {
            $entity->setFieldName($data["field_name"]);
        }
        if (isset($data["form_element"]) && $data["form_element"]) {
            $entity->setFormElement($data["form_element"]);
        }
        if (isset($data["options"]) && $data["options"]) {
            $entity->setOptions(json_encode($data["options"]));
        }
        if (isset($data["image_url"]) && $data["image_url"]) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }

        if (isset($data["sort"]) && $data["sort"]) {
            $entity->setSort($data["sort"]);
        }

        if (isset($data["is_required"])) {
            $entity->setIsRequired($data["is_required"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'field_title' => $entity->getFieldTitle(),
            'field_name' => $entity->getFieldName(),
            'form_element' => $entity->getFormElement(),
            'status' => $entity->getStatus(),
            'sort' => $entity->getSort(),
            'is_required' => $entity->getIsRequired(),
            'image_url' => $entity->getImageUrl(),
            'options' => json_decode($entity->getOptions()),
        ];
    }
}
