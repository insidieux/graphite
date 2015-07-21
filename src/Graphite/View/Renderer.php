<?php
namespace Graphite\View;

use Graphite\Std\Exception;

class Renderer
{
    /**
     * Base tpl path
     * @var string
     */
    private $basePath;

    /**
     * Default templates extension
     * @var string
     */
    private $ext = '.phtml';

    /**
     * Shared params
     * @var array
     */
    private $params = array();

    /**
     * @param string $basePath
     */
    public function __construct($basePath = '')
    {
        $this->setBasePath($basePath);
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\\/');
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Sets shared param
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Graphite\Std\Exception
     */
    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new Exception('Shared param name must be a string. "'.gettype($key).'" given!');
        }

        $this->params[$key] = $value;
    }

    /**
     * Sets many shared params
     *
     * @param array $values
     */
    public function mset($values)
    {
        foreach ($values as $key => $val) {
            $this->set($key, $val);
        }
    }

    /**
     * Get one or all shared params
     *
     * @param string $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    /**
     * Returns all shared params
     * @return array
     */
    public function getAll()
    {
        return $this->params;
    }

    /**
     * Clear one or all shared params
     * @param string|null $key
     */
    public function clear($key = null)
    {
        if (empty($key)) {
            if (isset($this->params[$key])) {
                unset($this->params[$key]);
            }
        } else {
            $this->params = array();
        }
    }

    /**
     * @param string $template
     * @param array $params
     *
     * @throws \Exception
     * @return
     */
    public function render($template, $params = array())
    {
        if (!empty($this->basePath)) {
            $template = $this->basePath . DIRECTORY_SEPARATOR . $template;
        }

        $template .= $this->ext;

        if (!file_exists($template)) {
            throw new Exception(sprintf('Cant found template "%s"', $template));
        }

        $closure = function ($__template, $__params, $view) {
            extract($__params, EXTR_SKIP);
            unset($__params);
            
            ob_start();
            include($__template);
            return ob_get_clean();
        };

        return $closure($template, array_merge($this->params, $params), $this);
    }

    public function startBuffer()
    {
        ob_start();
    }

    public function stopBuffer()
    {
        return ob_get_clean();
    }

    /* --- Helpers -------------------------------------------------------------------------------------------------- */

    //@todo implement it ......
}
