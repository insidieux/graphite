<?php
namespace Graphite\Db;

class Profiler
{
    const QTYPE_CONNECT = 1;
    const QTYPE_SELECT  = 2;
    const QTYPE_INSERT  = 4;
    const QTYPE_UPDATE  = 8;
    const QTYPE_DELETE  = 16;

    /**
     * @var array
     */
    protected $_log = array();

    /**
     * @var array|null
     */
    protected $_query;

    /**
     * @var string
     */
    protected $_staticCaller;

    /**
     * @var string
     */
    protected $_basePath;

    /**
     * @var callable
     */
    protected $_callback;

    /**
     * Установить вызывающий класс, если вызов идет из статического метода
     * @param string $class
     */
    public function setStaticCaller($class)
    {
        $this->_staticCaller = $class;
    }

    /**
     * Базовый путь, который будет выризаться при определении пути к файлам
     * @param string $path
     */
    public function setBasePath($path)
    {
        $this->_basePath = $path;
    }

    /**
     * Установить функцию (php callable) которая будет вызыватсья при start и stop
     * @param callable $callback
     */
    public function setCallback($callback)
    {
        if (is_callable($callback)) {
            $this->_callback = $callback;
        }
    }

    /**
     * @param string $sql
     */
    public function start($sql)
    {
        // find caller
        $context   = array();
        $ns        = str_replace('\\', '/', __NAMESPACE__);
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($backtrace as $i => $trace) {
            if (strpos($trace['file'], $ns) === false) {

                switch ($trace['class']) {
                    case 'Graphite\Db\Connection':
                    case 'Graphite\Db\QueryBuilder':
                    case 'Graphite\Db\Query\AbstractQuery': {
                        $next = $backtrace[$i+1];
                        $initiator = $this->_formatClass($next['class'], $next['type'], $next['function']);
                        break;
                    }
                    case 'Graphite\Db\ActiveRecord': {
                        $initiator = $this->_formatClass($this->_staticCaller, $trace['type'], $trace['function']);
                        break;
                    }
                    case 'Graphite\Db\ActiveRecord\Finder': {
                        $initiator = $this->_formatClass($this->_staticCaller, $trace['type'], 'find');
                        break;
                    }
                    default: {
                        $initiator = $this->_formatClass($trace['class'], $trace['class'], $trace['function']);
                    }
                }

                $context['initiator'] = $initiator;
                $context['file'] = empty($this->_basePath) ? $trace['file'] : str_replace($this->_basePath, '', $trace['file']);
                $context['line'] = $trace['line'];

                break;
            }
        }

        $this->_query = array(
            'sql'     => $sql,
            'start'   => microtime(true),
            'type'    => $this->parseType($sql),
            'table'   => $this->parseTable($sql),
            'context' => $context,
        );

        if ($this->_callback) {
            call_user_func($this->_callback, 'start', $this->_query);
        }
    }

    /**
     * @param int $numRows кол-во строк в результате
     *
     * @return array|null
     */
    public function stop($numRows = 0)
    {
        $query = $this->_query;
        $query['stop'] = microtime(true);
        $query['time'] = $query['stop'] - $query['start'];
        $query['rows'] = (int) $numRows;

        $this->_log[] = $query;

        $this->_query = null;

        if ($this->_callback) {
            call_user_func($this->_callback, 'stop', $query);
        }

        return $query;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->_log;
    }

    /**
     * @return array
     */
    public function getLast()
    {
        return end($this->_log);
    }

    /**
     * @param string $sql
     *
     * @return int Profiler::QTYPE_* constant
     */
    public function parseType($sql)
    {
        $typeConst = __CLASS__ . '::QTYPE_' . strtoupper(strstr($sql, ' ', true));
        return defined($typeConst) ? constant($typeConst) : 0;
    }

    /**
     * Parse table name from sql
     *
     * @param string $sql
     *
     * @return string
     */
    public function parseTable($sql)
    {
        $tablePattern = '`?([^ `]+)`?';
        switch ($this->parseType($sql)) {
            case self::QTYPE_SELECT : {
                $pattern = "/^select(?:.*) from $tablePattern/i";
                break;
            }
            case self::QTYPE_INSERT : {
                $pattern = "/^insert(?: |LOW_PRIORITY|DELAYED|IGNORE)* into $tablePattern/i";
                break;
            }
            case self::QTYPE_UPDATE : {
                $pattern = "/^update(?: |LOW_PRIORITY|IGNORE)* $tablePattern/i";
                break;
            }
            case self::QTYPE_DELETE : {
                $pattern = "/^delete(?:.*) from $tablePattern/i";
                break;
            }
            case self::QTYPE_CONNECT :
            default: {
                return '';
            }
        }

        if (preg_match($pattern, $sql, $match)) {
            return empty($match[1]) ? '' : $match[1];
        } else {
            return '';
        }
    }

    /**
     * @param string $class
     * @param string $type
     * @param string $function
     *
     * @return string
     */
    private function _formatClass($class, $type, $function)
    {
        return $class . $type . $function . '()';
    }
}
