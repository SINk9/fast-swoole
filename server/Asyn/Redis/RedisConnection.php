<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Server\Asyn\Redis;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Server\Exceptions\SwooleException;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Server\Asyn\Redis\Traits\ScanCaller;
use Server\ProxyServer;

/**
 * @method bool select(int $db)
 */
class RedisConnection extends BaseConnection implements ConnectionInterface
{
    use ScanCaller;


    /**
     * @var \Redis
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config = [
        'host'    => 'localhost',
        'port'    => 6379,
        'auth'    => null,
        'db'      => 0,
        'timeout' => 0.0,
        'cluster' => [
            'enable' => false,
            'name' => null,
            'seeds' => [],
        ],
        'options' => [],
    ];

    /**
     * Current redis database.
     * @var null|int
     */
    protected $database;

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = $config[$config['active']];
        $this->active = $config['active'];
        //$this->reconnect();
    }

    public function __call($name, $arguments)
    {
        try {
            //$arguments = RedisQueryHelp::arguments([$name,$arguments]);
            $result = call_user_func_array([$this->connection, $name], $arguments);
        } catch (\Throwable $exception) {
            LogEcho('RedisCall:', $exception->getMessage());
            $result = $this->retry($name, $arguments, $exception);
        }

        return $result;
    }

    public function getActiveConnection()
    {

        if (ProxyServer::getInstance()->isTaskWorker()) {
            LogEcho('RedisConnection:','Sync');
            return $this->getSync();
        }

        if ($this->check()) {
            return $this;
        }

        if (! $this->reconnect()) {
            throw new SwooleException('Connection reconnect failed.');
        }

        return $this;
    }


    public function getSync()
    {
        if ($this->connection != null) {
            return $this;
        }
        if(! $this->reconnect()){
            throw new SwooleException('Connection reconnect failed.');
        }

        return $this;
    }



    public function reconnect(): bool
    {
        $host    = $this->config['host'];
        $port    = $this->config['port'];
        $auth    = $this->config['auth'];
        $db      = $this->config['db'];
        $timeout = $this->config['timeout'];

        $redis = new \Redis();
        if (! $redis->connect($host, $port, $timeout)) {
            LogEcho('RedisConnection:', 'Connection reconnect failed.');
            throw new SwooleException('Connection reconnect failed.');
        }

        $options = $this->config['options'] ?? [];

        foreach ($options as $name => $value) {
            // The name is int, value is string.
            $redis->setOption($name, $value);
        }

        if (isset($auth) && $auth !== '') {
            $redis->auth($auth);
        }

        $database = $this->database ?? $db;
        if ($database > 0) {
            $redis->select($database);
        }

        $this->connection = $redis;
        $this->lastUseTime = microtime(true);

        return true;
    }

    public function close(): bool
    {
        unset($this->connection);

        return true;
    }

    public function release(): void
    {
        if ($this->database && $this->database != $this->config['db']) {
            // Select the origin db after execute select.
            $this->select($this->config['db']);
            $this->database = null;
        }
        parent::release();
    }

    public function setDatabase(?int $database): void
    {
        $this->database = $database;
    }



    protected function retry($name, $arguments, \Throwable $exception)
    {
        //$logger = $this->container->get(StdoutLoggerInterface::class);
        //$logger->warning(sprintf('Redis::__call failed, because ' . $exception->getMessage()));

        try {
            $this->reconnect();
            $result = call_user_func_array([$this->connection, $name], $arguments);
        } catch (\Throwable $exception) {
            $this->lastUseTime = 0.0;
            throw $exception;
        }

        return $result;
    }
}
