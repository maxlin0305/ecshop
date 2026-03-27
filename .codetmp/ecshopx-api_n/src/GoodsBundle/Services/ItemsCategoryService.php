<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsCategory;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\BusService;

class ItemsCategoryService
{
    /**
     * @var itemsCategoryRepository
     */
    private $itemsCategoryRepository;

    /**
     * ItemsService 構造函數.
     */
    public function __construct()
    {
        $this->itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
    }

    /**
     * 獲取所有分類
     */
    public function getItemsCategory($filter, $isShow = true, $page = 1, $pageSize = -1, $orderBy = ["sort" => "DESC", "created" => "ASC"], $columns = "*", $profit = 1)
    {
        $data['filter'] = $filter;
        $data['is_show'] = $isShow;
        $data['page'] = $page;
        $data['page_size'] = $pageSize;
        $data['order_by'] = $orderBy;
        $data['columns'] = $columns;
        $result = BusService::instance('goods')->post('/service/goods/category/list', $data);
        if ($result && (isset($filter['is_main_category']) && $filter['is_main_category'])) {
            $itemsCategoryProfitService = new ItemsCategoryProfitService();
            $categoryIds = [];
            foreach ($result as $v1) {
                if (isset($filter['parent_id']) || isset($filter['category_level'])) {
                    if ($v1['category_level'] == 3) {
                        $categoryIds[] = $v1['category_id'];
                    }
                } else {
                    if ($v1['children'] ?? 0) {
                        foreach ($v1['children'] as $v2) {
                            if ($v2['children'] ?? 0) {
                                foreach ($v2['children'] as $v3) {
                                    $categoryIds[] = $v3['category_id'];
                                }
                            }
                        }
                    }
                }
            }

            if ($profit) {
                if ($categoryIds) {
                    //獲取分銷價格
                    $itemsCategoryProfitList = $itemsCategoryProfitService->lists(['category_id' => $categoryIds, 'company_id' => $filter['company_id']]);
                    $itemsCategoryProfitList = array_column($itemsCategoryProfitList['list'], null, 'category_id');
                }
                foreach ($result as &$v1) {
                    if (isset($filter['parent_id']) || isset($filter['category_level'])) {
                        if ($v1['category_level'] == 3) {
                            if (!isset($itemsCategoryProfitList[$v1['category_id']])) {
                                continue;
                            }
                            $v1['profit_type'] = (int)(isset($itemsCategoryProfitList[$v1['category_id']]) ? $itemsCategoryProfitList[$v1['category_id']]['profit_type'] : 0);
                            $profitConf = isset($itemsCategoryProfitList[$v1['category_id']]) ? json_decode($itemsCategoryProfitList[$v1['category_id']]['profit_conf'], 1) : [];
                            $v1['profit_conf_profit'] = $profitConf['profit'];
                            $v1['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
                        }
                    } else {
                        if ($v1['children'] ?? 0) {
                            foreach ($v1['children'] as &$v2) {
                                if ($v2['children'] ?? 0) {
                                    foreach ($v2['children'] as &$v3) {
                                        if (!isset($itemsCategoryProfitList[$v3['category_id']])) {
                                            continue;
                                        }
                                        $v3['profit_type'] = (int)(isset($itemsCategoryProfitList[$v3['category_id']]) ? $itemsCategoryProfitList[$v3['category_id']]['profit_type'] : 0);
                                        $profitConf = isset($itemsCategoryProfitList[$v3['category_id']]) ? json_decode($itemsCategoryProfitList[$v3['category_id']]['profit_conf'], 1) : [];
                                        $v3['profit_conf_profit'] = $profitConf['profit'];
                                        $v3['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
        // $itemsCategoryInfo = $this->itemsCategoryRepository->lists($filter, $orderBy, $pageSize, $page);
        // return $this->getTree($itemsCategoryInfo['list'], 0, 0, $isShow);
    }

    /**
     * 關於節點的回溯
     * @param $categories
     * @return array
     */
    public function removeNoneCategories($categories): array
    {
        return $this->removeNoneCategory($this->removeNoneCategory($this->removeNoneCategory($categories), 2), 1);
    }

    /**
     * 刪除商品類目中為空的節點, 效率問題這裏可以改成&
     * @param $categories
     * @param int $level 指定遍曆的 category_level
     * @return array
     */
    private function removeNoneCategory($categories, int $level = 0): array
    {
        foreach ($categories as $k => $cate) {
            if ($level && isset($cate['category_level']) && $cate['category_level'] > $level) {
                continue;
            }
            if (!$cate) {
                unset($categories[$k]);
                continue;
            }
            if (!isset($cate['children'])) {
                continue;
            }
            if (!$cate['children']) {
                unset($categories[$k]);
            } else {
                $categories[$k]['children'] = $this->removeNoneCategory($cate['children'], $level);
            }
        }
        return array_values($categories);
    }

    public function processingParams(&$filter)
    {
        foreach ($filter as &$value) {
            if (isset($value['children']) && $value['children']) {
                $value['name'] = $value['category_name'];
                $value['img'] = $value['image_url'];
                $this->processingParams($value['children']);
            } else {
                $value['name'] = $value['category_name'];
                $value['img'] = $value['image_url'];
            }
        }
        return $filter;
    }

    /**
     * 根據商品ID，獲取到分類結構
     */
    public function getCategoryPathById($categoryId, $companyId, $isMainCategory = false)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId, 'is_main_category' => $isMainCategory];

        $info = $this->itemsCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }

        $path = explode(',', $info['path']);
        $orderBy = ["sort" => "DESC", "created" => "ASC"];
        if (count($path) == 3) {
            $list = $this->itemsCategoryRepository->lists(['category_id' => $path], $orderBy, -1);
            $treeList = $list['list'];
        } elseif (count($path) == 2) {
            // 獲取上級
            $parentData = $this->itemsCategoryRepository->lists(['category_id' => $path[0]], $orderBy, -1);
            $treeList = array_merge($parentData['list'], [$info]);

            // 獲取下級
            $childrenlist = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId], $orderBy, -1);
            if ($childrenlist['total_count'] > 0) {
                $treeList = array_merge($treeList, $childrenlist['list']);
            }
        } else {
            $treeList = [$info];
            // 獲取所有下級
            $childrenlist = $this->itemsCategoryRepository->getChildrenByTopCatId($categoryId);
            if ($childrenlist) {
                $treeList = array_merge($treeList, $childrenlist);
            }
        }
        return $this->getTree($treeList);
    }

    /**
     * 獲取指定主類目下的所有子類目id
     */
    public function getMainCatChildIdsBy($categoryId, $companyId)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId, 'is_main_category' => true];

        $info = $this->itemsCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }
        $mainCatIds = [];
        $childrenlist['total_count'] = 0;
        $path = explode(',', $info['path']);
        if (count($path) == 2) {
            $childrenlist = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId, 'is_main_category' => true], array(), -1);
            if ($childrenlist['total_count'] > 0) {
                $mainCatIds = array_column($childrenlist['list'], 'category_id');
            }
        } else {
            $childrenlist = $this->itemsCategoryRepository->getChildrenByTopCatId($categoryId);
            if ($childrenlist) {
                $mainCatIds = array_column($childrenlist, 'category_id');
            }
        }

        return $mainCatIds;
    }

    /**
     * 獲取單個分類信息
     */
    public function getCategoryInfo($filter)
    {
        $itemInfo = $this->itemsCategoryRepository->getInfo($filter);
        if (!$itemInfo) {
            return [];
        }
        $attributeIds = array_merge($itemInfo['goods_params'], $itemInfo['goods_spec']);
        if (!$attributeIds) {
            return $itemInfo;
        }

        $itemsAttributesService = new ItemsAttributesService();
        $attrList = $itemsAttributesService->getAttrList(['attribute_id' => $attributeIds], 1, 100, ['attribute_sort' => 'asc']);
        $itemInfo['goods_params'] = [];
        $itemInfo['goods_spec'] = [];
        foreach ($attrList['list'] as $row) {
            if ($row['attribute_type'] == 'item_params') {
                $itemInfo['goods_params'][] = $row;
            } else {
                $itemInfo['goods_spec'][] = $row;
            }
        }

        return $itemInfo;
    }

    public function getItemsCategoryIds($categoryId, $companyId)
    {
        if (is_array($categoryId)) {
            $ids = $categoryId;
        } else {
            $ids[] = $categoryId;
        }
        $itemsCategoryInfo = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId, 'company_id' => $companyId]);
        if ($itemsCategoryInfo['total_count'] > 0) {
            $tmpIds = array_column($itemsCategoryInfo['list'], 'category_id');
            $ids = array_merge($ids, $tmpIds);
            $itemsCategoryInfo = $this->itemsCategoryRepository->lists(['parent_id' => $tmpIds, 'company_id' => $companyId]);
            if ($itemsCategoryInfo['total_count'] > 0) {
                $ids = array_merge($ids, array_column($itemsCategoryInfo['list'], 'category_id'));
            }
        }

        return $ids;
    }

    public function getItemIdsByCatId($categoryId, $companyId)
    {
        $catId = $this->getItemsCategoryIds($categoryId, $companyId);
        if ($catId) {
            $itemsService = new ItemsRelCatsService();
            $filter['company_id'] = $companyId;
            $filter['category_id'] = $catId;
            $data = $itemsService->lists($filter);
            if ($data['list']) {
                $itemIds = array_column($data['list'], 'item_id');
                return $itemIds;
            }
        }
        return [];
    }

    /**
     * 添加分類
     *
     * @param array params 分類數據
     * @return array
     */
    public function saveItemsCategory($data, $companyId, $distributorId)
    {
        $data['form'] = $data;
        $data['company_id'] = $companyId;
        $data['distributor_id'] = $distributorId;
        return $result = BusService::instance('goods')->post('/service/goods/category', $data);
    }

    /**
     * 添加分類
     */
    public function createClassificationService($params, $companyId, $distributorId, $level = 1, $parentId = 0, $path = "", $is_main_category = 0)
    {
        $params['category_level'] = $level;
        $params['company_id'] = $companyId;
        $params['distributor_id'] = $distributorId;
        $params['path'] = $path;
        if (!isset($params['is_main_category'])) {
            $params['is_main_category'] = $is_main_category;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!isset($params['parent_id'])) {
                $params['parent_id'] = $parentId;
                $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $params['category_name'],'parent_id' => $params['parent_id'],'company_id' => $companyId,'is_main_category' => $params['is_main_category'],'distributor_id' => $distributorId]);
                if ($uniqueName) {
                    throw new ResourceException('分類名稱已存在');
                } else {
                    $res = $this->itemsCategoryRepository->create($params);
                    $updPath = $this->itemsCategoryRepository->updateOneBy(['category_id' => $res['category_id']], ['path' => $res['category_id']]);
                    if ($res && $updPath) {
                        $result = ['status' => true];
                    }
                }
            } else {
                $parentInfo = $this->itemsCategoryRepository->getInfo(['category_id' => $params['parent_id']]);
                if ($parentInfo['parent_id'] == 0) {
                    $params['category_level'] = $level + 1;
                    $path = $parentInfo['path'];
                } else {
                    $params['category_level'] = $parentInfo['category_level'] + 1;
                    $path = $parentInfo['path'];
                }
                $res = $this->itemsCategoryRepository->create($params);
                $updPath = $this->itemsCategoryRepository->updateOneBy(['category_id' => $res['category_id']], ['path' => $path.','.$res['category_id']]);
                if ($res && $updPath) {
                    $result = ['status' => true];
                }
            }

            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 保存分類
     */
    private function __saveCategory($data, $companyId, $distributorId, $level = 1, $parentId = 0, $path = "")
    {
        foreach ($data as $row) {
            $params = [
                'category_name' => $row['category_name'],
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'is_main_category' => $row['is_main_category'] ?? false,
                'category_level' => $level,
                'path' => $path,
                'sort' => $row['sort'],
                'goods_params' => $row['goods_params'] ?? null,
                'goods_spec' => $row['goods_spec'] ?? null,
                'image_url' => $row['image_url'],
                'distributor_id' => $distributorId,
            ];
            if (isset($row['category_id'])) {
                $result = $this->itemsCategoryRepository->updateOneBy(['category_id' => $row['category_id'], 'company_id' => $companyId], $params);
            } else {
                $result = $this->itemsCategoryRepository->create($params);
            }

            if ($result['path']) {
                $newPath = $result['path']. ',' . $result['category_id'];
            } else {
                $newPath = $result['category_id'];
            }
            $result = $this->itemsCategoryRepository->updateOneBy(['category_id' => $result['category_id']], ['path' => $newPath]);

            if (isset($row['children']) && $row['children']) {
                $this->__saveCategory($row['children'], $companyId, $distributorId, $level + 1, $result['category_id'], $result['path']);
            }
        }

        return true;
    }

    /**
     * 刪除分類
     *
     * @param array filter
     * @return bool
     */
    public function deleteItemsCategory($filter)
    {
        return $result = BusService::instance('goods')->delete('/service/goods/category/'.$filter['category_id'], $filter);
    }

    /**
     * 遞歸實現無限極分類
     * @param $array 分類數據
     * @param $pid 父ID
     * @param $level 分類級別
     * @return $list 分好類的數組 直接遍曆即可 $level可以用來遍曆縮進
     */

    public function getTree($array, $pid = 0, $level = 0, $isShowChildren = true)
    {
        $list = [];
        foreach ($array as $k => $v) {
            $v['children'] = [];
            if ($v['parent_id'] == $pid) {
                $v['level'] = $level;
                $v['children'] = $this->getTree($array, $v['category_id'], $level + 1, $isShowChildren);
                if ($v['category_level'] == 3) {
                    unset($v['children']);
                }
                if (!$isShowChildren && false == $v['is_main_category'] && isset($v['children']) && empty($v['children'])) {
                    unset($v['children']);
                }
                $list[] = $v;
            }
        }
        return $list;
    }
    /**
     * 更新數據表字段數據
     *
     * @param $filter 更新的條件
     * @param $data 更新的內容
     */
    public function updateOneBy(array $filter, array $data = [])
    {
        return BusService::instance('goods')->put("/service/goods/category/{$filter['company_id']}/{$filter['category_id']}", $data);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsCategoryRepository->$method(...$parameters);
    }
}
