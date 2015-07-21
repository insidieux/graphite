<?php
namespace Graphite\App;

use Graphite\ServiceManager\ServiceManager;

class ModulesManager
{
    /**
     * Путь до директории для поиска модулей
     * @var string
     */
    private $path = '';

    /**
     * @var  ServiceManager
     */
    private $serviceManager;

    /**
     * @var AbstractModule[]
     */
    private $modules;

    /**
     * @param ServiceManager $serviceManager
     * @param string         $path
     */
    public function __construct(ServiceManager $serviceManager, $path)
    {
        $this->serviceManager = $serviceManager;
        $this->path = $path;
    }

    /**
     * @return array|null
     */
    public function getModulesPath()
    {
        return $this->path;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getModulePath($name)
    {
        return $this->path . '/' . ucfirst($name);
    }

    /**
     *
     */
    private function _loadModules()
    {
        if (null === $this->modules) {
            $this->modules = array();

            foreach (new \DirectoryIterator($this->path) as $dir) { /** @var $dir \DirectoryIterator  */

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
                    $this->modules[$name] = new $class($this->serviceManager, $dir->getPathname());
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
        return $this->hasModule($name) ? $this->modules[$name] : null;
    }

    /**
     * @return AbstractModule[]
     */
    public function getModules()
    {
        return $this->_loadModules()->modules;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasModule($name)
    {
        $this->_loadModules();
        return isset($this->modules[$name]);
    }

    /**
     * Запускает инициализацию загруженных модулей
     */
    public function initModules()
    {
        $this->_loadModules();
        foreach ($this->modules as $module) {
            $module->init();
        }
    }
}
