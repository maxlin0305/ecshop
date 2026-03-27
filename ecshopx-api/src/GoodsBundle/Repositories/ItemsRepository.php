<?php

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use GoodsBundle\Entities\Items;
use Doctrine\Common\Collections\Criteria;

class ItemsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'items';

    public $cols = [
        'item_id', 'item_type', 'consume_type', 'is_show_specimg','store', 'barcode', 'sales', 'approve_status', 'rebate', 'rebate_conf', 'cost_price','is_point', 'point', 'item_source', 'goods_id', 'brand_id',
        'consume_type', 'item_name', 'item_unit', 'item_bn', 'brief', 'price', 'market_price', 'special_type', 'goods_function', 'goods_series', 'volume',
        'goods_color', 'goods_brand', 'item_address_province', 'item_address_city', 'regions_id', 'regions', 'brand_logo', 'sort', 'templates_id', 'is_default', 'nospec', 'default_item_id', 'pics', 'pics_create_qrcode', 'distributor_id',
        'company_id', 'enable_agreement', 'date_type', 'item_category', 'rebate_type', 'weight', 'begin_date', 'end_date', 'fixed_term','tax_rate', 'created', 'updated', 'video_type', 'videos', 'video_pic_url', 'purchase_agreement',
        'intro', 'audit_status', 'audit_reason', 'is_gift', 'is_package', 'profit_type', 'profit_fee', 'is_profit','crossborder_tax_rate','origincountry_id','taxstrategy_id','taxation_num','type','tdk_content','is_epidemic',
        'place_origin',
    ];

    /**
     * 添加商品
     */
    public function create($params)
    {
        $itemsEnt = new Items();

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        $result = $this->getColumnNamesData($itemsEnt, $this->cols);
        return $result;
    }

    public function updateSort($itemId, $sort)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSort($sort);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_source' => $itemsEnt->getItemSource() ? $itemsEnt->getItemSource() : 'mall',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];

        return $result;
    }

    public function updateStore($itemId, $store, $is_log = false)
    {
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',line:'.__LINE__);
        }
        $itemsEnt = $this->find($itemId);

        if (!$itemsEnt) {
            if ($is_log) {
                app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',itemsEnt is null,line:'.__LINE__);
            }
            return true;
        }

        $itemsEnt->setStore($store);
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.',line:'.__LINE__);
        }
        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_source' => $itemsEnt->getItemSource() ? $itemsEnt->getItemSource() : 'mall',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:'.$itemId.',store===>'.$store.'====end====,line:'.__LINE__);
        }
        return $result;
    }

    /**
     * 更新销量
     * @param $itemId 商品id
     * @param $sales 销量
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSales($itemId, $sales)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSales((int)$sales + (int)$itemsEnt->getSales());

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush($itemsEnt);

        return true;
    }

    /**
     * 更新运费模板
     * @param $itemId 商品id
     * @param $templates_id 运费模板id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setTemplatesId($itemId, $templates_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setTemplatesId($templates_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    /**
     * 更新商品分类
     * @param $itemId 商品id
     * @param $category_id 分类id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setCategoryId($itemId, $category_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setItemCategory($category_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    /**
     * 更新多条数数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
     * @param bool $needLiteral false表示不需要为值做双引号的操作
     * @return mixed
     */
    public function updateBy(array $filter, array $data, bool $needLiteral = true)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $needLiteral ? $qb->expr()->literal($val) : $val);
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->set('updated', time())->execute();
    }



    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateProfitBy($filter, $profitType, $profitScale)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->update($this->table, 'i');
        $qb->set('i.profit_type', $qb->expr()->literal($profitType))
            ->set('i.profit_fee', 'FLOOR(i.price * ' . $profitScale . ')');

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    private function _filter($filter, $qb)
    {
        if (isset($filter['or']) && $filter['or']) {
            foreach ($filter['or'] as $key => $filterValue) {
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if ($k == 'direct') {
                        $orWhere[] = $qb->andWhere($qb->expr()->eq($v, $filterValue));
                        continue;
                    }
                    if ($k == 'contains') {
                        $k = 'like';
                    }
                    if ($k == 'like') {
                        $filterValue = '%'.$filterValue.'%';
                    }
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->$k($v, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->$k($v, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->$k($v, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                } else {
                    if (is_array($filterValue)) {
                        if (!$filterValue) continue;
                        array_walk($filterValue, function (&$colVal) use ($qb) {
                            $colVal = $qb->expr()->literal($colVal);
                        });
                        $orWhere[] = $qb->expr()->in($key, $filterValue);
                    } else {
                        if (is_string($filterValue)) {
                            $orWhere[] = $qb->expr()->eq($key, $qb->expr()->literal($filterValue));
                        } else {
                            $orWhere[] = $qb->expr()->eq($key, is_bool($filterValue) ? ($filterValue ? 1 : 0) : $filterValue);
                        }
                    }
                }
            }
            $qb->andWhere(
                $qb->expr()->orX(...$orWhere)
            );
            unset($filter['or']);
        }

        if (isset($filter['is_default'], $filter['approve_status']) && $filter['is_default']) {
            $dqb = app('registry')->getConnection('default')->createQueryBuilder()->select('goods_id')->from('items', 'inner_items');
            if (is_array($filter['approve_status'])) {
                array_walk($filter['approve_status'], function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $dqb->andWhere($qb->expr()->in('inner_items.approve_status', $filter['approve_status']));
            } else {
                $dqb->andWhere($qb->expr()->eq('inner_items.approve_status', $qb->expr()->literal($filter['approve_status'])));
            }
            $qb->andWhere('exists('.$dqb->getSQL().' AND inner_items.goods_id='.$this->table.'.goods_id)');
            unset($filter['approve_status']);
        }

        foreach ($filter as $field => $value) {
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'direct') {
                    $qb = $qb->andWhere($qb->expr()->eq($v, $value));
                    continue;
                }
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($v, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->$k($v, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            } else {
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in($field, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->eq($field, is_bool($value) ? ($value ? 1 : 0) : $value));
                    }
                }
            }
        }
        return $qb;
    }

    public function deleteBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 更新商品信息
     */
    public function update($item_id, $params)
    {
        $itemsEnt = $this->find($item_id);

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return $this->getColumnNamesData($itemsEnt);
    }

    /**
     * 删除商品
     */
    public function delete($item_id)
    {
        $delItemsEntity = $this->find($item_id);
        if (!$delItemsEntity) {
            return true;
        }
        $this->getEntityManager()->remove($delItemsEntity);

        return $this->getEntityManager()->flush($delItemsEntity);
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
     * 获取会员商品详细信息
     */
    public function get($item_id, $columns = null)
    {
        $itemsEnt = $this->find($item_id);
        if (!$itemsEnt) {
            return [];
        }

        $result = $this->getColumnNamesData($itemsEnt, $columns);
        return $result;
    }

    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 指定条件，获取最多的商品所属catid
     */
    public function countItemsMainCatIdBy($filter)
    {
        if (isset($filter['distributor_id'])) {
            unset($filter['distributor_id']);
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('count("item_id") as _count, item_category')->from($this->table);

        $qb = $this->_filter($filter, $qb);

        $qb->orderBy('_count', 'desc');
        $qb->groupBy('item_category');

        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
     * 指定条件，获取所有的品牌id
     */
    public function getBrandIds($filter)
    {
        unset($filter['brand_id']);
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('DISTINCT brand_id')->from($this->table);

        $qb = $this->_filter($filter, $qb);

        $lists = $qb->execute()->fetchAll();

        return $lists;
    }

    /**
     * 获取商品列表
     */
    public function list($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            if (!$columns || $columns == '*') {
                $columns = $this->cols;
            }
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            }
            $qb = $conn->createQueryBuilder()->select(implode(',', $columns))->from($this->table);
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
            $list = $qb->execute()->fetchAll();
            foreach ($list as $key => $row) {
                $values = [];
                foreach ($columns as $col) {
                    if (in_array($col, ['intro', 'purchase_agreement'])) {
                        continue;
                    }
                    if (in_array($col, ['pics', 'pics_create_qrcode', 'rebate_conf'])) {
                        $values[$col] = json_decode($row[$col], true);
                    } else {
                        $values[$col] = $row[$col];
                    }
                }
                $values['itemId'] = $values['item_id'];
                $values['consumeType'] = $values['consume_type'] ?? '';
                $values['itemName'] = $values['item_name'] ?? '';
                $values['itemBn'] = $values['item_bn'] ?? '';
                $values['companyId'] = $values['company_id'] ?? '';
                $values['item_main_cat_id'] = $values['item_category'] ?? '';
                $values['nospec'] = (isset($values['nospec']) && $values['nospec'] == 'true') ? true : false;
                $list[$key] = $values;
            }
        }
        $result['list'] = $list ?? [];
        return $result;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter
     * @param string $cols
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return mixed
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
        return $qb->execute()->fetchAll();
    }

    /**
     * 获取商品列表
     */
    public function listCopy($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
        $res['total_count'] = intval($total);

        $newItemsList = [];
        if ($res['total_count']) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $list = $this->matching($criteria);
            if (!$columns) {
                $columns = $this->cols;
            }
            foreach ($list as $v) {
                $newItemsList[] = $this->getColumnNamesData($v, $columns);
            }
        }
        $res['list'] = $newItemsList;
        return $res;
    }

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = 'set'. str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
                $entity->$fun($params[$col]);
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
            $fun = 'get'. str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
            $values[$col] = $entity->$fun();
        }
        // 历史原因特使处理
        if (isset($values['intro'])) {
            $intro = json_decode($values['intro'], true);
            $values['intro'] = $intro ? $intro : $values['intro'];
        }
        $values['itemId'] = $values['item_id'];
        $values['consumeType'] = $values['consume_type'] ?? '';
        $values['itemName'] = $values['item_name'] ?? '';
        $values['itemBn'] = $values['item_bn'] ?? '';
        $values['companyId'] = $values['company_id'] ?? '';
        $values['item_main_cat_id'] = $values['item_category'] ?? '';
        $values['nospec'] = (isset($values['nospec']) && $values['nospec'] == 'true') ? true : false;
        return $values;
    }

    /**
     * 简单的更新操作，不支持大于 小于等条件更新
     */
    public function simpleUpdateBy($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        return $conn->update($this->table, $data, $filter);
    }

    //获取指定条件的所有商品列表，可指定字段
    public function getItemsLists($filter, $cols = 'item_id, default_item_id')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    public function getSimpleInfo($filter, $cols)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity, $cols);
    }
}
