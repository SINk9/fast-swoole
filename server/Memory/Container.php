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
namespace Server\Memory;

use Server\Exception\SwooleException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{


    private static $instance;

    /**
     * @var Pool[]
     */
    protected $pools = [];


    /**
     * 获取实例
     * @return Pool
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Container();
        }
        return self::$instance;
    }



    public function get($name)
    {
   
        $pool = $this->pools[$name]??null;
        if ($pool == null) {
            $pool = $this->applyNewPool($name);
        }
        if (!$pool->isEmpty()) {
            return $pool->shift();
        } else {
            return new $name($this);
        }

        if (array_key_exists($name, $this->pools)) {
            throw new SwooleException('the name is exists in pool pools');
        }
        $this->pools[$name] = new \SplStack();
        return $this->pools[$name];

    }


    /**
     * 添加一个
     * @param  $class
     * @return mixed
     */
    private function applyNewPool($class)
    {
        if (array_key_exists($class, $this->pools)) {
            throw new SwooleException('the name is exists in pool pools');
        }
        $this->pools[$class] = new \SplStack();
        return $this->pools[$class];
    }




    public function has($name)
    {
        $pool = $this->pools[$name];
        if ($pool == null) {
            return false;
        }else{
            return true;
        }
    }

}
