<?php
namespace Graphite\App;

use Graphite\ServiceManager\ServiceManager;

class ModulesManager
{
    /**
     * Путь до директории для поиска модулей
     * @var string
     */
    private $_path = '';

    /**
     * @var  ServiceManager
     */
    private $_serviceManager;

    /**
     * @var AbstractModule[]
     */
    private $_modules;

    /**
     * @param ServiceManager $serviceManager
     * @param string         $path
     */
    public function __construct(ServiceManager $serviceManager, $path)
    {
        $this->_serviceManager = $serviceManager;
        $this->_path = $path;
    }

    /**
     * @return array|null
     */
    public function getModulesPath()
    {
        return $this->_path;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getModulePath($name)
    {
        return $this->_path . '/' . ucfirst($name);
    }

    /**
     *
     */
    private function _loadModules()
    {
        if (null === $this->_modules) {
            $this->_modules = array();

            foreach (new \DirectoryIterator($this->_path) as $dir) { /** @var $dir \DirectoryIterator  */

                if ($dir->isDot()) {
                    continue;
                }

                $name  = $dir->getBasename();
                $file  = $dir->getPathname() . '/Module.php';
                $class = 'Modules\\'.$name.'\\Module';

                if (!file_exists($file)) {
                    continue;
                }

                require_once $file;

                if (class_exists($class) && is_subclass_of($class, 'Graphite\App\AbstractModule')) {
                    $this->_modules[$name] = new $class($this->_serviceManager, $dir->getPathname());
                }
            }
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return AbstractModule|null
     */
    public function getModule($name)
    {
        return $this->hasModule($name) ? $this->_modules[$name] : null;
    }

    /**
     * @return AbstractModule[]
     */
    public function getModules()
    {
        return $this->_loadModules()->_modules;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasModule($name)
    {
        $this->_loadModules();
        return isset($this->_modules[$name]);
    }

    /**
     * Запускает инициализацию загруженных модулей
     */
    public function initModules()
    {
        $this->_loadModules();
        foreach ($this->_modules as $module) {
            $module->init();
        }
    }
}
