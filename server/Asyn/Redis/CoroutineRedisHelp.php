<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:23:28
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-06 13:39:54
 */


namespace Server\Asyn\Redis;


use Server\Memory\Pool;
use Server\ProxyServer;

class CoroutineRedisHelp
{
    /**
     * @var RedisAsynPool
     */
    private $redisAsynPool;

    public function __construct($redisAsynPool)
    {
        $this->redisAsynPool = $redisAsynPool;
    }

    public function __call($name, $arguments)
    {
        if (ProxyServer::getInstance()->isTaskWorker()) {//如果是task进程自动转换为同步模式

            return call_user_func_array([$this->redisAsynPool->getSync(), $name], $arguments);
        } else {

            return Pool::getInstance()->get(RedisCoroutine::class)->init($this->redisAsynPool, $name, $arguments, null);
        }
    }
}