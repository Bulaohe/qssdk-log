<?php

/**
 *  Oalog configuration file
 *  
 */
return [
    'channel' => env('OALOG_CHANNEL', 'my_logger'),
    'file_log_path' => env('OALOG_FILE_LOG_PATH','/opt/logs/oalog.log'),
    'file_log_buffer' => (int)env('OALOG_FILE_LOG_BUFFER', 10),
    'mysql' => [
        'host'    => env('OALOG_DB_HOST', '127.0.0.1:3306'),
        'db'      => env('OALOG_DB_NAME', 'bjwp'),
        'db_user' => env('OALOG_DB_USER', 'root'),
        'db_pwd'  => env('OALOG_DB_PASSWORD', '123456'),
    ],
    'udplog' => [
        'logger_buffer_size' => (int)env('OALOG_UDPLOG_BUFFER_SIZE', 1),
        'host_port' => env('OALOG_UDPLOG_HOST_PORT', '127.0.0.1:9502'),
    ]
];