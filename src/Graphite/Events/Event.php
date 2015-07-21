<?php
namespace Graphite\Events;

use Graphite\Std;

class Event
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var mixed
     */
    protected $_source;

    /**
     * @var \Graphite\Std\Properties
     */
    protected $_params;

    /**
     * @var bool
     */
    protected $_propagation = true;

    /**
     * @param string $name
     * @param mixed  $source
     * @param array  $params
     */
    public function __construct($name, $source = null, $params = array())
    {
        $this->setName($name);
        $this->setSource($source);
        $this->setParams($params);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     * @throws \Graphite\Std\Exception
     */
    public function setName($name)
    {
        if (empty($name) || !is_string($name)) {
            throw new Std\Exception('Event name must be a non empty string');
        }

        $this->_name = $name;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param \Graphite\Std\Properties|array $params
     * @throws \Graphite\Std\Exception
     */
    public function setParams($params)
    {
        if ($params instanceof Std\Properties) {
            $this->_params = $params;
        } elseif (is_array($params)) {
            $this->_params = new Std\Properties($params);
        } else {
            throw new Std\Exception(sprintf('Event $params must be an array or Std\Properties! "%s" given', gettype($params)));
        }
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->_source = $source;
    }

    /**
     *
     */
    public function stopPropagation()
    {
        $this->_propagation = false;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped()
    {
        return $this->_propagation === false;
    }
}