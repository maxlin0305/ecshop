<?php

namespace KaquanBundle\Events;

use App\Events\Event;

class CouponAddEvent extends Event
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
