<?php
namespace Graphite\Di;

abstract class Provider
{
    abstract public function get(Container $di);
}