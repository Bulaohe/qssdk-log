<?php

namespace Test\Ssdk\Oalog;

use Ssdk\Oalog\Logger;
// use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ssdk\Oalog\Formatter\LineFormatter;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
// use Monolog\Formatter\LineFormatter;
use PDO;
use Ssdk\Oalog\Handler\MysqlHandler;
use Ssdk\Oalog\Facades\Oalog;

class TestLogger
{
    public static function testMe()
    {
        return 'ok';
    }
    
    public static function testMonolog()
    {
        // 创建Logger实例
        $logger = new Logger('my_logger');
        // 添加handler
        $logger->pushHandler((new StreamHandler(dirname(__FILE__).'/my_app.log', Logger::DEBUG))->setFormatter(new LineFormatter(null, null, false, false)));
        //$logger->pushHandler(new FirePHPHandler());
        $logger->pushProcessor(new HostnameProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new ProcessIdProcessor());
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $logger->pushProcessor(new UidProcessor(32));
        $logger->pushProcessor(new WebProcessor());
        // 开始使用
        $logger->info('My logger is now ready', ['age' => 18, 'address' => '上海']);
    }
    
    public static function testMonologMysql()
    {
        $mysql_conf = array(
            'host'    => '127.0.0.1:3306',
            'db'      => 'bjwp',
            'db_user' => 'root',
            'db_pwd'  => 'root',
        );
        $pdo = new PDO("mysql:host=" . $mysql_conf['host'] . ";dbname=" . $mysql_conf['db'], $mysql_conf['db_user'], $mysql_conf['db_pwd']);//创建一个pdo对象
        $pdo->exec("set names 'utf8'");
        
        // 创建Logger实例
        $logger = new Logger('my_logger');
        // 添加handler
        $logger->pushHandler((new MysqlHandler($pdo, 'logs', Logger::DEBUG)));
        //$logger->pushHandler(new FirePHPHandler());
        $logger->pushProcessor(new HostnameProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new ProcessIdProcessor());
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $logger->pushProcessor(new UidProcessor(32));
        $logger->pushProcessor(new WebProcessor());
        // 开始使用
        $logger->info('My logger is now ready', ['age' => 18, 'address' => '上海']);
    }
    
    public static function testFacadeLog($a=1) 
    {
        $context['data'] =  ['age' => 18, 'address' => '上海'];
        $context['module'] =  'testlogger';
        
        // 开始使用
        Oalog::log('My logger is now ready', $context,  Logger::INFO);
        Oalog::log('My logger is now ready', $context,  Logger::ERROR);
        Oalog::log('My logger is now ready', $context,  Logger::WARNING);
        Oalog::log('My logger is now ready', $context,  Logger::CRITICAL);
        Oalog::log('My logger is now ready', $context,  Logger::DEBUG);
        Oalog::log('My logger is now ready', $context,  Logger::EMERGENCY);
    }
    
    public static function testFacadeMysqlLog() 
    {
        // 开始使用
        Oalog::logMysql('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::INFO);
        Oalog::logMysql('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::ERROR);
        Oalog::logMysql('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::WARNING);
        Oalog::logMysql('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::CRITICAL);
    }
    
    public static function testFacadeUdpLog() 
    {
        // 开始使用
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::INFO);
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::ERROR);
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::WARNING);
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::CRITICAL);
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'],  Logger::DEBUG);
        Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'],  Logger::EMERGENCY);
    }
    
    
    public static function testLogParams()
    {
        //校验参数
        $res = Oalog::log('', []);
        assert($res === false, "校验参数：缺少参数，文件日志校验失败");
        
        $res = Oalog::logMysql('', []);
        assert($res === false, "校验参数：缺少参数，Mysql日志校验失败");
        
        $res = Oalog::logUdp('', []);
        assert($res === false, "校验参数：缺少参数，UDP日志校验失败");
        
        //错误级别 - 错误数字
        $res = Oalog::log('test param', [], 900);
        assert($res === false, "校验参数：错误级别，文件日志校验失败");
        $res = Oalog::logMysql('test param', [], 900);
        assert($res === false, "校验参数：错误级别，文件日志校验失败");
        $res = Oalog::logUdp('test param', [], 900);
        assert($res === false, "校验参数：错误级别，文件日志校验失败");
        
        //错误级别 - 错误参数类型
        $res = Oalog::log('test param', [], 'error');
        assert($res === false, "校验参数：错误级别-错误参数类型，文件日志校验失败");
        $res = Oalog::logMysql('test param', [], 'error');
        assert($res === false, "校验参数：错误级别-错误参数类型，文件日志校验失败");
        $res = Oalog::logUdp('test param', [], 'error');
        assert($res === false, "校验参数：错误级别-错误参数类型，文件日志校验失败");
        
        //校验参数-正确参数
        $res = Oalog::log('This is alert', [], Logger::ALERT);
        assert($res === true, "校验参数：正确参数alert，文件日志校验失败");
        $res = Oalog::log('This is debug', [], Logger::DEBUG);
        assert($res === true, "校验参数：正确参数debug，文件日志校验失败");
        $res = Oalog::log('This is info', [], Logger::INFO);
        assert($res === true, "校验参数：正确参数info，文件日志校验失败");
        $res = Oalog::log('This is notice', [], Logger::NOTICE);
        assert($res === true, "校验参数：正确参数notice，文件日志校验失败");
        $res = Oalog::log('This is warning', [], Logger::WARNING);
        assert($res === true, "校验参数：正确参数warning，文件日志校验失败");
        $res = Oalog::log('This is error', [], Logger::ERROR);
        assert($res === true, "校验参数：正确参数error，文件日志校验失败");
        $res = Oalog::log('This is critical', [], Logger::CRITICAL);
        assert($res === true, "校验参数：正确参数critical，文件日志校验失败");
        $res = Oalog::log('This is emergency', [], Logger::EMERGENCY);
        assert($res === true, "校验参数：正确参数emergency，文件日志校验失败");
        
        $res = Oalog::logMysql('This is alert', [], Logger::ALERT);
        assert($res === true, "校验参数：正确参数alert，Mysql日志校验失败");
        $res = Oalog::logMysql('This is debug', [], Logger::DEBUG);
        assert($res === true, "校验参数：正确参数debug，Mysql日志校验失败");
        $res = Oalog::logMysql('This is info', [], Logger::INFO);
        assert($res === true, "校验参数：正确参数info，Mysql日志校验失败");
        $res = Oalog::logMysql('This is notice', [], Logger::NOTICE);
        assert($res === true, "校验参数：正确参数notice，Mysql日志校验失败");
        $res = Oalog::logMysql('This is warning', [], Logger::WARNING);
        assert($res === true, "校验参数：正确参数warning，Mysql日志校验失败");
        $res = Oalog::logMysql('This is error', [], Logger::ERROR);
        assert($res === true, "校验参数：正确参数error，Mysql日志校验失败");
        $res = Oalog::logMysql('This is critical', [], Logger::CRITICAL);
        assert($res === true, "校验参数：正确参数critical，Mysql日志校验失败");
        $res = Oalog::logMysql('This is emergency', [], Logger::EMERGENCY);
        assert($res === true, "校验参数：正确参数emergency，Mysql日志校验失败");
        
        $res = Oalog::logUdp('This is alert', [], Logger::ALERT);
        assert($res === true, "校验参数：正确参数alert，UDP日志校验失败");
        $res = Oalog::logUdp('This is debug', [], Logger::DEBUG);
        assert($res === true, "校验参数：正确参数debug，UDP日志校验失败");
        $res = Oalog::logUdp('This is info', [], Logger::INFO);
        assert($res === true, "校验参数：正确参数info，UDP日志校验失败");
        $res = Oalog::logUdp('This is notice', [], Logger::NOTICE);
        assert($res === true, "校验参数：正确参数notice，UDP日志校验失败");
        $res = Oalog::logUdp('This is warning', [], Logger::WARNING);
        assert($res === true, "校验参数：正确参数warning，UDP日志校验失败");
        $res = Oalog::logUdp('This is error', [], Logger::ERROR);
        assert($res === true, "校验参数：正确参数error，UDP日志校验失败");
        $res = Oalog::logUdp('This is critical', [], Logger::CRITICAL);
        assert($res === true, "校验参数：正确参数critical，UDP日志校验失败");
        $res = Oalog::logUdp('This is emergency', [], Logger::EMERGENCY);
        assert($res === true, "校验参数：正确参数emergency，UDP日志校验失败");
        
        
        self::testTrackLog();
        
        echo "\n";
        echo 'Test Finish';
        echo "\n";
    }
    
    public static function testTrackLog(){
        $res = Oalog::log('This is info', [], Logger::INFO);
        assert($res === true, "校验参数：正确参数info，文件日志校验失败");
    }
    
    public static function testException()
    {
        throw new \Exception('this is test Exception');
    }
    
    public static function testErrorException()
    {
        throw new \ErrorException('this is test Exception');
    }
    
    public static function testError()
    {
        throw new \Error('this is test error', 1004);
    }
}