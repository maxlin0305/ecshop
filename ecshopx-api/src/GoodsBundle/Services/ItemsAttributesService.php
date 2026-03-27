<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsAttributes;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Entities\ItemRelAttributes;
use Dingo\Api\Exception\ResourceException;

class ItemsAttributesService
{
    /**
     * @var itemsAttributesRepository
     */
    private $itemsAttributesRepository;
    public $itemsAttributeValuesRepository;

    /**
     * ItemsService 構造函數.
     */
    public function __construct()
    {
        $this->itemsAttributesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributes::class);
        $this->itemsAttributeValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
    }

    public function createAttr($data)
    {
        // is_image 隻對規格屬性類型有效
        $insertData = [
            'company_id' => $data['company_id'],
            'shop_id' => isset($data['shop_id']) ? $data['shop_id'] : 0,
            'attribute_type' => $data['attribute_type'],
            'attribute_name' => $data['attribute_name'],
            'attribute_memo' => isset($data['attribute_memo']) ? $data['attribute_memo'] : '',
            'attribute_sort' => isset($data['attribute_sort']) ? $data['attribute_sort'] : 1,
            'distributor_id' => isset($data['distributor_id']) ? $data['distributor_id'] : 0,
            'is_show' => isset($data['is_show']) ? $data['is_show'] : 'true',
            'is_image' => isset($data['is_image']) ? $data['is_image'] : 'true',
            'image_url' => isset($data['image_url']) ? $data['image_url'] : '',
        ];
        //這個字段 是oms來源數據唯一標識
        if (isset($data['attribute_code']) && $data['attribute_code']) {
            $insertData['attribute_code'] = $data['attribute_code'];
        }

        if (isset($data['attribute_values']) && count($data['attribute_values']) > 60) {
            throw new ResourceException('參數或規格值不能超過60個');
        }

        if (isset($data['attribute_values']) && count($data['attribute_values']) <= 0 && $data['is_show'] == 'true') {
            throw new ResourceException('請添加參數或者規格值');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // item_params 和 item_spec 類型會有相同的值
            $result = $this->itemsAttributesRepository->create($insertData);
            if (isset($data['attribute_values']) && $data['attribute_values']) {
                $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
                $attributeValueName = [];
                foreach ($data['attribute_values'] as $key => $value) {
                    if (isset($attributeValueName[$value['attribute_value']])) {
                        throw new ResourceException('參數或者規格值不能重複');
                    } else {
                        $attributeValueName[$value['attribute_value']] = true;
                    }
                    if (!$value['attribute_value']) {
                        throw new ResourceException('參數值不能為空');
                    }
                    $attributeValues = [
                        'attribute_id' => $result['attribute_id'],
                        'company_id' => $data['company_id'],
                        'shop_id' => isset($data['shop_id']) ? $data['shop_id'] : 0,
                        'attribute_value' => trim($value['attribute_value']),
                        'sort' => $key,
                        'image_url' => isset($value['image_url']) ? $value['image_url'] : '',
                    ];

                    if ($value['oms_value_id'] ?? 0) { //這個字段是oms屬性值ID，通過更新時做關聯
                        $attributeValues['oms_value_id'] = $value['oms_value_id'];
                    }
                    $itemsAttributesValuesRepository->create($attributeValues);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    /**
     * 檢查規格是否可以刪除
     *
     * @param $companyId
     * @param $attributeId
     * @return bool
     */
    private function checkDeleteAttr($companyId, $attributeId)
    {
        $filter = ['company_id' => $companyId, 'attribute_id' => $attributeId];
        $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $relItemsData = $itemRelAttributesRepository->lists($filter, 1, -1);
        if ($relItemsData['total_count'] > 0) {
            $itemIdList = array_column($relItemsData['list'], 'item_id');
            if (empty($itemIdList)) {
                return true;
            }
            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
            $itemFilter = [
                'company_id' => $companyId,
                'item_id' => $itemIdList
            ];
            $itemList = $itemsRepository->getItemsLists($itemFilter);
            if (!empty($itemList)) {
                throw new ResourceException('有關聯商品，請先處理關聯的商品');
            }
        }
        return true;
    }

    /**
     * 檢查分類規格是否可以取消關聯
     *
     * @param $companyId
     * @param $categoryId
     * @param $attributeId
     * @return bool
     */
    public function checkDeleteCategoryAttr($companyId, $categoryId, $attributeId)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria = $criteria->select('count(*)')
        ->from('items', 'i')
        ->leftJoin('i', 'items_rel_attributes', 'r', 'i.item_id = r.item_id')
        ->andWhere($criteria->expr()->eq('i.company_id', $companyId))
        ->andWhere($criteria->expr()->eq('i.item_category', $categoryId));

        if (is_array($attributeId)) {
            $criteria->andWhere($criteria->expr()->in('r.attribute_id', $attributeId));
        } else {
            $criteria->andWhere($criteria->expr()->eq('r.attribute_id', $attributeId));
        }

        $exist = $criteria->execute()->fetchColumn();
        if ($exist) {
            throw new ResourceException('有關聯商品，請先處理關聯的商品');
        }

        return true;
    }

    public function deleteAttr($filter)
    {
        $this->checkDeleteAttr($filter['company_id'], $filter['attribute_id']);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsAttributesRepository->deleteBy($filter);

            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $itemsAttributesValuesRepository->deleteBy($filter);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 處理更新的規格值
     */
    private function __preUpdateAttrValues($filter, $data)
    {
        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValueslists = $itemsAttributesValuesRepository->lists($filter);

        $oldAttributeValueIds = [];
        if ($attrValueslists['total_count'] > 0) {
            $oldAttributeValueIds = array_column($attrValueslists['list'], 'attribute_value_id');
            $newAttributeValueIds = array_column($data['attribute_values'], 'attribute_value_id');
            $deleteIds = array_diff($oldAttributeValueIds, $newAttributeValueIds);
            if (!$deleteIds) {
                return true;
            }
            $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
            $relItemsData = $itemRelAttributesRepository->getInfo(['attribute_value_id' => $deleteIds, 'attribute_id' => $filter['attribute_id']]);
            if ($relItemsData) {
                throw new ResourceException('數值有關聯商品，請先處理關聯的商品');
            }
            // 刪除老數據
            $itemsAttributesValuesRepository->deleteBy(['attribute_value_id' => $deleteIds, 'attribute_id' => $filter['attribute_id'], 'company_id' => $filter['company_id']]);
        }

        return true;
    }

    /**
     * 更新商品屬性
     */
    public function updateAttr($filter, $data)
    {
        $info = $this->itemsAttributesRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('更新的數據不存在');
        }

        $result = $this->itemsAttributesRepository->updateOneBy($filter, $data);
        // 如果是品牌則不需要進行處理其他
        if ($info['attribute_type'] == 'brand') {
            return true;
        }

        if (isset($data['attribute_values']) && count($data['attribute_values']) > 60) {
            throw new ResourceException('參數或規格值不能超過60個');
        }
        if ((!isset($data['attribute_values']) || count($data['attribute_values']) === 0) && $data['is_show'] == 'true') {
            throw new ResourceException('請添加參數或者規格值');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // item_params 和 item_spec 類型會有相同的值

            if (!isset($data['from_oms'])) { //oms 同步過來數據不做刪除操作
                $this->__preUpdateAttrValues($filter, $data);
            }

            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $attributeValueName = [];

            if ($data['from_oms'] ?? 0) { //oms 同步更新時預處理下
                $omsValueIds = array_column($data['attribute_values'], 'oms_value_id');
                $attributeValueIds = $itemsAttributesValuesRepository->lists(['oms_value_id' => $omsValueIds]);
                $attributeValueIds = array_column($attributeValueIds['list'], null, 'oms_value_id');
                array_walk($data['attribute_values'], function (&$val) use ($attributeValueIds) {
                    if ($attributeValueIds[$val['oms_value_id']] ?? 0) {
                        $val['attribute_value_id'] = $attributeValueIds[$val['oms_value_id']]['attribute_value_id'];
                    }
                });
            }

            if ($data['attribute_values']) {
                foreach ($data['attribute_values'] as $key => $value) {
                    if (isset($attributeValueName[$value['attribute_value']])) {
                        throw new ResourceException('參數或者規格值不能重複');
                    } else {
                        $attributeValueName[$value['attribute_value']] = true;
                    }
                    if (!$value['attribute_value']) {
                        throw new ResourceException('參數值不能為空');
                    }
                    $attributeValues = [
                        'attribute_id' => $result['attribute_id'],
                        'company_id' => $result['company_id'],
                        'shop_id' => $result['shop_id'],
                        'attribute_value' => trim($value['attribute_value']),
                        'sort' => $key,
                        'image_url' => isset($value['image_url']) ? $value['image_url'] : '',
                        'updated' => time(),
                    ];
                    if ($value['oms_value_id'] ?? 0) { //這個字段是oms屬性值ID，通過更新時做關聯
                        $attributeValues['oms_value_id'] = $value['oms_value_id'];
                    }

                    if (isset($value['attribute_value_id'])) {
                        $itemsAttributesValuesRepository->updateOneBy(['attribute_value_id' => $value['attribute_value_id'], 'company_id' => $result['company_id']], $attributeValues);
                    } else {
                        $itemsAttributesValuesRepository->create($attributeValues);
                    }
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 獲取商品屬性列表
     */
    public function getAttrList($filter, $page = 1, $pageSize = 20, $orderBy = array())
    {
        $lists = $this->itemsAttributesRepository->lists($filter, $page, $pageSize, $orderBy);
        if ($lists['total_count'] > 0) {
            // 後續優化
            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            foreach ($lists['list'] as $key => $row) {
                if ($row['attribute_type'] == 'item_spec' || $row['attribute_type'] == 'item_params') {
                    $lists['list'][$key]['attribute_values'] = $itemsAttributesValuesRepository->lists(['attribute_id' => $row['attribute_id']], 1, 100);
                }
            }
        }

        return $lists;
    }

    public function getAttrValuesListBy($filter)
    {
        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        return $itemsAttributesValuesRepository->lists($filter, 1, 100);
    }

    public function getAttrValue($filter)
    {
        return $this->itemsAttributeValuesRepository->getInfo($filter);
    }

    /**
     * 獲取單個商品屬性值
     */
    public function getItemsAttrValuesList($attributeId, $companyId)
    {
        $info = $this->itemsAttributesRepository->getInfo(['company_id' => $companyId, 'attribute_id' => $attributeId]);
        if ($info) {
            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $info['attribute_values'] = $itemsAttributesValuesRepository->lists(['company_id' => $companyId, 'attribute_id' => $attributeId]);
        }

        return $info;
    }

    /**
     * 獲取屬性數據和對應的屬性值
     */
    public function getAttrValuesList($attributeIds, $attributeValueIds, $bindData)
    {
        $attributeList = $this->itemsAttributesRepository->lists(['attribute_id' => $attributeIds], 1, -1);
        if ($attributeList['total_count'] <= 0) {
            return [];
        }
        $attributeList = array_column($attributeList['list'], null, 'attribute_id');

        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValuesList = $itemsAttributesValuesRepository->lists(['attribute_value_id' => $attributeValueIds], 1, -1);
        if ($attrValuesList['total_count'] <= 0) {
            return [];
        }

        $attrValuesList = array_column($attrValuesList['list'], null, 'attribute_value_id');

        $result = [];
        foreach ($bindData as $attributeId => $attributeValuesIds) {
            $attributeValues = [];
            foreach ($attributeValuesIds as $attributeValueId) {
                if (isset($attrValuesList[$attributeValueId])) {
                    $attributeValues[] = [
                        'attribute_value_id' => $attrValuesList[$attributeValueId]['attribute_value_id'],
                        'attribute_value_name' => $attrValuesList[$attributeValueId]['attribute_value'],
                    ];
                }
            }

            if (isset($attributeList[$attributeId]) && $attributeValues) {
                $result[] = [
                    'attribute_id' => $attributeId,
                    'attribute_name' => $attributeList[$attributeId]['attribute_name'],
                    'attribute_values' => $attributeValues,
                ];
            }
        }

        return $result;
    }

    /**
     * 獲取商品關聯的屬性值
     */
    public function getItemsRelAttrValuesList($data)
    {
        if (!$data) {
            return [];
        }

        $attributeValuesIds = [];
        $attributeValuesImgs = [];
        $attributeValuesCustomName = [];
        $attributeIds = [];
        $return['attr_values_custom'] = [];
        foreach ($data as $row) {
            if ($row['attribute_value_id']) {
                $attributeValuesIds[] = $row['attribute_value_id'];
                if (!isset($attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) || !$attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) {
                    $attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']] = $row['image_url'];
                }
                $attributeValuesCustomName[$row['item_id'].'_'.$row['attribute_value_id']] = $row['custom_attribute_value'];
                $return['attr_values_custom'][$row['attribute_value_id']] = $row['custom_attribute_value'];
            }
            $attributeIds[] = $row['attribute_id'];
        }

        $attributeIds = array_unique($attributeIds);
        $attributeValuesIds = array_unique($attributeValuesIds);

//        $return['attr_values_custom'] = $attributeValuesCustomName;

        $attrList = $this->itemsAttributesRepository->lists(['attribute_id' => $attributeIds], 1, -1);
        $attrListNew = array_column($attrList['list'], null, 'attribute_id');

        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValuesList = $itemsAttributesValuesRepository->lists(['attribute_value_id' => $attributeValuesIds, 'attribute_value_id|neq' => null], 1, -1);
        $attrValuesListNew = array_column($attrValuesList['list'], null, 'attribute_value_id');

        $itemSpecDesc = [];
        $itemParams = [];
        foreach ($data as $row) {
            if ($row['attribute_type'] == 'brand') {
                $return['brand']['brand_id'] = $row['attribute_id'];
                $return['brand']['goods_brand'] = $attrListNew[$row['attribute_id']]['attribute_name'];
                $return['brand']['brand_logo'] = $row['image_url'];
            } else {
                $return['attribute_ids'][] = $row['attribute_id'];
            }

            if ($row['attribute_type'] == 'item_params' && $row['attribute_value_id']) {
                $attributeValueName = isset($attrValuesListNew[$row['attribute_value_id']]) ? $attrValuesListNew[$row['attribute_value_id']]['attribute_value'] : '';
                $itemParams[$row['attribute_id']] = [
                    'attribute_id' => $row['attribute_id'],
                    'attribute_name' => $attrListNew[$row['attribute_id']]['attribute_name'],
                    'attribute_value_id' => $row['attribute_value_id'],
                    'attribute_value_name' => $attributeValueName,
                ];
            }

            if ($row['attribute_type'] == 'item_spec') {

                //兼容商品屬性和商品數據不一致的問題
                if (!isset($attrListNew[$row['attribute_id']])) {
                    $attrListNew[$row['attribute_id']] = [
                        'attribute_name' => '-',
                        'is_image' => 'false',
                    ];
                }

                $itemSpecDesc[$row['attribute_id']]['spec_id'] = $row['attribute_id'];
                $itemSpecDesc[$row['attribute_id']]['spec_name'] = $attrListNew[$row['attribute_id']]['attribute_name'];
                $itemSpecDesc[$row['attribute_id']]['is_image'] = ($attrListNew[$row['attribute_id']]['is_image'] == 'true') ? true : false;
                $itemSpecDesc[$row['attribute_id']]['spec_values'][$row['attribute_value_id']] = [
                    'spec_value_id' => $row['attribute_value_id'],
                    'spec_custom_value_name' => $attributeValuesCustomName[$row['item_id'].'_'.$row['attribute_value_id']] ?: null,
                    'spec_value_name' => $attributeValuesCustomName[$row['item_id'].'_'.$row['attribute_value_id']] ?: $attrValuesListNew[$row['attribute_value_id']]['attribute_value'],
                    'item_image_url' => $attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']] ?? '',
                    'spec_image_url' => $attrValuesListNew[$row['attribute_value_id']]['image_url'],
                ];
                //商品詳情頁規格圖片默認優先顯示自定義規格，然後是規格圖片
                if (isset($attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) && $attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) {
                    $itemSpecDesc[$row['attribute_id']]['spec_values'][$row['attribute_value_id']]['spec_image_url'] = reset($attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]);
                }
                $return['item_spec'][$row['item_id']][$row['attribute_id']] = [
                    'item_id' => $row['item_id'],
                    'spec_id' => $row['attribute_id'],
                    'spec_value_id' => $row['attribute_value_id'],
                    'spec_name' => $attrListNew[$row['attribute_id']]['attribute_name'],
                    'spec_custom_value_name' => $attributeValuesCustomName[$row['item_id'].'_'.$row['attribute_value_id']] ?: null,
                    'spec_value_name' => $attributeValuesCustomName[$row['item_id'].'_'.$row['attribute_value_id']] ?: $attrValuesListNew[$row['attribute_value_id']]['attribute_value'],
                    'item_image_url' => $attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']] ?? '',
                    'spec_image_url' => $attrValuesListNew[$row['attribute_value_id']]['image_url'],
                ];
                //加入購物車規格圖片默認優先顯示自定義規格，然後是規格圖片
                if (isset($attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) && $attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]) {
                    $return['item_spec'][$row['item_id']][$row['attribute_id']]['spec_image_url'] = reset($attributeValuesImgs[$row['item_id'].'_'.$row['attribute_value_id']]);
                }
            }
        }
        if ($itemParams) {
            foreach ($itemParams as $itemParamsRow) {
                $return['item_params'][] = $itemParamsRow;
            }
        }
        if ($itemSpecDesc) {
            $tempItemSpecDesc = [];
            $specImages = [];
            foreach ($itemSpecDesc as $value) {
                sort($value['spec_values']);
                if ($value['is_image']) {
                    $specImages = $value['spec_values'];
                }
                $tempItemSpecDesc[] = $value;
            }
            $return['item_spec_desc'] = $tempItemSpecDesc;
            $return['spec_images'] = $specImages;
        }
        return $return;
    }

    public function getBrandList($filter)
    {
        // 根據商品分類id，獲取到對應的商品ID
        $itemsService = new ItemsService();
        $itemIds = [];
        if (isset($filter['category_id']) && $filter['category_id']) {
            $categoryFilter = [
                'company_id' => $filter['company_id'],
                'category_id' => $filter['category_id'],
            ];
            $itemIds = $itemsService->getItemIdsByCategoryId($categoryFilter);
            if ($itemIds == -1 || !$itemIds) {
                return [
                    'total_count' => 0,
                    'list' => []
                ];
            }
            unset($filter['category_id']);
        }
        $newfilter = [
            'company_id' => $filter['company_id'],
        ];
        if (isset($filter['distributor_id'])) {
            $newfilter['distributor_id'] = $filter['distributor_id'];
        }
        if ($itemIds) {
            $newfilter['item_id'] = $itemIds;
        }
        if ($filter['item_name'] ?? 0) {
            $newfilter['item_name|contains'] = $filter['item_name'];
        }
        $brandList = $itemsService->getBrandIds($newfilter);
        $brandFilter = [
            'company_id' => $filter['company_id'],
                'attribute_type' => 'brand',
            'attribute_id' => array_column($brandList, 'brand_id'),
        ];
        $itemSelectList['brand_list'] = $this->itemsAttributesRepository->lists($brandFilter, 1, -1);
        return $itemSelectList;
    }

    public function __call($method, $parameters)
    {
        return $this->itemsAttributesRepository->$method(...$parameters);
    }
}
