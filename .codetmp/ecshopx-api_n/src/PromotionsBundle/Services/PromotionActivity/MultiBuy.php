<?php

namespace PromotionsBundle\Services\PromotionActivity;

use PromotionsBundle\Interfaces\MarketingAcivityInterface;

class MultiBuy implements MarketingAcivityInterface
{
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        $rulestr = '';
        switch ($filterType) {
        case "quantity":
            foreach ($rulesArr as $value) {
                $rulestr .= '';
                $rulestr .= '購買滿'.$value['min'].'~'.$value['max'].'件，每件'.$value['act_price']."元;";
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
        * @return integer
     */
    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee)
    {
        $rules = $activity['condition_value'];
        $discountPrice = 0;
        $discountDesc = '';
        foreach ($rules as $k => $rule) {
            if ($totalNum >= $rule['min'] && $totalNum<=$rule['max']) {
                $newTotalFee = $rule['act_price'] * 100 * $totalNum;
                $discountPrice = bcsub($totalFee, $newTotalFee);
                $discountDesc = "購買數量在".$rule['min']."~".$rule['max']."件，按每件".$rule['act_price']."元";
                break;
            }
        }
        if ($discountPrice < 0 || $totalFee < $discountPrice) {
            $discountPrice = 0;
        }

        $discountInfo = [
            'type' => 'multi_buy',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];
        $result['discount_fee'] = $discountPrice;
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];
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
        $discountPrice = 0;
        $discountDesc = '';
        foreach ($rules as $k => $rule) {
            if ($totalFee >= $rule['min'] && $totalFee<=$rule['max']) {
                $discountPrice = bcsub($totalFee, $rule['act_price']*100);
                $discountDesc = "消費滿".$rule['min']."~".$rule['max']."元，只需要".$rule['act_price']."元";
                break;
            }
        }

        if ($discountPrice < 0) {
            $discountPrice = 0;
        }
        $discountInfo = [
            'type' => 'full_discount',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];

        $result['discount_fee'] = $discountPrice;
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];
        return $result;
    }
}
