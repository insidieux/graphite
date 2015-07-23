<?php
namespace Graphite\Db;

class ResultSet
{
    /**
     * @var  \PDOStatement
     */
    private $stmt;

    /**
     * @param \PDOStatement $stmt Prepared & executed PDO statement
     */
    public function __construct(\PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * @return \PDOStatement
     */
    public function getRawStmt()
    {
        return $this->stmt;
    }

    /**
     * Вернет все строки в виде ассоциативного массива. Массив будет проиндексирован в порядке добавления
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Returns all result rows as assoc arrays indexed by $indexBy column
     *
     * Example table columns:
     *
     * ```
     * id,  age, sex
     * --------------
     * 10   16   1
     * 20   18   2
     * 30   21   1
     *
     * fetchAllIndexed() -> [
     *   10 => [10, 16, 1],
     *   20 => [20, 18, 2],
     *   30 => [30, 21, 1]
     * ];
     *
     * fetchAllIndexed('age') -> [
     *   16 => [10, 16, 1],
     *   18 => [20, 18, 2],
     *   21 => [30, 21, 1]
     * ];
     * ```
     *
     * @param string $indexBy Column name to index by. By default indexing by first column
     *
     * @return array
     */
    public function fetchAllIndexed($indexBy = null)
    {
        $res = array();

        while (($row = $this->stmt->fetch()) !== false) {
            if ($indexBy === null) {
                $indexBy = key($row);
            }
            $res[$row[$indexBy]] = $row;
        }

        return $res;
    }

    /**
     * Вернет все строки в виде ассоциативного массива, сгруппированные по указанной колонке.
     *
     * Example table columns:
     * ```
     * id,  age, sex
     * --------------
     * 10   16   1
     * 20   18   2
     * 30   21   1
     *
     * fetchAllGrouped('sex') -> [
     *   1 => [0 => [10, 16, 1], 1 => [30, 21, 1]]
     *   2 => [0 => [20, 18, 2]]
     * ]
     *
     * fetchAllGrouped('sex', 'id') -> [
     *   1 => [10 => [10, 16, 1], 30 => [30, 21, 1]]
     *   2 => [20 => [20, 18, 2]]
     * ]
     * ```
     *
     * @param string $groupBy Имя колонки для группировки
     * @param string $indexBy Имя колонки для индексации внутри группы. Если не передано - проиндексируется в порядке
     *                        добавления
     *
     * @return array
     */
    public function fetchAllGrouped($groupBy, $indexBy = null)
    {
        $res = array();

        while (($row = $this->stmt->fetch()) !== false) {
            if ($indexBy === null) {
                $res[$row[$groupBy]][] = $row;
            } else {
                $res[$row[$groupBy]][$row[$indexBy]] = $row;
            }
        }

        return $res;
    }

    /**
     * Вернет одну строку из результата
     *
     * @return array
     */
    public function fetchRow()
    {
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        return $result === false ? [] : (array) $result;
    }

    /**
     * Вернет все строки из результата, содержащие только 1 колонку
     *
     * @return array
     */
    public function fetchColumn()
    {
        return $this->stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Вернет все строки в виде ассоциативного массива, где key - значение первой колонки, value - значение второй
     *
     * @return array
     */
    public function fetchPairs()
    {
        return $this->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Вернет значение первой колонки первой строки. Для выборки скалярных/одиночных значений
     *
     * @return string
     */
    public function fetchOne()
    {
        return $this->stmt->fetchColumn(0);
    }

    /**
     * Вернет все строки результата как массив объектов указанного класса.
     * Если класс не указан - объекты будут \StdClass
     *
     * @param string $className
     *
     * @return array
     */
    public function fetchClass($className = null)
    {
        if ($className === null) {
            $className = '\StdClass';
        }
        return $this->stmt->fetchAll(\PDO::FETCH_CLASS, $className);
    }

    /**
     * Вернет кол-во строк затронутых запросом
     *
     * @return int
     */
    public function getRowCount()
    {
        return $this->stmt->rowCount();
    }
}
