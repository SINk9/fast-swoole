<?php
declare(strict_types=1);
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:56:09
 */

namespace Server\Asyn\Mysql;

use Hyperf\Pool\Connection as BaseConnection;;
use Server\Exceptions\SwooleException;
use Server\ProxyServer;
use Hyperf\Database\ConnectionInterface as DbConnectionInterface;
use Hyperf\Database\Connectors\ConnectionFactory;
use Psr\Container\ContainerInterface;
//use Psr\EventDispatcher\EventDispatcherInterface;

class Connection extends BaseConnection
{

    private $active;

    /**
     * @var DbConnectionInterface
     */
    protected $connection;

    /**
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var float
     */
    protected $lastUseTime = 0.0;

    /**
     * @var boolean
     */
    protected $transaction = false;


    public function __construct(ContainerInterface $container, MysqlPool $pool, $config)
    {
        parent::__construct($container, $pool);
        $this->factory = $container->get(ConnectionFactory::class);
        $this->config = $config[$config['active']];
        //$this->logger = $container->get(StdoutLoggerInterface::class);
        $this->active = $config['active'];
    }


    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }


    /**
     * * 获取活跃的链接
     * @return [type] [description]
     */
    public function getActiveConnection(): DbConnectionInterface
    {

        if (ProxyServer::getInstance()->isTaskWorker()) {
            return $this->getSync();
        }

        if ($this->check()) {
            return $this;
        }
        return $this->reconnect();
    }



    public function reconnect(): bool
    {
        $this->connection = $this->factory->make($this->config);

        if ($this->connection instanceof \Hyperf\Database\Connection) {
            // Reset event dispatcher after db reconnect.
            // if ($this->container->has(EventDispatcherInterface::class)) {
            //     $dispatcher = $this->container->get(EventDispatcherInterface::class);
            //     $this->connection->setEventDispatcher($dispatcher);
            // }

            // Reset reconnector after db reconnect.
            $this->connection->setReconnector(function ($connection) {
                $this->logger->warning('Database connection refreshing.');
                if ($connection instanceof \Hyperf\Database\Connection) {
                    $this->refresh($connection);
                }
            });
        }

        $this->lastUseTime = microtime(true);
        return $this->connection;
    }



    /**
     * * 获取同步连接
     * @return [type] [description]
     */
    public function getSync()
    {
        if ($this->connection != null) {
            return $this->connection;
        }
        return $this->reconnect();
    }



    public function close(): bool
    {
        unset($this->connection);

        return true;
    }


    public function release(): void
    {
        if ($this->connection instanceof \Hyperf\Database\Connection) {
            // Reset $recordsModified property of connection to false before the connection release into the pool.
            $this->connection->resetRecordsModified();
        }

        if ($this->isTransaction()) {
            $this->rollBack(0);
            //$this->logger->error('Maybe you\'ve forgotten to commit or rollback the MySQL transaction.');
        }

        parent::release();
    }


    public function setTransaction(bool $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function isTransaction(): bool
    {
        return $this->transaction;
    }


    /**
     * Refresh pdo and readPdo for current connection.
     */
    protected function refresh(\Hyperf\Database\Connection $connection)
    {
        $refresh = $this->factory->make($this->config);
        if ($refresh instanceof \Hyperf\Database\Connection) {
            $connection->disconnect();
            $connection->setPdo($refresh->getPdo());
            $connection->setReadPdo($refresh->getReadPdo());
        }

        //$this->logger->warning('Database connection refreshed.');
    }



}
