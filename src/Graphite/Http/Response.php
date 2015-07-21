<?php
namespace Graphite\Http;

class Response
{
    private $_statusCode = 200;
    private $_body = '';
    private $_headers = array();

    protected $_posibleCodes = array(
        // informational codes
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        // success codes
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',

        // redirection codes
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',

        // client error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',

        // server errors
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
     * @param string $body
     * @param int    $statusCode
     * @param array  $headers
     */
    public function __construct($body = '', $statusCode = 200, $headers = array())
    {
        $this->setBody($body);
        $this->setStatusCode($statusCode);

        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool   $rewrite
     *
     * @throws \Exception
     * @internal param string $header
     *
     * @return Response
     */
    public function addHeader($name, $value, $rewrite = false)
    {
        $name = (string) $name;

        if (substr($name, 0, 5) == 'HTTP/') {
            throw new \Exception('Header name can`t start with HTTP! To set response status code use "setStatusCode" method.');
        }

        if (!isset($this->_headers[$name]) || $rewrite) {
            $this->_headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @return Response
     */
    public function clearHeaders()
    {
        $this->_headers = array();
        return $this;
    }

    /**
     * @return bool
     */
    public function isHeadersSent()
    {
        return headers_sent();
    }

    /**
     * @return Response
     */
    public function sendHeaders()
    {
        foreach ($this->_headers as $name => $value) {
            header($name . ': ' . $value);
        }
        return $this;
    }

    /**
     * @param string $body
     *
     * @return Response
     */
    public function setBody($body)
    {
        $this->_body = (string) $body;
        return $this;
    }

    /**
     * @param string $content
     *
     * @return Response
     */
    public function appendBody($content)
    {
        $this->_body .= (string) $content;
        return $this;
    }

    /**
     * @return Response
     */
    public function clearBody()
    {
        $this->_body = '';
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * @param int $code
     *
     * @return Response
     * @throws \Exception
     */
    public function setStatusCode($code)
    {
        $code = (int) $code;
        if (!isset($this->_posibleCodes[$code])) {
            throw new \Exception('Trying to set undefined status code "'.$code.'"');
        }

        $this->_statusCode = $code;
        return $this;
    }

    public function send()
    {
        if (!$this->isHeadersSent()) {
            if ($this->_statusCode != 200) {
                header("HTTP/1.1 {$this->_statusCode} {$this->_posibleCodes[$this->_statusCode]}");
            }
            $this->sendHeaders();
        }

        echo $this->_body;
    }
}