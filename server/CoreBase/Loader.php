<?php
/**
 * @Author: sink
 * @Date:   2019-08-12 15:11:07
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:57:54
 */

namespace Server\CoreBase;

use Server\Asyn\Mysql\MysqlPool;
use Server\Asyn\Redis\RedisPool;
use Server\Memory\Pool;
use Server\ProxyServer;

class Loader implements ILoader
{
    private $_task_proxy;
    private $_model_factory;
    private $_mysql_container;

    public function __construct()
    {
        //$this->_task_proxy = new TaskProxy();
        //$this->_model_factory = ModelFactory::getInstance();
    }

    /**
     * 获取一个redis
     * @param $name
     * @return \Redis
     */
    public function redis($name, Child $parent)
    {
        if (empty($name)) {
            return null;
        }
        if($parent->root == null){
            $parent->root = $parent;
        }
        $root = $parent->root;
        $core_name = RedisPool::AsynName . ":" .$name;
        if ($root->hasChild($core_name)) {
            return $root->getChild($core_name);
        }
        $redisPool = ProxyServer::getInstance()->getAsynPool($name);
        $redisPool = $redisPool->get();
        if($redisPool == null){
            return null;
        }
        $redisCoroutine = $redisPool->getActiveConnection();
        $root->newAddChild($core_name, $redisCoroutine);
        return $redisCoroutine;
    }

    /**
     * 获取一个mysql
     * @param $name
     * @param Child $parent
     * @return Miner
     */
    public function mysql($name, Child $parent)
    {
        if (empty($name)) {
            return null;
        }
        if($parent->root == null){
            $parent->root = $parent;
        }
        $root = $parent->root;
        $core_name = MysqlPool::AsynName . ":" .$name;
        if ($root->hasChild($core_name)) {
            return $root->getChild($core_name);
        }
        $mysqlPool = ProxyServer::getInstance()->getAsynPool($name);
        $mysqlPool = $mysqlPool->get();
        if($mysqlPool == null){
            return null;
        }
        $mysqlContainer = $mysqlPool->getActiveConnection();
        $root->newAddChild($core_name, $mysqlContainer);
        return $mysqlContainer;
    }


    /**
     * 获取一个model
     * @param $model
     * @param Child $parent
     * @return mixed|null
     * @throws SwooleException
     */
    public function model($model, Child $parent)
    {
        if (empty($model)) {
            return null;
        }
        if($parent->root == null){
            $parent->root = $parent;
        }

    }

    /**
     * 获取一个task
     * @param $task
     * @param Child $parent
     * @return mixed|null|TaskProxy
     * @throws SwooleException
     */


    public function task($task, Child $parent = null)
    {
        if (empty($task)) {
            return null;
        }
        if (class_exists($task)) {
            $task_class = $task;
        } else {
            $task = str_replace('/', '\\', $task);
            $task_class = "App\\Tasks\\" . $task;
            if (!class_exists($task_class)) {
                throw new SwooleException("class task_class not exists");
            }
        }
        $task_instance = Pool::getInstance()->get($task_class);
        $task_instance->reUse();
        return $task_instance;
    }


}
