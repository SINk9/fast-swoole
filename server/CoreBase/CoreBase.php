<?php

/**
 * @Author: sink
 * @Date:   2019-08-12 15:11:07
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 10:56:45
 */

namespace Server\CoreBase;

use Monolog\Logger;
use Server\Memory\Pool;
use Server\ProxyServer;
use Noodlehaus\Config;

class CoreBase extends Child
{
    /**
     * 销毁标志
     * @var bool
     */
    public $is_destroy = false;

    /**
     * @var Loader
     */
    public $loader;
    /**
     * @var Logger
     */
    public $logger;
    /**
     * @var swoole_server
     */
    public $server;
    /**
     * @var Config
     */
    public $config;

    /**
     * Task constructor.
     * @param string $proxy
     */
    public function __construct()
    {
        if (!empty(ProxyServer::getInstance())) {
            $this->loader = ProxyServer::getInstance()->loader;
            $this->logger = ProxyServer::getInstance()->logger;
            $this->server = ProxyServer::getInstance()->server;
            $this->config = ProxyServer::getInstance()->config;
        }
    }

    /**
     * 销毁，解除引用
     */
    public function destroy()
    {
        parent::destroy();
        $this->is_destroy = true;
    }

    /**
     * 对象复用
     */
    public function reUse()
    {
        $this->is_destroy = false;
    }

    /**
     * 打印日志
     * @param $message
     * @param int $level
     */
    protected function log($message, $level = Logger::DEBUG)
    {
        try {
            //$this->logger->addRecord($level, $message, $this->getContext());
        } catch (\Exception $e) {

        }
    }
}
