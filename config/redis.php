<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:21:42
 */


return [
	'redis' => [
		'enable' => true,
		'active' => 'local',

		'local'   => [
			'ip'       => '127.0.0.1',
			'port'     => 6379,
			'select'   => 1,
		],
		'test'   => [
			'ip'     => 'localhost',
			'port'   => 6379,
			'select' => 0,
			'password' => '',
		],
		'asyn_max_count' => 10,
	],
];
