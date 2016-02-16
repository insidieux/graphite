<?php

namespace tests\Fixtures\Loader;

use Graphite\Loader\Autoloader;

/**
 * Class TestAutoloader
 * @package tests\Fixtures\Loader
 */
class TestAutoloader extends Autoloader
{
    /**
     * @var array
     */
    protected $files = [];

    /**
     * @param array $files
     */
    public function setFiles(array $files)
    {
        $this->files = $files;
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function requireFile($file)
    {
        return in_array($file, $this->files);
    }
}
