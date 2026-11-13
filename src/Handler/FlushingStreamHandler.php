<?php

declare(strict_types=1);

namespace Ssdk\Oalog\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;

/**
 * 写盘后立即 fflush，避免常驻进程（Swoole Process Pool / Octane）中
 * PHP stream 默认 8KB 缓冲导致日志迟迟不落盘。
 */
class FlushingStreamHandler extends StreamHandler
{
    protected function streamWrite($stream, LogRecord $record): void
    {
        if (function_exists('stream_set_write_buffer')) {
            @stream_set_write_buffer($stream, 0);
        }
        parent::streamWrite($stream, $record);
        fflush($stream);
    }
}
