<?php

namespace OrdersBundle\Events;

use App\Events\Event;

/**
 * Class NormalOrderCancelEvent
 * @package OrdersBundle\Events
 *
 * 普通订单取消事件
 */
class NormalOrderCancelEvent extends Event
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
