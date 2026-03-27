<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemRelAttributes;

class ItemRelAttributesService
{
    public $ItemRelAttributes;
    /**
     * ItemsTagsService 構造函數.
     */
    public function __construct()
    {
        $this->ItemRelAttributes = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
    }

    public function getItemIdsByAttributeids($filter)
    {
        $ItemRelAttributesList = $this->ItemRelAttributes->lists($filter);
        $itemIds = array_column($ItemRelAttributesList['list'], 'item_id');
        return $itemIds;
    }



    // 如果可以直接調取Repositories中的方法，則直接調用
    public function __call($method, $parameters)
    {
        return $this->ItemRelAttributes->$method(...$parameters);
    }
}
