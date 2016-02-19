<?php
namespace Graphite\Db\Query;

use Graphite\Db\Expr;

/**
 * Class Select
 *
 * @todo функции в колонках для вборки (COUNT, MAX). Сейчас надо использовать Expr, чтобы не экранировались
 */
class Select extends AbstractQuery
{
    const JOIN_INNER = 'join_inner';
    const JOIN_LEFT  = 'join_left';
    const JOIN_RIGHT = 'join_right';

    protected $cols   = [];
    protected $group  = [];
    protected $join   = [];
    protected $having = [];

    /**
     * ```
     * 'table_name' -> '`table_name`'
     * ```
     *
     * @param string $table
     *
     * @return Select
     */
    public function from($table)
    {
        return $this->table($table);
    }

    /**
     * Set columns with aliases to select
     * ```
     * 'a' -> `a`
     * ['a', 'b']  -> `a`, `b`
     * ['a' => 'A', 'b' => 'B'] -> `a` as A, `b` as B
     * ```
     *
     * @param string|string[]|Expr $columns
     *
     * @return Select
     */
    public function columns($columns)
    {
        if (!empty($columns)) {
            $this->cols = is_array($columns) ? $columns : [$columns];
        }

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return Select
     */
    public function distinct($flag = true)
    {
        $this->flags['DISTINCT'] = (bool) $flag;
        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     * @param string $type
     *
     * @return Select
     */
    public function join($table, $on, $type = self::JOIN_INNER)
    {
        $this->join[] = array(
            'table' => $table,
            'on'    => $on,
            'type'  => $type
        );

        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     *
     * @return Select
     */
    public function innerJoin($table, $on)
    {
        return $this->join($table, $on);
    }

    /**
     * @param string $table
     * @param string $on
     *
     * @return Select
     */
    public function leftJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_LEFT);
    }

    /**
     * @param string $table
     * @param string $on
     *
     * @return Select
     */
    public function rightJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_RIGHT);
    }

    /**
     * @param array $where
     * @param string $operator
     * @return Select
     */
    public function having($where, $operator = self::OP_AND)
    {
        $this->having[] = array(
            'group_op' => self::OP_AND,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param array  $where
     * @param string $operator
     *
     * @return Select
     */
    public function orHaving($where, $operator = self::OP_AND)
    {
        $this->having[] = array(
            'group_op' => self::OP_OR,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * Add GROUP BY part
     *
     * ```
     * 'parent_id'          -> GROUP BY `parent_id`
     * ['parent_id', 'lvl'] -> GROUP BY `parent_id`, `lvl`
     * ```
     *
     * @param string|string[] $criteria
     *
     * @return Select
     */
    public function groupBy($criteria)
    {
        if (!is_array($criteria)) {
            $criteria = array($criteria);
        }
        foreach ($criteria as $val) {
            $this->group[] = $val;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function makeHaving()
    {
        return $this->makeCriteria('having');
    }

    /**
     * @return string
     */
    public function toString()
    {
        $select = array('SELECT');

        if (isset($this->flags['DISTINCT']) && $this->flags['DISTINCT'] === true) {
            $select[] = 'DISTINCT';
        }

        $select[] = $this->makeColumns();

        $select[] = 'FROM ' . $this->makeTable();

        if (($joins = $this->makeJoins()) != '') {
            $select[] = $joins;
        }

        if (($where = $this->makeWhere()) != '') {
            $select[] = 'WHERE ' . $where;
        }

        if (!empty($this->group)) {
            $select[] = 'GROUP BY ' . implode(', ', $this->conn->quoteNames($this->group));
        }

        if (($having = $this->makeHaving()) != '') {
            $select[] = "HAVING $having";
        }

        if (($orderBy = $this->makeOrderBy()) != '') {
            $select[] = "ORDER BY $orderBy";
        }

        if (!empty($this->limit)) {
            $select[] = 'LIMIT ' . (empty($this->offset) ? '' : $this->offset.', ') . $this->limit;
        }

        return implode(' ', $select);
    }

    /**
     * Соберет в строку колонки для выборки
     *
     * @return string
     */
    public function makeColumns()
    {
        if (empty($this->cols)) {
            return '*';
        }

        $cols = [];
        foreach ($this->cols as $name => $alias) {
            if (is_int($name)) {
                $cols[] = $this->conn->quoteName($alias);
            } else {
                $cols[] = $this->conn->quoteName($name) . ' AS ' . $this->conn->quoteName($alias);
            }
        }

        return implode(', ', $cols);
    }

    /**
     * Соберет JOIN часть запроса
     *
     * @return string
     */
    public function makeJoins()
    {
        if (empty($this->join)) {
            return '';
        }

        $joins = [];
        foreach ($this->join as $join) {

            $table = $this->conn->quoteName($join['table']);

            switch ($join['type']) {
                case self::JOIN_LEFT  : { $type = 'LEFT JOIN'; break; }
                case self::JOIN_RIGHT : { $type = 'RIGHT JOIN'; break; }
                default : { $type = 'JOIN'; }
            }

            $joins[] = "$type $table ON({$join['on']})";
        }

        return implode(' ', $joins);
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->cols   = [];
        $this->group  = [];
        $this->join   = [];
        $this->having = [];

        return parent::clear();
    }
}
