<?php
namespace Graphite\Events;

use Graphite\Std;

class EventsManager
{
    /**
     * Признак того, что массив подписчиков бы отсортирован по приоритету
     * @var bool
     */
    private $_sorted = false;

    /**
     * @var array
     */
    private $_listeners = array();

    /**
     * @param string $eventName
     *
     * @return array
     */
    public function getListeners($eventName = null)
    {
        if ($eventName === null) {
            return $this->_listeners;
        } else {
            return isset($this->_listeners[$eventName]) ? $this->_listeners[$eventName] : array();
        }
    }

    /**
     * @param string   $eventName
     * @param callable $callback
     * @param int      $priority
     *
     * @return EventsManager
     *
     * @throws \Graphite\Std\Exception
     */
    public function on($eventName, $callback, $priority = 1)
    {
        if (!is_string($eventName) || empty($eventName)) {
            throw new Std\Exception(sprintf('Event name must be a string! "%s" given.', gettype($eventName)));
        }

        if (!is_callable($callback)) {
            throw new Std\Exception(sprintf('Callback must be a valid callable! "%s" given.', gettype($callback)));
        }

        $this->_listeners[$eventName][$priority][] = $callback;
        $this->_sorted = false;

        return $this;
    }

    /**
     * Вызов события. Отработают все подписчики
     *
     * @param string $name
     * @param mixed  $sender
     * @param array  $params
     *
     * @return Event
     */
    public function trigger($name, $sender = null, $params = array())
    {
        $e = new Event($name, $sender, $params);

        if (!isset($this->_listeners[$name])) {
            return $e;
        }

        // sort events listeners by priority
        if (!$this->_sorted) {
            foreach ($this->_listeners as $eName => $events) {
                krsort($this->_listeners[$eName]);
            }
            $this->_sorted = true;
        }

        // run event listeners
        foreach ($this->_listeners[$name] as $listeners) {
            foreach ($listeners as $listener) {
                call_user_func($listener, $e);
                if ($e->isPropagationStopped()) {
                    return $e;
                }
            }
        }

        return $e;
    }
}
