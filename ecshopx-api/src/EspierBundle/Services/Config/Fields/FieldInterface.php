<?php

namespace EspierBundle\Services\Config\Fields;

interface FieldInterface
{
    /**
     * 將描述轉成值
     * @param string $description
     * @return string
     */
    public function toValue(string $description): string;

    /**
     * 將值轉成描述
     * @param string $value
     * @return string
     */
    public function toDescription(string $value): string;
}
