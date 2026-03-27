<?php

namespace OrdersBundle\Services;

use OrdersBundle\Entities\Statements;
use CompanysBundle\Ego\CompanysActivationEgo;
use OrdersBundle\Jobs\GenerateStatementsJob;

class StatementsService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Statements::class);
    }

    public function scheduleGenerateStatements()
    {
        $ego = new CompanysActivationEgo();
        $settingService = new StatementPeriodSettingService();

        $offset = 0;
        $limit = 1000;

        do {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $list = $qb->select('company_id,distributor_id,created')
                ->from('distribution_distributor')
                ->andWhere($qb->expr()->neq('is_valid', $qb->expr()->literal('delete')))
                ->addOrderBy('created', 'ASC')
                ->setFirstResult($offset)->setMaxResults($limit)
                ->execute()->fetchAll();
            
            if ($list) {
                $defaultSetting = $settingService->getLists(['company_id' => array_column($list, 'company_id'), 'distributor_id' => 0], 'company_id,period');
                $defaultSetting = array_column($defaultSetting, 'period', 'company_id');
                $distributorSetting = $settingService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,period');
                $distributorSetting = array_column($distributorSetting, 'period', 'distributor_id');
            }

            foreach ($list as $row) {
                if (!isset($productModel[$row['company_id']])) {
                    try {
                        $result = $ego->check($row['company_id']);
                    } catch (\Exception $e) {
                        //conpany不存在
                        continue;
                    }
                    $productModel[$row['company_id']] = $result['product_model'];
                }

                //不是平台版不结算
                if ($productModel[$row['company_id']] != 'platform') {
                    continue;
                }

                //没有配置结算周期不结算
                if (isset($distributorSetting[$row['distributor_id']])) {
                    $period = $distributorSetting[$row['distributor_id']];
                } elseif (isset($defaultSetting[$row['company_id']])) {
                    $period = $defaultSetting[$row['company_id']];
                } else {
                    continue;
                }

                $lastEndTime = $this->getLastEndTime($row['company_id'], $row['distributor_id']);
                if (!$lastEndTime) {
                    $lastEndTime = $row['created'];
                }

                switch ($period[1]) {
                    case 'day':
                        $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.$period[0].' day');
                        break;
                    case 'week':                
                        if (strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.(7 - date('w', $lastEndTime)).' day') == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 + 7 - date('w', $lastEndTime)).' day');
                        } else {
                            $endTime = strtotime(date('Y-m-d H:i:s', $lastEndTime) .'+'.($period[0] * 7 - date('w', $lastEndTime)).' day');
                        }
                        break;
                    case 'month':
                        if (strtotime(date('Y-m-01', $lastEndTime).' +1 month') - 1 == $lastEndTime) {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.($period[0] + 1).' month') - 1;
                        } else {
                            $endTime = strtotime(date('Y-m-01', $lastEndTime).' +'.$period[0].' month') - 1;
                        }
                        break;
                }

                //还没到结算时间不结算
                if ($endTime > time()) {
                    continue;
                }

                $gotoJob = (new GenerateStatementsJob($row['company_id'], $row['distributor_id'], $period, $lastEndTime))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }

            $offset += $limit;
        } while(count($list) == $limit);
    }

    public function setLastEndTime($companyId, $distributorId, $lastEndTime)
    {
        $redisKey = 'generate_statements_last_end_time:'.$companyId.'_'.$distributorId;
        return app('redis')->set($redisKey, $lastEndTime);
    }

    public function getLastEndTime($companyId, $distributorId)
    {
        $redisKey = 'generate_statements_last_end_time:'.$companyId.'_'.$distributorId;
        return app('redis')->get($redisKey);
    }


    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
