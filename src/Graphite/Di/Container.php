<?php
namespace Graphite\Di;

class Container
{
    const TYPE_SINGLETON = 'singleton';
    const TYPE_FACTORY   = 'factory';

    /**
     * @var array
     */
    protected $_services = [];

    /**
     * Register service factory. $factory can be:
     * - object   - uses as service itself
     * - string   - use as class name for create a service
     * - \Closure - use as factory function; result uses as service
     *
     * @param string                 $name
     * @param \Closure|Object|string $factory
     * @param string                 $type
     *
     * @return Container
     * @throws \Exception
     */
    public function set($name, $factory, $type = self::TYPE_SINGLETON)
    {
        // Check name
        if (!is_string($name)) {
            throw new \Exception('Service name can only be a string! "'.gettype($name).'" given');
        }

        // check factory
        if (!($factory instanceof \Closure) && !is_object($factory) && !is_string($factory)) {
            throw new \Exception('Service factory can only be a \Closure | Object | string! "'.gettype($factory).'" given');
        }

        // check type
        if ($type != self::TYPE_SINGLETON && $type != self::TYPE_FACTORY) {
            throw new \Exception('Unknown service type "'.$type.'"!');
        }

        if (is_object($factory) && !($factory instanceof \Closure)) {
            $this->_services[$name] = [
                'instance' => $factory,
                'factory'  => null,
                'type'     => self::TYPE_SINGLETON
            ];
        } else {
            $this->_services[$name] = [
                'instance' => null,
                'factory'  => $factory,
                'type'     => $type
            ];
        }

        return $this;
    }

    /**
     * @param string                 $name
     * @param \Closure|Object|string $factory
     *
     * @return Container
     */
    public function setSingleton($name, $factory)
    {
        return $this->set($name, $factory, self::TYPE_SINGLETON);
    }

    /**
     * Mass register services as singleton
     *
     * @param array $config array (serviceName => factory)
     * @return Container
     * @throws \Exception
     */
    public function mSetSingleton(array $config)
    {
        if (empty($config)){
            throw new \Exception("Empty config array called");
        }
        foreach ($config as $name => $factory){
            $this->setSingleton($name, $factory);
        }
        return $this;
    }

    /**
     * @param string                 $name
     * @param \Closure|Object|string $factory
     *
     * @return Container
     */
    public function setFactory($name, $factory)
    {
        return $this->set($name, $factory, self::TYPE_FACTORY);
    }

    /**
     * Mass register services as factory
     *
     * @param array $config array (serviceName => factory)
     * @return Container
     * @throws \Exception
     */
    public function mSetFactory(array $config)
    {
        if (empty($config)){
            throw new \Exception("Empty config array called");
        }
        foreach ($config as $name => $factory){
            $this->setFactory($name, $factory);
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws \Exception
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \Exception('Service with name "'.$name.'" not registered');
        }

        $instance = $this->_services[$name]['instance'];

        // resolve instance
        if ($instance === null) {

            $factory = $this->_services[$name]['factory'];

            if (is_string($factory)) {

                if (!class_exists($factory)) {
                    throw new \Exception('Cant create service "'.$name.'". Class "'.$factory.'" not found');
                }

                $instance = new $factory;
                if ($instance instanceof Provider) {
                    $instance = $instance->get($this);
                }

            } elseif ($factory instanceof \Closure) {
                $instance = $factory($this);
            } elseif (is_object($factory)) {
                $instance = $factory;
            }

            // check instance
            if (!is_object($instance)) {
                throw new \Exception('Create "'.$name.'" fails! Factory result must be an object, "'.gettype($instance).'"');
            }

            if ($this->_services[$name]['type'] == self::TYPE_SINGLETON) {
                $this->_services[$name]['instance'] = $instance;
            }
        }

        return $instance;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_services[$name]);
    }

    /**
     * @return array
     */
    public function getRegisteredServices()
    {
        return array_keys($this->_services);
    }

    /**
     * @param $name
     * @return array
     * @throws \Exception
     */
    public function getServiceInfo($name)
    {
        if (!$this->has($name)) {
            throw new \Exception('Service with name "'.$name.'" not registered');
        }

        return $this->_services[$name];
    }
}
