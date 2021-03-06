<?php

declare(strict_types=1);
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:09:38
 */

namespace Server\Asyn\Mysql;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;


class MysqlPool extends Pool
{

    const AsynName = 'database:';

    protected $config;

    public function __construct($config, ContainerInterface $container)
    {
        $this->config = $config;
        $options = $config[$config['active']]['pool'];
        parent::__construct($container, $options);
    }


    protected function createConnection(): ConnectionInterface
    {
        return new MysqlConnection($this->container, $this, $this->config);
    }



}
