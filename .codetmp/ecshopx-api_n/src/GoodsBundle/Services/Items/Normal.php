<?php

namespace GoodsBundle\Services\Items;

use GoodsBundle\Entities\Items;
use Dingo\Api\Exception\ResourceException;

// 普通商品
class Normal
{
    public function preRelItemParams($data, $params)
    {
        $rules = [
            'rebate' => ['numeric|min:0', '請輸入正確的分銷傭金'],
            'store' => ['required|integer|min:0|max:999999999', '庫存為0-999999999的整數'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            app('log')->debug("\n preRelItemParams error =>:".json_encode($params, 256));
            throw new ResourceException($errorMessage);
        }

        if (isset($params['cost_price'])) {
            $data['cost_price'] = $params['cost_price'] ? bcmul($params['cost_price'], 100) : 0;
        }

        if (isset($params['rebate'])) {
            $data['rebate'] = $params['rebate'] ? bcmul($params['rebate'], 100) : 0;
        }

        $data['store'] = intval($params['store']);

        $itemId = $params['item_id'] ?? null;

        $data = $this->__checkItemBn($data, $itemId);

        return $data;
    }

    /**
     * 檢查商品編號是否重複
     */
    private function __checkItemBn($data, $itemId = null)
    {
        if (empty($data['item_bn'])) {
            $data['item_bn'] = strtoupper(uniqid('s'));
        }

        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $itemData = $itemsRepository->getInfo(['item_bn' => $data['item_bn'], 'company_id' => $data['company_id']]);
        if (!$itemData) {
            return $data;
        }

        if (!$itemId) {
            throw new ResourceException($data['item_bn'].'商品編碼重複，請添加正確的商品編碼');
        }

        if ($itemId && $itemData['item_id'] != $itemId) {
            throw new ResourceException('商品編碼重複，請添加正確的商品編碼');
        }

        return $data;
    }
}
