<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 14:21:21
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 10:00:52
 */

namespace App;

use Server\ProxyServer;
use App\Process\CustomProcess;
use App\Timer\Timing;
use Server\Process\ProcessManager;

class AppServer extends ProxyServer
{
    /**
     * 可以在这里自定义Loader，但必须是ILoader接口
     * AppServer constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 用户进程
     * @throws \Exception
     */
    public function startProcess()
    {
    	//ProcessManager::getInstance()->addProcess(CustomProcess::class);
        parent::startProcess();
    }


   /**
     * 重写onSwooleWorkerStart方法
     * @param $serv
     * @param $workerId
     * @throws \Exception
     */
    public function onSwooleWorkerStart($serv, $workerId)
    {
        parent::onSwooleWorkerStart($serv, $workerId);
        if (!$this->isTaskWorker()) {
            Timing::start();
        }
    }


}
