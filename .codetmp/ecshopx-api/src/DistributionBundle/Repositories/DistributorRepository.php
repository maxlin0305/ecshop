<?php

namespace DistributionBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorService;

use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Traits\Repository\FilterRepositoryTrait;
use MerchantBundle\Entities\Merchant;

class DistributorRepository extends EntityRepository
{
    use FilterRepositoryTrait;

    public $table = "distribution_distributor";

    public $cols = [
        'distributor_id',
        'shop_id',
        'is_distributor',
        'company_id',
        'mobile',
        'address',
        'name',
        'auto_sync_goods',
        'logo',
        'contract_phone',
        'banner',
        'contact',
        'is_valid',
        'lng',
        'lat',
        'child_count',
        'is_default',
        'is_audit_goods',
        'is_ziti',
        'regions_id',
        'regions',
        'is_domestic',
        'is_direct_store',
        'province',
        'is_delivery',
        'city',
        'area',
        'hour',
        'created',
        'updated',
        'shop_code',
        'wechat_work_department_id',
        'distributor_self',
        'regionauth_id',
        'is_open',
        'rate',
        'is_dada',
        'business',
        'dada_shop_create',
        'review_status',
        'dealer_id',
        'split_ledger_info',
        'introduce',
        'merchant_id',
        'distribution_type',
        'is_require_subdistrict',
        'is_require_building',
        'delivery_distance',
        'offline_aftersales',
        'offline_aftersales_self',
        'offline_aftersales_distributor_id',
        'offline_aftersales_other',
    ];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }

        $entity = new Distributor();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    public function fake()
    {
        return $this->getColumnNamesData(new Distributor());
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }

        if (isset($data['auto_sync_goods'])) {
            $data['auto_sync_goods'] = $data['auto_sync_goods'] == 'true' ? 1 : 0;
        }

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
        if ($data['regions'] ?? []) {
            $data['regions'] = is_array($data['regions']) ? json_encode($data['regions']) : $data['regions'];
        }
        if ($data['regions_id'] ?? []) {
            $data['regions_id'] = is_array($data['regions_id']) ? json_encode($data['regions_id']) : $data['regions_id'];
        }
        if ($data['offline_aftersales_distributor_id'] ?? []) {
            $data['offline_aftersales_distributor_id'] = is_array($data['offline_aftersales_distributor_id']) ? json_encode($data['offline_aftersales_distributor_id']) : $data['offline_aftersales_distributor_id'];
        }

        if (isset($data['auto_sync_goods'])) {
            $data['auto_sync_goods'] = $data['auto_sync_goods'] == 'true' ? 1 : 0;
        }

        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
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
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
        }
        return $lists;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $isTotalCount = true, $column = "*", $noHaving = false)
    {
        // 将字段拆成数组
        $column = $this->table.'.*';
        $select = explode(",", $column);

        // 获取经度
        $lng = $filter['lng'] ?? null;
        // 获取纬度
        $lat = $filter['lat'] ?? null;
        // 将经纬度从筛选条件中移除
        unset($filter['lng']);
        unset($filter['lat']);

        $conn = app('registry')->getConnection('default');

        $qb = $conn->createQueryBuilder();
        $merchantTable = app('registry')->getManager('default')->getRepository(Merchant::class)->table;
        if (empty($filter['merchant_id'])) {
            $qb->leftJoin(
                $this->table,
                $merchantTable,
                $merchantTable,
                sprintf("%s.merchant_id=%s.id", $this->table, $merchantTable)
            );
            $select[] = 'merchant_name';
        }


        if ($lng && $lat) {
            $select[] = '( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat ) ) ) ) AS distance';
            $having = true;
        } else {
            $select[] = $column;
            $having = false;
        }

        // 是否需要聚合操作, 如果只需要获取距离，则不需要过滤条离用户比较远的店铺数据
        if ($noHaving) {
            $having = false;
        }

        $qb->select(implode(",", $select))->from($this->table);
        $qb = $this->_filter($filter, $qb);

        $res["total_count"] = 0;
        if ($isTotalCount) {
            $totalCountQb = clone $qb;
            $res["total_count"] = (int)$totalCountQb->select('count(*) as _count')->execute()->fetchColumn();
        }

        // 聚合操作
        if ($having) {
            $distributorService = new DistributorService();
            $distance = $distributorService->getDistanceRedis($filter['company_id'] ?? 0) ?? config('common.distributor_distance');
            if (is_numeric($distance) && $distance > 0) {
                $qb->having($qb->expr()->lte('distance', $distance));
            }
            /*$qb->having(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->gt('delivery_distance', 0),
                        $qb->expr()->lte('distance', 'delivery_distance')
                    ),
                    $qb->expr()->eq('delivery_distance', 0)
                )
            );*/
        }

        // 分页设置
        if ($pageSize > 0) {
            $qb->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        }

        // 设置排序方式
        if (is_array($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy($key, $value);
            }
        }
//        if ($lng && $lat) {
//            $qb->orderBy('distance', $orderBy["distance"] ?? "ASC");
//        } elseif ($orderBy) {
//            foreach ($orderBy as $key => $value) {
//                $qb->addOrderBy($key, $value);
//            }
//        }

        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
            // 解密
            isset($v['mobile']) and $v['mobile'] = fixeddecrypt($v['mobile']);
            isset($v['contact']) and $v['contact'] = fixeddecrypt($v['contact']);
        }
        $res["list"] = $lists;
        return $res;
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
        $qb->select('count(distributor_id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 获取指定位置最近的店铺
     */
    public function getNearDistributorList($filter, $lat = 0, $lng = 0)
    {
        $conn = app('registry')->getConnection('default');

        $select = '*, ' . '( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( lat ) ) ) ) AS distance';

        $qb = $conn->createQueryBuilder()->select($select)->from($this->table);
        $qb = $this->_filter($filter, $qb);

        $qb->orderBy('distance', 'asc');

        $qb->setFirstResult(0);
        $qb->setMaxResults(1);

        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$v) {
            if ($v['regions_id'] ?? '') {
                $v['regions_id'] = json_decode($v['regions_id'], true);
            }
            if ($v['regions'] ?? '') {
                $v['regions'] = json_decode($v['regions'], true);
            }
            if ($v['offline_aftersales_distributor_id'] ?? '') {
                $v['offline_aftersales_distributor_id'] = json_decode($v['offline_aftersales_distributor_id'], true);
            }
            if ($v['mobile'] ?? '') {
                $v['mobile'] = fixeddecrypt($v['mobile']);
            }
            if ($v['contact'] ?? '') {
                $v['contact'] = fixeddecrypt($v['contact']);
            }
            if ($v['phone'] ?? '') {
                $v['phone'] = fixeddecrypt($v['phone']);
            }
        }
        return $lists;
    }

    public function changeChildCount($companyId, $distributorId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->update($this->table)
            ->set('child_count', 'child_count + 1')
            ->where('is_distributor=1 and company_id = :company_id and distributor_id = :distributor_id')
            ->setParameters([
                ':company_id' => $companyId,
                ':distributor_id' => $distributorId,
            ]);
        $result = $qb->execute();
        return $result;
    }

    public function setDefaultDistributor($companyId, $distributorId, $isDistributor = true)
    {
        $distributorEntity = $this->find($distributorId);
        if (!$distributorEntity) {
            throw new UpdateResourceFailedException('distributor_id={$distributorId}的店铺不存在');
        }
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->getConnection('default')->update('distribution_distributor', ['is_default' => 0], ['company_id' => $companyId, 'is_distributor' => $isDistributor]);
            $isDefault = true;
            $distributorEntity->setIsDefault($isDefault);
            $em->persist($distributorEntity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        return true;
    }

    public function openOrClose($distributor_id, $status = 1)
    {
        if ($status === 'false' || $status === false) {
            $status = false;
        } elseif ($status === 'true' || $status === true) {
            $status = true;
        }
        $shopsEntity = $this->find($distributor_id);
        if (!$shopsEntity) {
            throw new DeleteResourceFailedException("门店id={$distributor_id}的门店不存在");
        }

        $shopsEntity->setIsValid($status);

        $em = $this->getEntityManager();
        $em->persist($shopsEntity);
        $em->flush();
        return true;
    }

    /**
     * 为key添加表的别名前缀
     * @param string $key
     * @return string
     */
    protected function appendPrefixTableAlias(string $key): string
    {
        if (strpos($key, ".") === false) {
            return sprintf("%s.%s", $this->table, $key);
        }
        return $key;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $v = $this->appendPrefixTableAlias($v);
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
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $value)
                    ));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                if ($field == "or") {
//                    [
//                        "or" => [
//                            "distributor_id" => [1,2,34],
//                            "name" => "asdasd"
//                        ]
//                    ];
//                    AND ( (distributor_id in (1,2,34) OR (name = "asdasd") )
                    $groupOr = [];
                    // or下的数组用or符号连接
                    foreach ($value as $itemColumn => $itemValue) {
                        $itemColumnArray = explode('|', $itemColumn);
                        // 获取列名
                        $itemColumnName = (string)array_shift($itemColumnArray);
                        $itemColumnName = $this->appendPrefixTableAlias($itemColumnName);
                        // 获取表达式的符号
                        $itemColumnSymbol = array_shift($itemColumnArray);
                        $groupOr[] = $this->getFilterExpression($qb, $itemColumnName, $itemColumnSymbol, $itemValue);
                    }
                    $qb = $qb->andWhere($this->getOrExpression($qb, ...$groupOr));
                } else {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in($field, $value));
                }
            } else {
                $field = $this->appendPrefixTableAlias($field);
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }


    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set" . str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
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
            $fun = "get" . str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            $values[$col] = $entity->$fun();
        }
        $values['regions_id'] = json_decode($values['regions_id'], true);
        $values['regions'] = json_decode($values['regions'], true);
        $values['offline_aftersales_distributor_id'] = json_decode($values['offline_aftersales_distributor_id'], true);

        return $values;
    }

    public function getDistributorIdByRegionAuthId($company_id, $regionauth_id)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $result = $criteria
            ->select('distributor_id')
            ->from($this->table)
            ->andWhere($criteria->expr()->eq('company_id', $criteria->expr()->literal($company_id)))
            ->andWhere($criteria->expr()->eq('regionauth_id', $criteria->expr()->literal($regionauth_id)))
            ->execute()
            ->fetchAll();
        if (is_array($result)) {
            return array_column($result, 'distributor_id');
        }
        return [];
    }
}
