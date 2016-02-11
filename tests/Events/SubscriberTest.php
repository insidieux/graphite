<?php

namespace tests\Events;

use tests\Fixtures\Events\TestSubscriber;

/**
 * Class SubscriberTest
 * @package tests\Events
 */
class SubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testGetSubscribedEventsEmpty()
    {
        $subscriber = new TestSubscriber([]);
        $this->assertEmpty($subscriber->getSubscribedEvents());
    }

    /**
     *
     */
    public function testGetSubscribedEventsNotEmpty()
    {
        $subscriber = new TestSubscriber(['test-event-1' => 'onEvent1']);
        $this->assertNotEmpty($subscriber->getSubscribedEvents());
    }

    /**
     *
     */
    public function testGetSubscribedEventsWithoutPriority()
    {
        $subscriber = new TestSubscriber(['test-event-1' => 'onEvent1']);
        $this->assertEquals(['test-event-1' => 'onEvent1'], $subscriber->getSubscribedEvents());
    }

    /**
     *
     */
    public function testGetSubscribedEventsWithPriority()
    {
        $subscriber = new TestSubscriber(['test-event-2' => ['onEvent2', 10]]);
        $this->assertEquals(['test-event-2' => ['onEvent2', 10]], $subscriber->getSubscribedEvents());
    }
}
