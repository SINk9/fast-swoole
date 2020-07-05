<?php

/**
 * 定时任务
 * @Author: sink
 * @Date:   2019-08-07 16:00:52
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:58:20
 */

namespace Server\TimerTasks;

use Server\ProxyServer;
use Server\Tasks\TaskProxy;
use Server\Events\EventDispatcher;
use Server\Events\Event;
use Server\Models\ModelProxy;

class TimerTask
{

    protected $timer_tasks_used;
    /**
     * @var HttpClientPool
     */
    protected $consul;
    protected $leader_name;
    protected $id;

    const TIMERTASK = 'timer_task';


    /**
     * * 构造函数
     */
    public function __construct()
    {
        $this->updateTimerTask();
        $this->timerTask();

        $this->id = swoole_timer_tick(1000, function () {
            //LogEcho('timerTask:',time());
            $this->timerTask();
        });
    }


    /**
     *
     * @throws SwooleException
     */
    protected function updateTimerTask()
    {
        $timer_tasks = ProxyServer::getInstance()->config['timerTask'];
        $this->timer_tasks_used = [];
        foreach ($timer_tasks as $name => $timer_task) {
            $task_name = $timer_task['task_name'] ?? '';
            $model_name = $timer_task['model_name'] ?? '';
            if (empty($task_name) && empty($model_name)) {
                LogEcho("[TIMERTASK]", "定时任务$name 配置错误，缺少task_name或者model_name.");
                continue;
            }
            $method_name = $timer_task['method_name'];
            $span = '';
            if (!array_key_exists('start_time', $timer_task)) {
                $start_time = time();
            } else {
                $start_time = strtotime(date($timer_task['start_time']));
                if (strpos($timer_task['start_time'], "i")) {
                    $span = " +1 minute";
                } else if (strpos($timer_task['start_time'], "H")) {
                    $span = " +1 hour";
                } else if (strpos($timer_task['start_time'], "d")) {
                    $span = " +1 day";
                } else if (strpos($timer_task['start_time'], "m")) {
                    $span = " +1 month";
                } else if (strpos($timer_task['start_time'], "Y")) {
                    $span = " +1 year";
                } else {
                    $span = '';
                }
            }
            if (!array_key_exists('end_time', $timer_task)) {
                $end_time = -1;
            } else {
                $end_time = strtotime(date($timer_task['end_time']));
            }
            if (!array_key_exists('delay', $timer_task)) {
                $delay = false;
            } else {
                $delay = $timer_task['delay'];
            }
            $interval_time = $timer_task['interval_time'] < 1 ? 1 : $timer_task['interval_time'];
            $max_exec = $timer_task['max_exec'] ?? -1;
            $this->timer_tasks_used[] = [
                'task_name'     => $task_name,
                'model_name'    => $model_name,
                'method_name'   => $method_name,
                'start_time'    => $start_time,
                'next_time'     => $start_time,
                'end_time'      => $end_time,
                'interval_time' => $interval_time,
                'max_exec'      => $max_exec,
                'now_exec'      => 0,
                'delay'         => $delay,
                'span'          => $span
            ];
        }
    }

    /**
     * 定时任务
     */
    public function timerTask()
    {
        $time = time();
        foreach ($this->timer_tasks_used as &$timer_task) {
            if ($timer_task['next_time'] < $time) {
                $count = round(($time - $timer_task['start_time']) / $timer_task['interval_time']);
                $timer_task['next_time'] = $timer_task['start_time'] + $count * $timer_task['interval_time'];
            }
            if ($timer_task['end_time'] != -1 && $time > $timer_task['end_time']) {//说明执行完了一轮，开始下一轮的初始化
                $timer_task['start_time'] = strtotime(date("Y-m-d H:i:s", $timer_task['start_time']) . $timer_task['span']);
                $timer_task['end_time'] = strtotime(date("Y-m-d H:i:s", $timer_task['end_time']) . $timer_task['span']);
                $timer_task['next_time'] = $timer_task['start_time'];
                $timer_task['now_exec'] = 0;
            }
            if (($time == $timer_task['next_time']) &&
                ($time < $timer_task['end_time'] || $timer_task['end_time'] == -1) &&
                ($timer_task['now_exec'] < $timer_task['max_exec'] || $timer_task['max_exec'] == -1)
            ) {
                if ($timer_task['delay']) {
                    $timer_task['next_time'] += $timer_task['interval_time'];
                    $timer_task['delay'] = false;
                    continue;
                }
                $timer_task['now_exec']++;
                $timer_task['next_time'] += $timer_task['interval_time'];

                EventDispatcher::getInstance()->randomDispatch(TimerTask::TIMERTASK, $timer_task);
            }
        }
    }

    /**
     * start
     */
    public static function start()
    {
        EventDispatcher::getInstance()->add(TimerTask::TIMERTASK, function (Event $event) {
            $timer_task = $event->data;
            $context = [];
            if (!empty($timer_task['task_name'])) {
                $task = TaskProxy::getInstance()->loader($timer_task['task_name']);
                $startTime = getMillisecond();
                $path = "[TimerTask] " . $timer_task['task_name'] . "::" . $timer_task['method_name'];
                $task->startTask($timer_task['task_name'], $timer_task['method_name'],[],-1, function () use (&$task) {
                    $task->destroy();
                });
            } else {
                $model = ModelProxy::getInstance()->loader($timer_task['model_name']);
                $startTime = getMillisecond();
                $path = "[TimerTask] " . $timer_task['model_name'] . "::" . $timer_task['method_name'];
                call_user_func([$model, $timer_task['method_name']]);
                $model->destroy();
            }
        });
    }
}
