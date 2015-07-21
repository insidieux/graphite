<?php
namespace Graphite\App;

use Graphite\Events;
use Graphite\Http;
use Graphite\ServiceManager\ServiceManager;

abstract class AbstractModule
{
    /**
     * Человеко-понятное название модуля
     * @var string
     */
    protected $_title = '';

    /**
     * Путь до директории модуля
     * @var string
     */
    protected $_basePath  = '';

    /**
     * @var ServiceManager
     */
    private $_serviceManager;

    /**
     * @var ModulesManager
     */
    private $_modulesManager;

    /**
     * @var Events\EventsManager;
     */
    private $_eventsManager;

    /**
     * @var Http\Request;
     */
    private $_request;

    /**
     * @param ServiceManager $serviceManager
     * @param string         $path
     */
    public function __construct(ServiceManager $serviceManager, $path = '')
    {
        $this->_serviceManager = $serviceManager;
        $this->_modulesManager = $serviceManager->get('ModulesManager');
        $this->_eventsManager  = $serviceManager->get('EventsManager');
        $this->_request        = $serviceManager->get('Request');

        $this->setBasePath($path);
    }

    /**
     * @param string $path
     *
     * @return AbstractModule
     */
    public function setBasePath($path)
    {
        $this->_basePath = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->_basePath . '/config';
    }

    /**
     * @return string
     */
    public function getControllersPath()
    {
        return $this->_basePath . '/controller';
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->_basePath . '/view';
    }

    /**
     * @param bool $relative
     *
     * @return string
     */
    public function getPublicPath($relative = false)
    {
        $basePath = str_replace('\\', '/', $this->_basePath);

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
        return $this->_title;
    }

    /**
     * @return null|ModulesManager
     */
    public function getModulesManager()
    {
        return $this->_modulesManager;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->_serviceManager;
    }

    /**
     * @return null|Events\EventsManager
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * @return null|Http\Request
     */
    public function getRequest()
    {
        return $this->_request;
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
