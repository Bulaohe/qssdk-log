<?php

declare(strict_types=1);

namespace Ssdk\Oalog\Facades;

use Ssdk\Oalog\Oalog as BaseOalog;

class Oalog
{
    public static  function __callstatic($method, $args)
    {
        if (! in_array($method, [
            'log',
            'logMysql',
            'logUdp',
            'registerFileLog',
        ])) {
            return false;
        }
        
        if (in_array($method, [
            'log',
            'logMysql',
            'logUdp',
        ])) {
            //获取上一级调用Trace的快捷方法
            // file | line | class | type | args
            $t = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $t[0]['function']  = $t[1]['function'];
            $t[0]['class']  = $t[1]['class'];
            
            $args[1] = array_merge($args[1], $t[0]);
        }
        
        try {
            $instance = BaseOalog::getInstance();
            if (! $instance) {
                throw new \Exception('A facade root has not been set.');
            }
            return $instance->$method(...$args);
        } catch (\Exception $e) {
            error_log("oalog日志记录错误exeption: " . $e->getMessage());
            return false;
        } catch (\Error $e) {
            error_log("oalog日志记录错误: " . $e->getMessage());
            return false;
        }
    }
}

