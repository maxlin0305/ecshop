<?php

namespace GoodsBundle\Services;

use CrossBorderBundle\Entities\CrossBorderSet;
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use GoodsBundle\Entities\Items;
use PromotionsBundle\Entities\MemberPrice;
use GoodsBundle\Entities\ItemsCategory;

class ItemTaxRateService
{
    public $company_id;
    public $item_id;

    /**
     * ItemsTagsService 構造函數.
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
    }

    // 獲取商品稅率
    public function getItemTaxRate($item_id = '', $price = 0)
    {
        $this->item_id = $item_id;
        $ItemInfo = $this->getItemInfo();
        if (empty($this->company_id)) {
            $this->company_id = $ItemInfo['companyId'];
        }

        // 判斷是否為跨境商品
        if ($ItemInfo['type'] != '1') {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => 0
            ];
        }

        // 判斷是否有規則稅率
        if (!empty($ItemInfo['taxstrategy_id'])) {
            if (empty($price)) {
                $price = $ItemInfo['price'];
            }
            $Taxstrategy_tax_rate = $this->getTaxstrategy_tax_rate($ItemInfo['taxstrategy_id'], $ItemInfo['taxation_num'], $price, $ItemInfo['company_id'], 1);
            if ($Taxstrategy_tax_rate != 0) {
                return [
                    'item_id' => $ItemInfo['item_id'],
                    'type' => $ItemInfo['type'],
                    'tax_rate' => $Taxstrategy_tax_rate
                ];
            }
        }

        // 判斷商品是否有稅率
        if (!empty($ItemInfo['crossborder_tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $ItemInfo['crossborder_tax_rate']
            ];
        }

        // 判斷主類目是否有稅率
        $CategoryInfo = $this->getCategoryInfo($ItemInfo['item_category']);
        if (!empty($CategoryInfo['crossborder_tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $CategoryInfo['crossborder_tax_rate']
            ];
        }

        // 判斷全局是否有稅率
        $CrossBorder = $this->getCrossBorder();
        if (!empty($CrossBorder['tax_rate'])) {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => $CrossBorder['tax_rate']
            ];
        } else {
            return [
                'item_id' => $ItemInfo['item_id'],
                'type' => $ItemInfo['type'],
                'tax_rate' => 0
            ];
        }
    }

    // 獲取商品的相關信息
    private function getItemInfo()
    {
        if (!empty($this->company_id)) {
            $filter['company_id'] = $this->company_id;
        }
        $filter['item_id'] = $this->item_id;
        // 商品信息
        $ItemInfo = app('registry')->getManager('default')->getRepository(Items::class)->getInfo($filter);
        // 商品會員價格(暫時無用)
//        $promotionsMemberPrice = app('registry')->getManager('default')->getRepository(MemberPrice::class)->getInfo($filter);
//        if (!empty($promotionsMemberPrice)) {
//            $ItemInfo['member_price'] = json_decode($promotionsMemberPrice['mprice'], true);
//        } else {
//            $ItemInfo['member_price'] = [];
//        }
        return $ItemInfo;
    }

    // 獲取主類目信息
    private function getCategoryInfo($item_category)
    {
        $filter['company_id'] = $this->company_id;
        $filter['category_id'] = $item_category;
        return app('registry')->getManager('default')->getRepository(ItemsCategory::class)->getInfo($filter);
    }

    // 獲取全局稅率
    private function getCrossBorder()
    {
        $filter['company_id'] = $this->company_id;
        return app('registry')->getManager('default')->getRepository(CrossBorderSet::class)->getInfo($filter);
    }

    // 獲取跨境稅費規則中的稅費
    public function getTaxstrategy_tax_rate($taxstrategy_id, $taxation_num, $taxable_fee, $company_id, $num)
    {
        // 單價
        $Price = bcdiv($taxable_fee, $num, 0);
        // 單位份數為0 ，稅費也為0
        if (empty($taxation_num)) {
            return 0;
        }
        // 單份計稅價格
        $OnePrice = bcdiv(bcdiv($Price, $taxation_num, 2), 100, 2);

        $taxstrategy_tax_rate = 0;
        $filter['id'] = $taxstrategy_id;
        $filter['company_id'] = $company_id;
//        $filter['state'] = 1;    // 不考慮策略當前狀態是否刪除
        $Strategy = new Strategy();
        $data = $Strategy->getInfo($filter);
        // 判斷是否有規則
        if (!empty($data)) {
            $taxstrategy_content = $data['taxstrategy_content'];
            foreach ($taxstrategy_content as $k => $v) {
                // 判斷是否符合當前規則
                if ($v['start'] < $OnePrice and $OnePrice <= $v['end']) {
                    $taxstrategy_tax_rate = $v['tax_rate'];
                    break;
                }
            }
        }
        return $taxstrategy_tax_rate;
    }
}
