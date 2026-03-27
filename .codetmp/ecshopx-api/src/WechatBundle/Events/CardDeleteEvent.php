<?php

namespace WechatBundle\Events;

use App\Events\Event;

class CardDeleteEvent extends Event
{
    public $cardId;
    public $openId;
    public $userCardCode;
    public $authorizerAppId;
    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->companyId = $receiveData['company_id'];
        $this->openId = $receiveData['openId'];
        $this->cardId = $receiveData['cardId'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
    }
}
