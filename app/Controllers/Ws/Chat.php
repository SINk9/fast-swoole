<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:20:05
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 12:11:40
 */
namespace App\Controllers\Ws;
use Server\Controllers\Controller;

class Chat extends Controller
{


	/**
	 * * 保持连接 (心跳包)
	 * @return [type] [description]
	 */
	public function keep()
	{
		$data = [
			'cmd'     => 'keep',
			'message' => '德玛西亚~',
		];
		$this->send($data);
	}
}


