<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:23:28
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-07 10:22:22
 */


namespace Server\Asyn\Redis;

use Server\Memory\Pool;
use Server\Start;

class RedisCoroutine
{
    /**
     * @var RedisAsynPool
     */
    public $redisAsynPool;
    public $name;
    public $arguments;
    public $token;
    public $request;
    protected $pool_chan;
    /**
     * 对象池模式用来代替__construct
     * @param $redisAsynPool
     * @param $name
     * @param $arguments
     * @param $set
     * @return $this
     */
    public function init($redisAsynPool, $name, $arguments, $set)
    {

        $this->redisAsynPool = $redisAsynPool;
        $this->name = $name;
        $this->arguments = $arguments;
        $d = "[$name ".implode(" ",$arguments)."]";
        $this->request = "[redis]$d";
        $callback = null; //未完善
        return $this->send($callback);
    }

    public function send($callback)
    {
        return $this->redisAsynPool->call($this->name, $this->arguments, $callback);
    }

    public function destroy()
    {
        $this->redisAsynPool->removeTokenCallback($this->token);
        $this->token = null;
        $this->redisAsynPool = null;
        $this->name = null;
        $this->arguments = null;
        Pool::getInstance()->push($this);
    }

    protected function onTimerOutHandle()
    {
        $this->redisAsynPool->destoryGarbage($this->token);
    }
}