# Oalog unified client standard SDK

Oalog depends on Monolog. It provides three types of logs: File log, MySQL log, and UDP log.

## Installation 

Add private 2345 pacakgist into composer.json

```
"repositories": {
    "0": {
        "type": "composer",
        "url": "http://packagist.2345.com/repo/private/"
    }
}
```

Install the latest version with:

``` bash
$ composer require ssdk/oalog
```

## Env Configuration
Please make sure .env configuration is correct.

```
#Oalog SDK logs File configuration
OALOG_CHANNEL=ae_logger
OALOG_FILE_LOG_PATH=/opt/logs/ae.log
OALOG_FILE_LOG_BUFFER=10

#Oalog SDK Logs Mysql configuration
OALOG_DB_HOST=127.0.0.1:3306
OALOG_DB_NAME=oalog
OALOG_DB_USER=root
OALOG_DB_PASSWORD=123456

#Oalog SDK Logs UDP configuration
OALOG_UDPLOG_BUFFER_SIZE=5
OALOG_UDPLOG_HOST_PORT=127.0.0.1:9402
```

## Basic usage

```php
<?php

use Ssdk\Oalog\Facades\Oalog;
use Ssdk\Oalog\Logger;

// 中台日志文件采集系统
Oalog::log('My logger is now ready', ['age' => 18, 'address' => '上海'],  Logger::INFO);

// MySQL 日志持久化
Oalog::logMysql('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::INFO);

// 待服务端接入 - 作为中台日志文件采集系统的备胎
Oalog::logUdp('My logger is now ready', ['age' => 18, 'address' => '上海'], Logger::INFO);

//新错误异常处理, 错误异常内存溢出输出到日志文件
Oalog::registerFileLog();

```

## Documentation

请参考Confluence日志架构设计

## About

### Requirements

Monolog 2.x works with PHP 7.2 or above

### Author
Simon Quan <quanqm@2345.com>

