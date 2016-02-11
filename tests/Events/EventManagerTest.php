<?php

namespace tests\Events;

use Graphite\Events\Event;
use Graphite\Events\EventsManager;
use tests\Fixtures\Events\TestListener;
use tests\Fixtures\Events\TestSubscriber;

/**
 * Class EventManagerTest
 * @package tests\Events
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventsManager
     */
    protected $eventManager;

    /**
     *
     */
    protected function setUp()
    {
        $this->eventManager = new EventsManager();
    }

    /**
     *
     */
    public function testOnReturn()
    {
        $this->assertInstanceOf(EventsManager::class, $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']));
    }

    /**
     * @expectedException \Graphite\Events\Exception
     */
    public function testOnEmptyNameFailure()
    {
        $this->eventManager->on('', [TestListener::class, 'testCallback']);
    }

    /**
     * @expectedException \Graphite\Events\Exception
     */
    public function testOnNotStringNameFailure()
    {
        $this->eventManager->on(1, [TestListener::class, 'testCallback']);
    }

    /**
     * @expectedException \Graphite\Events\Exception
     */
    public function testOnCallableFailure()
    {
        $this->eventManager->on('test-event-1', '');
    }

    /**
     *
     */
    public function testGetListenersEmpty()
    {
        $this->assertEmpty($this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testGetListenersNotEmpty()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertNotEmpty($this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testGetListenersEqualStructure()
    {
        $structure = [
            'test-event-1' => [
                EventsManager::DEFAULT_PRIORITY => [
                    [TestListener::class, 'testCallback']
                ]
            ]
        ];
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertEquals($structure, $this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testGetListenersSearchEmpty()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertEmpty($this->eventManager->getListeners('test-event-2'));
    }

    /**
     *
     */
    public function testGetListenersSearchNotEmpty()
    {
        $structure = [
            EventsManager::DEFAULT_PRIORITY => [
                [TestListener::class, 'testCallback']
            ]
        ];
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertNotEmpty($this->eventManager->getListeners('test-event-1'));
        $this->assertEquals($structure, $this->eventManager->getListeners('test-event-1'));
    }

    /**
     *
     */
    public function testGetListenersSearchEqualStructure()
    {
        $structure = [
            EventsManager::DEFAULT_PRIORITY => [
                [TestListener::class, 'testCallback']
            ]
        ];
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertEquals($structure, $this->eventManager->getListeners('test-event-1'));
    }

    /**
     *
     */
    public function testGetListenerSorted()
    {
        $structure = [
            'test-event-2' => [
                100 => [
                    [TestListener::class, 'testCallback']
                ]
            ],
            'test-event-1' => [
                50 => [
                    [TestListener::class, 'testCallback']
                ]
            ]
        ];

        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback'], 50);
        $this->eventManager->on('test-event-2', [TestListener::class, 'testCallback'], 100);
        $this->assertEquals($structure, $this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testRemoveListenersReturn()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback']);
        $this->assertInstanceOf(EventsManager::class, $this->eventManager->removeListeners());
    }

    /**
     *
     */
    public function testRemoveListenersEmpty()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback'])
            ->removeListeners();
        $this->assertEmpty($this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testRemoveListenersNotExists()
    {
        $structure = [
            EventsManager::DEFAULT_PRIORITY => [
                [TestListener::class, 'testCallback']
            ]
        ];
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback'])
            ->removeListeners('test-event-2');
        $this->assertNotEmpty($this->eventManager->getListeners());
        $this->assertNotEmpty($this->eventManager->getListeners('test-event-1'));
        $this->assertEquals($structure, $this->eventManager->getListeners('test-event-1'));
    }

    /**
     *
     */
    public function testRemoveListenersExists()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback'])
            ->removeListeners('test-event-1');
        $this->assertEmpty($this->eventManager->getListeners());
        $this->assertEmpty($this->eventManager->getListeners('test-event-1'));
    }

    /**
     *
     */
    public function testAddSubscriberReturn()
    {
        $subscriber = new TestSubscriber(['test-event-1' => 'onEvent1']);
        $this->assertInstanceOf(EventsManager::class, $this->eventManager->addSubscriber($subscriber));
    }

    /**
     * @expectedException \Graphite\Events\Exception
     */
    public function testAddSubscriberFailure()
    {
        $this->eventManager->addSubscriber(new TestSubscriber(['test-event-1' => false]));
    }

    /**
     *
     */
    public function testAddSubscriberEmpty()
    {
        $this->eventManager->addSubscriber(new TestSubscriber([]));
        $this->assertEmpty($this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testAddSubscriberNotEmpty()
    {
        $this->eventManager->addSubscriber(new TestSubscriber(['test-event-1' => 'onEvent1']));
        $this->assertNotEmpty($this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testAddSubscriberWithoutPriority()
    {
        $subscriber = new TestSubscriber(['test-event-1' => 'onEvent1']);
        $this->eventManager->addSubscriber($subscriber);
        $structure = [
            'test-event-1' => [
                EventsManager::DEFAULT_PRIORITY => [
                    [$subscriber, 'onEvent1']
                ]
            ]
        ];
        $this->assertEquals($structure, $this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testAddSubscriberWithPriority()
    {
        $subscriber = new TestSubscriber(['test-event-1' => ['onEvent1', 100]]);
        $this->eventManager->addSubscriber($subscriber);
        $structure = [
            'test-event-1' => [
                100 => [
                    [$subscriber, 'onEvent1']
                ]
            ]
        ];
        $this->assertEquals($structure, $this->eventManager->getListeners());
    }

    /**
     *
     */
    public function testTriggerReturn()
    {
        $this->assertInstanceOf(Event::class, $this->eventManager->trigger('test-event-1', []));
    }

    /**
     *
     */
    public function testTriggerString()
    {
        $event = $this->eventManager->trigger('test-event-1', []);
        $this->assertEquals('test-event-1', $event->getName());
        $this->assertEquals([], $event->getParams()->getAll());
    }

    /**
     *
     */
    public function testTriggerNotEmptyParams()
    {
        $event = $this->eventManager->trigger('test-event-1', ['param1' => 'value1']);
        $this->assertEquals('test-event-1', $event->getName());
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    /**
     *
     */
    public function testTriggerEventObject()
    {
        $event = $this->eventManager->trigger(new Event('test-event-1', ['param1' => 'value1']));
        $this->assertEquals('test-event-1', $event->getName());
        $this->assertEquals(['param1' => 'value1'], $event->getParams()->getAll());
    }

    /**
     *
     */
    public function testTriggerWithStop()
    {
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallbackWithStop'], 100);
        $this->eventManager->on('test-event-1', [TestListener::class, 'testCallback'], 50);
        $event = $this->eventManager->trigger(new Event('test-event-1', []));
        $this->assertTrue($event->getParams()->get('stop'));
    }
}
