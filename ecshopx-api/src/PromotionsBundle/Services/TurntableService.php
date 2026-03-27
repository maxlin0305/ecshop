<?php

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Entities\MembersInfo;
use PromotionsBundle\Entities\TurntableLog;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableFactory;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupon;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupons;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizePoint;
use CompanysBundle\Services\OperatorsService;

class TurntableService
{
    public $memberInfoRepository;
    public $discountCardsRepository;
    public $turntableLog;
    public $redisConn;

    public function __construct()
    {
        $this->memberInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $this->discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->turntableLog = app('registry')->getManager('default')->getRepository(TurntableLog::class);

        $this->redisConn = app('redis')->connection('default');
    }

    /**
     * 修改大转盘配置
     * @param $company_id string 公司id
     * @param $datas array 大转盘数据
     * @return array
     */
    public function setTurntableConfig($company_id, $datas)
    {
        $key = 'turntableConfigCompany_'.$company_id;
        $this->redisConn = app('redis')->connection('default');
        $this->redisConn->del($key);
        $this->redisConn->hmset($key, $datas);

        return ['result' => true];
    }

    /**
     * 获取大转盘配置
     * @param $company_id string 公司id
     * @return mixed 大转盘配置
     */
    public function getTurntableConfig($company_id, $userId = null)
    {
        $this->redisConn = app('redis')->connection('default');

        $result = $this->redisConn->hgetall('turntableConfigCompany_'.$company_id);
        if ($result) {
            $result['prizes'] = json_decode($result['prizes'], true);
        }

        if ($userId) {
            //用户今日已抽奖次数
            $result['today_times'] = intval($this->getUserTodayJoinTimes($userId));
            //用户剩余抽奖次数
            $result['surplus_times'] = intval($this->getUserSurplusTimes($company_id, $userId));
        }


        return $result;
    }

    /**
     * 用户参与转盘抽奖
     * @param $user_info array
     */
    public function joinTurntable($user_info)
    {
        $this->redisConn = app('redis')->connection('default');
        $turntable_config = $this->redisConn->hgetall('turntableConfigCompany_'.$user_info['company_id']);
        //检查大转盘是否已开启
        if ($turntable_config['turntable_open'] != 1) {
            throw new ResourceException('转盘活动未开启');
        }

        if ($turntable_config['long_term'] == 0) {
            if ($turntable_config['start_time'] > time() || $turntable_config['end_time'] + 86400 < time()) {
                throw new ResourceException('不在活动时间内');
            }
        }

        //检查用户抽奖次数
        $surplus = $this->getUserSurplusTimes($user_info['company_id'], $user_info['user_id']); //剩余次数
        $joined = $this->getUserTodayJoinTimes($user_info['user_id']); //今日已抽奖次数
        if ($surplus <= 0) {
            throw new ResourceException('您的抽奖次数不足');
        }
        if (($turntable_config['max_times_day'] ?? '-1') != '-1') {
            if ($joined >= $turntable_config['max_times_day']) {
                throw new ResourceException('抽奖次数到达今日上限');
            }
        }
        //抽奖次数
        $this->subUserSurplusTimes($user_info['company_id'], $user_info['user_id']);
        $this->addUserTodayJoinTimes($user_info['user_id']);

        //空奖
        $nullPrize = [
            "prize_type" => "thanks",
            "prize_name" => "谢谢惠顾"
        ];

        //处理所有奖项
        $prizes = json_decode($turntable_config['prizes'], true);
        $i = 0;
        $prizes_arr = [];
        foreach ($prizes as &$prize) {
            $prize['id'] = $i;
            $prizes_arr[$i] = intval($prize['prize_probability']);
            $i++;
        }
        unset($prize);

        //执行抽奖
        $prize_id = $this -> turntableRun($prizes_arr);
        //奖品信息
        $winning_prize = [];
        foreach ($prizes as $prize) {
            if ($prize['id'] == $prize_id) {
                $winning_prize = $prize;
            }
        }
        if (empty($winning_prize)) {
            return $nullPrize;
        }
        if ($winning_prize['prize_type'] === 'thanks') {
            return $nullPrize;
        }
        if ($winning_prize['prize_type'] === 'points') { //积分
            //加积分
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizePoint($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        } elseif ($winning_prize['prize_type'] === 'coupon') { //优惠券
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupon($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        } elseif ($winning_prize['prize_type'] === 'coupons') { //优惠券包
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupons($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        }
        if (!$result) {
            $winning_prize = $nullPrize;
        }

        //中奖记录
        $data = [
            'company_id' => $user_info['company_id'],
            'user_id' => $user_info['user_id'],
            'prize_title' => $winning_prize['prize_name'],
            'prize_type' => $winning_prize['prize_type']
        ];
        if ($data['prize_type'] === 'points') {
            $data['prize_value'] = $winning_prize['prize_value'];
        } elseif ($data['prize_type'] === 'coupon') {
            $tmp[] = $winning_prize['prize_value'];
            $data['prize_value'] = json_encode($tmp);
        } elseif ($data['prize_type'] === 'coupons') {
            $data['prize_value'] = json_encode($winning_prize['prize_value']);
        } elseif ($data['prize_type'] === 'thanks') {
            $data['prize_value'] = 0;
        }
        $this->turntableLog->create($data);


        return $winning_prize;
    }

    /**
     * 获取用户剩余抽奖次数
     * @param $userId
     * @param $companyId
     * @return mixed
     */
    public function getUserSurplusTimes($companyId, $userId)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        return $this->redisConn->hget($key, $field);
    }

    /**
     * 获取指定年月日用户的抽奖次数
     * @param $userId
     * @param $date string 日期Ymd
     * @return mixed
     */
    public function getUserTodayJoinTimes($userId, $date = null)
    {
        $key = self::getUserTodayJoinTimesKey($date);
        $field = self::getUserTodayJoinTimesField($userId);
        return $this->redisConn->hget($key, $field);
    }

    /**
     * 增加今日已抽奖次数
     * @param $userId
     */
    public function addUserTodayJoinTimes($userId, $date = null)
    {
        $key = self::getUserTodayJoinTimesKey(); //今日抽奖次数
        $field = self::getUserTodayJoinTimesField($userId);
        $this->redisConn->hincrby($key, $field, 1);
    }

    /**
     * 增加用户剩余抽奖次数
     * @param $userId
     * @param int $times
     */
    public function addUserSurplusTimes($companyId, $userId, $times = 1)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        $result = $this->redisConn->hincrby($key, $field, $times);
        return $result;
    }

    /**
     * 减少用户抽奖次数
     * @param $userId
     * @param int $times
     */
    public function subUserSurplusTimes($companyId, $userId, $times = 1)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        $result = $this->redisConn->hincrby($key, $field, -$times);
        return $result;
    }

    /**
     * 剩余抽奖次数key
     * @param $userId
     * @return string
     */
    private static function getUserSurplusTimesKey($companyId)
    {
        return 'turntableUserSurplusTimes:CompanyId:'.$companyId;
    }

    /**
     * 剩余抽奖次数field
     * @param $userId
     * @return string
     */
    private static function getUserSurplusTimesField($userId)
    {
        return 'UserId:'.$userId;
    }

    /**
     * 今日已抽奖次数key
     * @param null $date
     * @return string
     */
    private static function getUserTodayJoinTimesKey($date = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }
        return 'turntableUserJoinTimes_'.$date;
    }

    /**
     * 今日已抽奖次数field
     * @param null $userId
     * @return string
     */
    private static function getUserTodayJoinTimesField($userId)
    {
        return 'UserId:'.$userId;
    }

    /**
     * 用户购物金额累计数键值
     * @param $companyId int 公司id
     * @return string
     */
    private function getShoppingFullKey($companyId)
    {
        return 'ShoppingFull:Company:'.$companyId;
    }

    /**
     * 用户购物金额累计数字段
     * @param $userId int 用户id
     * @return string
     */
    private function getShoppingFullFiled($userId)
    {
        return 'UserId:'.$userId;
    }

    /**
     * 登陆赠送次数
     * @param $companyId
     * @param $userId
     * @return array
     */
    public function loginAddSurplusTimes($companyId, $userId)
    {
        $date = date('Ymd');
        $key = 'turntableLoginAddTimes_'.$date;
        $res = $this->redisConn->sadd($key, $userId);
        if ($res) {
            $result = $this->getTurntableConfig($companyId);
            if ($result['login_get_times'] > 0) {
                $addRes = $this->addUserSurplusTimes($companyId, $userId, $result['login_get_times']);
            }
        }
        if ($addRes ?? 0) {
            return [
                'result' => $result['login_get_times'] ?? 0
            ];
        } else {
            return [
                'result' => 0
            ];
        }
    }

    /**
     * 购物满送抽奖次数
     * @param $userId
     * @param $companyId
     * @param $totalFee
     */
    public function payGetTurntableTimes($userId, $companyId, $totalFee)
    {
        $config = $this->getTurntableConfig($companyId);

        if (($config['shopping_full'] ?? -1) != -1) {
            $configShoppingFull = bcmul($config['shopping_full'], 100);
            $div = bcdiv($totalFee, $configShoppingFull, 0);
            if ($div >= 1) {
                //增加抽奖次数
                $this->addUserSurplusTimes($companyId, $userId, $div);
            }
        }
    }

    /**
     * 执行大转盘抽奖
     * @param $proArr ['id'=>'probability']
     * @return int|string
     */
    private function turntableRun($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum); //返回随机整数

            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        return $result;
    }

//    /**
//     * 检查获奖奖品信息
//     * @param $turntable_config array 原转盘配置
//     * @param $winning_prize array 获奖奖项
//     * @param $prizes array 所有奖项
//     * @param $company_id string 公司id
//     * @return bool
//     */
//    private function checkPrizeInfo($turntable_config, $winning_prize, $prizes, $company_id)
//    {
//        if ($winning_prize['prize_surplus'] <= 0) {
//            return false;
//        } else { //减少奖品余量
//            foreach ($prizes as &$prize) {
//                if ($prize['id'] == $winning_prize['id']) {
//                    $prize['prize_surplus']--;
//                    unset($prize['id']);
//                }
//            }
//            unset($prize);
//            $turntable_config['prizes'] = json_encode($prizes);
//            $this->setTurntableConfig($company_id, $turntable_config);
//        }
//
//        return true;
//    }

    /**
     * 检查优惠券余量
     * @param $card_id string 优惠券id
     * @param $company_id string 公司id
     * @return bool
     */
    private function checkCouponsSurplus($card_id, $company_id)
    {
        //检查优惠券余量
        $discountCardService = new KaquanService(new CardService());
        $filter['card_id'] = $card_id;
        $filter['company_id'] = $company_id;
        $card_info = $discountCardService->getKaquanDetail($filter);
        $discountCardService = new UserDiscountService();
        $coupon_num = $discountCardService -> getCardGetNum($card_id, $company_id);

        if (!$card_info) { //无优惠券信息
            return false;
        } elseif ($card_info['quantity'] - $coupon_num <= 0) { //优惠券数量不足
            return false;
        }

        return true;
    }

    /**
    * 大转盘活动结束时清空抽奖次数
    */
    public function scheduleClearTurntableTimesOver()
    {
        $operatorsService = new OperatorsService();
        $orderBy = ['created' => 'DESC'];
        $operatorList = $operatorsService->lists([], $orderBy, 2000, 1);
        if ($operatorList) {
            foreach ($operatorList['list'] as $key => $value) {
                $this->clearTurntableTimesOver($value['company_id']);
            }
        }
        return true;
    }

    /**
     * 大转盘活动结束时清空抽奖次数
     * @param $companyId
     */
    public function clearTurntableTimesOver($companyId)
    {
        $config = $this->getTurntableConfig($companyId);
        $config['long_term'] = $config['long_term'] ?? '0';
        $config['clear_times_after_end'] = $config['clear_times_after_end'] ?? '0';
        $config['end_time'] = $config['end_time'] ?? 0;
        if ($config['long_term'] != "1" && $config['clear_times_after_end'] == '1' && $config['end_time'] < time()) {
            $key = self::getUserSurplusTimesKey($companyId);
            $this->redisConn->del($key);
        }
    }
}
