<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:21:18
 */


return [
	'mysql' => [
		'enable' => true,
		'active' => 'local',
		'test'   => [
			'host'     => '127.0.0.1',
			'port'     => '3306',
			'user'     => 'root',
			'password' => '',
			'database' => 'xiaoq',
			'charset'  => 'utf8',
		],
		'asyn_max_count' => 10,
	],
];
