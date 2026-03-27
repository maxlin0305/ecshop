<?php

namespace WechatBundle\Events;

use App\Events\Event;

class UserPayFromPayCellEvent extends Event
{
    public $openId;
    public $cardId;
    public $userCardCode;
    public $authorizerAppId;
    public $transId;
    public $LocationId;
    public $fee;
    public $originalFee;
    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->openId = $receiveData['openId'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->cardId = $receiveData['cardId'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->transId = $receiveData['transId'];
        $this->LocationId = $receiveData['LocationId'];
        $this->fee = $receiveData['fee'];
        $this->originalFee = $receiveData['originalFee'];
        $this->companyId = $receiveData['company_id'];
    }
}
