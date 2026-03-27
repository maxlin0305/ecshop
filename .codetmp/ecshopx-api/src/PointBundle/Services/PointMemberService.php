<?php

namespace PointBundle\Services;

use DepositBundle\Services\DepositTrade;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Support\Facades\DB;
use MembersBundle\Services\MemberService;
use PointBundle\Entities\PointMember;
use PointBundle\Entities\PointMemberLog;
use PointBundle\Entities\PointMemberMultipleIntegral;
use PopularizeBundle\Services\BrokerageService;
use PromotionsBundle\Services\RegisterPromotionsService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use PointBundle\Jobs\SendMemberPointJob;
use GoodsBundle\Services\ItemRelPointAccessService;
use PromotionsBundle\Services\ExtraPointActivityService;
use AftersalesBundle\Services\AftersalesRefundService;
use function Symfony\Component\String\s;

class PointMemberService
{
    public $pointMemberRepository;
    public $pointMemberLogRepository;
    public $pointMemberMultipleIntegralRepository;
    public $normalOrdersRepository;
    public $normalOrdersItmesRepository;

    /**
     * PointMemberService 构造函数.
     */
    public function __construct()
    {
        $this->pointMemberRepository = app('registry')->getManager('default')->getRepository(PointMember::class);
        $this->pointMemberLogRepository = app('registry')->getManager('default')->getRepository(PointMemberLog::class);
        $this->pointMemberMultipleIntegralRepository = app('registry')->getManager('default')->getRepository(PointMemberMultipleIntegral::class);
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItmesRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
    }

    /**
     * 积分交易类型 >> 开放接口
     */
    public const JOURNAL_TYPE_OPENAPI = 13;
    public const JOURNAL_TYPE_PROMOTER = 14;
    public const JOURNAL_TYPE_MAP = [
        1 => "注册赠送",
        2 => "邀请注册赠送",
        3 => "充值赠送",
        4 => "储值兑换",
        5 => "积分换购",
        6 => "消费购物（支出）",
        7 => "消费购物（获取）",
        8 => "会员等级返佣",
        9 => "取消订单返还",
        10 => "退款返还",
        11 => "大转盘",
        12 => "商家手动修改",
        13 => "开放接口",
        14 => "分销佣金（积分）",
        15 => "商家导入修改",
    ];

    /**
     * 增加积分接口
     * @param int $userId 人员id
     * @param int $companyId 公司id
     * @param int $point 积分id
     * @param int $journalType 积分记录状态
     * @param bool $status 积分状态 （true 增加积分） | （false 减少积分）
     * @param string $record 积分变更记录
     * @param array $otherParams 其他参数（包含 external_id, operater, operater_remark）
     * @param array|bool $multipleIntegral 其他参数（包含 external_id, operater, operater_remark）
     * @throws \Exception
     */
    public function addPoint($userId, $companyId, $point, $journalType = 1, $status = true, $record = '', $orderId = '', array $otherParams = [], $multipleIntegral = false, $isExpirationPoints = false)
    {
        if ($point == 0) {
            return true;
        }
        //判断是否多倍积分
        if ($multipleIntegral) {
            //积分翻倍
            $point = $point * $multipleIntegral['mi_multiple'];
            $record .= "多倍积分活动[{$multipleIntegral['mi_record_activities']}]";
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = ['user_id' => $userId, 'company_id' => $companyId];
            $data = [
                'user_id' => $userId,
                'company_id' => $companyId,
                'point' => $point,
                'status' => $status
            ];

            //判断 如果是扣除积分
            if ($status === false && !$isExpirationPoints) {
                //执行积分扣除
                $this->pointMemberMultipleIntegralRepository->deductionOfPoints($userId, $point);
            }

            $info = $this->pointMemberRepository->addPoint($filter, $data);
            if ($info) {
                $pointMemberLog = $this->pointMemberLogRepository->create([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'journal_type' => $journalType,
                    'point_desc' => ($record ?: '无记录') . '，当前剩余积分：' . $info['point'],
                    'income' => $status ? $point : 0,
                    'order_id' => $orderId,
                    'outcome' => $status ? 0 : $point,
                    "external_id" => $otherParams["external_id"] ?? "",
                    "operater" => $otherParams["operater"] ?? "",
                    "operater_remark" => $otherParams["operater_remark"] ?? "",
                ]);
                //多倍积分记录日志
                if ($multipleIntegral) {
                    $expirationTime = 0;
                    if ($multipleIntegral['mi_zeroing_time'] != 0) {
                        $expirationTime = time() + ($multipleIntegral['mi_zeroing_time'] * (60 * 60 * 24));
                    }
                    $this->pointMemberMultipleIntegralRepository->create([
                        'user_id' => $userId,
                        'point_member_log_id' => $pointMemberLog['id'] ?? 0,
                        'income' => $point,
                        'mi_multiple' => $multipleIntegral['mi_multiple'],
                        'mi_expiration_reminder' => $multipleIntegral['mi_expiration_reminder'],
                        'mi_reminder_copy' => $multipleIntegral['mi_reminder_copy'],
                        'expiration_time' => $expirationTime

                    ]);
                }
                //扣除积分 首先从积分记录扣除
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 邀请注册赠送积分
     * @param $userId 用户id
     * @param $$inviterId 推荐注册用户
     * @param $companyId 公司id
     * @return bool
     * @throws \Exception
     */
    public function RegisterPoint($userId, $inviterId, $companyId)
    {
        $registerPromotionsService = new RegisterPromotionsService();
        $info = $registerPromotionsService->getRegisterPointConfig($companyId, 'point');
        if ($info && 'true' == $info['is_open']) {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                app('log')->info('point:member-' . $userId . '-' . $companyId . '|注册赠送积分' . $info['point']);

                $this->addPoint($userId, $companyId, $info['point'], 1, true, '注册赠送积分');

                if ($inviterId) {
                    app('log')->info('point:member-' . $inviterId . '-' . $companyId . '|邀请' . $userId . '注册赠送积分' . $info['rebate']);
                    $this->addPoint($inviterId, $companyId, $info['rebate'], 2, true, '邀请注册赠送积分');
                }

                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw $e;
            }
        } else { // 注册未开始注册送积分默认添加数据
            $info = $this->pointMemberRepository->getInfo(['user_id' => $userId, 'company_id' => $companyId]);
            if (!$info) {
                $data = [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'point' => 0,
                ];
                $info = $this->pointMemberRepository->create($data);
            }
        }
        return true;
    }

    /**
     * 储值兑换积分
     * @param $data
     * @throws \Exception
     */
    public function depositToPoint($data)
    {
        $pointMemberRuleService = new PointMemberRuleService();
        $point = $pointMemberRuleService->moneyToPoint($data['company_id'], $data['money']);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            app('log')->info('point:member-' . $data['company_id'] . '-' . $data['company_id'] . '|' . $data['money'] / 100 . '储值兑换积分' . $point);
            $this->addPoint($data['user_id'], $data['company_id'], $point, 16, true, $data['money'] / 100 . '储值兑换积分' . $point);
            // 消费储值
            $depositTrade = new DepositTrade();
            $consumeData['company_id'] = $data['company_id'];
            $consumeData['member_card_code'] = $data['user_card_code'];
            $consumeData['shop_id'] = $data['shop_id'] ?? '';
            $consumeData['shop_name'] = $data['shop_name'] ?? '';
            $consumeData['user_id'] = $data['user_id'];
            $consumeData['mobile'] = $data['mobile'] ?? '';
            $consumeData['open_id'] = $data['open_id'] ?? '';
            $consumeData['money'] = $data['money'];
            $consumeData['trade_type'] = 'consume';
            $consumeData['trade_status'] = 'SUCCESS';
            $consumeData['detail'] = '购买商品';
            $consumeData['time_start'] = time();
            $consumeData['cur_pay_fee'] = $data['pay_fee'] ?? '';
            $depositTrade->consume($consumeData);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 注册赠送积分，必须满足积分不足100的时候
     * @param $userId
     * @param $companyId
     * @throws \Exception
     */
    public function sendRegPoint($userId, $companyId)
    {
        $memberService = new MemberService();
        $memberFilter = ['user_id' => $userId, 'company_id' => $companyId];
        $memberInfo = $memberService->getMemberInfo($memberFilter);
        if (false == $memberInfo['use_point']) {
            $depositTrade = new DepositTrade();
            $deposit = $depositTrade->getDepositTradeRechargeCount($userId);
            if ($deposit > 10000) {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    $brokerageService = new BrokerageService();
                    $promoterList = $brokerageService->getParentPromoterList($userId, 1);
                    if (!empty($promoterList)) {
                        $promoterInfo = isset($promoterList[0]) ? $promoterList[0] : null;
                        // 存在对应的推广员并且未被禁用
                        if ($promoterInfo && false == $promoterInfo['disabled']) {
                            $registerPromotionsService = new RegisterPromotionsService();
                            $info = $registerPromotionsService->getRegisterPointConfig($companyId, 'point');
                            app('log')->info('point:member-' . $promoterInfo['user_id'] . '-' . $companyId . '|邀请' . $userId . '注册赠送积分' . $info['rebate']);
                            $this->addPoint($promoterInfo['user_id'], $companyId, $info['rebate'], 2, true, '邀请注册赠送积分');
                        }
                    }
                    $memberService->updateMemberInfo(['use_point' => true], $memberFilter);
                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
            }
        }
    }

    public function scheduleSendMemberPoint()
    {
        $gotoJob = (new SendMemberPointJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return true;
    }

    /**
     * 根据获取积分配置及订单获取订单
     */
    public function SendMemberPoint()
    {
        app('log')->debug('会员结算积分开始');
        $pageSize = 100;
        $time = time();
        $filter = [
            'order_status' => 'DONE',
            'delivery_status' => 'DONE',
            'cancel_status|in' => ['NO_APPLY_CANCEL', 'FAILS'],
            'send_point' => 0,
            'user_id|gt' => 0,
        ];
        //获取是否双倍积分
        $pointMemberRuleService = new PointMemberRuleService();
        $multipleIntegral = $pointMemberRuleService->getMultipleIntegral();
        app('log')->debug('获取是否双倍积分：' . var_export($multipleIntegral, 1));
        $totalCount = $this->normalOrdersRepository->count($filter);
        if ($totalCount) {
            $pointService = new PointMemberRuleService();
            $pointLogService = new PointMemberLogService();
            $totalPage = ceil($totalCount / $pageSize);
            $succ_orders = [];
            for ($i = 0; $i < $totalPage; $i++) {
                $data = $this->normalOrdersRepository->getList($filter, $i * $pageSize, $pageSize, ["end_time" => "ASC"]);
                $company_ids = array_column($data, 'company_id');
                foreach ($company_ids as $company_id) {
                    $rules[$company_id] = $pointService->getPointRule($company_id);
                }
                foreach ($data as $row) {
                    $rule = $rules[$row['company_id']];
                    if ($rule['isOpenMemberPoint'] == 'true' && $row['end_time'] <= $time - (24 * 60 * 60) * $rule['gain_time']) {
                        $params = [
                            'company_id' => $row['company_id'],
                            'user_id' => $row['user_id'],
                            'journal_type' => 7
                        ];
                        $mark = "订单获取积分";
                        $pointTotal = $pointLogService->check_point_income($params);
                        // 为了兼容升级后的老数据
                        if ($row['get_point_type'] == 1) {
                            $return_point = $this->getReturnPoint($row['order_id']);
                            $point = bcsub(bcadd($row['get_points'], $row['extra_points']), $return_point);
                        } else {
                            if ($rule['access'] == 'items') {
                                $orderItems = $this->normalOrdersItmesRepository->getList(['order_id' => $row['order_id']]);
                                $orderItems = array_column($orderItems['list'], null, 'item_id');
                                $point = $this->getPointByItems($row['company_id'], $orderItems);
                            } else {
                                //从订单获取积分是否包含运费
                                if (isset($rule['include_freight']) && $rule['include_freight']) {
                                    $point = bcmul($rule['gain_point'], ($row['total_fee'] / 100));
                                } else {
                                    $pointFreightFee = 0;
                                    if ($row['point_fee'] > 0 && $row['freight_fee'] > 0) {
                                        $pointFreightFee = bcsub($row['point_fee'], array_sum(array_column($row['items'], 'point_fee')));
                                    }
                                    $point = bcmul($rule['gain_point'], (($row['total_fee'] - ($row['freight_fee'] - $pointFreightFee)) / 100));
                                }
                            }
                        }
                        if (($pointTotal + $point) >= $rule['gain_limit']) {
                            $minpoint = $rule['gain_limit'] - $pointTotal;
                            $mark = "应增加" . $point . "积分，本月订单获取积分达到限度";
                            $point = ($minpoint > 0) ? $minpoint : 0;
                        }
                        try {
                            $this->addPoint($row['user_id'], $row['company_id'], intval($point), 7, true, $mark, $row['order_id'], [], $multipleIntegral);
                            $succ_orders[] = $row['order_id'];
                            continue;
                        } catch (\Exception $e) {
                            app('log')->debug('积分增加失败:' . $row['order_id'] . '---->' . $e->getMessage());
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }
            if ($succ_orders) {
                foreach ($succ_orders as $order_id) {
                    $this->normalOrdersRepository->update(['order_id' => $order_id], ['send_point' => 1]);
                }
            }
        }

        return true;
    }

    /**
     * 已支付订单取消订单退还积分,根据订单的积分抵扣积分返还
     * @param $orderData
     * @return bool
     * @throws \Exception
     */
    public function cancelOrderReturnBackPoints($orderData)
    {
        $orderData['point_use'] = $orderData['point_use'] ?? 0;
        if (intval($orderData['point_use']) > 0 && $orderData['pay_type'] != 'point') {
            try {
                $result = $this->addPoint($orderData['user_id'], $orderData['company_id'], $orderData['point_use'], 9, true, '取消订单' . $orderData['order_id'] . '返还', $orderData['order_id']);

                return $result;
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
        return false;
    }

    /**
     * 根据skuId 获取商品设置的可获取积分-获取积分为商品模式时
     * @param $companyId
     * @param $orderItems
     * @return int
     */
    public function getPointByItems($companyId, $orderItems)
    {
        $point = 0;
        $itemRelPointAccessService = new ItemRelPointAccessService();
        $result = $itemRelPointAccessService->lists(['item_id' => array_column($orderItems, 'item_id')]);
        if (isset($result['list']) && $result['list']) {
            $result = array_column($result['list'], null, 'item_id');
            foreach ($orderItems as $item) {
                if (isset($result[$item['item_id']])) {
                    $point += bcmul($result[$item['item_id']]['point'], $item['num']); //先用 + / * % 后面在转成系统公用math方法
                }
            }
        }
        return $point;
    }

    /**
     * 获取会员可以获取到的积分
     * @param $companyId 企业ID
     * @param $row 订单数据
     * @return int|string
     */
    public function memberGetPoints($companyId, $row)
    {
        $itemIds = array_column($row['items'], 'item_id');
        $orderItems = array_column($row['items'], null, 'item_id');
        $pointService = new PointMemberRuleService();
        $rule = $pointService->getPointRule($companyId);
        $extraPointActivityService = new ExtraPointActivityService();

        $point = 0;
        $pointFreightFee = 0;
        if ($row['point_fee'] > 0 && $row['freight_fee'] > 0) {
            $pointFreightFee = bcsub($row['point_fee'], array_sum(array_column($row['items'], 'point_fee')));
        }
        if ($rule['isOpenMemberPoint'] == 'true') {
            if ($rule['access'] == 'items') {
                $point = $this->getPointByItems($companyId, $orderItems);
            } else {
                if (isset($rule['include_freight']) && $rule['include_freight'] == "false") {
                    $point = bcmul($rule['gain_point'], (($row['total_fee'] - ($row['freight_fee'] - $pointFreightFee)) / 100));
                } else {
                    $point = bcmul($rule['gain_point'], ($row['total_fee'] / 100));
                }
            }
            $point = (int)$point > 0 ? $point : 0;
        }
        $row['get_points'] = $point;
        $filter = [
            'distributor_id' => $row['distributor_id'],
            'user_id' => $row['user_id'],
            'company_id' => $row['company_id'],
            'total_fee' => $row['total_fee'] - ($row['freight_fee'] - $pointFreightFee)
        ];
        if ($point > 0) {
            $row['extra_points'] = $extraPointActivityService->getExtrapoints($filter, $point);
            $row = $this->orderItemGetPoints($companyId, $row, $rule, $itemIds, $pointFreightFee);
        }
        return $row;
    }

    /**
     * 订单所得积分分摊到明细上
     */
    public function orderItemGetPoints($companyId, $orderData, $rule, $itemIds, $pointFreightFee)
    {
        if ($rule['access'] == 'items') {
            $itemRelPointAccessService = new ItemRelPointAccessService();
            $result = $itemRelPointAccessService->lists(['item_id' => $itemIds]);
            if (isset($result['list']) && $result['list']) {
                $result = array_column($result['list'], null, 'item_id');
                foreach ($orderData['items'] as $key => $val) {
                    if (isset($result[$val['item_id']])) {
                        $orderData['items'][$key]['get_points'] = bcmul($result[$val['item_id']]['point'], $val['num']); //先用 + / * % 后面在转成系统公用math方法
                    }
                }
            }
            // $t
        } else {
            if (isset($rule['include_freight']) && $rule['include_freight'] == "false") {
                $total_fee = $orderData['total_fee'] - ($orderData['freight_fee'] - $pointFreightFee);
            } else {
                $total_fee = $orderData['total_fee'];
            }
            foreach ($orderData['items'] as $key => $val) {
                $orderData['items'][$key]['get_points'] = round(bcmul(bcdiv($val['total_fee'], $total_fee, 5), bcadd($orderData['extra_points'], $orderData['get_points']), 1));
            }
        }
        return $orderData;
    }


    /**
     * Dynamically call the TemplateService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->pointMemberRepository->$method(...$parameters);
    }

    /**
     * 退还订单售后部分的积分
     *
     * @param string $order_id
     */
    public function getReturnPoint($order_id)
    {
        $aftersalesRefundService = new AftersalesRefundService();
        $refundlist = $aftersalesRefundService->getList(['order_id' => $order_id, 'refund_status' => 'success']);
        $total_return_point = 0;
        if (isset($refundlist['list']) && $refundlist['list']) {
            $list = $refundlist['list'];
            foreach ($list as $key => $value) {
                $total_return_point += $value['return_point'];
            }
        }
        return $total_return_point;
    }


    /**
     * 积分到期
     */
    public function expirationPoints()
    {
        $time = time();
        $conn = app('registry')->getConnection('default');
        //获取以及到期 并且没有消耗完成的积分列表
        $sql = "select *
from point_member_multiple_integral where used_points <> income and expiration_time <> 0 and is_become_due = 1 and  expiration_time < $time;";
        $list = DB::select($sql);
        app('log')->debug('积分到期---->' .var_export($list,1));
        foreach ($list as $val) {
            try {
                $this->addPoint($val->user_id, 1, ($val->income - $val->used_points), 2, false, '积分到期', '', [], false, true);
                //更改积分到期
                $affectNum = $conn->executeUpdate("UPDATE point_member_multiple_integral SET is_become_due = 2 WHERE id= {$val->id}");
                if (!$affectNum) {
                    throw new ResourceException('积分不足');
                }
            } catch (\Exception $e) {
                app('log')->debug('积分到期扣除失败---->' . $e->getMessage());
                app('log')->debug('积分到期扣除失败---->' . var_export($val, 1));

            }
        }

    }

    public function getUsedPoints($userId): int
    {
        return $this->pointMemberLogRepository->sumPointByField([
            'user_id' =>$userId
        ], 'outcome');

    }


}
