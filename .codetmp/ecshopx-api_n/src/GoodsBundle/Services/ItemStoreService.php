<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Items;
use Dingo\Api\Exception\ResourceException;

use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Events\ItemStoreUpdateEvent;

// 商品庫存處理
class ItemStoreService
{
    /**
     * 保存商品庫存
     */
    public function saveItemStore($itemId, $store, $distributorId = 0)
    {
        if ($distributorId) {
            $key = $distributorId . '_' . $itemId;
        } else {
            $key = $itemId;
        }

        event(new ItemStoreUpdateEvent($itemId, $store, $distributorId));
        return app('redis')->set('item_store:' . $key, $store);
    }

    /**
     * 保存商品庫存
     */
    public function deleteItemStore($itemId, $distributorId = 0)
    {
        if ($distributorId) {
            $key = $distributorId . '_' . $itemId;
        } else {
            $key = $itemId;
        }
        return app('redis')->del('item_store:' . $key);
    }

    /**
     * 批量處理庫存
     */
    public function batchMinusItemStore($data)
    {
        //@todo 臨時處理
        return true;
        foreach ($data as $row) {
            $items[] = implode(':', $row);
        }
        $itemsString = implode('/', $items) . '/';

        $redisLuaScript = new \EspierBundle\RedisLuaScript\ItemsStoreMinus();
        $result = app('redis')->eval($redisLuaScript->getScript(), 1, $itemsString);

        if (!is_array($result)) {
            throw new ResourceException($result);
        }

        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $distributorItemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
        foreach ($result as $value) {
            $itemId = $value[1];
            $store = $value[2];
            $itemkeyarr = explode('_', $value[3]);
            $distributorId = $itemkeyarr[0];
            if ((count($itemkeyarr) == 2) && ($distributorId > 0)) {
                $filter = [
                    'item_id' => $itemId,
                    'distributor_id' => $distributorId
                ];
                $distributorItemsRepository->updateOneBy($filter, ['store' => $store]);
            } else {
                $itemsRepository->updateStore($itemId, $store);
            }
        }
        return true;
    }

    /**
     * 扣減商品庫存
     */
    public function minusItemStore($itemId, $num, $distributorId = 0, $isTotalStore = true)
    {
        if ($distributorId && !$isTotalStore) {
            $key = $distributorId . '_' . $itemId;
            $msg = '經銷商ID ' . $distributorId . ',商品ID ' . $itemId;
        } else {
            $key = $itemId;
            $msg = '商品ID ' . $itemId;
        }

        app('log')->debug('扣減庫存開始：' . $msg . ',扣減數量 ' . $num);
        $store = app('redis')->decrby('item_store:' . $key, $num);
        if ($store < 0) {
            app('redis')->incrby('item_store:' . $key, $num);
            app('log')->debug('扣減庫存結束：' . $msg . ',庫存數量為 ' . app('redis')->get('item_store:' . $key) . ',失敗恢複');
            return false;
        } else {
            app('log')->debug('扣減庫存結束：' . $msg . ',庫存數量為 ' . app('redis')->get('item_store:' . $key) . ',扣減成功');

            if ($distributorId && !$isTotalStore) {
                $itemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
                $filter['distributor_id'] = $distributorId;
                $filter['item_id'] = $itemId;
                $data['store'] = $store;
                $itemsRepository->updateOneBy($filter, $data);
            } else {
                $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                $itemsRepository->updateStore($itemId, $store);
            }

            return true;
        }
    }

    //設置商品庫存預警
    public function setWarningStore($companyId, $store, $distributorId = 0)
    {
        if ($distributorId) {
            return app('redis')->set('item_warning_store:' . $companyId . $distributorId, $store);
        } else {
            return app('redis')->set('item_warning_store:' . $companyId, $store);
        }
    }

    //獲取庫存預警
    public function getWarningStore($companyId, $distributorId = 0)
    {
        if ($distributorId) {
            $store = app('redis')->get('item_warning_store:' . $companyId . $distributorId);
        } else {
            $store = app('redis')->get('item_warning_store:' . $companyId);
        }
        return $store ?: 5;
    }
}
