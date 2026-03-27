<?php

namespace PromotionsBundle\Interfaces;

interface SmsInterface
{
    /**
     * add content 添加短信签名
     */
    public function addSmsSign($sign);

    /**
     * update content 更新短信签名
     */
    public function updateSmsSign($newContent, $oldContent);

    /**
     * send 发送短信
     */
    public function send($phones, $type = "notice");
}
