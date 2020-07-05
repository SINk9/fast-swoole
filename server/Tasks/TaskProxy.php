<?php

/**
 * Task 代理
 * @Author: sink
 * @Date:   2019-08-12 15:20:20
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:30:22
 */

namespace Server\Tasks;

use Server\Memory\Pool;
use Server\SwooleConst;
use Server\ProxyServer;
use Server\Exceptions\SwooleException;
use Server\CoreBase\CoreBase;

class TaskProxy extends CoreBase
{
    protected $task_id;
    protected $from_id;
    /**
     * task代理数据
     * @var mixed
     */
    private $task_proxy_data;

   /**
     * @var ControllerFactory
     */
    private static $instance;

    /**
     * TaskProxy constructor.
     * @param string $proxy
     */
    public function __construct()
    {
        parent::__construct();
    	self::$instance = $this;
    }


    /**
     * 获取单例
     * @return ControllerFactory
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            new TaskProxy();
        }
        return self::$instance;
    }



    private function buildData($task_name, $method_name, $arguments)
    {
        $this->task_proxy_data =
            [
                'type' => SwooleConst::SERVER_TYPE_TASK,
                'message' =>
                    [
                        'task_name' => $task_name,
                        'task_fuc_name' => $method_name,
                        'task_fuc_data' => $arguments,
                        'task_context' => $this->getContext(),
                    ]
            ];
    }


    /**
     * *
     * @param  [type] $task [description]
     * @return [type]       [description]
     */
    public function loader($task)
    {

        if (empty($task)) {
            return null;
        }
        if (class_exists($task)) {
            $task_class = $task;
        } else {
            $task = str_replace('/', '\\', $task);
            $task_class = "App\\Tasks\\" . $task;
            if (!class_exists($task_class)) {
                throw new SwooleException("class task_class not exists");
            }
        }
        $task_instance = Pool::getInstance()->get($task_class);
        return $task_instance;

    }


    /**
     * 开始异步任务
     * @param $name
     * @param $arguments
     * @param int $dst_worker_id
     * @param null $callback
     */
    public function startTask($task_name, $method_name, $arguments, $dst_worker_id = -1, $callback = null)
    {
        $this->buildData($task_name, $method_name, $arguments);
        ProxyServer::getInstance()->server->task($this->task_proxy_data, $dst_worker_id, $callback);
    }

}
