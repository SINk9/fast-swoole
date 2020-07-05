<?php

/**
 * @Author: sink
 * @Date:   2019-08-05 13:57:02
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:03
 */

namespace Server\Process;

use Server\ProxyServer;

abstract class Process
{
    public $process;
    public $instance;

    public $worker_id;
    protected $config;
    protected $log;
    protected $token = 0;
    protected $params;
    protected $socketBuff = "";

    /**
     * Process constructor.
     * @param string $name
     * @param $worker_id
     * @param $params
     */
    public function __construct($name, $worker_id, $params)
    {
        $this->name = $name;
        $this->worker_id = $worker_id;
        $this->instance = ProxyServer::getInstance();
        $this->instance->workerId = $worker_id;
        $this->config = $this->instance->config;
        $this->params = $params;
        if ($this->instance->server != null) {
            $this->process = new \swoole_process([$this, '__start'], false, 1);
            $this->instance->server->addProcess($this->process);
        }
    }


    public function __start($process)
    {
        \swoole_process::signal(SIGTERM, [$this, "__shutdown"]);
        $this->instance->workerId = $this->worker_id;
        if (PHP_OS != "Darwin") {
            $process->name($this->name);
        }
        swoole_event_add($process->pipe, [$this, 'onRead']);
        $this->instance->server->worker_id = $this->worker_id;
        $this->instance->server->taskworker = false;
        go(function () use ($process) {
            $this->start($process);
        });
    }


    /**
     * @param $process
     */
    public abstract function start($process);


    /**
     * 关服处理
     */
    public function __shutdown()
    {
        $this->shutdown();
        LogEcho("Process:$this->worker_id", get_class($this) . "关闭成功");
        $this->process->exit(0);
    }

    abstract protected function shutdown();

    /**
     * onRead
     */
    public function onRead()
    {
        while (true) {
            try {
                $recv = $this->process->read();
            } catch (\Throwable $e) {
                return;
            }
            $this->socketBuff .= $recv;
            while (strlen($this->socketBuff) > 4) {
                $len = unpack("N", $this->socketBuff)[1];
                if (strlen($this->socketBuff) >= $len) {//满足完整一个包
                    $data = substr($this->socketBuff, 4, $len - 4);
                    $recv_data = \swoole_serialize::unpack($data);
                    $this->readData($recv_data);
                    $this->socketBuff = substr($this->socketBuff, $len);
                } else {
                    break;
                }
            }
        }
    }

    /**
     * @param $data
     */
    public function readData($data)
    {
        go(function () use ($data) {
            $message = $data['message'];
            if (!empty($data['func'])) {
                $data['func']($message);
            }
        });
    }

    /**
     * 执行外部命令
     * @param $path
     * @param $params
     */
    protected function exec($path, $params)
    {
        $this->process->exec($path, $params);
    }
}
