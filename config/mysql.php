<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'mysql' =>[
        'enable'  => true,
        'active'  => 'default',
        'default' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'xiaoq',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => 'xq_',
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
                'max_idle_time' => 60,
            ],
            'cache' => [
                'handler' => Hyperf\ModelCache\Handler\RedisHandler::class,
                'cache_key' => 'mc:%s:m:%s:%s:%s',
                'prefix' => 'default',
                'ttl' => 3600 * 24,
                'load_script' => true,
            ],
            'commands' => [
                'gen:model' => [
                    'path' => 'app/Model',
                    'force_casts' => true,
                    'inheritance' => 'Model',
                ],
            ],
        ],
    ]
];
