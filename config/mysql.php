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
            'driver'    => 'mysql',
            'host'      => '121.196.52.20',
            'port'      => '3306',
            'database'  => 'xiaoq',
            'username'  => 'root',
            'password'  => 'xiaoq2020',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'init_',
            'pool'      => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout'    => 3.0,
                'heartbeat'       => -1,
                'max_idle_time'   => 60,
            ],
        ],
    ]
];
