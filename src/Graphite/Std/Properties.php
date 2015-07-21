<?php
namespace Graphite\Std;

class Properties implements \Countable
{
    private $_data = array();

    /**
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->_data = $data;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->_data;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Properties
     */
    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    /**
     * @param array $props
     *
     * @return Properties
     */
    public function setMany($props)
    {
        foreach ($props as $key => $value) {
            $this->_data[$key] = $value;
        }
        return $this;
    }

    /**
     * @param string $key
     *
     * @return Properties
     */
    public function remove($key)
    {
        unset($this->_data[$key]);
        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_data);
    }
}
