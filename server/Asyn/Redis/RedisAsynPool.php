<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:23:28
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-07 17:59:54
 */

namespace Server\Asyn\Redis;

use Server\Asyn\AsynPool;
use Server\Exceptions\SwooleException;
use Server\Memory\Pool;
use Server\ProxyServer;

class RedisAsynPool extends AsynPool
{
    const AsynName = 'redis';
    /**
     * 连接
     * @var array
     */
    public $connect;
    private $active;
    private $coroutineRedisHelp;
    private $redis_client;
    protected $name;


    public function __construct($config, $active)
    {
        parent::__construct($config);
        $this->active = $active;
        $this->coroutineRedisHelp = new CoroutineRedisHelp($this);
        $this->client_max_count = $this->config->get('redis.asyn_max_count', 10);
    }

    /**
     * 映射redis方法
     * @param $name
     * @param $arguments
     * @return int
     */
    public function __call($name, $arguments)
    {
        $data = [
            'name' => $name,
            'arguments' => $arguments
        ];
        $data['token'] = $this->addTokenCallback(null);
        $return = $this->execute($data);
        return $return;
    }


    /**
     * @param $name
     * @param $arguments
     * @param $callback
     * @return array
     */
    public function call($name, $arguments, $callback)
    {
        $data = [
            'name' => $name,
            'arguments' => $arguments
        ];
        $arguments = $this->help_arguments($data);
        $data['arguments'] = $arguments;
        $data['token'] = $this->addTokenCallback($callback);
        $return = $this->execute($data);

        return $return;
    }


    protected function help_arguments(&$data)
    {
        $arguments = $data['arguments'];
        $dataName = strtolower($data['name']);
        //异步的时候有些命令不存在进行替换
        switch ($dataName) {
            case 'delete':
                $dataName = $data['name'] = 'del';
                break;
            case 'lsize':
                $dataName = $data['name'] = 'llen';
                break;
            case 'getmultiple':
                $dataName = $data['name'] = 'mget';
                break;
            case 'lget':
                $dataName = $data['name'] = 'lindex';
                break;
            case 'lgetrange':
                $dataName = $data['name'] = 'lrange';
                break;
            case 'lremove':
                $dataName = $data['name'] = 'lrem';
                break;
            case 'scontains':
                $dataName = $data['name'] = 'sismember';
                break;
            case 'ssize':
                $dataName = $data['name'] = 'scard';
                break;
            case 'sgetmembers':
                $dataName = $data['name'] = 'smembers';
                break;
            case 'zdelete':
                $dataName = $data['name'] = 'zrem';
                break;
            case 'zsize':
                $dataName = $data['name'] = 'zcard';
                break;
            case 'zdeleterangebyscore':
                $dataName = $data['name'] = 'zremrangebyscore';
                break;
            case 'zunion':
                $dataName = $data['name'] = 'zunionstore';
                break;
            case 'zinter':
                $dataName = $data['name'] = 'zinterstore';
                break;
        }
        //特别处理下M命令(批量)
        switch ($dataName) {
            case 'set':
                if (count($arguments) == 3) {
                    $harray = array_pop($arguments);
                    if (is_array($harray)) {
                        if (isset($harray['EX'])) {
                            $arguments[] = 'EX';
                            $arguments[] = $harray['EX'];
                        } elseif (isset($harray['PX'])) {
                            $arguments[] = 'PX';
                            $arguments[] = $harray['PX'];
                        }
                        if (in_array("NX", $harray)) {
                            $arguments[] = "NX";
                        } elseif (in_array("XX", $harray)) {
                            $arguments[] = "XX";
                        }
                    } elseif (is_numeric($harray)) {
                        $arguments[] = "EX";
                        $arguments[] = $harray;
                    }
                }
                break;
            case 'lpush':
            case 'srem':
            case 'zrem':
            case 'sadd':
                $key = $arguments[0];
                if (is_array($arguments[1])) {
                    $arguments = $arguments[1];
                    array_unshift($arguments, $key);
                }
                break;
            case 'del':
            case 'delete':
                if (is_array($arguments[0])) {
                    $arguments = $arguments[0];
                }
                break;
            case 'mset':
                $harray = $arguments[0];
                unset($arguments[0]);
                foreach ($harray as $key => $value) {
                    $arguments[] = $key;
                    $arguments[] = $value;
                }
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'hmset':
                $harray = $arguments[1];
                unset($arguments[1]);
                foreach ($harray as $key => $value) {
                    $arguments[] = $key;
                    $arguments[] = $value;
                }
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'mget':
                $harray = $arguments[0];
                unset($arguments[0]);
                $arguments = array_merge($arguments, $harray);
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'hmget':
                $harray = $arguments[1];
                unset($arguments[1]);
                $arguments = array_merge($arguments, $harray);
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'lrem'://这里和redis扩展的参数位置有区别
                $value = $arguments[1];
                $arguments[1] = $arguments[2];
                $arguments[2] = $value;
                break;
            case 'zrevrange':
            case 'zrange':
                if (count($arguments) == 4) {//存在withscores
                    if ($arguments[3]) {
                        $arguments[3] = 'withscores';
                        $data['withscores'] = true;
                    } else {
                        unset($arguments[3]);
                    }
                }
                break;
            case 'zrevrangebyscore'://需要解析参数
            case 'zrangebyscore'://需要解析参数
                if (count($arguments) == 4) {//存在额外参数
                    $arg = $arguments[3];
                    unset($arguments[3]);
                    $data['withscores'] = $arg['withscores'] ?? false;
                    if ($data['withscores']) {
                        $arguments[] = 'withscores';
                    }
                    if (array_key_exists('limit', $arg)) {//存在limit
                        $arguments[] = 'limit';
                        $arguments[] = $arg['limit'][0];
                        $arguments[] = $arg['limit'][1];
                    }
                }
                break;
            case 'zinterstore':
            case 'zunionstore':
                $arg = $arguments;
                $argCount = count($arg);
                unset($arguments);
                $arguments[] = $arg[0];
                $arguments[] = count($arg[1]);
                foreach ($arg[1] as $value) {
                    $arguments[] = $value;
                }
                if ($argCount >= 3) {//有WEIGHT
                    $arguments[] = 'WEIGHTS';
                    foreach ($arg[2] as $value) {
                        $arguments[] = $value;
                    }
                }
                if ($argCount == 4) {//有AGGREGATE
                    $arguments[] = 'AGGREGATE';
                    $arguments[] = $arg[3];
                }
                break;
            case 'sort':
                $arg = $arguments;
                $argCount = count($arg);
                unset($arguments);
                $arguments[] = $arg[0];
                if ($argCount == 2) {
                    if (array_key_exists('by', $arg[1])) {
                        $arguments[] = 'by';
                        $arguments[] = $arg[1]['by'];
                    }
                    if (array_key_exists('limit', $arg[1])) {
                        $arguments[] = 'limit';
                        $arguments[] = $arg[1]['limit'][0];
                        $arguments[] = $arg[1]['limit'][1];
                    }
                    if (array_key_exists('get', $arg[1])) {
                        if (is_array($arg[1]['get'])) {
                            foreach ($arg[1]['get'] as $value) {
                                $arguments[] = 'get';
                                $arguments[] = $value;
                            }
                        } else {
                            $arguments[] = 'get';
                            $arguments[] = $arg[1];
                        }
                    }
                    if (array_key_exists('sort', $arg[1])) {
                        $arguments[] = $arg[1]['sort'];
                    }
                    if (array_key_exists('alpha', $arg[1])) {
                        $arguments[] = $arg[1]['alpha'];
                    }
                    if (array_key_exists('store', $arg[1])) {
                        $arguments[] = 'store';
                        $arguments[] = $arg[1]['store'];
                    }
                }
                break;
            case 'eval':
            case 'evalsha':
                $sha1 = $arguments[0];
                $keys = $arguments[1];
                $keynum = $arguments[2] ?? 0;
                $args = $arguments[3] ?? [];
                $arguments = $keys;
                array_unshift($arguments, $keynum);
                array_unshift($arguments, $sha1);
                foreach ($args as $value) {
                    $arguments[] = $value;
                }
                break;
        }
        return array_values($arguments);
    }



    /**
     * 执行redis命令
     * @param $data
     */
    public function execute($data)
    {
        $client = $this->shiftFromPool($data);
        if ($client) {
            try {
                $arguments = $data['arguments'];
                $data['result'] = call_user_func_array([$client, $data['name']], $arguments);
            } catch (\RedisException $e) {
                $this->reconnect($client);
                $this->execute($data);
                // $this->commands->push($data);
            }

            //分发消息
            $this->distribute($data);
            //回归连接
            $this->pushToPool($client);
            return $data['result'];
        }
    }


    /**
     * 重连或者连接
     * @param null $client
     */
    public function reconnect($client = null)
    {
        if ($client == null) {
            $client = new \Redis();
        }

        if ($client->connect($this->config['redis'][$this->active]['ip'], $this->config['redis'][$this->active]['port']) == false) {
            throw new SwooleException($client->getLastError());
            $client = null;
        }
        if (!empty($this->config->get('redis.' . $this->active . '.password', ""))) {//存在验证
            if ($client->auth($this->config['redis'][$this->active]['password']) == false) {
                throw new SwooleException($client->getLastError());
                $client = null;
            }
        }
        if ($this->config->has('redis.' . $this->active . '.select')) {//存在select
            $client->select($this->config['redis'][$this->active]['select']);
        }
        $this->pushToPool($client);
    }



    /**
     * 准备一个redis
     */
    public function prepareOne()
    {
        if (parent::prepareOne()) {
            $this->reconnect();
        }
    }



    /**
     * 协程模式
     * @param string $name
     * @param array ...$arg
     * @param callable $set
     * @return RedisCoroutine
     * @throws SwooleException
     */
    public function coroutineSend($name, $arg, callable $set = null)
    {
        if (ProxyServer::getInstance()->isTaskWorker()) {//如果是task进程自动转换为同步模式
            try {
                $value = call_user_func_array([$this->getSync(), $name], $arg);
            } catch (\RedisException $e) {
                $this->redis_client = null;
                $value = call_user_func_array([$this->getSync(), $name], $arg);
            }
            return $value;
        } else {
            return Pool::getInstance()->get(RedisCoroutine::class)->init($this, $name, $arg, $set);
        }
    }

    /**
     * 获取同步
     * @return \Redis
     * @throws SwooleException
     */
    public function getSync()
    {
        if ($this->redis_client != null) {
            return $this->redis_client;
        }
        //同步redis连接，给task使用
        $this->redis_client = new \Redis();
        if ($this->redis_client->connect($this->config['redis'][$this->active]['ip'], $this->config['redis'][$this->active]['port']) == false) {
            throw new SwooleException($this->redis_client->getLastError());
            $this->redis_client = null;
        }
        if (!empty($this->config->get('redis.' . $this->active . '.password', ""))) {//存在验证
            if ($this->redis_client->auth($this->config['redis'][$this->active]['password']) == false) {
                throw new SwooleException($this->redis_client->getLastError());
                $this->redis_client = null;
            }
        }
        if ($this->config->has('redis.' . $this->active . '.select')) {//存在select
            $this->redis_client->select($this->config['redis'][$this->active]['select']);
        }
        return $this->redis_client;
    }

    /**
     * 协程模式 更加便捷
     * @return \Redis
     * @throws SwooleException
     */
    public function getCoroutine()
    {
        return $this->coroutineRedisHelp;
    }




    /**
     * @return string
     */
    public function getAsynName()
    {
        return self::AsynName . ":" . $this->name;
    }

    /**
     * 销毁Client
     * @param \Redis $client
     */
    protected function destoryClient($client)
    {
        $client->close();
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
