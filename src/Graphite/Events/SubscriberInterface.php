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
     * ```
     * [ 'event1' => 'onEvent1']            -> listen event1, call method $this->onEvent1()
     * [ 'event2' => ['onEvent2', 10] ]     -> listen event2 with priority 10, call method $this->onEvent2()
     * ```
     *
     * @return array
     */
    public function getSubscribedEvents();
}
