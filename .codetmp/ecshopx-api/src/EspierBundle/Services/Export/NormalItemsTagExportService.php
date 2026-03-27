<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;

class NormalItemsTagExportService implements ExportFileInterface
{
    private $title = [
        'item_name' => '商品名稱',
        'item_bn' => '商品貨號',
        'tag_name' => '標簽名稱',
    ];

    public function exportData($filter)
    {
        // TODO: Implement exportData() method.
        $itemService = new ItemsService();

        if (isset($filter['item_id'])) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id']
            ];
        }
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        $count = $itemService->getSkuItemsList($filter, 1, 1)['total_count'];
        if ($count <= 0) {
            return [];
        }
        $fileName = date('YmdHis') . "normal_items_tag";
        $dataList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $dataList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;
        $limit = 500;
        $totalPage = ceil($count / $limit);
        $itemService = new ItemsService();
        $itemsTagsService = new ItemsTagsService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemsTagData = [];
            if (isset($filter['item_id']) && $filter['item_id']) {
                $filter['default_item_id'] = $filter['item_id'];
                unset($filter['item_id']);
            }
            unset($filter['is_default']);
            $orderBy = ['default_item_id' => 'DESC'];
            $result = $itemService->getSkuItemsList($filter, $i, $limit, $orderBy);
            $default_item_ids = array_column($result['list'], 'default_item_id');
            // 查询商品标签
            $tag_filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $default_item_ids,
            ];
            $itemTagList = $itemsTagsService->getItemsRelTagList($tag_filter);
            foreach ($itemTagList as $tag) {
                $_itemTagList[$tag['item_id']][] = $tag['tag_name'];
            }
            foreach ($result['list'] as $key => $value) {
                foreach ($title as $k => $val) {
                    if ($k == 'tag_name') {
                        $tag_name = $_itemTagList[$value['default_item_id']] ?? [];
                        $itemsTagData[$key][$k] = implode(',', $tag_name);
                    } if ($k == 'item_bn' && is_numeric($value[$k])) {
                        $itemsTagData[$key][$k] = "\"'".$value[$k]."\"";
                    } elseif (isset($value[$k])) {
                        $itemsTagData[$key][$k] = $value[$k];
                    }
                }
            }
            yield $itemsTagData;
        }
    }
}
