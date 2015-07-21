<?php
namespace Graphite\Db\Query;

class Insert extends AbstractQuery
{
    protected $values = array();

    /**
     * @param string $table
     *
     * @return static
     */
    public function into($table)
    {
        return $this->table($table);
    }

    /**
     * @param array $values
     *
     * @return static
     */
    public function values($values)
    {
        if (is_int(key($values))) {
            $this->values = array_merge($this->values, $values);
        } else {
            $this->values[] = $values;
        }

        return $this;
    }

    /**
     * @param bool $flag
     *
     * @return static
     */
    public function onDuplicateKeyIgnore($flag = true)
    {
        $this->flags['IGNORE'] = (bool) $flag;
        return $this;
    }

    /**
     * @param array $set
     *
     * @return static
     */
    public function onDuplicateKeyUpdate($set)
    {
        return $this->set($set);
    }

    /**
     * @return string
     */
    public function toString()
    {
        $insert = array('INSERT');

        // ignore flag
        if (isset($this->flags['IGNORE']) && $this->flags['IGNORE']) {
            $insert[] = 'IGNORE';
        }

        // table name
        $insert[] = 'INTO ' . $this->makeTable();

        if (!empty($this->values)) {

            // columns (get from first row)
            $columns = array_keys(reset($this->values));
            $insert[] = '(' . implode(', ', $this->conn->quoteNames($columns)) . ')';

            // values
            $values = array();
            foreach ($this->values as $row) {
                $values[] = implode(',', $this->conn->quoteValues($row));
            }
            $insert[] = 'VALUES (' . implode('), (', $values) . ')';
        }

        // duplicate update
        if (!empty($this->set)) {
            $insert[] = 'ON DUPLICATE KEY UPDATE ' . $this->makeSet();
        }

        return implode(' ', $insert);
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->values = [];
        return parent::clear();
    }
}
