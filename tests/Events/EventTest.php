<?php

namespace tests\Events;

use Graphite\Events\Event;

/**
 * Class EventTest
 * @package tests\Events
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $event = new Event('event1');
        $this->assertEquals('event1', $event->getName());
        $this->assertEquals([], $event->getParams()->getAll());

        $event = new Event('event2', ['param1' => 'value1']);
        $this->assertEquals('event2', $event->getName());
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    public function testAssign()
    {
        $event = new Event('event1');

        $event->setName('event2');
        $this->assertEquals('event2', $event->getName());

        $event->setParams(['param1' => 'value1']);
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    public function testPropagation()
    {
        $event = new Event('event1');
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
}
