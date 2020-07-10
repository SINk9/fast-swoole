<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:56:38
 */


return [
	'redis' => [
		'enable' => true,
		'active' => 'default',
	    'default' => [
	        'host'           => '127.0.0.1',
	        'auth'           => '',
	        'port'           => 6379,
	        'db'             => 2,
	        'timeout'	     => 0.0,
	        'asyn_max_count' => 10,
	        'pool' => [
	            'min_connections' => 1,
	            'max_connections' => 10,
	            'connect_timeout' => 10.0,
	            'wait_timeout'    => 3.0,
	            'heartbeat'       => -1,
	            'max_idle_time'   => 60,
	        ],
	    ],

	],
];
