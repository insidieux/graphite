<?php

namespace Graphite\Events;

/**
 * Class SubscriberInterface
 * @package Graphite\Events
 */
interface SubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to
     * Array keys - event names
     * Array values
     *  - string name of public method in implementation class
     *  - array with event name (watch previous value) and priority
     *
     * Example:
     *  return [
     *      'event1' => 'onEvent1',
     *      'event2' => ['onEvent2', 10]
     * ]
     *
     * @return array
     */
    public function getSubscribedEvents();
}
