<?php

/**
 * @Author: sink
 * @Date:   2019-08-12 15:11:35
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 18:35:18
 */

namespace Server\CoreBase;


class Child
{
    /**
     * 名称
     * @var string
     */
    public $core_name;

    /**
     * @var Child
     */
    public $root;
    /**
     * @var Child
     */

    public $parent;
    /**
     * 子集
     * @var array
     */
    public $child_list = [];

    /**
     * 上下文
     * @var array
     */
    protected $context = [];


    public function __construct()
    {
    }

    /**
     * 加入一个Child
     * @param $child Child
     */
    public function addChild($child)
    {
        if ($child == null) return;
        $child->onAddChild($this);
        $this->child_list[$child->core_name] = $child;
    }


    /**
     * 加入一个Child
     * @param $child Child
     */
    public function newAddChild($core_name, $child)
    {
        if ($child == null) return;
        $this->child_list[$core_name] = $child;
    }


    /**
     * 被加入列表时
     * @param $parent
     */
    public function onAddChild($parent)
    {
        $this->parent = $parent;
    }

    /**
     * 是否存在Child
     * @param $name
     * @return bool
     */
    public function hasChild($name)
    {
        return array_key_exists($name, $this->child_list);
    }

    /**
     * 获取Child
     * @param $name
     * @return mixed|null
     */
    public function getChild($name)
    {
        return $this->child_list[$name]??null;
    }

    /**
     * 获取上下文
     * @return array
     */
    public function &getContext()
    {
        return $this->context;
    }

    /**
     * 设置上下文
     * @param $context
     */
    public function setContext(&$context)
    {
        $this->context = &$context;
    }

    /**
     * 销毁，解除引用
     */
    public function destroy()
    {
        foreach ($this->child_list as $core_child) {
            $core_child->destroy();
        }
        $this->child_list = [];
        $this->parent = null;
    }
}
