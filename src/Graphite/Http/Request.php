<?php
namespace Graphite\Http;

use Graphite\Std;

class Request
{
    /** @var string */
    protected $_uri = '/';

    /** @var Std\Properties */
    protected $_query;

    /** @var Std\Properties */
    protected $_post;

    /** @var Std\Properties */
    protected $_cookie;

    /** @var Std\Properties */
    protected $_files;

    /** @var Std\Properties */
    protected $_headers;

    /** @var Std\Properties */
    protected $_server;

    /** @var Std\Properties */
    protected $_params;

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
        $this->_headers = new Std\Properties($headers);
        $this->_server = new Std\Properties($server);

        // Fetch post data
        $postData = $_POST;
        if ($this->isPost() && strpos($this->_server->get('CONTENT_TYPE', ''), 'application/json') === 0) {
            $postData = json_decode(file_get_contents('php://input'), true);
        }
        $this->_post = new Std\Properties($postData);

        $this->_query  = new Std\Properties($_GET);
        $this->_cookie = new Std\Properties($_COOKIE);
        $this->_files  = new Std\Properties($_FILES);
        $this->_params = new Std\Properties();

        $uri = parse_url($this->_server->get('REQUEST_URI', '/'));
        $this->_uri = $uri['path'];
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getPost()
    {
        return $this->_post;
    }

    /**
     * @return Std\Properties
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * @return Std\Properties
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getCookie()
    {
        return $this->_cookie;
    }

    /**
     * @return \Graphite\Std\Properties
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->_server->get('REQUEST_METHOD', 'GET');
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
        return $this->_headers->get('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }
}