<?php

namespace MembersBundle\Events;

use App\Events\Event;

class SyncWechatFansEvent extends Event
{
    public $count;

    public $openIds;

    public $companyId;

    public $authorizerAppId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->companyId = $eventData['company_id'];
        $this->authorizerAppId = $eventData['authorizer_appid'];
        $this->count = $eventData['count'];
        $this->openIds = $eventData['data']['openid'];
    }
}
