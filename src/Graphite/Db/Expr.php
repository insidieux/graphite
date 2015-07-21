<?php
namespace Graphite\Db;

/**
 * Class Expr
 *
 * @deprecated
 */
class Expr
{
    /**
     * @var string
     */
    private $_expr;

    public function __construct($expression)
    {
        $this->_expr = $expression;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->_expr;
    }
}