<?php
namespace Graphite\Db;

class Expr
{
    /**
     * @var string
     */
    private $expr;

    public function __construct($expression)
    {
        $this->expr = $expression;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->expr;
    }
}
