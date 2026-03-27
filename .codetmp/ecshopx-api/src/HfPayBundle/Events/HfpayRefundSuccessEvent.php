<?php

namespace HfPayBundle\Events;

use App\Events\Event;

class HfpayRefundSuccessEvent extends Event
{
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->entities = $eventData;
    }
}
