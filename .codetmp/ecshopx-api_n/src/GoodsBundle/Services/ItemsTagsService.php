<?php

namespace GoodsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemsTags;
use GoodsBundle\Entities\ItemsRelTags;
use GoodsBundle\Events\ItemTagEditEvent;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\MarketingActivityService;

class ItemsTagsService
{
    public $entityRepository;
    public $itemsRelTags;
    /**
     * ItemsTagsService 構造函數.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(itemsTags::class);
        $this->itemsRelTags = app('registry')->getManager('default')->getRepository(itemsRelTags::class);
    }

    /**
     * 創建標簽
     * @param $params array 修改信息
     */
    public function createTag($params) {
        $filter = [
            'company_id' => $params['company_id'],
            'tag_name' => $params['tag_name'],
            'distributor_id' => $params['distributor_id'],
        ];
        $tagInfo = $this->entityRepository->getInfo($filter);
        if ($tagInfo) {
            throw new ResourceException('標簽名稱不能重複');
        }
        return $this->entityRepository->create($params);
    }

    /**
     * 修改標簽
     * @param $filter array 條件
     * @param $params array 修改信息
     */
    public function updateTag($filter, $params) {
        $tagFilter = [
            'company_id' => $filter['company_id'],
            'tag_name' => $params['tag_name'],
            'distributor_id' => $filter['distributor_id'],
        ];
        $tagInfo = $this->entityRepository->getInfo($tagFilter);
        if ($tagInfo && $tagInfo['tag_id'] != $filter['tag_id']) {
            throw new ResourceException('標簽名稱不能重複');
        }
        return $this->entityRepository->updateOneBy($filter, $params);
    }

    public function getListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'], $is_front_show = false)
    {
        if (isset($filter['item_id']) && $filter['item_id']) {
            $relTags = $this->itemsRelTags->lists(['item_id' => $filter['item_id']]);
            unset($filter['item_id']);
            $filter['tag_id'] = array_column($relTags['list'], 'tag_id');
        }
        if ($is_front_show) {
            $filter['front_show'] = 1;
        }
        return $this->entityRepository->lists($filter, $page, $limit, $orderBy);
    }

    public function getTagIdsByItem($itemIds, $page = 1, $limit = 100)
    {
        $relTags = $this->itemsRelTags->lists(['item_id' => $itemIds]);
        return array_unique(array_column($relTags['list'], 'tag_id'));
    }

    public function getFrontListTags($filter, $page = 1, $limit = 100, $orderBy = ['created' => 'DESC'])
    {
        return $this->getListTags($filter, $page, $limit, $orderBy, true);
    }

    public function getTagsInfo($tag_id)
    {
        return $this->entityRepository->getInfoById($tag_id);
    }

    public function getItemsRelTagList($filter, string $columns = "reltag.item_id,tag.*")
    {
        return $this->itemsRelTags->getListsWithItemTag($filter, $columns);
//
//        $conn = app('registry')->getConnection('default');
//        $criteria = $conn->createQueryBuilder();
//        $criteria->select('count(*)')
//        ->from('items_rel_tags', 'reltag')
//        ->leftJoin('reltag', 'items_tags', 'tag', 'reltag.tag_id = tag.tag_id');
//        if (isset($filter['company_id']) && $filter['company_id']) {
//            $criteria->andWhere($criteria->expr()->eq('tag.company_id', $criteria->expr()->literal($filter['company_id'])));
//        }
//
//        if (isset($filter['item_id']) && $filter['item_id']) {
//            $itemIds = (array)$filter['item_id'];
//            $criteria->andWhere($criteria->expr()->in('reltag.item_id', $itemIds));
//        }
//        $criteria->select('reltag.item_id,tag.*');
//        $list = $criteria->execute()->fetchAll();
//        return $list;
    }

    /**
     * @param $filter
     * @param int $page
     * @param int $pageSize 默認 -1 返回全部
     * @return array
     */
    public function getItemIdsByTagids($filter, $page = 1, $pageSize = -1)
    {
        $relTags = $this->itemsRelTags->lists($filter);
        $itemIds = array_column($relTags['list'], 'item_id');
        return $itemIds;
    }

    public function getItemsByTagidsLimit($filter, $page = 1, $pageSize = 500)
    {
        $relTags = $this->itemsRelTags->lists($filter, $page, $pageSize);
        return $relTags;
    }

    public function getRelCount($filter)
    {
        return $this->itemsRelTags->count($filter);
    }

    public function deleteById($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $lists = $this->itemsRelTags->lists($filter);
            if (isset($lists['list']) && $lists['list']) {
                $result = $this->itemsRelTags->deleteBy($filter);
            }
            $result = $this->entityRepository->deleteBy($filter);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function checkActivity($itemIds, $tagIds, $companyId, &$errMsg = '')
    {
        if (!$tagIds) {
            return true;
        }

        //根據標簽查詢
        $filters[] = [
            'item_id' => $tagIds,
            'item_type' => 'tag',
        ];

        //商品id轉換成主商品ID
        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemIds, $companyId);
        if (!$items) {
            return true;
        }

        $itemFilter['item_main_cat_id'] = array_column($items, 'item_main_cat_id');
        $itemFilter['brand_id'] = array_column($items, 'brand_id');

        //指定商品查詢
        if ($itemIds) {
            $filters[] = [
                'item_id' => $itemIds,
                'item_type' => 'normal',
            ];
        }

        //根據品牌查詢
        if ($itemFilter['brand_id']) {
            $filters[] = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
            ];
        }

        //根據主類目查詢
        if ($itemFilter['item_main_cat_id']) {
            $filters[] = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
            ];
        }

        //獲取當前商品和商品標簽符合的所有商品限購活動ID
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('limit_id,item_id,item_type,start_time,end_time')->from('promotions_limit_item');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    //$criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relItemArr = $criteria->execute()->fetchAll();
        foreach ($relItemArr as $k => $v) {
            foreach ($relItemArr as $kk => $vv) {
                if ($kk <= $k) {
                    continue;
                }
                if ($vv['start_time'] < $v['end_time'] && $vv['end_time'] > $v['start_time']) {
                    //查詢衝突活動的名稱
                    $limit_ids = [$v['limit_id'], $vv['limit_id']];
                    if ($limit_ids) {
                        $limitService = new LimitService();
                        $limitInfo = $limitService->lists(['limit_id' => $limit_ids], 'limit_name');
                        if ($limitInfo['list']) {
                            $limitInfo = array_column($limitInfo['list'], 'limit_name');
                            $errMsg = '商品標簽導致限購活動 '.implode(', ', $limitInfo).' 衝突';
                        }
                    }
                    return false;
                }
            }
        }

        //獲取當前商品和商品標簽符合的所有滿減滿折活動ID
        $criteria = $conn->createQueryBuilder();
        $criteria->select('marketing_id,item_id,item_type,start_time,end_time,marketing_type')->from('promotions_marketing_activity_items');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    //$criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relActivityItems = $criteria->execute()->fetchAll();

        //滿減滿折互相衝突，滿贈自身衝突, 加價購自身衝突
        //營銷類型: full_discount:滿折,full_minus:滿減,full_gift:滿贈,
        //營銷類型: self_select:任選優惠,plus_price_buy:加價購,member_preference:會員優先購
        $relItems = [];
        foreach ($relActivityItems as $v) {
            switch ($v['marketing_type']) {
                case 'full_discount':
                case 'full_minus':
                    $relItems['滿減滿折'][] = $v;//滿減滿折互相衝突
                    break;

                default:
                    $relItems[$v['marketing_type']][] = $v;//其他活動和自身衝突
                    break;
            }
        }

        foreach ($relItems as $relItemArr) {
            foreach ($relItemArr as $k => $v) {
                foreach ($relItemArr as $kk => $vv) {
                    if ($kk <= $k) {
                        continue;
                    }
                    if ($vv['start_time'] < $v['end_time'] && $vv['end_time'] > $v['start_time']) {
                        //查詢衝突活動的名稱
                        $marketing_ids = [$v['marketing_id'], $vv['marketing_id']];
                        if ($marketing_ids) {
                            $limitService = new MarketingActivityService();
                            $activityInfo = $limitService->lists(['marketing_id' => $marketing_ids], 'marketing_name,promotion_tag');
                            if ($activityInfo['list']) {
                                $activityTips = [];
                                foreach ($activityInfo['list'] as $activity) {
                                    $activityTips[] = '【'.$activity['promotion_tag'].'】'.$activity['marketing_name'];
                                }
                                $errMsg = '商品標簽導致活動 '.implode(', ', $activityTips).' 衝突';
                            }
                        }
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
    * 為會員批量打標簽
    */
    public function createRelTags($itemIds, $tagIds, $companyId)
    {
        $savedata['company_id'] = $companyId;
        foreach ($itemIds as $itemId) {
            $savedata['item_id'] = $itemId;
            foreach ($tagIds as $tagId) {
                $savedata['tag_id'] = $tagId;
                if (!$this->itemsRelTags->getInfo($savedata)) {
                    $result = $this->itemsRelTags->create($savedata);
                }
            }
        }
        return true;
    }

    /**
    * 為指定會員打標簽
    */
    public function createRelTagsByItemId($itemId, $tagIds, $companyId)
    {
        $savedata['item_id'] = $itemId;
        $savedata['company_id'] = $companyId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->itemsRelTags->getInfo($savedata)) {
                $result = $this->itemsRelTags->deleteBy($savedata);
            }
            if ($tagIds) {
                foreach ($tagIds as $tagId) {
                    $savedata['tag_id'] = $tagId;
                    $this->itemsRelTags->create($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 單一標簽關聯多會員
    */
    public function createRelTagsByTagId($itemIds, $tagId, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $savedata['company_id'] = $companyId;
            foreach ($itemIds as $itemId) {
                $savedata['item_id'] = $itemId;
                $this->itemsRelTags->deleteBy($savedata);
                if ($tagId) {
                    $savedata['tag_id'] = $tagId;
                    $result = $this->itemsRelTags->create($savedata);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function updateItemTag($result)
    {
        event(new ItemTagEditEvent($result));
    }

    // 如果可以直接調取Repositories中的方法，則直接調用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
