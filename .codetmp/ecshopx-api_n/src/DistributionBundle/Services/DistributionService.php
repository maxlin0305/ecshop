<?php

namespace DistributionBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;

class DistributionService
{
    // 商品按照默认配置分润比例
    public const PROFIT_ITEM_DEFAULT = 0;

    public const DISTRIBUTION_RATIO = 100;
    /**
     * 获取分润配置
     * @param $companyId
     * @return array|mixed
     */
    public function getDistributionConfig($companyId = null)
    {
        $info = [
            'company_id' => $companyId,
            'distributor' => [
                'show' => 0,
                'distributor' => 0,
                'seller' => 0,
                'popularize_seller' => 0,
                'distributor_seller' => 0,
                'plan_limit_time' => 0,
            ],
        ];

        $key = $this->getCompanyCacheKey($companyId);
        $redis = app('redis')->connection('default');
        $result = $redis->get($key);
        if ($result) {
            $result = json_decode($result, true);
            $info = array_merge_deep($info, $result);
        }
        return $info;
    }

    /**
     * 保存分润配置
     * @param $companyId
     * @param array $params
     * @return array|mixed
     */
    public function setDistributionConfig($companyId, array $params)
    {
        $rules = [
            'distributor.show' => ['required_with:0,1', '是否开启分润配置'],
            'distributor.distributor' => ['required', '拉新店铺分润配置必填'],
            'distributor.seller' => ['required', '拉新导购分润配置必填'],
            'distributor.popularize_seller' => ['required', '推广导购分润配置必填'],
            'distributor.distributor_seller' => ['required', '门店开单分润配置必填'],
            'distributor.plan_limit_time' => ['required', '结算时间必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $redis = app('redis')->connection('default');
        $data = [
            'company_id' => $companyId,
            'distributor' => $params['distributor'],
        ];
        $key = $this->getCompanyCacheKey($companyId);
        $redis->set($key, json_encode($data));

        $itemsService = new ItemsService();
        $itemsService->updateProfitBy(['company_id' => $companyId, 'profit_type' => self::PROFIT_ITEM_DEFAULT], self::PROFIT_ITEM_DEFAULT, bcdiv($params['distributor']['popularize_seller'], 100, 4));
        $result = $this->getDistributionConfig($companyId);
        return $result;
    }

    public function getCompanyCacheKey($companyId)
    {
        return 'distribution:config:' . sha1($companyId);
    }
}
