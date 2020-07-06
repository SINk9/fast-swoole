<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-9-1
 * Time: 下午4:25
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
        $callback = array_pop($this->arguments);
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
        parent::onTimerOutHandle();
        $this->redisAsynPool->destoryGarbage($this->token);
    }
}