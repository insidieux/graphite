<?php
namespace Graphite\Std;

class Properties implements \Countable
{
    private $data = array();

    /**
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Properties
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
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
            $this->data[$key] = $value;
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
        unset($this->data[$key]);
        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }
}
