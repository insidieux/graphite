<?php

namespace Graphite\Events;

use Graphite\Std;

/**
 * Class Event
 * @package Graphite\Events
 */
class Event
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Graphite\Std\Properties
     */
    protected $params;

    /**
     * @var bool
     */
    protected $propagation = true;

    /**
     * @param string $name Event name, used for binding listeners
     * @param array  $params array of custom parameters, used for processing event
     */
    public function __construct($name, array $params = [])
    {
        $this->setName($name);
        $this->setParams($params);
    }

    /**
     * Return event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set event name
     *
     * @param string $name
     *
     * @throws Exception
     */
    public function setName($name)
    {
        if (empty($name) || !is_string($name)) {
            throw new Exception('Event name must be a non empty string');
        }

        $this->name = $name;
    }

    /**
     * Get event params like properties object
     *
     * @return \Graphite\Std\Properties
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set custom event params
     *
     * @param \Graphite\Std\Properties|array $params
     *
     * @throws Exception
     */
    public function setParams($params)
    {
        if ($params instanceof Std\Properties) {
            $this->params = $params;
        } elseif (is_array($params)) {
            $this->params = new Std\Properties($params);
        } else {
            throw new Exception(sprintf('Event $params must be an array or Std\Properties! "%s" given', gettype($params)));
        }
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation()
    {
        $this->propagation = false;
    }

    /**
     * Check event propagation. If true - stop processing event, and don't pass to listeners
     *
     * @return bool
     */
    public function isPropagationStopped()
    {
        return $this->propagation === false;
    }
}
