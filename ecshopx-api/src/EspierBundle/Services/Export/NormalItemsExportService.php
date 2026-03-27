<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsCategoryService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemRelAttributesService;
use DistributionBundle\Services\DistributorService;

class NormalItemsExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $distributorId = $filter['distributor_id'] ?? 0;
        unset($filter['distributor_id']);

        $itemsService = new ItemsService();
        $count = $itemsService->getItemCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . "normal_items";
        $itemList = $this->getLists($filter, $count, $distributorId);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $itemList);
        return $result;
    }

    private $title = [
        'goods_id' => '商品id',
        'item_name' => '商品名稱',
        'brief' => '商品副標題',
        'barcode' => '商品條碼',
        'pics' => '商品圖片',
        'item_main_category' => '管理分類',
        'item_category' => '前端分類',
        'goods_brand' => '品牌',
        'origin_place' => '產地',
        'tag_name' => '商品標簽',
        'item_unit' => '銷售單位',
        'item_spec' => '銷售規格',
        'item_params' => '商品參數',
        'approve_status' => '商品狀態',
        'price' => '商品售價',
        'market_price' => '商品劃線價',
        'cost_price' => '商品進價',
        'intro' => '簡介',
        'templates_id' => '運費模板',
        'weight' => '重量',
        'volume' => '體積',
        'storage_way' => '貯存方式',
        'specification' => '商品規格',
    ];

    private function getLists($filter, $count, $distributorId = 0)
    {
        $title = $this->title;
        if ($count > 0) {
            $itemsService = new ItemsService();
            $itemsTagsService = new ItemsTagsService();
            if ($distributorId) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfo(['distributor_id' => $distributorId, 'company_id' => $filter['company_id']]);
            }
            $limit = 500;
            $fileNum = ceil($count / $limit);
            for ($page = 1; $page <= $fileNum; $page++) {
                $itemData = [];
                $result = $itemsService->getItemsList($filter, $page, $limit);

                //獲取商品標簽
                $itemIds = array_column($result['list'], 'item_id');
                $tagFilter = [
                    'item_id' => $itemIds,
                    'company_id' => $filter['company_id'],
                ];

                $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
                foreach ($tagList as $tag) {
                    $newTags[$tag['item_id']][] = $tag;
                }

                foreach ($result['list'] as $i => $value) {
                    foreach ($title as $key => $val) {
                        if ($key == 'shop_code') {
                            $itemData[$i][$key] = $distributorInfo['shop_code'] ?? '無';
                        } elseif ($key == 'name') {
                            $itemData[$i][$key] = $distributorInfo['name'] ?? '無';
                        } elseif ($key == 'tag_name') {
                            $itemTag = $newTags[$value['item_id']] ?? [];
                            $itemData[$i][$key] = implode(',', array_column($itemTag, 'tag_name'));
                        } elseif ($key == 'item_main_category') {
                            $itemData[$i][$key] = $this->getItemCategory($filter['company_id'], $value, true);
                        } elseif ($key == 'item_category') {
                            $itemData[$i][$key] = $this->getItemCategory($filter['company_id'], $value, false);
                        } elseif ($key == 'goods_brand') {
                            $itemData[$i][$key] = $this->getBrandName($filter['company_id'], $value);
                        } elseif ($key == 'templates_id') {
                            $itemData[$i][$key] = $this->getTemplatesName($filter['company_id'], $value);
                        } elseif ($key == 'pics') {
                            $itemData[$i][$key] = is_array($value[$key]) ? implode(',', $value[$key]) : $value[$key];
                        } elseif ($key == 'item_params') {
                            $itemData[$i][$key] = $this->getItemParams($filter['company_id'], $value);
                        } elseif ($key == 'item_spec') {
                            $itemData[$i][$key] = $this->getItemSpec($filter['company_id'], $value);
                        } elseif ($key == 'price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } elseif ($key == 'market_price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } elseif ($key == 'cost_price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } else {
                            $itemData[$i][$key] = $value[$key] ?? '';
                        }
                    }
                }
                yield $itemData;
            }
        }
    }

    /**
     * 獲取商品分類
     */
    private function getItemCategory($companyId, $row, $isMain = false)
    {
        $itemsCategoryService = new ItemsCategoryService();
        if ($isMain) {
            $categoryPath = $itemsCategoryService->getCategoryPathById($row['item_main_cat_id'], $companyId, $isMain);
            $catNames = [];
            if ($categoryPath) {
                $this->getCategoryPathName($categoryPath[0], $catNames);
            }
            return implode('->', $catNames);
        } else {
            $catNamesArr = [];
            foreach (($row['item_cat_id'] ?? []) as $v) {
                $categoryPath = $itemsCategoryService->getCategoryPathById($v, $companyId, $isMain);
                $catNames = [];
                if ($categoryPath) {
                    $this->getCategoryPathName($categoryPath[0], $catNames);
                    $catNamesArr[] = implode('->', $catNames);
                }
            }
            return implode('|', $catNamesArr);
        }
    }

    /**
     * 通過運費模版ID，獲取運費模版名稱
     */
    private function getTemplatesName($companyId, $row)
    {
        if (!$row['templates_id']) {
            return '';
        }

        $shippingTemplatesService = new ShippingTemplatesService();
        $data = $shippingTemplatesService->getInfo($row['templates_id'], $companyId);
        return $data['name'] ?? '';
    }

    /**
     * 通過品牌ID獲取品牌名稱
     */
    private function getBrandName($companyId, $row)
    {
        $brandId = $row['brand_id'] ?? 0;
        $brandName = '';
        if ($brandId) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'attribute_id' => $brandId, 'attribute_type' => 'brand']);
            $brandName = $data['attribute_name'] ?? '';
        }
        return $brandName;
    }

    private function getCategoryPathName($categoryPath, &$catNames)
    {
        $catNames[] = $categoryPath['category_name'];
        if ($categoryPath['children'] ?? []) {
            $this->getCategoryPathName($categoryPath['children'][0], $catNames);
        }
    }

    /**
     * 獲取商品參數
     *
     * item_params: 功效:美白提亮|性別:男性
     */
    private function getItemParams($companyId, $row)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $itemRelAttributesService = new ItemRelAttributesService();
        $itemRelAttributes = $itemRelAttributesService->lists(['item_id' => $row['item_id'], 'attribute_type' => 'item_params', 'company_id' => $companyId]);
        $itemParams = [];
        foreach ($itemRelAttributes['list'] as $value) {
            $attrValues = $itemsAttributesService->getAttrValuesListBy(['attribute_id' => $value['attribute_id'], 'attribute_value_id' => $value['attribute_value_id'], 'company_id' => $companyId]);
            $attr = $itemsAttributesService->getInfo(['attribute_id' => $value['attribute_id'], 'attribute_type' => 'item_params', 'company_id' => $companyId]);
            if ($attr && $attrValues['total_count'] > 0) {
                $itemParams[] = $attr['attribute_name'] . ':' . $attrValues['list'][0]['attribute_value'];
            }
        }

        return implode('|', $itemParams);
    }

    private function getItemSpec($companyId, $row)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $itemRelAttributesService = new ItemRelAttributesService();
        $itemRelAttributes = $itemRelAttributesService->lists(['item_id' => $row['item_id'], 'attribute_type' => 'item_spec', 'company_id' => $companyId]);
        $itemSpec = [];
        foreach ($itemRelAttributes['list'] as $value) {
            $attrValues = $itemsAttributesService->getAttrValuesListBy(['attribute_id' => $value['attribute_id'], 'attribute_value_id' => $value['attribute_value_id'], 'company_id' => $companyId]);
            $attr = $itemsAttributesService->getInfo(['attribute_id' => $value['attribute_id'], 'attribute_type' => 'item_spec', 'company_id' => $companyId]);
            if ($attr && $attrValues['total_count'] > 0) {
                $itemSpec[] = $attr['attribute_name'] . ':' . $attrValues['list'][0]['attribute_value'];
            }
        }

        return implode('|', $itemSpec);
    }
}
