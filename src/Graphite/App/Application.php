<?php
namespace Graphite\App;

use Graphite\Std,
    Graphite\ServiceManager,
    Graphite\Loader,
    Graphite\Events,
    Graphite\Http;

class Application
{
    /**
     * @var string
     */
    private $basePath = '';

    /**
     * @var bool
     */
    private $started  = false;

    /**
     * @var ServiceManager\ServiceManager
     */
    private $_sm;

    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->_sm;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Запуск приложения в работу.
     *
     * @throws Std\Exception
     * @return string Ответ клиенту
     */
    public function run()
    {
        if ($this->started) {
            throw new Std\Exception('Application already started!');
        }

        try {
            $this->started = true;
            $this->_init();

            /** @var $em Events\EventsManager */
            $em = $this->getServiceManager()->get('EventsManager');
            $em->trigger('app:init', $this);

            $this->_route();
            $em->trigger('app:route', $this);

            // resolve controller
            try {

                $ctrl = $this->_resolveController();

            } catch (Std\Exception $e) {

                /** @var Std\Properties $params */
                $params = $this->_sm->get('Request')->getParams();

                if (!$params->get('isAdmin')) {
                    throw $e;
                }

                $params->setMany(array(
                    'module'     => 'main',
                    'controller' => 'index',
                    'action'     => 'notfound',
                ));

                $ctrl = $this->_resolveController();
            }

            // run controller action
            $controller = $ctrl['controller']; /** @var AbstractController $controller */
            $action     = $ctrl['action'];

            $controller->preActionRun();
            $response = $controller->$action();
            $controller->postActionRun();

            // resolving response
            if (!($response instanceof Http\Response)) {
                $em->trigger('app:resolveResponse', $this, array('response' => $response));
                $response = $this->getServiceManager()->get('Response');
            }

            if (!($response instanceof Http\Response)) {
                throw new Std\Exception('Controller must return Http\Response, or a string! "'.gettype($response).'" returned');
            }

            // sending result
            $em->trigger('app:beforeResponse', $this, array('response' => $response));
            $response->send();

        } catch (\Exception $e) {

            // reset output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            $message  = '<pre>';
            $message .= '<h2>'.($e instanceof Std\Exception ? 'System' : 'Server').' error!</h2>';
            $message .= '<b>'.get_class($e).': '.$e->getMessage().'</b>';
            $message .= '<p>---</p>';
            $message .= $e->getTraceAsString();
            $message .= '</pre>';

            $response = new Http\Response($message, 500);
            $response->send();
        }
    }

    /**
     * Initializing service manager & base app services (events, modules, request etc...)
     */
    private function _init()
    {
        $base = $this->basePath;

        // first - init services locator
        $this->_sm = new ServiceManager\ServiceManager();
        $this->_sm->setService('EventsManager', new Events\EventsManager());

        $this->_sm->setService('Request', new Http\Request());

        $modManager = new ModulesManager($this->_sm, $base . '/modules');
        $this->_sm->setService('ModulesManager', $modManager);
        $modManager->initModules();
    }

    /**
     * default app routing
     * 1) check context: admin or not
     * 2) for admin:  /admin/<module>/<controller>/<action>
     *    for public: all requests to /main/public/index
     *
     * @todo Не должно приложение ничего знать о модуле main к примеру
     *       Пока тут, в качестве прототипа, но в будущем решить как поступать более гибко
     */
    private function _route()
    {
        /** @var $request Http\Request */
        $request = $this->_sm->get('Request');
        $uri = $request->getUri();

        if (preg_match('#^/admin[/]?#', $uri)) {
            $pathParts = explode('/', trim($uri, '/'));
            $params = array(
                'module'     => empty($pathParts[1]) ? 'main'  : $pathParts[1],
                'controller' => empty($pathParts[2]) ? 'index' : $pathParts[2],
                'action'     => empty($pathParts[3]) ? 'index' : $pathParts[3],
                'isAdmin'    => true,
            );
        } else {
            $params = array(
                'module'     => 'pages',
                'controller' => 'public',
                'action'     => 'index',
                'isAdmin'    => false,
            );
        }

        $request->getParams()->setMany($params);
    }

    /**
     * Find & load controller class depends on Request params
     *
     * @return array
     *
     * @throws \Graphite\Std\Exception
     */
    private function _resolveController()
    {
        /** @var Std\Properties $params */
        $params = $this->_sm->get('Request')->getParams();

        /** @var $modules ModulesManager */
        $modules = $this->_sm->get('ModulesManager');

        $moduleName = ucfirst($params->get('module'));
        if (!$modules->hasModule($moduleName)) {
            throw new Std\Exception('Cant find module "'.$moduleName.'"');
        }

        $module = $modules->getModule($moduleName);

        $ctrlName  = ucfirst($params->get('controller')) . 'Controller';;
        $ctrlFile  = $module->getControllersPath() . '/' . $ctrlName . '.php';;
        $ctrlClass = "\\Modules\\$moduleName\\Controller\\$ctrlName";

        if (!file_exists($ctrlFile)) {
            throw new Std\Exception('Cant find controller file "'.$ctrlFile.'"');
        }

        require_once $ctrlFile;

        $ctrl = new $ctrlClass($module, $this->_sm);
        if (!$ctrl instanceof AbstractController) {
            throw new Std\Exception('Controller class must be instance of AbstractController');
        }

        $actionName = $params->get('action') . 'Action';
        if (!method_exists($ctrl, $actionName)) {
            throw new Std\Exception("$ctrlName does not have action $actionName");
        }

        return array(
            'controller' => $ctrl,
            'action'     => $actionName,
        );
    }
}
