<?php

/**
 * * 竞拍成交
 * @Author: sink
 * @Date:   2020-07-09 16:16:27
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 09:16:45
 */

namespace App\Tasks;
use Server\Tasks\Task;
use App\Consts\CacheKey;


class Deal extends Task
{


	/**
	 * * 成交处理
	 * @return [type]
	 */
	public function action()
	{
		LogEcho('Timer:Deal:Action',time());
	}

}
