<?php

namespace GoodsBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DiscountGoodsUploadService extends MarketingGoodsUploadService
{
    public $header = [
        '商品編碼-精確到規格' => 'item_bn',
        '商品名稱' => 'item_name',
        '商品可兌換上限' => 'limit_num',
    ];

    public $headerInfo = [
        '商品編碼-精確到規格' => [
            'size' => 32,
            'remarks' => '商品編碼-精確到規格',
            'is_need' => true,
        ],
        '商品名稱' => [
            'size' => 255,
            'remarks' => '商品名稱',
            'is_need' => true,
        ],
        '商品可兌換上限' => [
            'size' => 255,
            'remarks' => '商品可兌換上限為大於0的整數，請注意是整數，小數或浮點數將向上取整',
            'is_need' => true,
        ],
    ];

    public $isNeedCols = [
        '商品編碼-精確到規格' => 'item_bn',
        '商品名稱' => 'item_name',
        '商品可兌換上限' => 'limit_num',
    ];

    /**
     * 返回上傳的活動商品列表
     *
     * @param $fileUrl
     *
     * @return array
     */
    public function syncProcess($fileUrl)
    {
        ini_set('memory_limit', '256M');
        $items = [];
        $fail_items = [];//數據庫裏不存在的商品貨號
        $invalid = []; //已參加其他活動的商品
        $maxItemNums = 500;//每次最多上傳500
        //設置頭部
        $results = app('excel')->toArray(new \stdClass(), $fileUrl);
        $results = $results[0];

        $headerData = array_filter($results[0]);
        $column = $this->headerHandle($headerData);
        $headerSuccess = true;
        unset($results[0]);

        if (count($results) > $maxItemNums) {
            throw new BadRequestHttpException("每次最多上傳{$maxItemNums}個商品...請減少後再提交");
        }

        // 如果頭部是正確的，才會處理到下一步
        if ($headerSuccess) {
            foreach ($results as $key => $row) {
                if (!array_filter($row)) {
                    continue;
                }

                $item = $this->preRowHandle($column, $row);
                $items[$item['item_bn']] = $item;
            }

            //批量查詢商品信息, ID 和 商品圖片
            if ($items) {
                $itemsService = new ItemsService();
                $params = [];
                $params['item_bn'] = array_keys($items);
                $list = $itemsService->getItemsList($params, 1, $maxItemNums);
                $datalist = array_column($list['list'], null, 'item_id');
                if ($datalist) {
                    foreach ($datalist as $v) {
                        $v['item_bn'] = trim($v['item_bn']);
                        $items[$v['item_bn']]['item_id'] = $v['item_id'];
                        $items[$v['item_bn']]['itemId'] = $v['item_id'];
                        $items[$v['item_bn']]['default_item_id'] = $v['default_item_id'];
                        $items[$v['item_bn']]['pics'] = $v['pics'];
                        $items[$v['item_bn']]['market_price'] = $v['market_price'];
                        $items[$v['item_bn']]['item_name'] = $v['item_name'];
                        $items[$v['item_bn']]['itemName'] = $v['item_name'];
                        $items[$v['item_bn']]['item_type'] = $v['item_type'];
                        $items[$v['item_bn']]['nospec'] = true;
                        $items[$v['item_bn']]['price'] = $v['price'];
                        $items[$v['item_bn']]['sort'] = $items[$v['item_bn']]['sort'] ?? 0;
                        $items[$v['item_bn']]['store'] = $items[$v['item_bn']]['activity_store'] ?? $v['store'];
                    }
                }
            }
            //將錯誤和正確的商品編碼分開返回
            foreach ($items as $k => $v) {
                if ($v['item_bn'] == null) {
                    throw new BadRequestHttpException('貨號不能為空...請檢查數據');
                }
                if (!isset($v['item_id']) && $v['item_bn']) {
                    $fail_items[] = [
                        'item_bn' => $v['item_bn'],
                        'item_name' => $v['item_name'],
                    ];
                    unset($items[$k]);
                }
            }
        }

        return [
            'succ' => array_values($items),
            'invalid' => $invalid,
            'fail' => $fail_items,
        ];
    }
}
