<?php
namespace Zanson\DebugHelper;

/**
 * Class Output
 * This class emulates the functionality of JS Console.log
 * you can pass any number of values to the function and they will be printed.
 * passing an array as argument will be parsed as print_r with the proper indentation level.
 *
 */
class Output
{
    /**
     * @var int Print 4 spaces per indent to help read output
     */
    public static $indent    = 0;
    public static $maxIndent = 6;
    /**
     * File Stream to output to
     * @var resource
     */
    public static $stdout = null;
    /**
     * Coloring on or off
     * @var bool
     */
    public static $colors = true;

    public static $errorPath = null;

    /**
     * Ansi Coloring
     * @var bool
     */
    public static $ansi = true;

    public static function init(){
        if(php_sapi_name() === 'cli'){
            self::$stdout = STDOUT;
        } else {
            if(!empty(self::$errorPath)){
                self::$stdout = fopen(self::$errorPath, 'a+');
            }
        }
    }

    /**
     * @param string $addon or prefix to the log line
     * @param array  $args  the args from the originating function as an array
     */
    public static function setArgs($addon, $args) {
        if (is_array($addon)) {
            $addon = implode('', $addon);
        }
        self::parseArgs($args);

        if (empty($args)) {
            $args[0] = $addon;
        } else {
            $args[0] = $addon . $args[0];
        }
        call_user_func_array(__NAMESPACE__ . '\Output::write', $args);
    }

    /**
     * Set colors if enabled for the app
     *
     * @param string $fg
     * @param string $bg
     * @param bool   $bold
     *
     * @return string
     */
    private static function getColor($fg = null, $bg = null, $bold = false) {
        $ret = '';
        if (self::$colors) {
            if ($bg) {
                if (self::$ansi) {
                    $ret .= Color\Console::bgRGB($bg);
                }
            }
            if ($fg) {
                if (self::$ansi) {
                    $ret .= Color\Console::fgRGB($fg, $bold);
                }
            } else {
                if ($bold) {
                    if (self::$ansi) {
                        $ret .= Color\Console::BOLD;
                    }
                }
            }
        }

        return $ret;
    }

    private static function resetColor() {
        if (self::$colors) {
            return Color\Console::RESET;
        }

        return '';
    }

    /**
     * parse the args that are arrays to print_r
     *
     * @param array $args
     */
    private static function parseArgs(&$args) {
        foreach ($args as &$arg) {
            if (is_array($arg) || is_object($arg)) {
                $arg = print_r($arg, true);
            }
        }
    }

    /**
     * Allows a string or function call to be prepended to all output.
     * Good for adding timestamps to output
     *
     * @return string|void
     */
    private static function getPrepend(){
        if(is_callable(self::$prepend)){
            $pre = self::$prepend;
            return $pre().' ';
        } else if(!is_null(self::$prepend)){
            return self::$prepend.' ';
        } else {
            return;
        }
    }

    /**
     * Main output function
     */
    public static function write() {
        if (empty(self::$stdout)) {
            return;
        }

        if (self::$indent < 0) {
            self::$indent = 0;
        }

        $args = func_get_args();
        self::parseArgs($args);

        $args = implode(' ', $args);
        $args = explode("\n", $args);

        $useIndent = (self::$indent <= self::$maxIndent) ? self::$indent : self::$maxIndent;
        foreach ($args as $key => $msg) {
            $args[$key] = str_pad('', $useIndent * 4, " ", STR_PAD_LEFT) . $msg;
        }

        if(is_resource(self::$stdout))
        {
            fwrite(self::$stdout, implode("\n", $args) . ' ' . self::resetColor() . "\n");
        }
    }

    public static function logb() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', false, true), $args);
    }

    public static function log() {
        $args = func_get_args();
        self::setArgs(self::getColor('#999999', false, true), $args);
    }

    public static function info() {
        $args = func_get_args();
        self::setArgs(self::getColor(false, false, true) . 'Info: ' . self::resetColor(), $args);
    }

    public static function error() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', '#FF0000', true) . ' ', $args);
    }

    public static function warn() {
        $args = func_get_args();
        self::setArgs(self::getColor('#ffd200', false, true) . 'Warning! ', $args);
    }

    public static function warnColor() {
        $args = func_get_args();
        self::setArgs(self::getColor('#ffd200', false, true), $args);
    }

    public static function alert() {
        $args = func_get_args();
        self::setArgs(self::getColor('#ff8400'), $args);
    }


    public static function section() {
        $args = func_get_args();
        self::log();
        self::setArgs(self::getColor('#00baff', false, true), $args);
    }

    public static function ok() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', '#27cf23', true) . ' ', $args);
    }

    public static function saved() {
        $args = func_get_args();
        self::setArgs(self::getColor('#27cf23', false, true) . ' ', $args);
    }

    public static function page() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', '#4301ba', true) . ' ', $args);
    }

    public static function question() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', '#0765ce', true) . ' ', $args);
    }

    public static function subQuestion() {
        $args = func_get_args();
        self::setArgs(self::getColor('#FFFFFF', '#07929e', true) . ' ', $args);
    }

    public static function indentUp() {
        return self::$indent++;
    }

    public static function indentDown() {
        return self::$indent--;
    }

    public static function setIndent($value = 0) {
        return self::$indent = $value;
    }

    public static function getIndent() {
        return self::$indent;
    }
}