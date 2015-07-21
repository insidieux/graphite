<?php
namespace Graphite\Db\Query;

class Delete extends AbstractQuery
{
    /**
     * @param string $table
     *
     * @return static
     */
    public function from($table)
    {
        return $this->table($table);
    }

    /**
     * @return string
     */
    public function toString()
    {
        $query = [
            'DELETE',
            'FROM ' . $this->makeTable()
        ];

        if (($where = $this->makeWhere()) != '') {
            $query[] = 'WHERE ' . $where;
        }

        if (($order = $this->makeOrderBy()) != '') {
            $query[] = 'ORDER BY ' . $order;
        }

        if ($this->limit) {
            $query[] = 'LIMIT ' . $this->limit;
        }

        return implode(' ', $query);
    }
}
