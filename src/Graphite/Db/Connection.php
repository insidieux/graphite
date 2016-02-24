<?php
namespace Graphite\Db;

class Connection
{
    /**
     * @var \PDO|null
     */
    private $pdo = null;

    /**
     * @var array
     */
    private $connOpts = array(
        'db_host'   => '',
        'db_port'   => '',
        'db_name'   => '',
        'username'  => '',
        'password'  => '',
        'charset'   => 'UTF8',
        'attr'      => array(),
    );

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct($options)
    {
        // check environment
        if (!extension_loaded('pdo')) {
            throw new Exception('DB ERROR: PDO extension is not loaded!');
        }
        if (!in_array('mysql', \PDO::getAvailableDrivers())) {
            throw new Exception('DB ERROR: PDO MySQL driver is missed!');
        }

        // set options
        foreach ($this->connOpts as $name => $value) {
            if (array_key_exists($name, $options)) {
                $this->connOpts[$name] = $options[$name];
            }
        }

        // check required options
        if (empty($this->connOpts['db_host'])) {
            throw new Exception('DB ERROR: connection option "db_host" can`t be empty!');
        }

        if (empty($this->connOpts['db_name'])) {
            throw new Exception('DB ERROR: connection option "db_name" can`t be empty');
        }

        if (empty($this->connOpts['username'])) {
            throw new Exception('DB ERROR: connection option "username" can`t be empty');
        }
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->pdo instanceof \PDO;
    }

    /**
     * Try to connect to database
     *
     * @return \PDO
     * @throws Exception
     */
    public function connect()
    {
        if (!$this->isConnected()) {

            $dsn = 'mysql:host=' . $this->connOpts['db_host'] . ';dbname=' . $this->connOpts['db_name'];
            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "' . $this->connOpts['charset'] . '";'
            );

            // @todo 1. валидация 2. setAttributes
            if (!empty($this->connOpts['attr'])){
                $options = $options + $this->connOpts['attr'];
            }

            $profilerEnabled = $this->isProfilerEnabled();

            try {
                if ($profilerEnabled) {
                    $this->profiler->start('CONNECT');
                }

                $this->pdo = new \PDO($dsn, $this->connOpts['username'], $this->connOpts['password'], $options);
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

                if ($profilerEnabled) {
                    $this->profiler->stop();
                }

            } catch (\PDOException $e) {
                throw new Exception('DB CONNECT ERROR: '.$e->getMessage());
            }
        }

        return $this->pdo;
    }

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @return Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * @return bool
     */
    public function isProfilerEnabled()
    {
        return ($this->profiler instanceof Profiler);
    }

    /**
     * @return \PDO|null
     */
    public function getPdoInstance()
    {
        return $this->pdo;
    }

    /**
     * Retrieve a database connection attribute
     *
     * @param int $attr \PDO::ATTR_* constant
     *
     * @see http://php.net/manual/en/pdo.getattribute.php
     *
     * @return mixed
     */
    public function getAttribute($attr)
    {
        return $this->connect()->getAttribute($attr);
    }

    /**
     * @param int $attr
     * @param int $value
     *
     * @return bool
     */
    public function setAttribute($attr, $value)
    {
        return $this->connect()->setAttribute($attr, $value);
    }

    /**
     * @return mixed
     */
    public function getServerInfo()
    {
        return $this->getAttribute(\PDO::ATTR_SERVER_INFO);
    }

    /**
     * @return mixed
     */
    public function getServerVersion()
    {
        return $this->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * @return mixed
     */
    public function getClientVersion()
    {
        return $this->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Quote database, table, or column names.
     * ```
     * '*'       -> '*'
     * 'col'     -> '`col`'
     * 'tbl.col' -> '`tbl`.`col`'
     * ```
     *
     * @param string|Expr $name
     *
     * @return string
     */
    public function quoteName($name)
    {
        if (empty($name)) {
            return '';
        }

        if ($name == '*') {
            return $name;
        }

        if ($name instanceof Expr) {
            return $name->get();
        }

        // try to quote name with table
        if (($pos = strpos($name, '.')) !== false) {
            $name = explode('.', $name);
            foreach ($name as &$val) {
                if ($val != '*') {
                    $val = "`$val`";
                }
            }
            return implode('.', $name);
        }

        return "`$name`";
    }

    /**
     * Quote an array of database, table, or column names. quoting rules the same as Connection::quoteName
     *
     * @param string[] $names
     *
     * @return array
     */
    public function quoteNames(array $names)
    {
        foreach ($names as $key => $name) {
            $names[$key] = $this->quoteName($name);
        }

        return $names;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function quoteValue($value)
    {
        if ($value instanceof Expr) {
            return $value->get();
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quoteValue($val);
            }
            return implode(', ', $value);
        }

        return $this->connect()->quote($value);
    }

    /**
     * @param array $values
     *
     * @return array
     */
    public function quoteValues($values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->quoteValue($value);
        }

        return $values;
    }

    /**
     * Replace "?" in a string by quoted values
     *
     * @param string           $string
     * @param string|int|array $value
     *
     * @return string
     */
    public function quoteInString($string, $value)
    {
        if (!is_array($value)) {
            $value = (array) $value;
        }

        if (empty($string) || count($value) == 0) {
            return $string;
        }

        if (preg_match_all('/\?/', $string, $matches, PREG_OFFSET_CAPTURE)) {

            // если вопросик 1, а параметров массив - заменяем сразу (для IN конструкций)
            if (count($matches[0]) == 1) {
                $value = array($value);
            }

            $offsetDelta = 0;
            foreach ($value as $index => $replace) {
                if (array_key_exists($index, $matches[0])) {
                    $quotedValue = $this->quoteValue($replace);
                    $string = substr($string, 0, $matches[0][$index][1] + $offsetDelta) .
                        $quotedValue .
                        substr($string, $matches[0][$index][1] + $offsetDelta + 1);
                    $offsetDelta += strlen($quotedValue) - 1;
                } else {
                    break;
                }
            }
        }

        return $string;
    }

    /**
     * @param string $sql
     * @param array  $binds
     * @param string $method
     *
     * @throws Exception
     * @return int|ResultSet
     */
    private function _execute($sql, $binds = array(), $method = 'query')
    {
        $this->connect();

        if (!empty($binds)) {
            $sql = $this->quoteInString($sql, $binds);
        }

        try {
            $profilerEnabled = $this->isProfilerEnabled();

            if ($profilerEnabled) {
                $this->profiler->start($sql);
            }

            $result = $this->pdo->$method($sql);

            if ($profilerEnabled) {
                $rows = ($result instanceof \PDOStatement) ? $result->rowCount() : 0;
                $this->profiler->stop($rows);
            }

            return ($method == 'query') ? new ResultSet($result) : $result;

        } catch (\PDOException $e) {
            throw new Exception('DB QUERY ERROR: ' . $e->getMessage());
        }
    }

    /**
     * @param string $sql
     * @param array  $binds
     *
     * @return ResultSet
     */
    public function query($sql, $binds = array())
    {
        return $this->_execute($sql, $binds, 'query');
    }

    /**
     * @param string $sql
     * @param array  $binds
     *
     * @return int
     */
    public function execute($sql, $binds = array())
    {
        return $this->_execute($sql, $binds, 'exec');
    }

    /**
     * Returns the ID of the last inserted row
     *
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->connect()->lastInsertId();
    }

    /**
     * @return Query\Select
     */
    public function select()
    {
        return new Query\Select($this);
    }

    /**
     * @return Query\Insert
     */
    public function insert()
    {
        return new Query\Insert($this);
    }

    /**
     * @return Query\Update
     */
    public function update()
    {
        return new Query\Update($this);
    }

    /**
     * @return Query\Delete
     */
    public function delete()
    {
        return new Query\Delete($this);
    }

    /**
     * Wraps string by Expr object
     *
     * @param string $string
     *
     * @return Expr
     */
    public function expr($string)
    {
        return new Expr($string);
    }
}
