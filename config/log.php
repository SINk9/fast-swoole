<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 19:04:03
 */

return [
	'log' => [
		'active'    => 'graylog',
		'log_level' => \Monolog\Logger::DEBUG,
		'log_name'  => 'demaixya~',
		'graylog'   => [
			'udp_send_port'             => 12500,
			'ip'                        => '127.0.0.1',
			'port'                      => 12201,
			'api_port'                  => 9000,
			'efficiency_monitor_enable' => true,
		],
		'file' => [
			'log_max_files'             => 15,
			'efficiency_monitor_enable' => false,
		],
		'syslog' => [
			'ident'                     => 'app',
			'efficiency_monitor_enable' => true,
		],
	],
];
