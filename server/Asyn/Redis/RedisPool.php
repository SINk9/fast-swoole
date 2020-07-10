<?php

/**
 * @Author: sink
 * @Date:   2020-07-10 11:13:32
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:44:09
 */

declare(strict_types=1);
namespace Server\Asyn\Redis;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;

class RedisPool extends Pool
{

    const AsynName = 'redis:';

    /**
     * @var array
     */
    protected $config;

    public function __construct($config, ContainerInterface $container)
    {
        $this->config = $config;
        $options = $config[$config['active']]['pool'];
        parent::__construct($container, $options);

    }


    protected function createConnection(): ConnectionInterface
    {
        return new RedisConnection($this->container, $this, $this->config);
    }
}
