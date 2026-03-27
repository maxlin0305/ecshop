<?php

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ItemsCategoryProfit;
use Dingo\Api\Exception\ResourceException;

class ItemsCategoryProfitService
{
    // 商品按照主類目配置分潤比例
    public const PROFIT_ITEM_CATEGORY = 1;

    public $itemsCategoryProfitRepository;

    /**
     * ItemsProfitService 構造函數
     *
     * @param Type $var
     */
    public function __construct()
    {
        $this->itemsCategoryProfitRepository = app('registry')->getManager('default')->getRepository(ItemsCategoryProfit::class);
    }

    public function getItemsCategoryProfit($filter)
    {
        $itemsCategoryService = new ItemsCategoryService();

        $categoryList = $itemsCategoryService->info(['company_id' => $filter["company_id"], 'is_main_category' => true], $orderBy = ["created" => "DESC"], -1);
        if ($categoryList['list'] ?? 0) {
            throw new ResourceException('商品分類獲取失敗');
        }

        $categoryIds = array_column($categoryList['list'], 'category_id');

        //獲取分銷價格
        $itemsCategoryProfitList = $this->itemsCategoryProfitRepository->list(['category_id' => $categoryIds, 'company_id' => $filter['company_id']]);
        $itemsCategoryProfitList = array_column($itemsCategoryProfitList['list'], null, 'category_id');

        foreach ($categoryList['list'] as $key => &$val) {
            if (!isset($itemsCategoryProfitList[$val['category_id']])) {
                continue;
            }
            $val['profit_type'] = (int)$itemsCategoryProfitList[$val['category_id']]['profit_type'];
            $profitConf = json_decode($itemsCategoryProfitList[$val['category_id']]['profit_conf'], 1);
            $val['profit_conf_profit'] = $profitConf['profit'];
            $val['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
        }

        return $categoryList;
    }

    public function saveItemsCategoryProfit($params)
    {
        $profitConf = json_decode($params['profit_conf'], 1);

        if (!$profitConf) {
            throw new ResourceException('導購分潤配置錯誤');
        }
        if (!($params['category_id'] ?? 0)) {
            throw new ResourceException('主類目id不存在');
        }

        //清除已存在的會員價信息
        $this->deleteBy(['category_id' => $params['category_id'], 'company_id' => $params['company_id']]);

        $profitConfData = [
            'profit' => $profitConf['profit_conf_profit'],
            'popularize_profit' => $profitConf['profit_conf_popularize_profit']
        ];

        $saveData = [
            'category_id' => $params['category_id'],
            'company_id' => $params['company_id'],
            'profit_type' => 1,
            'profit_conf' => $profitConfData,
        ];

        $result = $this->create($saveData);

        $itemsService = new ItemsService();
        $itemsService->updateProfitBy(['company_id' => $params['company_id'], 'item_category' => $params['category_id']], self::PROFIT_ITEM_CATEGORY, bcdiv($profitConf['profit_conf_popularize_profit'], 100, 4));
        if (!$result) {
            throw new ResourceException('保存商品分類導購分潤配置失敗');
        }

        return $result;
    }

    /**
     * Dynamically call the ItemsProfitService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsCategoryProfitRepository->$method(...$parameters);
    }
}
