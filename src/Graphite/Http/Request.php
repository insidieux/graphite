<?php
namespace Graphite\Http;

use Graphite\Std;

class Request
{
    /**
     * @var string
     */
    protected $uri = '/';

    /**
     * @var Std\Properties
     */
    protected $query;

    /**
     * @var Std\Properties
     */
    protected $post;

    /**
     * @var Std\Properties
     */
    protected $cookie;

    /**
     * @var Std\Properties
     */
    protected $files;

    /**
     * @var Std\Properties
     */
    protected $headers;

    /**
     * @var Std\Properties
     */
    protected $server;

    /**
     * @var Std\Properties
     */
    protected $params;

    public function __construct()
    {
        // Split $_SERVER to http headers & server vars
        $headers = $server = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $headers[$key] = $value;
            } else {
                $server[$key] = $value;
            }
        }
        $this->headers = new Std\Properties($headers);
        $this->server = new Std\Properties($server);

        // Fetch post data
        $postData = $_POST;
        if ($this->isPost() && strpos($this->server->get('CONTENT_TYPE', ''), 'application/json') === 0) {
            $postData = json_decode(file_get_contents('php://input'), true);
        }
        $this->post = new Std\Properties($postData);

        $this->query  = new Std\Properties($_GET);
        $this->cookie = new Std\Properties($_COOKIE);
        $this->files  = new Std\Properties($_FILES);
        $this->params = new Std\Properties();

        $uri = parse_url($this->server->get('REQUEST_URI', '/'));
        $this->uri = $uri['path'];
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return Std\Properties
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return Std\Properties
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->server->get('REQUEST_METHOD', 'GET');
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return $this->getRequestMethod() == 'GET';
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return $this->getRequestMethod() == 'POST';
    }

    /**
     * @return bool
     */
    public function isPut()
    {
        return $this->getRequestMethod() == 'PUT';
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->headers->get('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }
}
