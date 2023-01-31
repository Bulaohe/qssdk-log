<?php declare(strict_types=1);

namespace Ssdk\Oalog;

use Monolog\Logger as MonoLogger;
use Monolog\DateTimeImmutable;
use Throwable;

class Logger extends MonoLogger
{
    protected static $levelCodes = [
        self::ALERT,
        self::DEBUG,
        self::INFO,
        self::NOTICE,
        self::WARNING,
        self::ERROR,
        self::CRITICAL,
        self::EMERGENCY,
    ];
    
    /**
     * 判断错误级别是否合法
     * 
     * @param int $level
     * @return bool
     */
    public static function isValidLevel(int $level):bool
    {
        if (!in_array($level, self::$levelCodes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log unified call entry.
     * 
     * @param string $message The log message
     * @param array $context The log context, the log details, some elemented must be contained, such as: 
     * @param int $level Log level, default Info
     * @see \Monolog\Logger::log()
     */
    public function oalog(string $message = '', array $context = [], int $level = Logger::INFO)
    {
        if ($message == '') {
            return false;
        }
        
        if (!in_array($level, self::$levelCodes)) {
            return false;
        }
        
        return $this->addRecord($level, (string) $message, $context);
    }
    
    
    /**
     * Adds a log record.
     *
     * @param  int               $level    The logging level (a Monolog or RFC 5424 level)
     * @param  string            $message  The log message
     * @param  mixed[]           $context  The log context
     * @param  DateTimeImmutable $datetime Optional log date to log into the past or future
     * @return bool              Whether the record has been processed
     *
     * @phpstan-param value-of<Level::VALUES>|Level $level
     */
    public function addRecord(int|Level $level, string $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling(['level' => $level])) {
                $handlerKey = $key;
                break;
            }
        }
        
        if (null === $handlerKey) {
            return false;
        }
        
        $levelName = static::getLevelName($level);
        $record = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'level_no' => $level,
            'channel' => $this->name,
            'datetime' => new DateTimeImmutable($this->microsecondTimestamps, $this->timezone),
            'extra' => []
        ];
        
        try {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
            
            // advance the array pointer to the first handler that will handle this record
            reset($this->handlers);
            while ($handlerKey !== key($this->handlers)) {
                next($this->handlers);
            }
            
            while ($handler = current($this->handlers)) {
                if (true === $handler->handle($record)) {
                    break;
                }
                
                next($this->handlers);
            }
        } catch (Throwable $e) {
            $this->handleException($e, $record);
        }
        
        return true;
    }
    
    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function debug($message, array $context = []): void
    {
        $this->oalog($message, $context, self::DEBUG);
    }
    
    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function info($message, array $context = []): void
    {
        $this->oalog($message, $context, self::INFO);
    }
    
    /**
     * Adds a log record at the NOTICE level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function notice($message, array $context = []): void
    {
        $this->oalog($message, $context, self::NOTICE);
    }
    
    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function warning($message, array $context = []): void
    {
        $this->oalog($message, $context, self::WARNING);
    }
    
    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function error($message, array $context = []): void
    {
        $this->oalog($message, $context, self::ERROR);
    }
    
    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function critical($message, array $context = []): void
    {
        $this->oalog($message, $context, self::CRITICAL);
    }
    
    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function alert($message, array $context = []): void
    {
        $this->oalog($message, $context, self::ALERT);
    }
    
    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     */
    public function emergency($message, array $context = []): void
    {
        $this->oalog($message, $context, self::EMERGENCY);
    }
}