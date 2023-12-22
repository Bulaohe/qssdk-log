<?php

declare(strict_types=1);

namespace Ssdk\Oalog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Handler\SyslogUdp\UdpSocket;
/**
 * A Handler for logging to a remote syslogd UDP server.
 * 
 */
class UdpHandler extends AbstractProcessingHandler
{
    protected $socket;
    private $recordBuffer = [];
    private $recordBufferMaxSize = 10;
    /**
     * @param string $host
     * @param int    $port
     * @param int    $level                 The minimum logging level at which this handler will be triggered
     * @param bool   $bubble                Whether the messages that are handled can bubble up the stack or not
     * @param int    $recordBufferMaxSize   Max size of record buffer
     */
    public function __construct(
        $host,
        $port = 514,
        $level = Logger::DEBUG,
        $bubble = true,
        $recordBufferMaxSize = 1
        )
    {
        parent::__construct($level, $bubble);
        $this->socket = new UdpSocket($host, $port ?: 514);
        $this->recordBufferMaxSize = $recordBufferMaxSize;
    }
    protected function write(LogRecord $logRecord):void
    {
        $flushAll = false;
        $record = (array)$logRecord;
        if (count($record) > 0) {
            $this->recordBuffer[] = $record;
        }
        if (!$flushAll && count($this->recordBuffer) < $this->recordBufferMaxSize) {
            return;
        }
        $logContent = '';
        foreach ($this->recordBuffer as $record) {
            $logContent .= '####--sk38_@--###' . $record['formatted'];
        }
        $this->recordBuffer = [];
        if ($logContent) {
            $this->socket->write($logContent);
        }
    }
    public function close():void
    {
        if (count($this->recordBuffer) > 0) {
            $this->write([], true);
        }
        $this->socket->close();
    }
    /**
     * Inject your own socket, mainly used for testing
     */
    public function setSocket(UdpSocket $socket)
    {
        $this->socket = $socket;
    }
}