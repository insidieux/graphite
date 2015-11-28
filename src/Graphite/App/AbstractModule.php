<?php
namespace Graphite\App;

use Graphite\Events;
use Graphite\Http;
use Graphite\Di\Container;

abstract class AbstractModule
{
    /**
     * Человеко-понятное название модуля
     * @var string
     */
    protected $title = '';

    /**
     * Путь до директории модуля
     * @var string
     */
    protected $basePath  = '';

    /**
     * @var Container
     */
    private $di;

    /**
     * @var ModulesManager
     */
    private $modulesManager;

    /**
     * @var Events\EventsManager;
     */
    private $eventsManager;

    /**
     * @var Http\Request;
     */
    private $request;

    /**
     * @param Container $di
     * @param string    $path
     *
     * @throws \Graphite\Di\Exception
     */
    public function __construct(Container $di, $path = '')
    {
        $this->di = $di;
        $this->modulesManager = $di->get('ModulesManager');
        $this->eventsManager  = $di->get('EventsManager');
        $this->request        = $di->get('Request');

        $this->setBasePath($path);
    }

    /**
     * @param string $path
     *
     * @return AbstractModule
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->basePath . '/config';
    }

    /**
     * @return string
     */
    public function getControllersPath()
    {
        return $this->basePath . '/controller';
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->basePath . '/view';
    }

    /**
     * @param bool $relative
     *
     * @return string
     */
    public function getPublicPath($relative = false)
    {
        $basePath = str_replace('\\', '/', $this->basePath);

        if ($relative) {
            $basePath = str_replace($this->getRequest()->getServer()->get('DOCUMENT_ROOT', ''), '', $basePath);
        }

        return $basePath . '/public';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return null|ModulesManager
     */
    public function getModulesManager()
    {
        return $this->modulesManager;
    }

    /**
     * @return Container
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * @return null|Events\EventsManager
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }

    /**
     * @return null|Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $fileName
     *
     * @return array
     */
    private function _loadConfig($fileName)
    {
        $file = $this->getConfigPath() . "/$fileName.php";
        $config = [];
        
        if (file_exists($file)) {
            $cfg = include $file;
            if (is_array($cfg)) {
                $config = $cfg;
            }
        }

        return $config;
    }

    /**
     * @return array
     */
    public function getNavigation()
    {
        return $this->_loadConfig('navigation');
    }

    /**
     * @return array
     */
    public function getAssetsComponents()
    {
        $components = $this->_loadConfig('assets');
        $public = $this->getPublicPath(true);

        foreach ($components as $name => &$assets) {
            foreach ($assets as &$path) {
                $path = $public . '/' . $path;
            }
        }

        return $components;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->_loadConfig('settings');
    }

    /**
     * Метод, вызываемый для инициализации модуля.
     * Именно тут нужно регистрировать свои сервисы, подписываться на события
     *
     * @return void
     */
    abstract public function init();
}
