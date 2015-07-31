<?php
/**
 * @author Jason Watt
 * @Date   12/11/12
 * @Time   2:41 PM
 */

namespace Zanson\DebugHelper\Exception;

use Exception;
use Zanson\DebugHelper\Color\Console;
use Zanson\DebugHelper\Output;


/**
 * Creates exception, error, and fatal handlers and
 * prints them in a more readable way to the log/console.
 */
class Handler
{
    private $rethrow;

    public $error      = true;
    public $warning    = true;
    public $notice     = true;
    public $deprecated = true;
    public $strict     = true;

    public function __construct()
    {
        set_exception_handler(array($this, 'handler'));
        set_error_handler(array($this, 'errorHandler'));
        register_shutdown_function( array($this, 'fatalHandler'));
    }

    public function __destruct()
    {
        if ($this->rethrow)
        {
            throw $this->rethrow;
        }
    }

    private function MakePrettyException($trace)
    {
        //$trace = $e->getTrace();
        Output::log();
        Output::log(Console::BOLD . 'Stack Trace:');
        foreach ($trace as $k => $t)
        {
            $after = '';
            $arrayCount = 1;
            $line = '#' . $k . ' ';
            if (!empty($t['file']))
            {
                $line .= $t['file'];
            }
            if (!empty($t['line']))
            {
                $line .= '(' . Console::BOLD . $t['line'] . Console::RESET . ') ';
            }
            if (!empty($t['class']))
            {
                $line .= Console::fgRGB('#ff6800', true) . $t['class'] . Console::RESET;
            }
            if (!empty($t['type']))
            {
                $line .= $t['type'];
            }
            if (!empty($t['function']))
            {
                $line .= Console::fgRGB('#ffea00', true) . $t['function'] . Console::RESET . '( ';
            }
            if (!empty($t['args']))
            {
                foreach ($t['args'] as $a)
                {
                    switch (gettype($a))
                    {
                        case"boolean":
                            $line .= Console::fgRGB('#ffea00');
                            break;
                        case"integer":
                            $line .= Console::fgRGB('#07a730');
                            break;

                        case "string":
                            $line .= Console::fgRGB('#0090ff');
                            break;
                        case 'object';
                            $line .= Console::fgRGB('#ff6800');
                            $a = get_class($a);
                            break;
                        case 'array';
                            $line .= Console::fgRGB('#baff00');
                            $after .= Console::fgRGB('#baff00') . 'Array[' . $arrayCount . ']: ' . Console::RESET;
                            $json = json_encode($a);
                            $after .= substr($json, 0, 500) . (strlen($json) > 500 ? Console::fgRGB('#baff00') . '[more not shown]' . Console::RESET : '');
                            unset($json);
                            $a = 'Array[' . $arrayCount . ']';
                            $arrayCount++;
                            break;
                        case 'NULL';
                            $a = 'NULL';
                            break;
                        default:
                            $line .= Console::fgRGB('#ffffff');
                    }
                    $line .= $a . Console::RESET;
                    if ($k < count($t['args']) - 1)
                    {
                        $line .= ', ';
                    }
                }
            }
            $line .= " )";
            Output::log($line);
            if (!empty($after))
            {
                Output::$indent++;
                Output::log($after);
                Output::log();
                Output::$indent--;
            }
        }
    }

    function fatalHandler() {
        $errno   = E_CORE_ERROR;
        $errfile = "unknown file";
        $errstr  = "shutdown";
        $errline = 0;

        $error = error_get_last();

        if( $error !== NULL) {
            if(!empty($error["type"])) {
                $errno = $error["type"];
            }
            if(!empty($error["file"])) {
                $errfile = $error["file"];
            }
            if(!empty($error["line"])) {
                $errline = $error["line"];
            }
            if(!empty($error["message"])) {
                $errstr = $error["message"];
            }

            $this->errorHandler($errno, $errstr, $errfile, $errline);
        }
    }

    public function errorHandler($errno, $message, $errfile, $errline)
    {
        Output::$indent = 0;
        switch ($errno)
        {
            case E_USER_ERROR:
            case E_ERROR:
                if (!$this->error)
                {
                    return;
                }
                Output::log('');
                Output::error('Error:', $message);
                break;

            case E_USER_WARNING:
            case E_WARNING:
                if (!$this->warning)
                {
                    return;
                }
                Output::log('');
                Output::warnColor('Warning:', $message);
                break;

            case E_USER_NOTICE:
            case E_NOTICE:
                if (!$this->notice)
                {
                    return;
                }
                Output::log('');
                Output::logb('Notice:', $message);
                break;

            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                if (!$this->deprecated)
                {
                    return;
                }
                Output::log('');
                Output::warnColor('Deprecated:', $message);
                break;

            case E_STRICT:
                if (!$this->strict)
                {
                    return;
                }
                Output::log('');
                Output::warnColor('Strict:', $message);
                break;
            default:
                Output::error($errno, $message);
                break;
        }
        Output::log('');
        Output::$indent++;
        $handle = @fopen($errfile, "r");
        if ($handle)
        {
            $code = array();
            $line = 1;
            while (($buffer = fgets($handle)) !== false)
            {
                if ($line > $errline - 5 && $line < $errline + 5)
                {
                    $code[] = array('line' => $line, 'code' => $buffer);
                }
                if ($line > $errline + 5)
                {
                    break;
                }
                $line++;
            }
            fclose($handle);
            Output::log(Console::BOLD . 'File Preview:', $errfile . '(' . $errline . ')');
            foreach ($code as $c) {
                $line = str_replace("\n", '', $c['line'] . ': ' . $c['code']);

                if ($c['line'] == $errline) {
                    $line = Console::bgRGB('#9c7a04') . Console::fgRGB('#FFFFFF', true) . $line;
                }
                Output::log($line);
            }
        }

        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $this->MakePrettyException($backtrace);
        Output::log();
    }

    public function handler($e)
    {
        if ($e instanceof \Exception)
        {
            $type = 'Internal-Exception';
            $message = sprintf($type . ': "%s" thrown at %s(%d)', $e->getMessage(), $e->getFile(), $e->getLine());
        }
        else
        {
            $type = 'PHP-Exception';
            $message = sprintf($type . '"%s" thrown at %s(%d)', $e->getMessage(), $e->getFile(), $e->getLine());
        }
        Output::$indent = 0;
        Output::log('');
        Output::error($message);
        Output::log('');
        Output::$indent++;
        $handle = @fopen($e->getFile(), "r");
        if ($handle)
        {
            $code = array();
            $line = 1;
            while (($buffer = fgets($handle)) !== false)
            {
                if ($line > $e->getLine() - 5 && $line < $e->getLine() + 5)
                {
                    $code[] = array('line' => $line, 'code' => $buffer);
                }
                if ($line > $e->getLine() + 5)
                {
                    break;
                }
                $line++;
            }
            fclose($handle);
            Output::log(Console::BOLD . 'File Preview:', $e->getFile() . '(' . $e->getLine() . ')');
            foreach ($code as $c)
            {
                $line = str_replace("\n", '', $c['line'] . ': ' . $c['code']);

                if ($c['line'] == $e->getLine())
                {
                    $line = Console::bgRGB('#9c7a04') . Console::fgRGB('#FFFFFF', true) . $line;
                }
                Output::log($line);
            }
        }

        $this->MakePrettyException($e->getTrace());
        Output::log();
    }
}