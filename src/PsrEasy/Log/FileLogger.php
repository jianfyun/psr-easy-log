<?php
namespace PsrEasy\Log;

use Psr\Log\AbstractLogger;

/**
 * File logger instance.
 *
 * @uses    Psr\Log\AbstractLogger
 * @package PsrEasy\Log
 * @see     http://www.php-fig.org/psr/psr-3/
 */
class FileLogger extends AbstractLogger
{
    /**
     * Absolute path of log directory.
     *
     * @var string
     * @access private
     */
    private $logDir = '';

    /**
     * Unique ID for process.
     *
     * @var string
     * @access private
     */
    private $uniqid = '';

    /**
     * Format for log message.
     *
     * Default: [$time]\t[$address]\t[$uniqid]\t[$file $line]\t[$message]\t[$context]\n
     *
     * @var string
     * @access private
     */
    private $format = "[%s]\t[%s]\t[%s]\t[%s %s]\t[%s]\t[%s]\n";

    /**
     * Number of stack frames returned by debug_backtrace.
     *
     * @var int
     * @access private
     */
    private $limit = 1;

    /**
     * Source code directory path.
     *
     * @var string
     * @access private
     */
    private $srcDir = '/src/';

    /**
     *
     * Available log levels.
     *
     * @var array
     * @access private
     */
    private $levels = [];

    /**
     * __construct
     *
     * @param  string $logDir Log directory path.
     * @access public
     * @return void
     */
    public function __construct($logDir)
    {
        $this->logDir = $logDir;
    }

    /**
     * Return an instance with the unique ID for process.
     *
     * @param  string $uniqid Unique ID.
     * @access public
     * @return self
     */
    public function withUniqid($uniqid)
    {
        $this->uniqid = $uniqid;
        return $this;
    }

    /**
     * Return an instance with the custom format for log message.
     *
     * @param  string $format Message format.
     * @access public
     * @return self
     */
    public function withFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Return an instance with the limit of stack frames returned by debug_backtrace.
     *
     * @param  int $limit Limit number for debug_backtrace.
     * @access public
     * @return self
     */
    public function withBacktraceLimit($limit)
    {
        if (is_numeric($limit) && $limit > 0) {
            $this->limit = intval($limit);
        }

        return $this;
    }

    /**
     * Return an instance with the source code directory path.
     *
     * @param  string $srcDir Source code directory path.
     * @access public
     * @return self
     */
    public function withSrcDir($srcDir)
    {
        $this->srcDir = $srcDir;
        return $this;
    }

    /**
     * Return an instance with the available log levels.
     *
     * @param  array $levels Available log levels. All levels are available by default.
     * @access public
     * @return self
     */
    public function withLevels(array $levels)
    {
        $this->levels = $levels;
        return $this;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param  sting  $level   Log level
     * @param  string $message Log message with placeholders
     * @param  array  $context Context values
     * @access public
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!empty($this->levels) && !in_array($level, $this->levels)) {
            return;
        }
        
        $now     = time();
        $date    = date('Ymd', $now);
        $message = $this->interpolate($level, $message, $context, $now);
        $logFile = "{$this->logDir}/{$level}.log.{$date}";

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
            chmod($this->logDir, 0777);
        }

        if (file_exists($logFile)) {
            error_log($message, 3, $logFile);
        } else {
            error_log($message, 3, $logFile);
            chmod($logFile, 0777);
        }
    }

    private function interpolate($level, $message, array $context, $timestamp)
    {
        $time      = date('Y-m-d H:i:s', $timestamp);
        $address   = $this->getIpAddress();
        $uniqid    = empty($this->uniqid) ? uniqid(getmypid()) : $this->uniqid;
        $replace   = [];
        $array     = [];
        $exception = [];

        foreach ($context as $key => $val) {
            if (is_array($val)) {
                $array[$key] = $val;
            } elseif ('exception' == $key && $val instanceof \Exception) {
                do {
                    $exception[] = $val->getTrace();
                } while ($val = $val->getPrevious());
            } else {
                $replace['{' . $key . '}'] = $val;
            }
        }

        if (!empty($exception)) {
            $array['exception'] = $exception;
        }

        list($file, $line) = $this->getFileLine();
        $message = strtr($message, $replace);
        $message = str_replace("\n", '', $message);
        $arrStr  = $this->getArrayString($level, $array);
        return sprintf($this->format, $time, $address, $uniqid, $file, $line, $message, $arrStr);
    }

    private function getIpAddress()
    {
        $address = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        return $address;
    }

    private function getFileLine()
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit)[$this->limit - 1];
        $srcPos = strpos($trace['file'], $this->srcDir);

        if (false !== $srcPos) {
            $trace['file'] = substr($trace['file'], $srcPos + strlen($this->srcDir));
        }

        return [$trace['file'], $trace['line']];
    }

    private function getArrayString($level, array $array)
    {
        if (empty($array)) {
            $string = '';
        } else {
            $string = json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $string;
    }
}
