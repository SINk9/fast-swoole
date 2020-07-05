<?php

/**
 * 进程管理
 * @Author: sink
 * @Date:   2019-08-05 13:50:29
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:08
 */

namespace Server\Process;

use Server\ProxyServer;
use Server\Exceptions\SwooleException;


class ProcessManager
{
    /**
     * @var ProcessManager
     */
    protected static $instance;
    protected $atomic;
    /**
     * @var Process[]
     */
    protected $map = [];

    public $oneWayFucName = [];

    /**
     * ProcessManager constructor.
     */
    public function __construct()
    {
        $this->atomic = new \swoole_atomic();
    }

    /**
     * @param $class_name
     * @param string $name
     * @param array $params
     * @return Process
     * @throws \Exception
     */
    public function addProcess($class_name, $name = '', $params = [])
    {
        $worker_id = ProxyServer::getInstance()->worker_num + ProxyServer::getInstance()->task_num + $this->atomic->get();
        $this->atomic->add();
        $names = explode("\\", $class_name);
        $process = new $class_name(ProxyServer::getServerName() . "-" . $names[count($names) - 1], $worker_id, $params);
        if (array_key_exists($class_name . $name, $this->map)) {
            throw new SwooleException('存在相同类型的进程，需要设置别名');
        }
        $this->map[$class_name . $name] = $process;
        return $process;
    }

    /**
     * @return ProcessManager
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ProcessManager();
        }
        return self::$instance;
    }

    /**
     * @param $class_name
     * @param $name
     * @return ProcessRPC
     * @throws \Exception
     */
    public function getProcess($class_name, $name = '')
    {
        if (!array_key_exists($class_name . $name, $this->map)) {
            throw new SwooleException("不存在$class_name 进程");
        }
        return $this->map[$class_name . $name];
    }

    /**
     * @param $workerId
     * @return Process
     */
    public function getProcessFromID($workerId)
    {
        foreach ($this->map as $process) {
            if ($process->worker_id == $workerId) {
                return $process;
            }
        }
        return null;
    }

    /**
     * 向所有进程广播消息
     * @param $data
     * @throws \Exception
     */
    public function sendToAllProcess($data)
    {
        foreach ($this->map as $process){
            $process->sendMessage($data,$process->worker_id);
        }
    }
}
