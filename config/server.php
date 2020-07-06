<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 19:07:11
 */

return [
    'name'   => 'demaxiya~',
    'server' => [
        'send_use_task_num'        => 500,
        'set' => [
            'reactor_num'              => 4,
            'backlog'                  => 128,   //listen backlog
            'worker_num'               => 2, //一般设置为服务器CPU数的1-4倍
            'task_worker_num'          => 2,  //task进程的数量
            'task_ipc_mode'            => 3,  //使用消息队列通信，并设置为争抢模式
            'task_max_request'         => 5000,  //task进程的最大任务数
            'max_request'              => 10000, //task最大任务数
            'max_connection'           => 100000,//最大连接数
            'dispatch_mode'            => 2, //数据包分发策略 (1，轮循模式2，固定模式3，抢占模式4，IP分配5，UID分配)
            'debug_mode'               => 1,
            'open_tcp_nodelay'         => 1,
            'heartbeat_check_interval' => 60,// 心跳检测的设置，自动踢掉掉线的fd
            'heartbeat_idle_time'      => 600, //秒内未向服务器端发送数据，将会被切断
            'log_file'                 => LOG_DIR . 'swoole.log',  //日志
            'pid_file'                 => PID_DIR . 'server.pid',
            'log_level'                => 5, //日志等级(0,1,2,3,4,5)
            'socket_buffer_size'       => 1024 * 1024 * 1024,
            'enable_reuse_port'        => true,
        ],
        'coroution' => [
            'timerOut' => 5000, //协程超时时间
        ],
        'auto_reload_enable'     => true, //是否启用自动reload
        'allow_ServerController' => true, //是否允许访问Server中的Controller，如果不允许将禁止调用Server包中的Controller
    ],
];
