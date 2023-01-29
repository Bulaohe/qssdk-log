<?php declare(strict_types=1);

namespace Ssdk\Oalog;

use Monolog\Logger as MonoLogger;
use Monolog\DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
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
     * Mapping between levels numbers defined in RFC 5424 and Monolog ones
     *
     * @phpstan-var array<int, Level> $rfc_5424_levels
     */
    private const RFC_5424_LEVELS = [
        7 => Level::Debug,
        6 => Level::Info,
        5 => Level::Notice,
        4 => Level::Warning,
        3 => Level::Error,
        2 => Level::Critical,
        1 => Level::Alert,
        0 => Level::Emergency,
    ];

    /**
     * Keeps track of depth to prevent infinite logging loops
     */
    private int $logDepth = 0;

    /**
     * Whether to detect infinite logging loops
     *
     * This can be disabled via {@see useLoggingLoopDetection} if you have async handlers that do not play well with this
     */
    private bool $detectCycles = true;
    
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
        if (is_int($level) && isset(self::RFC_5424_LEVELS[$level])) {
            $level = self::RFC_5424_LEVELS[$level];
        }

        if ($this->detectCycles) {
            $this->logDepth += 1;
        }
        if ($this->logDepth === 3) {
            $this->warning('A possible infinite logging loop was detected and aborted. It appears some of your handler code is triggering logging, see the previous log record for a hint as to what may be the cause.');
            return false;
        } elseif ($this->logDepth >= 5) { // log depth 4 is let through so we can log the warning above
            return false;
        }

        try {
            $recordInitialized = count($this->processors) === 0;

            $levelName = static::getLevelName($level);
            $record = new LogRecord(
                message: $message,
                context: $context,
                level: self::toMonologLevel($level),
                channel: $this->name,
                datetime: $datetime ?? new DateTimeImmutable($this->microsecondTimestamps, $this->timezone),
                extra: [
                    'level_name' => $levelName,
                    'level_no' => $level,
                ],
            );
            $handled = false;

            foreach ($this->handlers as $handler) {
                if (false === $recordInitialized) {
                    // skip initializing the record as long as no handler is going to handle it
                    if (!$handler->isHandling($record)) {
                        continue;
                    }

                    try {
                        foreach ($this->processors as $processor) {
                            $record = $processor($record);
                        }
                        $recordInitialized = true;
                    } catch (Throwable $e) {
                        $this->handleException($e, $record);

                        return true;
                    }
                }

                // once the record is initialized, send it to all handlers as long as the bubbling chain is not interrupted
                try {
                    $handled = true;
                    if (true === $handler->handle($record)) {
                        break;
                    }
                } catch (Throwable $e) {
                    $this->handleException($e, $record);

                    return true;
                }
            }

            return $handled;
        } finally {
            if ($this->detectCycles) {
                $this->logDepth--;
            }
        }
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