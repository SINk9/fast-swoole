<?php

/**
 * Model 涉及到数据有关的处理
 * 对象池模式，实例会被反复使用，成员变量缓存数据记得在销毁时清理
 * @Author: sink
 * @Date:   2019-08-29 18:33:58
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 12:52:14
 */

namespace Server\Models;

use Server\CoreBase\CoreBase;

class Model extends CoreBase
{

    /**
     * @var Miner
     */
    public $db;

    /**
     * @var \Redis
     */
    protected $redis;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 当被loader时会调用这个方法进行初始化
     * @param $context
     */
    public function initialization(&$context)
    {
        $this->setContext($context);
        $this->redis = $this->loader->redis("redisPool", $this);
        $this->db = $this->loader->mysql("mysqlPool", $this);
    }


    /**
     * 销毁回归对象池
     */
    public function destroy()
    {
        parent::destroy();
        ModelFactory::getInstance()->revertModel($this);
    }

}
