<?php
//Async UDP server
$server = new Swoole\Server("127.0.0.1", 9502, SWOOLE_BASE, SWOOLE_SOCK_UDP);
$server->set([
    'reactor_num' => 4, //reactor thread num
    'worker_num' => 4,    //worker process num
    'backlog' => 10000,   //accept wait requests
    'max_request' => 0,
    'dispatch_mode' => 1,
    'max_conn' => 10000,
    'daemonize' => 0,
    'task_worker_num' => 20,
]);

$server->on('connect', function ($server, $fd) {
    echo "connection open: {$fd}\n";
});
$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->on('workerStart', function ($server, $fd) {
    echo "worker start: {$fd}\n";
});

$server->on('packet', function ($server, $data) {
    echo "packet data: {$data}\n";
    
    $server->task($data);
});

$server->on('task', function ($serv, $task_id, $from_id, $data) {
    echo "task data: taskworkerid:" . $task_id . ":workerid:{$from_id}" . ": {$data}\n";
});

$server->on('finish', function ($response) {
    echo "finish data: \n";
});

$server->start();
