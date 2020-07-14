<?php

/**
 * @Author: sink
 * @Date:   2019-08-07 15:17:24
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 12:02:24
 */

namespace Server\Routes;

use Server\Exceptions\SwooleException;
use Server\ProxyServer;


class HttpRoute implements IRoute
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
    {
        $this->client_data->path = $request->server['path_info'];
        $route = explode('/', $request->server['path_info']);
        $count = count($route);
        if ($count == 2) {
            $this->client_data->controller_name = $route[$count - 1] ?? null;
            $this->client_data->method_name = null;
            return;
        }
        $this->client_data->method_name = $route[$count - 1] ?? null;
        unset($route[$count - 1]);
        unset($route[0]);
        $this->client_data->controller_name = implode("\\", $route);
    }

    /**
     * 获取控制器名称
     * @return string
     */
    public function getControllerName()
    {
        return 'Http/' . $this->client_data->controller_name;
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
    {}

    /**
     * * 处理http error
     */
    public function errorHttpHandle(\Throwable $e, $request, $response)
    {
        if (! $response instanceof \Swoole\Http\Response) {
            throw new Exception("object must be instance of Swoole\\Response");
        }
        $response->status(404);
        //重定向到404
        $location = 'http://' . $request->header['host'] . "/" . '404';
        $response->header('Location', $location);
        $response->end('Not Found');
    }
}
