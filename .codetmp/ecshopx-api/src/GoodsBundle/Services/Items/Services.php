<?php

namespace GoodsBundle\Services\Items;

use GoodsBundle\Entities\ItemsRelType;
use Dingo\Api\Exception\ResourceException;

// 服務商品
class Services
{
    /**
     * 檢查關聯參數
     *
     * @return void
     */
    private function checkRelParams($params)
    {
        if (!$params['type_labels']) {
            throw new ResourceException('請選擇商品內容');
        }

        $rules = [
            'consume_type' => ['in:every,all', '核銷類型參數不正確'],
            'begin_date' => ['required_if:date_type,DATE_TYPE_FIX_TIME_RANGE', '有效期開始日期必填'],
            'end_date' => ['required_if:date_type,DATE_TYPE_FIX_TIME_RANGE', '有效期結束日期必填'],
            'fixed_term' => ['required_if:date_type,DATE_TYPE_FIX_TERM', '有效期天數必填'],
            'type_labels.*.labelId' => ['required|integer|min:1', '缺少參數數值屬性ID'],
            'type_labels.*.num' => ['required|integer', '數值規則必須是正整數'],
            'type_labels.*.limitTime' => ['required_if:consume_type,every|integer', '有效期必須是正整數'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        return true;
    }

    /**
     * 新增服務商品特有的關聯數據
     */
    public function createRelItem($itemsResult, $params)
    {
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $this->checkRelParams($params);

        $typeLabelsResult = [];
        if (isset($params['type_labels']) && $params['type_labels']) {
            foreach ($params['type_labels'] as $v) {
                if (isset($v['labelId'])) {
                    $tmp = [
                        'item_id' => $itemsResult['item_id'],
                        'label_id' => $v['labelId'],
                        'label_name' => $v['labelName'],
                        'label_price' => $v['labelPrice'] ? bcmul($v['labelPrice'], 100) : 0,
                        'num_type' => 'plus',
                        'num' => (isset($v['isNotLimit']) && $v['isNotLimit'] == 1) ? 0 : $v['num'],
                        'is_not_limit_num' => $v['isNotLimitNum'] ?? 2 ,
                        'limit_time' => $params['consume_type'] === 'every' ? $v['limitTime'] : 0,
                        'company_id' => $params['company_id'],
                    ];
                    $typeLabelsResult[] = $itemsRelTypeRepository->create($tmp);
                }
            }
        }

        $itemsResult['type_labels'] = $typeLabelsResult;
        return $itemsResult;
    }

    public function deleteRelItemById($itemId)
    {
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $itemsRelTypeRepository->deleteAllBy($itemId);

        return true;
    }

    public function listByItemId($itemId)
    {
        $itemsRelTypeRepository = app('registry')->getManager('default')->getRepository(ItemsRelType::class);

        $typeLabels = $itemsRelTypeRepository->list($itemId);

        return $typeLabels;
    }
}
