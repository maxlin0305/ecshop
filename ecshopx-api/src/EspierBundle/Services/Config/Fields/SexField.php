<?php

namespace EspierBundle\Services\Config\Fields;

class SexField implements FieldInterface
{
    /**
     * 將描述轉成值
     * @param string $description
     * @return string
     */
    public function toValue(string $description): string
    {
        $value = "0";
        // 參數轉換
        switch ($description) {
            case "男":
            case "男性":
                $value = "1";
                break;
            case "女":
            case "女性":
                $value = "2";
                break;
        }
        return $value;
    }

    /**
     * 將值轉成描述
     * @param string $value
     * @return string
     */
    public function toDescription(string $value): string
    {
        $description = "未知";
        switch ($value) {
            case "1":
                $description = "男";
                break;
            case "2":
                $description = "女";
                break;
        }
        return $description;
    }
}
