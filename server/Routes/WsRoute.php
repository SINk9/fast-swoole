<?php

/**
 * @Author: sink
 * @Date:   2019-08-07 15:17:24
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 16:48:49
 */

namespace Server\Routes;

use Server\Exceptions\SwooleException;
use Server\ProxyServer;


class WsRoute implements IRoute
{
    private $client_data;

    public function __construct()
    {
        $this->client_data = new \stdClass();
    }

    /**
     * 设置反序列化后的数据 Object
     * @param $data
     * @return \stdClass
     * @throws SwooleException
     */
    public function handleClientData($data)
    {
        $this->client_data = $data;
        if (isset($this->client_data->controller_name) && isset($this->client_data->method_name)) {
            return $this->client_data;
        } else {
            throw new SwooleException('route 数据缺少必要字段');
        }

    }

    /**
     * 处理http request
     * @param $request
     */
    public function handleClientRequest($request)
    {}

    /**
     * 获取控制器名称
     * @return string
     */
    public function getControllerName()
    {
        return '/ws/' . $this->client_data->controller_name;
    }

    /**
     * 获取方法名称
     * @return string
     */
    public function getMethodName()
    {
        return $this->client_data->method_name ?? "index";
    }

    /**
     * * 获取请求地址
     * @return string
     */
    public function getPath()
    {
        return $this->client_data->path ?? "";
    }

    /**
     * * 获取请求参数
     * @return string or array
     */
    public function getParams()
    {
        return $this->client_data->params??null;
    }

    /**
     * * 处理tcp error
     */
    public function errorHandle(\Throwable $e, $fd)
    {
        ProxyServer::getInstance()->send($fd, "Error:" . $e->getMessage(), true);
        ProxyServer::getInstance()->close($fd);
    }

    /**
     * * 处理http error
     */
    public function errorHttpHandle(\Throwable $e, $request, $response)
    {}
}
