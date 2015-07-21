<?php
namespace Graphite\Helper;

class Cli
{
    const COLOR_FG_BLACK        = '0;30';
    const COLOR_FG_DARK_GRAY    = '1;30';
    const COLOR_FG_BLUE         = '0;34';
    const COLOR_FG_LIGHT_BLUE   = '1;34';
    const COLOR_FG_GREEN        = '0;32';
    const COLOR_FG_LIGHT_GREEN  = '1;32';
    const COLOR_FG_CYAN         = '0;36';
    const COLOR_FG_LIGHT_CYAN   = '1;36';
    const COLOR_FG_RED          = '0;31';
    const COLOR_FG_LIGHT_RED    = '1;31';
    const COLOR_FG_PURPLE       = '0;35';
    const COLOR_FG_LIGHT_PURPLE = '1;35';
    const COLOR_FG_BROWN        = '0;33';
    const COLOR_FG_YELLOW       = '1;33';
    const COLOR_FG_LIGHT_GRAY   = '0;37';
    const COLOR_FG_WHITE        = '1;37';

    const COLOR_BG_BLACK      = '40';
    const COLOR_BG_RED        = '41';
    const COLOR_BG_GREEN      = '42';
    const COLOR_BG_YELLOW     = '43';
    const COLOR_BG_BLUE       = '44';
    const COLOR_BG_MAGENTA    = '45';
    const COLOR_BG_CYAN       = '46';
    const COLOR_BG_LIGHT_GRAY = '47';

    /**
     * @param string $string
     * @param string $foreground
     * @param string $background
     *
     * @return string
     */
    public static function getColoredString($string, $foreground = null, $background = null)
    {
        $colored = '';

        if ($foreground !== null) {
            $colored .= "\033[" . $foreground . 'm';
        }

        if ($background !== null) {
            $colored .= "\033[" . $background . 'm';
        }

        return empty($colored)
            ? $string
            : $colored . $string . "\033[0m";
    }
}