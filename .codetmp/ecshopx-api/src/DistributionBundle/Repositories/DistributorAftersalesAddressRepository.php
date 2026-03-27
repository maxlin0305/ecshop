<?php

namespace DistributionBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DistributionBundle\Entities\DistributorAftersalesAddress;

use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class DistributorAftersalesAddressRepository extends EntityRepository
{
    public $table = "distributor_aftersales_address";
    public $cols = ['address_id','distributor_id','company_id','province','city','area','regions_id','regions','address', 'lng', 'lat','contact','mobile','post_code','created','updated', 'is_default','merchant_id','name','hours','return_type'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new DistributorAftersalesAddress();
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
    public function updateBy(array $filter, array $params)
    {
        $params['is_default'] = 1;
        if (isset($params['company_id'], $params['distributor_id'])) {
            $isDefault = $this->checkIsDefault($params['company_id'], ($params['distributor_id'] ?? 0));
            $params['is_default'] = $isDefault ? 2 : 1;
        }
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($params as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
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

    private function setColumnNamesData($entity, $params)
    {
        $params['is_default'] = 1;
        if (isset($params['company_id'], $params['distributor_id'])) {
            $isDefault = $this->checkIsDefault($params['company_id'], ($params['distributor_id'] ?? 0));
            $params['is_default'] = $isDefault ? 2 : 1;
        }
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                if ($col == 'contact') {
                    // 最大长度为50
                    $params[$col] = mb_substr($params[$col], 0, 50);
                }
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function checkIsDefault($companyId, $distributorId)
    {
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'is_default' => 1,
        ];
        $entity = $this->findOneBy($filter);
        if ($entity) {
            return true;
        }
        return false;
    }

    public function setDefaultAddress($id, $companyId)
    {
        $distributorEntity = $this->find($id);
        if (!$distributorEntity) {
            throw new UpdateResourceFailedException('地址不存在');
        }
        $distributorId = $distributorEntity->getDistributorId();
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->getConnection('default')->update('distributor_aftersales_address', ['is_default' => 2], ['distributor_id' => $distributorId, 'company_id' => $companyId]);
            $isDefault = 1;
            $distributorEntity->setIsDefault($isDefault);
            $em->persist($distributorEntity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
        return ['status' => true];
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            $values[$col] = $entity->$fun();
        }
        return $values;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
            foreach ($lists as $key => $value) {
                $lists[$key]['mobile'] = fixeddecrypt($value['mobile']);
                $lists[$key]['contact'] = fixeddecrypt($value['contact']);
            }
        }
        $result['list'] = $lists ?? [];
        return $result;
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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(address_id)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function getNearlists($filter, $lng, $lat, $cols = '*', $page = 1, $pageSize = -1)
    {
        if ($lng && $lat) {
            $cols .= ',(6371 * acos(cos(radians('.$lat.')) * cos(radians(lat)) * cos(radians(lng)-radians('.$lng.')) + sin(radians('.$lat.')) * sin(radians(lat)))) AS distance';
        }
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($lng && $lat) {
                $qb->addOrderBy('distance', 'ASC');
            } else {
                $qb->addOrderBy('created', 'DESC');
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
            foreach ($lists as $key => $value) {
                $lists[$key]['mobile'] = fixeddecrypt($value['mobile']);
                $lists[$key]['contact'] = fixeddecrypt($value['contact']);
            }
        }
        $result['list'] = $lists ?? [];
        return $result;
    }
}
