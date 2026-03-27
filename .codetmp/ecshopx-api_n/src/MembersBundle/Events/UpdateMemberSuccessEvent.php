<?php

namespace MembersBundle\Events;

use App\Events\Event;

class UpdateMemberSuccessEvent extends Event
{
    public $companyId;

    public $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->companyId = $eventData['company_id'];
        $this->userId = $eventData['user_id'];
    }
}
