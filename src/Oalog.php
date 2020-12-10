<?php

declare(strict_types=1);

namespace Ssdk\Oalog;

use Ssdk\Oalog\Logger;
use Monolog\Handler\StreamHandler;
use Ssdk\Oalog\Formatter\LineFormatter;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use PDO;
use Ssdk\Oalog\Handler\MysqlHandler;
use Monolog\Handler\BufferHandler;
use Ssdk\Oalog\Handler\UdpHandler;
use Monolog\Formatter\JsonFormatter;
use Ssdk\Oalog\ErrorHandler;

/**
 * 日志基础调用类
 * File, Mysql, Udp
 * 
 * @author Simon Quan
 *
 */
class Oalog
{

    private $logFile;
    
    private $logFileNobuffer = null;

    private $logMysql;

    private $logUdp;
    
    private $config;
    
    //udp hosts
    private $hosts = [];

    static private $instance;
    
    private function __construct()
    {
        //
        $this->config = require 'config/oalog.php';
    }
    
    private function __clone()
    {
        //
    }
    
    public static function getInstance()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Log unified call entry. 文件日志并不100%可靠，最多只会保留几个月
     *
     * @param string $message The log message
     * @param array $context The log context, the log details, some elemented must be contained, such as:
     * @param int $level Log level, default Info
     * @see \Monolog\Logger::log()
     */
    public function log(string $message = '', array $context = [], int $level = Logger::INFO):bool
    {
        if (! $this->validateContext($message, $context, $level)) {
            return false;
        }
        
        if ($level == Logger::ERROR) {
            $context['debug_info'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }
        
        if (in_array($level, [Logger::ERROR, Logger::CRITICAL, Logger::EMERGENCY])) {
            $this->getFileInstanceNoBuffer();
            return $this->logFileNobuffer->oalog($message, $context, $level);
        } else {
            $this->getFileInstance();
            return $this->logFile->oalog($message, $context, $level);
        }
    }

    /**
     * Mysql 日志持久化
     * 
     * @param string $message
     * @param array $context
     * @param int $level
     */
    public function logMysql(string $message = '', array $context = [], int $level = Logger::INFO):bool
    {
        if (! $this->validateContext($message, $context, $level)) {
            return false;
        }
        
        $this->getMysqlInstance();
        return $this->logMysql->oalog($message, $context, $level);
    }
    
    /**
     * Log unified call entry. UDP日志并不100%可靠，只会保留几个月
     *
     * @param string $message The log message
     * @param array $context The log context, the log details, some elemented must be contained, such as:
     * @param int $level Log level, default Info
     * @see \Monolog\Logger::log()
     */
    public function logUdp(string $message = '', array $context = [], int $level = Logger::INFO):bool
    {
        if (! $this->validateContext($message, $context, $level)) {
            return false;
        }
        
        $this->getUdpInstance();
        return $this->logUdp->oalog($message, $context, $level);
    }

    private function getFileInstance():void
    {
        if ($this->logFile == null) {
            // 创建Logger实例
            $this->logFile = new Logger($this->config['channel']);
            // 添加handler
            //添加Buffer处理，每达到10条写入Stream，程序结束一并写入Stream
            $this->logFile->pushHandler(new BufferHandler(
                (new StreamHandler($this->config['file_log_path'], Logger::DEBUG))->setFormatter(new LineFormatter(null, null, false, false)),
                $this->config['file_log_buffer'],
                Logger::DEBUG,
                false,
                true
                ));
            
            $this->logFile->pushProcessor(new HostnameProcessor());
            $this->logFile->pushProcessor(new MemoryUsageProcessor());
            $this->logFile->pushProcessor(new ProcessIdProcessor());
            $this->logFile->pushProcessor(new PsrLogMessageProcessor());
            $this->logFile->pushProcessor(new UidProcessor(32));
            $this->logFile->pushProcessor(new WebProcessor());
        }
    }
    
    private function getFileInstanceNoBuffer():void
    {
        if ($this->logFileNobuffer == null) {
            // 创建Logger实例
            $this->logFileNobuffer = new Logger($this->config['channel']);
            // 添加handler
            //添加Buffer处理，每达到10条写入Stream，程序结束一并写入Stream
            $this->logFileNobuffer->pushHandler((new StreamHandler($this->config['file_log_path'], Logger::DEBUG, true, 0777))->setFormatter(new LineFormatter(null, null, false, false)));
            
            $this->logFileNobuffer->pushProcessor(new HostnameProcessor());
            $this->logFileNobuffer->pushProcessor(new MemoryUsageProcessor());
            $this->logFileNobuffer->pushProcessor(new ProcessIdProcessor());
            $this->logFileNobuffer->pushProcessor(new PsrLogMessageProcessor());
            $this->logFileNobuffer->pushProcessor(new UidProcessor(32));
            $this->logFileNobuffer->pushProcessor(new WebProcessor());
        }
    }

    private function getMysqlInstance():void
    {
        if ($this->logMysql == null) {
            $mysql_conf = $this->config['mysql'];
            $pdo = new PDO("mysql:host=" . $mysql_conf['host'] . ';dbname=' . $mysql_conf['db'], $mysql_conf['db_user'], $mysql_conf['db_pwd']);//创建一个pdo对象
            $pdo->exec("set names 'utf8'");
            
            // 创建Logger实例
            $this->logMysql = new Logger($this->config['channel']);
            // 添加handler
            $this->logMysql->pushHandler((new MysqlHandler($pdo, 'logs', Logger::DEBUG)));
            $this->logMysql->pushProcessor(new HostnameProcessor());
            $this->logMysql->pushProcessor(new MemoryUsageProcessor());
            $this->logMysql->pushProcessor(new ProcessIdProcessor());
            $this->logMysql->pushProcessor(new PsrLogMessageProcessor());
            $this->logMysql->pushProcessor(new UidProcessor(32));
            $this->logMysql->pushProcessor(new WebProcessor());
        }
    }
    
    private function getUdpInstance():void
    {
        if ($this->logUdp == null) {
            $udp_conf = $this->config['udplog'];
            
            // 创建Logger实例
            $this->logUdp = new Logger($this->config['channel']);
            
            $host_ports = explode(',',$udp_conf['host_port']);
            foreach ($host_ports as $host_port) {
                $this->hosts[] = explode(':', $host_port);
            }
            $rand = rand(0, count($this->hosts) - 1);
            $this->logUdp->pushHandler((new UdpHandler(
                (string)$this->hosts[$rand][0],
                (int)$this->hosts[$rand][1],
                Logger::DEBUG,
                true,
                $udp_conf['logger_buffer_size']
                ))->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false)));
            $this->logUdp->pushProcessor(new HostnameProcessor());
            $this->logUdp->pushProcessor(new MemoryUsageProcessor());
            $this->logUdp->pushProcessor(new ProcessIdProcessor());
            $this->logUdp->pushProcessor(new PsrLogMessageProcessor());
            $this->logUdp->pushProcessor(new UidProcessor(32));
            $this->logUdp->pushProcessor(new WebProcessor());
        }
    }
    
    /**
     * 验证日志消息不能为空
     * 验证日志消息体
     * $context | file | line | class | type | args 由门面静态方法自动产生
     * moule | uniqueid | error_code 作为$context的一级元素需要传递，否则会生成默认值
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    private function validateContext(string $message = '', array &$context, int $level):bool
    {
        if (! Logger::isValidLevel($level)) {
            return false;
        }
        
        if ($message == '') {
            return false;
        }
        
        if (! isset($context['function'])) {
            return false;
        }
        
        if (! isset($context['line'])) {
            return false;
        }
        
        if (! isset($context['file'])) {
            return false;
        }
        
        if (! isset($context['class'])) {
            return false;
        }
        
        if (! isset($context['module'])) {
            $context['module'] = 'cannotget_module';
        }
        
        if (! isset($context['uniqueid'])) {
            $context['uniqueid'] = (new UidProcessor(32))->getUid();
        }
        
        if (! isset($context['error_code'])) {
            $context['error_code'] = 0;
        }
        
        if(isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }else if(isset($_SERVER['HOSTNAME'])) {
            $ip = $_SERVER['HOSTNAME'];
        }else {
            $ip = gethostname();
        }
        $context['hostname'] = $ip;
        
        return true;
    }
    
    /**
     * 注册错误处理器 - File log
     */
    public function registerFileLog()
    {
        $this->getFileInstance();
        
        ErrorHandler::register($this->logFile);
    }
    
    /**
     * 注册错误处理器 - Mysql log - 暂不开放
     */
    public function registerMysqlLog()
    {
        $this->getMysqlInstance();
        
        ErrorHandler::register($this->logMysql);
    }
    
    /**
     * 注册错误处理器 - Udp log - 暂不开放
     */
    public function registerUdpLog()
    {
        $this->getUdpInstance();
        
        ErrorHandler::register($this->logUdp);
    }
}
