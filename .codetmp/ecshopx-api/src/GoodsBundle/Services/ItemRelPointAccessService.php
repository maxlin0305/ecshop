<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsRelPointAccess;

class ItemRelPointAccessService
{
    public $itemsRelPointAccess;
    /**
     * ItemsTagsService 構造函數.
     */
    public function __construct()
    {
        $this->itemsRelPointAccess = app('registry')->getManager('default')->getRepository(ItemsRelPointAccess::class);
    }

    /**
    * 保存sku關聯的獲取積分
    */
    public function saveOneData($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'item_id' => $params['item_id'],
        ];
        $info = $this->getInfo($filter);
        if ($info) {
            return $this->updateOneBy($filter, $params);
        } else {
            return $this->create($params);
        }
    }

    // 如果可以直接調取Repositories中的方法，則直接調用
    public function __call($method, $parameters)
    {
        return $this->itemsRelPointAccess->$method(...$parameters);
    }
}
