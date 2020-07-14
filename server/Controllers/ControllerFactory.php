<?php

/**
 * 控制器工厂  [对象池模式] 实例会被反复使用，成员变量缓存数据记得在销毁时清理
 * @Author: sink
 * @Date:   2019-08-09 11:57:10
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 12:03:01
 */


namespace Server\Controllers;


class ControllerFactory
{
    /**
     * @var ControllerFactory
     */
    private static $instance;
    private $pool = [];
    private $pool_count = [];

    /**
     * ControllerFactory constructor.
     */
    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * 获取单例
     * @return ControllerFactory
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            new ControllerFactory();
        }
        return self::$instance;
    }

    /**
     * 获取一个Controller
     * @param $controller string
     * @return Controller
     */
    public function getController($controller)
    {
        if ($controller == null) return null;
        $controllers = $this->pool[$controller] ?? null;
        if ($controllers == null) {
            $controllers = $this->pool[$controller] = new \SplQueue();
        }
        if (!$controllers->isEmpty()) {
            $controller_instance = $controllers->shift();
            return $controller_instance;
        }
        if (class_exists($controller)) {
            $controller_instance = new $controller;
            if ($controller_instance instanceof Controller) {
                $controller_instance->core_name = $controller;
                $this->addNewCount($controller);
                return $controller_instance;
            }
        }
        $controller_new = str_replace('/', '\\', $controller);
        $class_name = "App\\Controllers\\$controller_new";
        if (!class_exists($class_name)) {
            $class_name = "Server\\Controllers\\$controller_new";
        }
        if(class_exists($class_name)){
            $controller_instance = new $class_name;
            $controller_instance->core_name = $controller;
            $this->addNewCount($controller);
            return $controller_instance;
        }
    }

    /**
     * 归还一个controller
     * @param $controller Controller
     */
    public function revertController($controller)
    {
        $this->pool[$controller->core_name]->push($controller);
    }


    /**
     * *
     * @param $name
     */
    private function addNewCount($name)
    {
        if (isset($this->pool_count[$name])) {
            $this->pool_count[$name]++;
        } else {
            $this->pool_count[$name] = 1;
        }
    }

    /**
     * 获取状态
     */
    public function getStatus()
    {
        $status = [];

        foreach ($this->pool as $key => $value) {
            $status[$key . '[pool]'] = count($value);
            $status[$key . '[new]'] = $this->pool_count[$key] ?? 0;
        }
        return $status;
    }
}
