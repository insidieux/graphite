<?php
namespace Graphite\Db\Query;

class Update extends AbstractQuery
{
    /**
     * @return string
     */
    public function toString()
    {
        $sql = [
            'UPDATE ' . $this->makeTable(),
        ];

        if ($set = $this->makeSet()) {
            $sql[] = 'SET ' . $set;
        }

        if (($where = $this->makeWhere()) != '') {
            $sql[] = 'WHERE ' . $where;
        }

        IF (($order = $this->makeOrderBy()) != '') {
            $sql[] = "ORDER BY $order";
        }

        if (!empty($this->limit)) {
            $sql[] = 'LIMIT ' . $this->limit;
        }

        return implode(' ', $sql);
    }
}
