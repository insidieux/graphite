<?php

namespace tests\Fixtures\Events;

use Graphite\Events\Event;
use Graphite\Events\SubscriberInterface;

/**
 * Class TestSubscriber
 * @package Fixtures\Events
 */
class TestSubscriber implements SubscriberInterface
{
    /**
     * @var array
     */
    protected $events;

    /**
     * TestSubscriber constructor.
     * @param array $events
     */
    public function __construct(array $events)
    {
        $this->events = $events;
    }

    /**
     * @param Event $event
     * @return Event
     */
    public function onEvent1(Event $event)
    {
        return $event;
    }

    /**
     * @param Event $event
     * @return Event
     */
    public function onEvent2(Event $event)
    {
        return $event;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return $this->events;
    }
}
