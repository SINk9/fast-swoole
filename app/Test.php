<?php

/**
 * @Author: sink
 * @Date:   2020-07-10 10:08:50
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 16:28:01
 */
namespace App;

use Server\Controllers\HttpResponse;
use App\Consts\CacheKey;
class Test
{

	public $response;


	public function start()
	{
		$rs = redisHashPaginateHelp(1,10);
		var_dump($rs);
	}
}
