<?php
namespace Graphite\Di;

abstract class Provider
{
    /**
     * @param Container $di
     *
     * @return mixed
     */
    abstract public function get(Container $di);
}
