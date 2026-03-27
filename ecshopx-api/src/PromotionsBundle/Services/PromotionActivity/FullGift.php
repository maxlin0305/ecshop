<?php

namespace PromotionsBundle\Services\PromotionActivity;

use PromotionsBundle\Interfaces\MarketingAcivityInterface;
use PromotionsBundle\Entities\MarketingGiftItems;
use GoodsBundle\Services\ItemsService;

class FullGift implements MarketingAcivityInterface
{
    private $totalDiscountFee = 0;
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        $rulestr = '';
        switch ($filterType) {
        case "quantity":
            foreach ($rulesArr as $value) {
                $rulestr .= '';
                $rulestr .= '购买满'.$value['full'].'件，送赠品;';
            }
            break;
        case "totalfee":
            foreach ($rulesArr as $value) {
                $rulestr .= '消费满'.$value['full'].'元，送赠品;';
            }
            break;
        default:
            $rulestr = '';
            break;
        }
        return $rulestr;
    }

    /**
     * @brief 应用满X件(Y折/Y元)
     *
     * @param $params
     *
     * @return array
     */
    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee)
    {
        $rules = $activity['condition_value'];
        foreach ($rules as $k => $rule) {
            $ruleArray['full'][$k] = $rule['full'];
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';
        $activityId = $activity['marketing_id'];
        $companyId = $activity['company_id'];

        if ($totalNum >= $ruleArray['full'][$ruleLength - 1]) {
            if(!$activity['in_proportion']) {
                $giftItem = $this->getGiftItem($companyId, $activityId);
            } else {
                $multiple = intval(bcdiv($totalNum, $ruleArray['full'][$ruleLength - 1]));
                // if ($multiple > $activity['join_limit']) {
                //     $multiple = $activity['join_limit'];
                // }
                $giftItem = $this->getGiftItem($companyId, $activityId);
                foreach ($giftItem as &$value) {
                    $value['gift_num'] = $value['gift_num'] * $multiple;
                }
            }
            $discountDesc = "消费满".$ruleArray['full'][$ruleLength - 1]."件，送赠品：";
            foreach ($giftItem as $gift) {
                $discountDesc .= $gift['item_name']." x ".$gift['gift_num']."；";
            }
        } elseif ($totalNum < $ruleArray['full'][0]) {
            $giftItem = [];
        } else {
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalNum >= $ruleArray['full'][$i] && $totalNum < $ruleArray['full'][$i + 1]) {
                    $giftItem = $this->getGiftItem($companyId, $activityId);
                    $discountDesc = "消费满".$ruleArray['full'][$i]."件，送赠品";
                    break;
                }
            }
        }
        if (!$giftItem) {
            $activityId = 0;
        }
        $result['discount_desc'] = [
            'type' => 'full_gift',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $this->totalDiscountFee,
            'max_limit' => $activity['join_limit'] == 0 ? PHP_INT_MAX : $activity['join_limit'],
        ];
        $result['activity_id'] = $activityId;
        $result['gifts'] = $giftItem;
        $result['discount_fee'] = $this->totalDiscountFee;
        return $result;
    }

    /**
     * @brief  应用满X元(Y折/Y元)
     *
     * @param $params
     *
     * @return
     */
    public function applyActivityTotalfee(array $activity, int $totalFee)
    {
        $rules = $activity['condition_value'];
        foreach ($rules as $k => $rule) {
            $ruleArray['full'][$k] = bcmul($rule['full'], 100);
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';
        $activityId = $activity['marketing_id'];
        $companyId = $activity['company_id'];

        if ($totalFee >= $ruleArray['full'][$ruleLength - 1]) {
            if(!$activity['in_proportion']) {
                $giftItem = $this->getGiftItem($companyId, $activityId);
            } else {
                $multiple = intval(bcdiv($totalFee, $ruleArray['full'][$ruleLength - 1]));
                // if ($multiple > $activity['join_limit']) {
                //     $multiple = $activity['join_limit'];
                // }
                $giftItem = $this->getGiftItem($companyId, $activityId);
                foreach ($giftItem as &$value) {
                    $value['gift_num'] = $value['gift_num'] * $multiple;
                }
            }
            $discountDesc = "消费满".($ruleArray['full'][$ruleLength - 1] / 100)."元，送赠品：";
            foreach ($giftItem as $gift) {
                $discountDesc .= $gift['item_name']." x ".$gift['gift_num']."；";
            }
        } elseif ($totalFee < $ruleArray['full'][0]) {
            $giftItem = [];
        } else {
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalFee >= $ruleArray['full'][$i] && $totalFee < $ruleArray['full'][$i + 1]) {
                    $giftItem = $this->getGiftItem($companyId, $activityId);
                    $discountDesc = "消费满".($ruleArray['full'][$i] / 100)."元，送赠品";
                    break;
                }
            }
        }
        if (!$giftItem) {
            $activityId = 0;
        }

        $result['discount_desc'] = [
            'type' => 'full_gift',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $this->totalDiscountFee,
            'max_limit' => $activity['join_limit'] == 0 ? PHP_INT_MAX : $activity['join_limit'],
        ];
        $result['activity_id'] = $activityId;
        $result['gifts'] = $giftItem;
        $result['discount_fee'] = $this->totalDiscountFee;
        return $result;
    }

    private function getGiftItem($companyId, $activityId)
    {
        $filter = ['company_id' => $companyId, 'marketing_id' => $activityId];
        $entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
        $giftLists = $entityGiftRelRepository->lists($filter)['list'];

        $itemIds = array_column($giftLists, 'item_id');
        $itemService = new ItemsService();
        $itemFilter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($itemFilter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        foreach ($giftLists as $key => &$value) {
            if (!isset($itemdata[$value['item_id']])) {
                unset($giftLists[$key]);
                continue;
            }
            $value = array_merge($value, $itemdata[$value['item_id']]);
            $this->totalDiscountFee += $value['price'];
        }

        return $giftLists;
    }
}
