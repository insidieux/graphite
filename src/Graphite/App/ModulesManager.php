<?php
namespace Graphite\App;

use Graphite\Di\Container;

class ModulesManager
{
    /**
     * Путь до директории для поиска модулей
     * @var string
     */
    private $path = '';

    /**
     * @var  Container
     */
    private $di;

    /**
     * @var AbstractModule[]
     */
    private $modules;

    /**
     * @param Container $di
     * @param string $path
     */
    public function __construct(Container $di, $path)
    {
        $this->di = $di;
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
                    $this->modules[$name] = new $class($this->di, $dir->getPathname());
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
