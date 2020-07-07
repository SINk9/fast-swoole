<?php

declare(strict_types=1);
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:56:09
 */

namespace Server\Asyn\Mysql;
use Server\Asyn\IAsynPool;
use Server\ProxyServer;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;


class MysqlPool extends Pool implements IAsynPool
{

    const AsynName = 'database:';

    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $mysql = ProxyServer::getInstance()->config->get('mysql');
        $this->config = $mysql;
        $options = $mysql[$mysql['active']]['pool'];
        parent::__construct($container, $options);
    }



    protected function createConnection(): ConnectionInterface
    {
        return new Connection($this->container, $this, $this->config);
    }


    function getAsynName()
    {

    }

    function pushToPool($client)
    {

    }

    function getSync()
    {

    }

    function setName($name)
    {

    }


}
