<?php

/**
 * 中间件接口类
 * @Author: sink
 * @Date:   2019-08-07 14:23:35
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:41
 */
namespace Server\Middlewares;

interface IMiddleware
{
    function setContext(&$context);

    function before_handle();

    function after_handle($path);
}
