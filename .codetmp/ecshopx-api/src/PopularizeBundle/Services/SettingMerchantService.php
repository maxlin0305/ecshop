<?php

namespace PopularizeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberRuleService;

/**
 * 分销基础配置
 */
class SettingMerchantService
{

    // 支持推广员分佣层级
    public $supportPromoterLevel = [
        'first_level' => [
            'level' => 1,
            'name' => '上级',
        ],
        'second_level' => [
            'level' => 2,
            'name' => '上上级',
        ],
    ];

    // 支持充值分佣层级
    public $rechargePromoterLevel = [
        'first_level' => [
            'level' => 1,
            'name' => '上级',
        ],
        'second_level' => [
            'level' => 2,
            'name' => '上上级',
        ],
    ];

    /**
     * 开启分销
     */
    public function openPopularize($distributorId, $isOpen = true)
    {
        // 后续增加开启关闭记录
        $key = $this->getOpenPopularizeKey($distributorId);
        return app('redis')->set($key, $isOpen);
    }

    /**
     * 获取当前企业是否开启分销
     */
    public function getOpenPopularize($distributorId)
    {
        $key = $this->getOpenPopularizeKey($distributorId);
        $status = app('redis')->get($key);
        return ($status == 'true') ? 'true' : 'false';
    }

    private function getOpenPopularizeKey($distributorId)
    {
        return 'isMerchantOpenPopularize:' . $distributorId;
    }


    public function closePointCommission(int $companyId)
    {
        $config = $this->getConfig($companyId);
        if ($config['commission_type'] == 'point') {
            $config['commission_type'] = 'money';
            $this->setConfig($companyId, $config);
        }
        return true;
    }


    /**
     * 保存分销配置
     */
    public function setConfig($distributorId, $data)
    {
        $config = $this->getConfig($distributorId);
        $config['goods'] = $data['goods'];
// 为每个推广员层级设置佣金比例
        if (isset($data['popularize_ratio']) && $data['popularize_ratio']) {
            $config['popularize_ratio']['type'] = $data['popularize_ratio']['type'] ?: 'profit';
            $ratioType = $config['popularize_ratio']['type'];

            $totalRatio = array_sum(array_column($data['popularize_ratio'][$ratioType], 'ratio'));
            if ($ratioType == 'profit' && $totalRatio > 100) {
                //如果以订单利润分佣则不能所有比例不能超过百分之100
                throw new ResourceException('按利润分佣不能总分佣比例不可超过100%');
            }
            if ($ratioType == 'order_money' && $totalRatio > 50) {
                //如果以订单实付金额分佣则不能所有比例不能超过百分之50
                throw new ResourceException('按订单金额分佣不能总分佣比例不可超过50%');
            }

            foreach ($this->supportPromoterLevel as $name => $row) {
                if (isset($data['popularize_ratio'][$ratioType][$name])) {
                    $config['popularize_ratio'][$ratioType][$name] = [
                        'ratio' => $data['popularize_ratio'][$ratioType][$name]['ratio'],
                        'name' => $row['name'],
                    ];
                } else {
                    $config['popularize_ratio'][$ratioType][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }
        }
        $key = 'popularizeMerchantConfig:' . $distributorId;
        return app('redis')->set($key, json_encode($config));
    }

    /**
     * 获取配置信息
     */
    public function getConfig($distributorId)
    {
        $key = 'popularizeMerchantConfig:' . $distributorId;
        $data = app('redis')->get($key);
        if ($data) {
            $data = json_decode($data, true);
            foreach ($this->supportPromoterLevel as $name => $row) {
                if (!isset($data['popularize_ratio']['profit'][$name])) {
                    $data['popularize_ratio']['profit'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                    $data['popularize_ratio']['order_money'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }

            foreach ($this->rechargePromoterLevel as $name => $row) {
                if (!isset($data['recharge']['profit'][$name])) {
                    $data['recharge']['profit'][$name] = [
                        'ratio' => 0,
                        'name' => $row['name'],
                    ];
                }
            }

            $data['commission_type'] = $data['commission_type'] ?? 'money';
            $data['goods'] = $data['goods'] ?? 'all';
        }

        $data = $data ? $data : $this->getDefaultConfig();
        $data['isOpenPopularize'] = $this->getOpenPopularize($distributorId);
        return $data;
    }

    /**
     * 获取默认配置
     */
    public function getDefaultConfig()
    {
        $config['goods'] = 'all';
        // 利润分佣
        $config['popularize_ratio']['type'] = 'profit';
        foreach ($this->supportPromoterLevel as $name => $row) {
            $config['popularize_ratio']['profit'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
            $config['popularize_ratio']['order_money'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
        }
        foreach ($this->rechargePromoterLevel as $name => $row) {
            $config['recharge']['profit'][$name] = [
                'ratio' => 0,
                'name' => $row['name'],
            ];
        }
        return $config;
    }


    /**
     * 获取全部开启的分销yuan
     */
    public function getAllOpenedDistribution(): array
    {
        $all = app('redis')->keys("isMerchantOpenPopularize:*");
        $resData = [];
        foreach ($all as $v) {
            //获取$distributorId
            $distributorId = substr($v, strpos($v, ":") + 1);
            if ($distributorId != 0) {
                $count = $this->getConfig($distributorId);
                if ($count['isOpenPopularize'] == 'true'){
                    $resData[] = array_merge($this->getConfig($distributorId), [
                        'distributorId' => $distributorId
                    ]);
                }
            }
        }
        return $resData;

    }
}
