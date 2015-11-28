<?php
namespace Graphite\App;

use Graphite\Di;
use Graphite\Loader;
use Graphite\Events;
use Graphite\Http;

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
     * @var Di\Container
     */
    private $di;

    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return Di\Container
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * @param Di\Container $di
     */
    public function setDi(Di\Container $di)
    {
        $this->di = $di;
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
     * @return string Ответ клиенту
     *
     * @throws Exception
     */
    public function run()
    {
        if ($this->started) {
            throw new Exception('Application already started!');
        }

        try {
            $this->started = true;
            $this->init();

            /** @var Events\EventsManager $em  */
            $em = $this->getDi()->get('EventsManager');
            $em->trigger('app:init', $this);

            $this->route();
            $em->trigger('app:route', $this);

            // resolve controller
            try {

                $ctrl = $this->resolveController();

            } catch (Exception $e) {

                /** @var \Graphite\Std\Properties $params */
                $params = $this->di->get('Request')->getParams();

                if (!$params->get('isAdmin')) {
                    throw $e;
                }

                $params->setMany(array(
                    'module'     => 'main',
                    'controller' => 'index',
                    'action'     => 'notfound',
                ));

                $ctrl = $this->resolveController();
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
                $response = $this->getDi()->get('Response');
            }

            if (!($response instanceof Http\Response)) {
                throw new Exception('Controller must return Http\Response, or a string! "'.gettype($response).'" returned');
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
            $message .= '<h2>System error!</h2>';
            $message .= '<b>' . get_class($e) . ': ' . $e->getMessage().'</b>';
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
    private function init()
    {
        $base = $this->basePath;

        // first - init services locator
        $this->di = new Di\Container();
        $this->di->setSingleton('EventsManager', new Events\EventsManager());
        $this->di->setSingleton('Request', new Http\Request());

        $modManager = new ModulesManager($this->di, $base . '/modules');
        $this->di->setSingleton('ModulesManager', $modManager);
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
    private function route()
    {
        /** @var $request Http\Request */
        $request = $this->di->get('Request');
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
     * @throws Exception
     */
    private function resolveController()
    {
        /** @var \Graphite\Std\Properties $params */
        $params = $this->di->get('Request')->getParams();

        /** @var $modules ModulesManager */
        $modules = $this->di->get('ModulesManager');

        $moduleName = ucfirst($params->get('module'));
        if (!$modules->hasModule($moduleName)) {
            throw new Exception('Cant find module "'.$moduleName.'"');
        }

        $module = $modules->getModule($moduleName);

        $ctrlName  = ucfirst($params->get('controller')) . 'Controller';;
        $ctrlFile  = $module->getControllersPath() . '/' . $ctrlName . '.php';;
        $ctrlClass = "\\Modules\\$moduleName\\Controller\\$ctrlName";

        if (!file_exists($ctrlFile)) {
            throw new Exception('Cant find controller file "'.$ctrlFile.'"');
        }

        require_once $ctrlFile;

        $ctrl = new $ctrlClass($module, $this->di);
        if (!$ctrl instanceof AbstractController) {
            throw new Exception('Controller class must be instance of AbstractController');
        }

        $actionName = $params->get('action') . 'Action';
        if (!method_exists($ctrl, $actionName)) {
            throw new Exception("$ctrlName does not have action $actionName");
        }

        return array(
            'controller' => $ctrl,
            'action'     => $actionName,
        );
    }
}
