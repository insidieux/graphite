<?php
namespace Graphite\Helper;

class Html
{
    /**
     * @param array $attrs
     *
     * @return string
     */
    public static function renderAttrs(array $attrs)
    {
        $result = [];

        foreach ($attrs as $key => $value) {
            $result[] = is_int($key) ? $value : "$key=\"$value\"";
        }

        return implode(' ', $result);
    }
}