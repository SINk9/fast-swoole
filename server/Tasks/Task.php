<?php

/**
 * Task 异步任务
 * @Author: sink
 * @Date:   2019-08-12 15:19:48
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:52:06
 */

namespace Server\Tasks;

use Server\Memory\Pool;
use Server\ProxyServer;

class Task extends TaskProxy
{
    protected $start_run_time;
    protected static $efficiency_monitor_enable;

   /**
     * @var db
     */
    public $db;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var parameter
     */
    protected $parameter;


    public function __construct()
    {
        parent::__construct();
        if (self::$efficiency_monitor_enable == null) {
            self::$efficiency_monitor_enable = $this->config['log'][$this->config['log']['active']]['efficiency_monitor_enable'];
        }
    }

    /**
     * @param $task_id
     * @param $from_id 来自哪个worker进程
     * @param $worker_pid 在哪个task进程中运行
     * @param $task_name
     * @param $method_name
     * @param $context
     */
    public function initialization($task_id, $from_id, $worker_pid, $task_name, $method_name, $arguments, $context)
    {
        $this->task_id = $task_id;
        $this->from_id = $from_id;
        ProxyServer::getInstance()->tid_pid_table->set($this->from_id . $this->task_id, ['pid' => $worker_pid, 'des' => "$task_name::$method_name", 'start_time' => time()]);
        $this->setContext($context);
        $this->start_run_time = microtime(true);
        $this->context['task_name'] = "$task_name:$method_name";
        $this->parameter = $arguments;
        $this->db = $this->loader->mysql('mysqlPool', $this);
        $this->redis = $this->loader->redis('redisPool', $this);
    }


    /**
     * 执行任务
     */
    public function execute($method_name)
    {
        //运行
        try {
            $this->$method_name();
        } catch (Throwable $e) {
            $this->onExceptionHandle($e);
        }
        $this->destroy();
    }


    /**
     * * 销毁
     * @return [type] [description]
     */
    public function destroy()
    {
        $this->context['execution_time'] = (microtime(true) - $this->start_run_time) * 1000;
        //log
        // if (self::$efficiency_monitor_enable) {
        //     $this->log('Monitor:Task');
        // }
        ProxyServer::getInstance()->tid_pid_table->del($this->from_id . $this->task_id);
        $this->task_id = 0;
        $this->parameter = null;
        Pool::getInstance()->push($this);
    }



    /**
     * 异常的回调
     * @param Throwable $e
     * @param callable $handle
     */
    public function onExceptionHandle(\Throwable $e)
    {

        if ($e instanceof SwooleException) {
            LogEcho("EX", "--------------------------[报错指南]----------------------------" . date("Y-m-d h:i:s"));
            LogEcho("EX", "异常消息：" . $e->getMessage());
            LogEcho("EX", "运行链路:");
            foreach ($this->context as $key => $value) {
                LogEcho("EX", "$key# $value");
            }
            LogEcho("EX", "--------------------------------------------------------------");
        }
        $this->context['error_message'] = $e->getMessage();
        //log
        // if (self::$efficiency_monitor_enable) {
        //     $this->log('Monitor:Task');
        // }
    }

    /**
     * sendToUid
     * @param $uid
     * @param $data
     * @throws \Exception
     */
    protected function sendToUid($uid, $data)
    {
        ProxyServer::getInstance()->sendToUid($uid, $data);
    }

    /**
     * sendToUids
     * @param $uids
     * @param $data
     * @throws SwooleException
     */
    protected function sendToUids($uids, $data)
    {
        ProxyServer::getInstance()->sendToUids($uids, $data);
    }

    /**
     * sendToAll
     * @param $data
     * @throws SwooleException
     */
    protected function sendToAll($data)
    {
        ProxyServer::getInstance()->sendToAll($data);
    }

    /**
     * @param $data
     * @throws SwooleException
     */
    protected function sendToAllFd($data)
    {
        ProxyServer::getInstance()->sendToAllFd($data);
    }

    /**
     * 添加订阅 [未开发]
     * @param $uid
     * @param $topic
     * @throws \Exception
     */
    protected function addSub($uid,$topic)
    {
        ProxyServer::getInstance()->addSub($topic, $uid);
    }

    /**
     * 移除订阅 [未开发]
     * @param $uid
     * @param $topic
     * @throws \Exception
     */
    protected function removeSub($uid,$topic)
    {
        ProxyServer::getInstance()->removeSub($topic, $uid);
    }

    /**
     * 发布订阅 [未开发]
     * @param $topic
     * @param $data
     * @param array $excludeUids 需要排除的uids
     * @throws \Server\Asyn\MQTT\Exception
     */
    protected function sendPub($topic, $data, $excludeUids = [])
    {
        ProxyServer::getInstance()->pub($topic, $data, $excludeUids);
    }

}
