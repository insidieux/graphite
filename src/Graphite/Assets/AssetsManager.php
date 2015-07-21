<?php
namespace Graphite\Assets;

class AssetsManager
{
    /**
     * @var array
     */
    private $assets = array(
        'css' => array(),
        'js'  => array(),
    );

    /**
     * @var array
     */
    private $components = array();

    /**
     * @param string $type
     * @param string $asset
     * @param bool   $append
     *
     * @return bool
     */
    private function _add($asset, $type = null, $append = true)
    {
        if (!$type) {
            $type = pathinfo($asset, PATHINFO_EXTENSION);
        }

        if (empty($type) || !isset($this->assets[$type])) {
            return false;
        }

        if (in_array($asset, $this->assets[$type])) {
            return false;
        }

        if ($append) {
            array_push($this->assets[$type], $asset);
        } else {
            array_unshift($this->assets[$type], $asset);
        }

        return true;
    }

    /**
     * @param $asset
     *
     * @return $this
     */
    public function append($asset)
    {
        foreach ((array) $asset as $row) {
            $this->_add($row);
        }

        return $this;
    }

    /**
     * @param $asset
     *
     * @return $this
     */
    public function prepend($asset)
    {
        foreach ((array) $asset as $row) {
            $this->_add($row, null, false);
        }

        return $this;
    }

    /**
     * @param string|array $path
     *
     * @return AssetsManager
     */
    public function appendJs($path)
    {
        foreach ((array) $path as $value) {
            $this->_add($value, 'js');
        }

        return $this;
    }

    /**
     * @param string|array $path
     *
     * @return AssetsManager
     */
    public function prependJs($path)
    {
        foreach ((array) $path as $value) {
            $this->_add($value, 'js', false);
        }

        return $this;
    }

    /**
     * @param string|array $path
     *
     * @return AssetsManager
     */
    public function appendCss($path)
    {
        foreach ((array) $path as $value) {
            $this->_add($value, 'css');
        }

        return $this;
    }

    /**
     * @param string|array $path
     *
     * @return AssetsManager
     */
    public function prependCss($path)
    {
        foreach ((array) $path as $value) {
            $this->_add($value, 'css', false);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getJs()
    {
        return $this->assets['js'];
    }

    /**
     * @return array
     */
    public function getCss()
    {
        return $this->assets['css'];
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->assets;
    }

    /**
     * @return AssetsManager
     */
    public function clearJs()
    {
        $this->assets['js'] = array();

        return $this;
    }

    /**
     * @return AssetsManager
     */
    public function clearCss()
    {
        $this->assets['css'] = array();

        return $this;
    }

    /**
     * @return AssetsManager
     */
    public function clearAll()
    {
        return $this->clearJs()->clearCss();
    }

    /**
     * @param string $name
     * @param array  $assets
     */
    public function registerComponent($name, $assets)
    {
        $this->components[$name] = $assets;
    }

    /**
     * @param string $name
     */
    public function appendComponent($name)
    {
        foreach ((array)$name as $cmp) {
            if (isset($this->components[$cmp])) {
                $this->append($this->components[$cmp]);
            }
        }
    }
}
