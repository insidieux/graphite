<?php

namespace tests\Events;

use tests\Fixtures\Events\TestException;
use tests\Fixtures\Events\TestEvent;

/**
 * Class EventTest
 * @package tests\Events
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $event = new TestEvent('event1');
        $this->assertEquals('event1', $event->getName());
        $this->assertInstanceOf('\Graphite\Std\Properties', $event->getParams());
        $this->assertEquals([], $event->getParams()->getAll());

        $event = new TestEvent('event2', ['param1' => 'value1']);
        $this->assertEquals('event2', $event->getName());
        $this->assertInstanceOf('\Graphite\Std\Properties', $event->getParams());
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    public function testAssign()
    {
        $event = new TestEvent('event1');

        $event->setName('event2');
        $this->assertEquals('event2', $event->getName());

        $event->setParams(['param1' => 'value1']);
        $this->assertInstanceOf('\Graphite\Std\Properties', $event->getParams());
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    public function testSetParamsException()
    {
        $this->expectException(TestException::class);

        $event = new TestEvent('event1');
        $event->setParams('1');
        $event->setParams(1);
        $event->setParams(null);
        $event->setParams(false);
    }

    public function testPropagation()
    {
        $event = new TestEvent('event1');
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
}
