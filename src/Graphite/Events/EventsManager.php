<?php

namespace Graphite\Events;

use Graphite\Std;

/**
 * Class EventsManager
 * @package Graphite\Events
 */
class EventsManager
{
    const DEFAULT_PRIORITY = 1;

    /**
     * Listeners array sorted by priority
     *
     * @var bool
     */
    private $sorted = false;

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @param string $eventName event name filter listeners
     *
     * @return array
     */
    public function getListeners($eventName = null)
    {
        $this->sortListeners();
        if ($eventName === null) {
            return $this->listeners;
        } else {
            return isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];
        }
    }

    /**
     * @param string $eventName event name filter listeners
     *
     * @return EventsManager
     */
    public function removeListeners($eventName = null)
    {
        if ($eventName === null) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventName]);
        }
        return $this;
    }

    /**
     * Sort listeners by priority
     */
    private function sortListeners()
    {
        if (!$this->sorted) {
            foreach ($this->listeners as $name => $events) {
                krsort($this->listeners[$name]);
            }
            $this->sorted = true;
        }
    }

    /**
     * @param string   $eventName event name for listen
     * @param callable $callback  function called for event
     * @param int      $priority  priority call
     *
     * @return EventsManager
     *
     * @throws Exception
     */
    public function on($eventName, $callback, $priority = self::DEFAULT_PRIORITY)
    {
        if (!is_string($eventName) || empty($eventName)) {
            throw new Exception(sprintf('Event name must be a string! "%s" given.', gettype($eventName)));
        }

        if (!is_callable($callback)) {
            throw new Exception(sprintf('Callback must be a valid callable! "%s" given.', gettype($callback)));
        }

        $this->listeners[$eventName][$priority][] = $callback;
        $this->sorted = false;

        return $this;
    }

    /**
     * @param SubscriberInterface $subscriber
     *
     * @return EventsManager
     *
     * @throws Exception
     */
    public function addSubscriber(SubscriberInterface $subscriber)
    {
        foreach ((array)$subscriber->getSubscribedEvents() as $eventName => $options) {
            $isString = is_string($options);
            if (!is_array($options) && !$isString) {
                throw new Exception(sprintf('Event subscribe options must be a string or array! "%s" given.', gettype($options)));
            }
            if ($isString) {
                $options = [$options];
            }
            if (!isset($options[1])){
                $options[1] = self::DEFAULT_PRIORITY;
            }
            list($callback, $priority) = $options;
            $this->on($eventName, [$subscriber, $callback], $priority);
        }
        return $this;
    }

    /**
     * Trigger event with custom params
     * Can be called by passing event model
     *
     * @param string|Event $event
     * @param array        $params
     *
     * @return Event
     */
    public function trigger($event, array $params = [])
    {
        if (!($event instanceof Event)) {
            $event = new Event($event, $params);
        }
        if ($listeners = $this->getListeners($event->getName())) {
            $this->dispatch($listeners, $event);
        }
        return $event;
    }

    /**
     * Dispatching listeners for current event
     *
     * @param array $listeners
     * @param Event $event
     */
    protected function dispatch(array $listeners, Event $event)
    {
        foreach ($listeners as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func($callback, $event);
                if ($event->isPropagationStopped()) {
                    break(2);
                }
            }
        }
    }
}
