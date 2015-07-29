<?php
namespace Zanson\DebugHelper\Color;

class Console
{
    const   RESET     = "\033[0m";
    const   BOLD      = "\033[1m";
    const   ITALIC    = "\033[3m";
    const   UNDERLINE = "\033[4m";

    public static function fgRGB($fg,  $bold = false) {
        return self::fg(Ansi::toAnsi($fg), $bold);
    }

    public static function bgRGB($r, $g = null, $b = null) {
        return self::bg(Ansi::toAnsi($r, $g, $b));
    }

    public static function fg($color, $bold = false) {
        if ($bold) {
            return "\033[1;38;5;" . $color . "m";
        }

        return "\033[38;5;" . $color . "m";
    }

    public static function bg($color) {
        return "\033[48;5;" . $color . "m";
    }
}