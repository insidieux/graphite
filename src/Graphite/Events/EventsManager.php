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
     * Признак того, что массив подписчиков бы отсортирован по приоритету
     * @var bool
     */
    private $sorted = false;

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @param string $eventName
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
     * Sort listeners by priority
     */
    private function sortListeners()
    {
        if (false === $this->sorted) {
            foreach ($this->listeners as $name => $events) {
                krsort($this->listeners[$name]);
            }
            $this->sorted = true;
        }
    }

    /**
     * @param string $eventName
     * @param callable $callback
     * @param int $priority
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
            $isString = is_array($options);
            if (!is_array($options) && !$isString) {
                throw new Exception(sprintf('Event subscribe options must be a string or array! "%s" given.', gettype($options)));
            }
            if ($isString) {
                $options = [$options, self::DEFAULT_PRIORITY];
            }
            list($callback, $priority) = $options;
            $this->on($eventName, [$subscriber, $callback], $priority);
        }
        return $this;
    }

    /**
     * Вызов события. Отработают все подписчики
     *
     * @param string|Event $event
     * @param array $params
     *
     * @return Event
     */
    public function trigger($event, $params = [])
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
        foreach ($listeners as $listener) {
            call_user_func($listener, $event);
            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }
}
