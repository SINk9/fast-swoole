<?php

/**
 * 路由接口类
 * @Author: sink
 * @Date:   2019-08-07 14:21:38
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:58:51
 */
namespace Server\Routes;

interface IRoute
{
    function handleClientData($data);

    function handleClientRequest($request);

    function getControllerName();

    function getMethodName();

    function getParams();

    function getPath();

    function errorHandle(\Throwable $e, $fd);

    function errorHttpHandle(\Throwable $e, $request, $response);

}
