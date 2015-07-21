<?php
namespace Graphite\Db\Query;

use Graphite\Db\Connection;

abstract class AbstractQuery
{
    const OP_AND = 'AND';
    const OP_OR  = 'OR';

    const ORDER_ASC  = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * @var  Connection
     */
    protected $conn;

    protected $table = '';
    protected $flags = [];
    protected $where = [];
    protected $order = [];
    protected $limit;
    protected $offset;
    protected $set = [];

    /**
     * @param Connection $conn
     */
    public function __construct(Connection $conn = null)
    {
        if ($conn) {
            $this->setConnection($conn);
        }
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @param Connection $conn
     *
     * @return static
     */
    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
        return $this;
    }

    /**
     * @param string $table
     *
     * @return static
     */
    public function table($table)
    {
        $this->table = (string) $table;
        return $this;
    }

    /**
     * Add where criteria
     * ```
     * ['id = 5', 'order = 10']                  ->  id = 5 AND order = 10
     * ['id' => 5, 'order' => 10]                -> `id` = 5 AND `order` = 10
     * ['id' => [1, 2, 3], 'order' => null]      -> `id` IN(1, 2, 3) AND `order` ISNULL
     * ['id > ?' => 10, 'name LIKE ?' => 'pit%'] ->  id > 10 AND name LIKE 'pit%'
     * ```
     *
     * @param array  $where    array of criteria
     * @param string $operator glue operator 'AND' by default
     *
     * @return static
     */
    public function where($where, $operator = self::OP_AND)
    {
        $this->where[] = array(
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
     * @return static
     */
    public function orWhere($where, $operator = self::OP_AND)
    {
        $this->where[] = array(
            'group_op' => self::OP_OR,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param array $values
     *
     * @return static
     */
    public function set($values)
    {
        if (is_array($values)) {
            $this->set = array_merge($this->set, $values);
        } else {
            $this->set[] = $values;
        }

        return $this;
    }

    /**
     * Add ORDER BY part
     * ```
     * 'parent_id'                   -> ORDER BY `parent_id`
     * ['parent_id ASC', 'lvl DESC'] -> ORDER BY `parent_id` ASC, `lvl` DESC
     * ```
     *
     * @param string|string[] $criteria
     *
     * @return static
     */
    public function orderBy($criteria)
    {
        if (!is_array($criteria)) {
            $criteria = array($criteria);
        }

        foreach ($criteria as $val) {
            if (strpos($val, ' ') === false) {
                $this->order[$val] = self::ORDER_ASC;
            } else {
                $parts = explode(' ', $val);
                $this->order[$parts[0]] = (strtoupper($parts[1]) == self::ORDER_DESC)
                    ? self::ORDER_DESC
                    : self::ORDER_ASC;
            }
        }

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return static
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @param int $offset
     *
     * @return static
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Собирает SQL строку запроса
     *
     * @return string
     */
    abstract public function toString();

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Собирает критерии WHERE/HAVING в строку
     *
     * @param string $part WHERE / HAVING
     *
     * @return string
     */
    protected function makeCriteria($part)
    {
        if (!isset($this->$part)) {
            return '';
        }

        $where = array();

        foreach ($this->$part as $i => $whereGroup) {

            $res = array();

            foreach ($whereGroup['where'] as $criteria => $bindValue) {

                // no quoting needed, use raw $bindValue
                if (is_int($criteria)) {
                    $res[] = $bindValue;
                    continue;
                }

                // criteria has placeholders - use quoteInString
                if (strpos($criteria, '?') !== false) {
                    $res[] = $this->conn->quoteInString($criteria, $bindValue);
                    continue;
                }

                // auto-compile clause by bindValue type
                $criteria = $this->conn->quoteName($criteria);
                if ($bindValue === null) {
                    $res[] = $criteria . ' IS NULL';
                } elseif (is_array($bindValue)) {
                    $res[] = $criteria . ' IN(' . $this->conn->quoteValue($bindValue) . ')';
                } else {
                    $res[] = $criteria . ' = ' . $this->conn->quoteValue($bindValue);
                }
            }

            $compiled = implode(" {$whereGroup['where_op']} ", $res);
            if (count($whereGroup['where']) > 1) {
                $compiled = '(' . $compiled . ')';
            }

            $where[] = ($i == 0 ? '' : $whereGroup['group_op'] . ' ') . $compiled;
        }

        return implode(' ', $where);
    }

    /**
     * Вернет строку для WHERE критериев
     *
     * @return string
     */
    public function makeWhere()
    {
        return $this->makeCriteria('where');
    }

    /**
     * @return string
     */
    public function makeSet()
    {
        if (empty($this->set)) {
            return '';
        }

        $set = array();

        foreach ($this->set as $key => $val) {

            if (is_int($key)) {
                $set[] = $val;
            } elseif (strpos($key, '?') !== false) {
                $set[] = $this->conn->quoteInString($key, $val);
            } else {
                $val = ($val === null) ? 'NULL' : $this->conn->quoteValue($val);
                $set[] = $this->conn->quoteName($key) . ' = ' . $val;
            }
        }

        return implode(', ', $set);
    }

    /**
     * @return string
     */
    public function makeTable()
    {
        return $this->conn->quoteName($this->table);
    }

    /**
     * @return string
     */
    public function makeOrderBy()
    {
        if (empty($this->order)) {
            return '';
        }

        $order = [];
        foreach ($this->order as $col => $sort) {
            $order[] = $this->conn->quoteName($col) . ' ' . $sort;
        }

        return implode(', ', $order);
    }

    /**
     * Run query execution. Returns ResultSet for Select, int - for other
     *
     * @return \Graphite\Db\ResultSet|int
     */
    public function run()
    {
        $sql = $this->toString();

        if ($this instanceof Select) {
            return $this->conn->query($sql);
        } else {
            return $this->conn->execute($sql);
        }
    }

    /**
     * Reset all query parts
     *
     * @return static
     */
    public function clear()
    {
        $this->flags  = array();
        $this->table  = '';
        $this->where  = array();
        $this->order  = array();
        $this->limit  = null;
        $this->offset = null;
        $this->set    = array();

        return $this;
    }

    /**
     * Reset query part
     *
     * @param string $part
     *
     * @return static
     */
    public function clearPart($part)
    {
        $property = '_' . $part;
        if (property_exists($this, $property)) {

            if ($part == 'limit' || $part == 'offset') {
                $default = null;
            } elseif ($part == 'from') {
                $default = '';
            } else {
                $default = array();
            }

            $this->$property = $default;
        }

        return $this;
    }
}
