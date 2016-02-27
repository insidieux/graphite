<?php
namespace Graphite\Db;

class SqlParser
{
    /**
     * Split sql with many queries to an array of queries.
     *
     * @param string $sql
     *
     * @return array|bool
     */
    public static function splitQueries($sql)
    {
        if (preg_match_all('@(?<!\\\)\'|"|;@', $sql, $matches, PREG_OFFSET_CAPTURE)) {
            $in = false;
            $offset = 0;
            $queries = [];

            foreach ($matches[0] as $pos) {
                if ($pos[0] == ';') {
                    if (!$in) {
                        $query = substr($sql, $offset, $pos[1] + 1 - $offset);
                        $queries[] = trim($query, "\r\n\t ");
                        $offset = $pos[1] + 1;
                    }
                } else {
                    // found a quote char - toggle in-quotes state
                    $in = !$in;
                }
            }

            return $queries;
        } else {
            return false;
        }
    }

    /**
     * Parse table name from query
     *
     * @param string $sql
     *
     * @return string|bool
     */
    public static function parseTableName($sql)
    {
        $patternsMap = [
            'select' => 'select(?:.*)\s+from',
            'insert' => 'insert(?:\s+|ignore|low_priority|high_priority|delayed|into)*',
            'update' => 'update(?:\s+|ignore|low_priority)*',
            'delete' => 'delete(?:.*)\s+from',
            'create' => 'create(?:\s+temporary)*\s+table(?:\s+if\s+not\s+exists)*',
            'alter'  => 'alter(?:\s+ignore)\s+table',
        ];

        $map = strtolower(strstr($sql, ' ', true));

        if (!isset($patternsMap[$map])) {
            return false;
        }

        $pattern = "/^{$patternsMap[$map]}\\s+([`A-Za-z0-9\$_.]+)/i";

        if (!preg_match($pattern, $sql, $matches)) {
            return false;
        }

        if (($pos = strpos($matches[1], '.')) !== false) {
            $matches[1] = substr($matches[1], $pos + 1);
        }

        return trim($matches[1], "`");
    }
}
