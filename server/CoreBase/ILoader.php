<?php
/**
 * @Author: sink
 * @Date:   2019-08-12 15:11:07
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:00:26
 */

namespace Server\CoreBase;

interface ILoader
{

    /**
     * 获取一个model
     * @param $model
     * @param Child $parent
     * @return mixed|null
     * @throws SwooleException
     */
    public function model($model, Child $parent);

    /**
     * 获取一个task
     * @param $task
     * @param Child $parent
     * @return mixed|null|TaskProxy
     * @throws SwooleException
     */
    public function task($task, Child $parent = null);

    /**
     * view 返回一个模板
     * @param $template
     * @return \League\Plates\Template\Template
     */
    //public function view($template);
}