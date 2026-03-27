<?php

namespace OrdersBundle\Events;

use App\Events\Event;

/**
 * Class NormalOrdersAddEvent
 * @package OrdersBundle\Events
 *
 * 普通订单发货事件
 */
class NormalOrderDeliveryEvent extends Event
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
