<?php

namespace tests\Fixtures\Events;

use Graphite\Events\Event;

/**
 * Class TestListener
 * @package tests\Fixtures\Events
 */
class TestListener
{
    /**
     * @param Event $event
     * @throws \Graphite\Events\Exception
     */
    public static function testCallback(Event $event)
    {
        $event->setParams(['stop' => false]);
    }

    /**
     * @param Event $event
     * @throws \Graphite\Events\Exception
     */
    public static function testCallbackWithStop(Event $event)
    {
        $event->setParams(['stop' => true]);
        $event->stopPropagation();
    }
}
