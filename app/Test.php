<?php

/**
 * @Author: sink
 * @Date:   2020-07-10 10:08:50
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 14:22:40
 */
namespace App;

use Server\Controllers\HttpResponse;
use App\Consts\CacheKey;
use Server\Asyn\Mysql\MysqlPool;
use Server\Asyn\Redis\RedisPool;
use Server\Memory\Container;
use Noodlehaus\Config;
use App\Service\Compete\RobotService;
use App\Service\Compete\JoinService;

class Test
{

	public $response;

	public $config;

	public function start()
	{
		$this->config = new Config(CONFIG_DIR);
		$redisPool = new RedisPool($this->config->get('redis'), Container::getInstance());
        $redisPool = $redisPool->get();
        if($redisPool == null){
        	echo 'hhh';
        }
        $redisContainer = $redisPool->getSync();
        $reslut = RobotService::generate_join_list(1, $redisContainer);
        var_dump($reslut);

	}
}
