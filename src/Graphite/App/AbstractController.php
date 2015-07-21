<?php
namespace Graphite\App;

use Graphite\Http,
    Graphite\View,
    Graphite\ServiceManager\ServiceManager;

abstract class AbstractController
{
    /**
     * Модуль, которому пренадлежит контроллер
     * @var AbstractModule
     */
    private $_module;

    /**
     * @var ServiceManager
     */
    private $_serviceManager;

    /**
     * @var Http\Request
     */
    private $_request;

    /**
     * @param AbstractModule $module
     * @param ServiceManager $serviceManager
     */
    public function __construct(AbstractModule $module, ServiceManager $serviceManager)
    {
        $this->_module         = $module;
        $this->_serviceManager = $serviceManager;
        $this->_request        = $this->_serviceManager->get('Request');
    }

    /**
     * @return Http\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->_serviceManager;
    }

    /**
     * @return AbstractModule
     */
    public function getModule()
    {
        return $this->_module;
    }

    /**
     * Метод, который будет вызван до выполнения action
     */
    public function preActionRun()
    {
    }

    /**
     * Метод, который будет вызвад после выполнения action, но до передачи результата action далее
     */
    public function postActionRun()
    {
    }

    /**
     * @param string $template
     * @param array  $params
     *
     * @return string
     */
    public function render($params = array(), $template = '')
    {
        if (empty($template)) {
            // соберем путь автоматически, основываясь на данных Request
            $vars = $this->getRequest()->getParams();
            $template = $vars->get('controller') . '/' . $vars->get('action');
        }

        $view = new View\Renderer($this->_module->getViewPath());
        return $view->render($template, $params);
    }

    /**
     * Returns response with redirect headers to $location
     *
     * @param string $location
     *
     * @return Http\Response
     */
    public function responseRedirect($location)
    {
        return new Http\Response('', 200, array(
            'Location' => $location
        ));
    }

    /**
     * @param array $data
     * @param int   $code
     *
     * @return Http\Response
     */
    public function responseJson($data, $code = 200)
    {
        return new Http\Response(json_encode($data), $code, array(
            'Content-type' => 'application/json'
        ));
    }

    /**
     * @param string $content
     * @param string $mimeType
     * @param string $fileName
     *
     * @return Http\Response
     */
    public function responseFile($content, $mimeType, $fileName)
    {
        return new Http\Response($content, 200, array(
            'Content-type'        => 'application/' . $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length'      => strlen($content)
        ));
    }

    /**
     * @param string $text
     *
     * @return Http\Response
     */
    public function responseText($text)
    {
        return new Http\Response($text);
    }

    /**
     * @param array  $params
     * @param string $template
     *
     * @return Http\Response
     */
    public function responseHtml($params = array(), $template = '')
    {
        return new Http\Response($this->render($params, $template));
    }
}
