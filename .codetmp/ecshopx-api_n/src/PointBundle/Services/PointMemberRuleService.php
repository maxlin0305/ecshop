<?php

namespace PointBundle\Services;

use PointBundle\Exception\PointResourceException;
use PopularizeBundle\Services\SettingService;

class PointMemberRuleService
{
    private $rule;
    public function __construct($companyId = '')
    {
        if ($companyId) {
            $this->rule = $this->getPointRule($companyId);
        }
    }

    /**
     * 获取积分规则
     * @param $companyId
     * @return mixed
     */
    public function getPointRule($companyId)
    {
        $config = [
            'name' => '积分',
            'isOpenMemberPoint' => false,
            'gain_point' => 1,
            'gain_limit' => 9999999,
            'gain_time' => 7,
            'isOpenDeductPoint' => false,
            'deduct_proportion_limit' => 100,// 每单积分抵扣金额上限
            'deduct_point' => 0,
            'access' => 'order',
            'rule_desc' => '',
        ];
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        if ($result) {
            $result = json_decode($result, true);
        }
        $result = array_merge($config, $result ?: []);
        return $result;
    }

    /**
     * 返回积分名
     * @return mixed
     */
    public function getPointName()
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->getPointRule($companyId);
        return $result['name'];
    }

    /**
     * 获取积分规则
     * @param $companyId
     * @return mixed
     */
    public function getUsePointRule($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        $result = $result ? json_decode($result, true) : '';
        return $result['recharge'] ?? 0;
    }

    /**
     * 保存积分规则
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function savePointRule($companyId, $data)
    {
        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId($companyId), json_encode($data));
        $result = $this->getPointRule($companyId);
        $this->rule = $result;

        // 关闭积分时 积分返佣将会关闭
        if (!$this->getIsOpenPoint()) {
            (new SettingService())->closePointCommission($companyId);
        }

        return $this->getPointRule($companyId);
    }

    /**
     * 钱换积分
     * @param $companyId
     * @param $money
     * @return int
     */
    public function moneyToPoint($companyId, $money)
    {
        $this->rule = $this->getPointRule($companyId);
        if (isset($this->rule['isOpenMemberPoint']) && 'true' == $this->rule['isOpenMemberPoint']) {
            return intval(bcmul(bcdiv($money, 100, 2), $this->rule['deduct_point'], 2));
        } else {
            throw new PointResourceException("{point}支付未开启");
        }
    }

    /**
     * 积分换钱
     * @param $point
     * @return int
     */
    public function pointToMoney($point)
    {
        if (isset($this->rule['isOpenDeductPoint']) && $this->rule['isOpenDeductPoint'] == 'true') {
            $deductPoint = $this->rule['deduct_point'];
            if ($deductPoint == 0) {
                return 0;
            }
            return intval(bcmul(bcdiv(100, $deductPoint, 2), $point));
        } else {
            return 0;
        }
    }

    /**
     * 钱换积分
     *
     * @param $money
     * @return int
     */
    public function moneyToPointSend($money): int
    {
        // 积分关闭
        if (!isset($this->rule['isOpenDeductPoint']) || $this->rule['isOpenDeductPoint'] != 'true') {
            return 0;
        }
        // 积分抵扣关闭
        if (!isset($this->rule['isOpenMemberPoint']) || $this->rule['isOpenMemberPoint'] != 'true') {
            return 0;
        }

        if (isset($this->rule['deduct_point'])) {
            $conversionMoney = bcdiv($money, 100, 2); // 转成元
            $point = bcmul($conversionMoney, $this->rule['deduct_point'], 2); // 转为积分
            $point = ceil($point); // 积分向上取整
            return intval($point);
        } else {
            return 0;
        }
    }

    /**
     * 是否打开积分
     *
     * @return bool
     */
    public function getIsOpenPoint(): bool
    {
        if (
            !isset($this->rule['isOpenMemberPoint']) || $this->rule['isOpenMemberPoint'] != 'true' ||
            !isset($this->rule['isOpenDeductPoint']) || $this->rule['isOpenDeductPoint'] != 'true'
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 积分规则键名
     * @param $companyId
     * @return string
     */
    public function getRedisId($companyId)
    {
        return 'memeberpoint:rule:' . $companyId;
    }

    /**
     *
     */
    public function shoppingGivePoint($companyId, $payFee)
    {
        // $this->rule = $this->getPointRule($companyId);
        // if ($this->rule['isOpenDeductPoint'] == "true" && $this->rule['deduct_shopping'] > 0) {
        //    $shoppingConfig = bcmul($this->rule['deduct_shopping'], 100);
        //    $point = bcdiv($payFee, $shoppingConfig, 0);
        //    return $point;
        // }
        return 0;
    }

    /**
    * 获取订单最大抵扣积分
    * @param memberPoint:会员积分
    * @param payFee:订单最终支付金额
    */
    public function orderMaxPoint($companyId, $memberPoint, $payFee)
    {
        //配置的限制 最大抵扣
        if ($this->rule['deduct_point']) {
            $maxMoney = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $payFee);
            $moneyToPoint = $this->moneyToPoint($companyId, $maxMoney);// 本单最大抵扣积分数
            if ($memberPoint > $moneyToPoint) {
                $useLimit = $moneyToPoint;
            } else {
                $maxMoney = $this->pointToMoney($memberPoint);
                $useLimit = $this->moneyToPoint($companyId, $maxMoney);
            }
            $useLimit = $useLimit > 0 ? $useLimit : 0;// 本地，当前会员，最大可使用积分数
            $maxMoney = $this->pointToMoney($useLimit);
        } else {
            $moneyToPoint = 0;
            $useLimit = 0;
            $maxMoney = 0;
        }

        return ['limit_point' => $moneyToPoint, 'max_point' => $useLimit, 'max_money' => $maxMoney];
    }


    /**
     * 订单最大可抵扣积分
     * @param  [type] $companyId [description]
     * @param  [type] $payFee    [description]
     * @return [type]            [description]
     */
    public function orderMaxMoneyToPoint($companyId, $payFee)
    {
        if ($this->rule['deduct_point']) {
            $maxMoney = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $payFee);
            $moneyToPoint = $this->moneyToPoint($companyId, $maxMoney);// 本单最大抵扣积分数
        }
        return $moneyToPoint ?? 0;
    }


    /**
     * 检查使用的积分数是否超出配置限制比例
     * @param $point
     * @param $totalFee
     * @return bool true:未超出，false:超出
     */
    public function moneyOutLimit($point, $totalFee)
    {
        $money = $this->pointToMoney($point);
        //配置的限制
        $limit = bcmul(bcdiv($this->rule['deduct_proportion_limit'], 100, 2), $totalFee);
        if ($limit < $money) {
            return false;
        } else {
            return true;
        }
    }
}
