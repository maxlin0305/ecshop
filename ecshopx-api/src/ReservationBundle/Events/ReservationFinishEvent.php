<?php

namespace ReservationBundle\Events;

use App\Events\Event;

class ReservationFinishEvent extends Event
{
    public $entities;

    public function __construct($eventData)
    {
        $this->entities = $eventData;
    }
}
