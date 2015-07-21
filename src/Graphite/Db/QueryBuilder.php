<?php
namespace Graphite\Db;

/**
 * Class QueryBuilder
 *
 * @deprecated
 *
 */
class QueryBuilder
{
    const OP_AND = 'AND';
    const OP_OR  = 'OR';

    const ORDER_ASC  = 'ASC';
    const ORDER_DESC = 'DESC';

    const JOIN_INNER = 'join_inner';
    const JOIN_LEFT  = 'join_left';
    const JOIN_RIGHT = 'join_right';

    /**
     * @var  Connection
     */
    private $_conn;

    /**
     * @var  string
     */
    private $_queryType;

    protected $_from   = '';
    protected $_cols   = array();
    protected $_where  = array();
    protected $_join   = array();
    protected $_having = array();
    protected $_group  = array();
    protected $_order  = array();
    protected $_limit  = null;
    protected $_offset = null;
    protected $_set    = array();
    protected $_values = array();
    protected $_flags  = array();

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
        return $this->_conn;
    }

    /**
     * @param Connection $conn
     */
    public function setConnection(Connection $conn)
    {
        $this->_conn = $conn;
    }

    /**
     * @param array $columns
     *
     * ['id', 'name']         -> `id`, `name`
     * ['id', 'name' => 'nm'] -> `id`, `name` as `mn`
     * [Expr, 'name' => 'nm'] -> Expr->get(), `name` as `mn`
     *
     * @return QueryBuilder
     */
    public function select($columns = array())
    {
        $this->_queryType = __FUNCTION__;
        $this->_cols = empty($columns) ? array() : (array) $columns;
        return $this;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function insert($table = '')
    {
        $this->_queryType = __FUNCTION__;

        if ($table != '') {
            $this->from($table);
        }

        return $this;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function update($table = '')
    {
        $this->_queryType = __FUNCTION__;

        if ($table != '') {
            $this->from($table);
        }

        return $this;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function delete($table = '')
    {
        $this->_queryType = __FUNCTION__;

        if ($table != '') {
            $this->from($table);
        }

        return $this;
    }

    /* --- query attrs setters -------------------------------------------------------------------------------------- */

    /**
     * @param bool $flag
     *
     * @return QueryBuilder
     */
    public function distinct($flag = true)
    {
        $this->_flags['DISTINCT'] = (bool) $flag;
        return $this;
    }

    /**
     * @param string $table
     *
     * 'table_name' -> `table_name`
     *
     * @return QueryBuilder
     */
    public function from($table)
    {
        $this->_from = (string) $table;
        return $this;
    }

    /**
     * @param array $where
     * @param string $operator
     *
     * How where works:
     *
     * ['id = 5', 'order = 10']                  ->  id = 5 AND order = 10
     * ['id' => 5, 'order' => 10]                -> `id` = 5 AND `order` = 10
     * ['id' => [1, 2, 3], 'order' => null]      -> `id` IN(1, 2, 3) AND `order` ISNULL
     * ['id > ?' => 10, 'name LIKE ?' => 'pit%'] ->  id > 10 AND name LIKE 'pit%'
     *
     * @return QueryBuilder
     */
    public function where($where, $operator = self::OP_AND)
    {
        $this->_where[] = array(
            'group_op' => self::OP_AND,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param array $where
     * @param string $operator
     * @return QueryBuilder
     */
    public function orWhere($where, $operator = self::OP_AND)
    {
        $this->_where[] = array(
            'group_op' => self::OP_OR,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     * @param string $type
     *
     * @return QueryBuilder
     */
    public function join($table, $on, $type = self::JOIN_INNER)
    {
        $this->_join[] = array(
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
     * @return QueryBuilder
     */
    public function innerJoin($table, $on)
    {
        return $this->join($table, $on);
    }

    /**
     * @param string $table
     * @param string $on
     *
     * @return QueryBuilder
     */
    public function leftJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_LEFT);
    }

    /**
     * @param string $table
     * @param string $on
     *
     * @return QueryBuilder
     */
    public function rightJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_RIGHT);
    }

    /**
     * @param array $where
     * @param string $operator
     * @return QueryBuilder
     */
    public function having($where, $operator = self::OP_AND)
    {
        $this->_having[] = array(
            'group_op' => self::OP_AND,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param $where
     * @param string $operator
     * @return $this
     */
    public function orHaving($where, $operator = self::OP_AND)
    {
        $this->_having[] = array(
            'group_op' => self::OP_OR,
            'where'    => $where,
            'where_op' => $operator
        );

        return $this;
    }

    /**
     * @param string|array $criteria
     *
     * 'parent_id'          -> GROUP BY `parent_id`
     * ['parent_id', 'lvl'] -> GROUP BY `parent_id`, `lvl`
     *
     * @return QueryBuilder
     */
    public function groupBy($criteria)
    {
        if (!is_array($criteria)) {
            $criteria = array($criteria);
        }
        foreach ($criteria as $val) {
            $this->_group[] = $val;
        }

        return $this;
    }

    /**
     * @param $criteria
     *
     * 'parent_id'                   -> ORDER BY `parent_id`
     * ['parent_id ASC', 'lvl DESC'] -> ORDER BY `parent_id` ASC, `lvl` DESC
     *
     * @return QueryBuilder
     */
    public function orderBy($criteria)
    {
        if (!is_array($criteria)) {
            $criteria = array($criteria);
        }
        foreach ($criteria as $val) {
            if (strpos($val, ' ') === false) {
                $this->_order[$val] = self::ORDER_ASC;
            } else {
                $parts = explode(' ', $val);
                $this->_order[$parts[0]] = (strtoupper($parts[1]) == self::ORDER_DESC)
                    ? self::ORDER_DESC
                    : self::ORDER_ASC;
            }
        }

        return $this;
    }

    /**
     * @param int $limit
     * @return QueryBuilder
     */
    public function limit($limit)
    {
        $this->_limit = (int) $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->_offset = (int)$offset;
        return $this;
    }

    /**
     * @param array $values
     * @return QueryBuilder
     */
    public function values($values)
    {
        if (is_int(key($values))) {
            $this->_values = array_merge($this->_values, $values);
        } else {
            $this->_values[] = $values;
        }

        return $this;
    }

    /**
     * @param $flag
     * @return QueryBuilder
     */
    public function onDuplicateKeyIgnore($flag = true)
    {
        $this->_flags['IGNORE'] = (bool) $flag;
        return $this;
    }

    /**
     * @param array $set
     * @return QueryBuilder
     */
    public function onDuplicateKeyUpdate($set)
    {
        return $this->set($set);
    }

    /**
     * @param array $values
     * @return QueryBuilder
     */
    public function set($values)
    {
        if (is_array($values)) {
            $this->_set = array_merge($this->_set, $values);
        } else {
            $this->_set[] = $values;
        }

        return $this;
    }


    /* --- Assemblers ----------------------------------------------------------------------------------------------- */

    /**
     * Return query as sql string
     *
     * @throws Exception
     *
     * @return string
     */
    public function getSql()
    {
        $methodName = '_make' . strtoupper($this->_queryType);
        if (empty($this->_queryType) || !method_exists($this, $methodName)) {
            throw new Exception('Unknown query type "'.$this->_queryType.'"');
        }

        return $this->$methodName();
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function toString()
    {
        return $this->getSql();
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function __toString()
    {
        return $this->getSql();
    }

    /**
     * Compiles WHERE part
     *
     * @param string $part WHERE or HAVING
     *
     * @return string
     */
    private function _makeWhere($part = null)
    {
        if ($part === null) {
            $part = $this->_where;
        }

        if (empty($part)) {
            return '';
        }

        $where = array();

        foreach ($part as $i => $whereGroup) {

            $res = array();

            foreach ($whereGroup['where'] as $criteria => $bindValue) {

                // no quoting needed, use raw $bindValue
                if (is_int($criteria)) {
                    $res[] = $bindValue;
                    continue;
                }

                // criteria has placeholders - use quoteInString
                if (strpos($criteria, '?') !== false) {
                    $res[] = $this->_conn->quoteInString($criteria, $bindValue);
                    continue;
                }

                // auto-compile clause by bindValue type
                $criteria = $this->_conn->quoteName($criteria);
                if ($bindValue === null) {
                    $res[] = $criteria . ' IS NULL';
                } elseif (is_array($bindValue)) {
                    $res[] = $criteria . ' IN(' . $this->_conn->quoteValue($bindValue) . ')';
                } else {
                    $res[] = $criteria . ' = ' . $this->_conn->quoteValue($bindValue);
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
     * Compiles SET part
     * @return string
     */
    private function _makeSet()
    {
        if (empty($this->_set)) {
            return '';
        }

        $set = array();

        foreach ($this->_set as $key => $val) {

            if (is_int($key)) {
                $set[] = $val;
            } elseif (strpos($key, '?') !== false) {
                $set[] = $this->_conn->quoteInString($key, $val);
            } else {
                $val = ($val === null) ? 'NULL' : $this->_conn->quoteValue($val);
                $set[] = $this->_conn->quoteName($key) . ' = ' . $val;
            }
        }

        return implode(', ', $set);
    }

    /**
     * Compiles SELECT sql string from query parts
     * @return string
     */
    private function _makeSelect()
    {
        $select = array('SELECT');

        // distinct flag
        if (isset($this->_flags['DISTINCT']) && $this->_flags['DISTINCT'] === true) {
            $select[] = 'DISTINCT';
        }

        // columns
        if (empty($this->_cols)) {
            $select[] = '*';
        } else {
            $cols = array();
            foreach ($this->_cols as $name => $alias) {
                if (is_int($name)) {
                    $cols[] = $this->_conn->quoteName($alias);
                } else {
                    $cols[] = $this->_conn->quoteName($name) . ' AS ' . $this->_conn->quoteName($alias);
                }
            }

            $select[] = implode(', ', $cols);
        }

        // from
        $select[] = 'FROM ' . $this->_conn->quoteName($this->_from);

        // joins
        if (!empty($this->_join)) {
            $joins = [];
            foreach ($this->_join as $join) {

                $table = $this->_conn->quoteName($join['table']);

                switch ($join['type']) {
                    case self::JOIN_LEFT  : { $type = 'LEFT JOIN'; break; }
                    case self::JOIN_RIGHT : { $type = 'RIGHT JOIN'; break; }
                    default : { $type = 'JOIN'; }
                }

                $joins[] = "$type $table ON({$join['on']})";
            }

            if (!empty($joins)) {
                $select[] = implode(' ', $joins);
            }
        }

        // where
        if (($where = $this->_makeWhere($this->_where)) != '') {
            $select[] = 'WHERE ' . $where;
        }

        // group
        if (!empty($this->_group)) {
            $group = array();
            foreach ($this->_group as $val) {
                $group[] = $this->_conn->quoteName($val);
            }
            $select[] = 'GROUP BY ' . implode(', ', $group);
        }

        // having
        if (($having = $this->_makeWhere($this->_having)) != '') {
            $select[] = 'HAVING ' . $having;
        }

        // order
        if (!empty($this->_order)) {
            $order = array();
            foreach ($this->_order as $key => $val) {
                $order[] = $this->_conn->quoteName($key) . ' ' . $val;
            }
            $select[] = 'ORDER BY ' . implode(', ', $order);
        }

        // limit
        if (!empty($this->_limit)) {
            $select[] = 'LIMIT ' . (empty($this->_offset) ? '' : $this->_offset.', ') . $this->_limit;
        }

        return implode(' ', $select);
    }

    /**
     * Compiles INSERT sql string from query parts
     * @return string
     */
    private function _makeInsert()
    {
        $sql = array('INSERT');

        // ignore flag
        if (isset($this->_flags['IGNORE']) && $this->_flags['IGNORE']) {
            $sql[] = 'IGNORE';
        }

        // table name
        $sql[] = 'INTO ' . $this->_conn->quoteName($this->_from);

        // columns (get from first row)
        $columns = array_keys(reset($this->_values));
        $sql[] = '(' . implode(', ', $this->_conn->quoteNames($columns)) . ')';

        // values
        $values = array();
        foreach ($this->_values as $row) {
            $values[] = implode(',', $this->_conn->quoteValues($row));
        }
        $sql[] = 'VALUES (' . implode('), (', $values) . ')';

        // duplicate update
        if (!empty($this->_set)) {
            $sql[] = 'ON DUPLICATE KEY UPDATE ' . $this->_makeSet();
        }

        return implode(' ', $sql);
    }

    /**
     * Compiles UPDATE sql string from query parts
     * @return string
     */
    private function _makeUpdate()
    {
        $sql  = 'UPDATE ' . $this->_conn->quoteName($this->_from) . ' SET ' . $this->_makeSet();

        if (($where = $this->_makeWhere()) != '') {
            $sql .= ' WHERE ' . $where;
        }

        if (!empty($this->_limit)) {
            $sql .= ' LIMIT ' . ((int) $this->_limit);
        }

        return $sql;
    }

    /**
     * Compiles DELETE sql string from query parts
     * @return string
     */
    private function _makeDelete()
    {
        $sql = 'DELETE FROM ' . $this->_conn->quoteName($this->_from);

        if (($where = $this->_makeWhere()) != '') {
            $sql .= ' WHERE ' . $where;
        }

        return $sql;
    }

    /**
     * @return ResultSet|int
     * @deprecated use run
     */
    public function query()
    {
        return $this->run();
    }

    /**
     * @return ResultSet|int
     */
    public function run()
    {
        if ($this->_queryType == 'select') {
            return $this->_conn->query($this->getSql());
        } else {
            return $this->_conn->execute($this->getSql());
        }
    }

    /**
     * @return QueryBuilder
     */
    public function reset()
    {
        $this->_flags = array();
        $this->_from   = '';
        $this->_cols   = array();
        $this->_where  = array();
        $this->_join   = array();
        $this->_having = array();
        $this->_group  = array();
        $this->_order  = array();
        $this->_limit  = null;
        $this->_offset = null;
        $this->_set    = array();
        $this->_values = array();

        $this->_currentQuerySql = '';
        $this->_isDirty = false;
        return $this;
    }

    /**
     * @param string $part
     *
     * @return $this
     */
    public function resetPart($part)
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
            $this->_isDirty = true;
        }

        return $this;
    }
}
