<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 13:36:17
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 21:20:46
 */

namespace App\Timer;
use Server\CoreBase\CoreBase;
use Server\Tasks\TaskProxy;
use Server\Events\EventDispatcher;
use Server\Events\Event;

class Timing extends CoreBase
{

	const TIMING = 'timing';

    /**
     * @var redis
     */
    public $redis;

    public function __construct()
    {
		parent::__construct();
    }

	/**
	 * *
	 * @return [type]
	 */
    public function robot()
    {
    	LogEcho('Timer:Robot:',time());
	    $data = [
	    	'task_name'   => 'Robot',
	    	'method_name' => 'join',
	    	'arguments'   => [
	        	'goods_id' => 1,
	        	'timing'   => 2
	    	],
	    ];
	    EventDispatcher::getInstance()->randomDispatch(self::TIMING, $data);


		// $timing_key = 'timing_key';
		// $timing = $this->redis->get($timing_key);
		// if(empty($timing)){
		//     $timing = intval(date('s',time()));
		//     $this->redis->SET($timing_key,$timing);
		// }else{
		//     $s = $timing + 1;
		//     if($s > 59){
		//         $s = $s - 59;
		//     }
		//     $this->redis->SET($timing_key,$s);
		// }
		// //机器人插入
		// $key = "timing_join_{$timing}";
		// $join_list = $this->redis->SMEMBERS($key);
		// $this->redis->DEL($key);
		// if(!empty($join_list)){
		//     foreach ($join_list as  $goods_id) {
		//         if(empty($goods_id)){
		//             continue;
		//         }
		//         $data = [
		//         	'task_name'   => 'Robot',
		//         	'method_name' => 'join',
		//         	'arguments'   => [
		// 	        	'goods_id' => $goods_id,
		// 	        	'timing'   => $timing
		//         	],
		//         ];
		//         EventDispatcher::getInstance()->randomDispatch(self::TIMING, $data);
		//     }
		// }
    }

    /**
     * *
     * @return [type]
     */
    public function deal()
    {
    	LogEcho('Timer:Deal:',time());
	    $data = [
	    	'task_name'   => 'Deal',
	    	'method_name' => 'action',
	    	'arguments'   => [
	        	'goods_id' => 1,
	        	'issue'    => 2
	    	],
	    ];
	    EventDispatcher::getInstance()->randomDispatch(self::TIMING, $data);
    }


    public static function start()
    {
        EventDispatcher::getInstance()->add(self::TIMING, function (Event $event) {
            $timer_task = $event->data;
            if (!empty($timer_task['task_name'])) {
                $task = TaskProxy::getInstance()->loader($timer_task['task_name']);
                $startTime = getMillisecond();
                $path = "[Timing] " . $timer_task['task_name'] . "::" . $timer_task['method_name'];
                $task->startTask($timer_task['task_name'], $timer_task['method_name'],[],-1, function () use (&$task) {
                    $task->destroy();
                });
            }
        });
    }
}
