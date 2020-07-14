<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 13:36:17
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 10:21:49
 */

namespace App\Timer;
//use Server\CoreBase\CoreBase;
use Server\Tasks\TaskProxy;
use Server\Events\EventDispatcher;
use Server\Events\Event;
use App\Consts\CacheKey;
use Noodlehaus\Config;
use Server\Asyn\Redis\RedisPool;
use Server\Memory\Container;

class Timing //extends CoreBase
{

	const TIMING = 'timing';

    /**
     * @var redis
     */
    public $redis;

    public function __construct()
    {

		$config = new Config(CONFIG_DIR);
		$redisPool = new RedisPool($config->get('redis'), Container::getInstance());
        $redisPool = $redisPool->get();
        $this->redis = $redisPool->getSync();

    }

	/**
	 * *
	 * @return [type]
	 */
    public function Join()
    {
    	$is_true = 'false';
		$timing_key = CacheKey::TIMING;
		$timing = $this->redis->get($timing_key);
		if(empty($timing)){
		    $timing = intval(date('s',time()));
		    $this->redis->set($timing_key,$timing);
		}else{
		    $s = $timing + 1;
		    if($s > 59){
		        $s = $s - 59;
		    }
		    $this->redis->set($timing_key,$s);
		}
		//机器人插入
		$key = CacheKey::TIMING_JOIN.$timing;
		$join_list = $this->redis->smembers($key);
		$this->redis->del($key);
		if(!empty($join_list)){
			$is_true = 'true';
		    foreach ($join_list as  $goods_id) {
		        if(empty($goods_id)){
		            continue;
		        }
		        $data = [
		        	'task_name'   => 'Join',
		        	'method_name' => 'Action',
		        	'arguments'   => [
			        	'goods_id' => $goods_id,
			        	'timing'   => $timing
		        	],
		        ];
		        EventDispatcher::getInstance()->randomDispatch(self::TIMING, $data);
		    }
		}
		LogEcho('Timer:Join:',$is_true);
    }

    /**
     * *
     * @return [type]
     */
    public function deal()
    {
    	LogEcho('Timer:Deal:',time());
        $key  = CacheKey::COMPETE_DEAL_TIMING;
        $data = $this->redis->hgetall($key);
        if (!empty($data)) {
            foreach ($data as $keys => $value) {
                $tmp = json_decode($value, true);
                if (time() >= $tmp['deal_time']) {

                    //交给task执行
				    $data = [
				    	'task_name'   => 'Deal',
				    	'method_name' => 'action',
				    	'arguments'   => [
				        	'goods_id' => $keys,
				        	'issue'    => $tmp['issue']
				    	],
				    ];
				    EventDispatcher::getInstance()->randomDispatch(self::TIMING, $data);
                   //交给队列执行
                    //$var = ['goods_id' => $keys, 'issue' => $tmp['issue']];
                    //Queue::add('Deal', $var);

                    $this->redis->deal($key, $keys);
                }
            }
        }




    }


    public static function start()
    {
        EventDispatcher::getInstance()->add(self::TIMING, function (Event $event) {
            $timer_task = $event->data;
            if (!empty($timer_task['task_name'])) {
                $task = TaskProxy::getInstance()->loader($timer_task['task_name']);
                $startTime = getMillisecond();
                $path = "[Timing] " . $timer_task['task_name'] . "::" . $timer_task['method_name'];
                $task->startTask($timer_task['task_name'], $timer_task['method_name'],$timer_task['arguments'],-1, function () use (&$task) {
                    $task->destroy();
                });
            }
        });
    }
}
