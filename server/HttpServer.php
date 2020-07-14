<?php

/**
 * @Author: sink
 * @Date:   2019-08-05 11:54:05
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 12:02:58
 */

namespace Server;
use Server\Exceptions\SwooleNotFoundException;
use Server\Controllers\ControllerFactory;


abstract class HttpServer extends SwooleServer
{


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 启动
     * @throws \Exception
     */
    public function start()
    {
        if (!$this->portManager->http_enable) {
            parent::start();
            return;
        }
        $first_config = $this->portManager->getFirstTypePort();
        $set = $this->portManager->getProbufSet($first_config['socket_port']);
        if (array_key_exists('ssl_cert_file', $first_config)) {
            $set['ssl_cert_file'] = $first_config['ssl_cert_file'];
        }
        if (array_key_exists('ssl_key_file', $first_config)) {
            $set['ssl_key_file'] = $first_config['ssl_key_file'];
        }
        $socket_ssl = $first_config['socket_ssl'] ?? false;
        //开启一个http服务器
        if ($socket_ssl) {
            $this->server = new \swoole_http_server($first_config['socket_name'], $first_config['socket_port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        } else {
            $this->server = new \swoole_http_server($first_config['socket_name'], $first_config['socket_port']);
        }
        $this->setServerParameter($set);
        $this->server->on('Start', [$this, 'onSwooleStart']);
        $this->server->on('WorkerStart', [$this, 'onSwooleWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onSwooleWorkerStop']);
        $this->server->on('Task', [$this, 'onSwooleTask']);
        $this->server->on('Finish', [$this, 'onSwooleFinish']);
        $this->server->on('PipeMessage', [$this, 'onSwoolePipeMessage']);
        $this->server->on('WorkerError', [$this, 'onSwooleWorkerError']);
        $this->server->on('ManagerStart', [$this, 'onSwooleManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onSwooleManagerStop']);
        $this->server->on('request', [$this, 'onSwooleRequest']);
        $this->server->on('Shutdown', [$this, 'onSwooleShutdown']);
        $this->portManager->buildPort($this, $first_config['socket_port']);
        $this->beforeSwooleStart();
        $this->server->start();
    }


    /**
     * workerStart
     * @param $serv
     * @param $workerId
     */
    public function onSwooleWorkerStart($serv, $workerId)
    {
        parent::onSwooleWorkerStart($serv, $workerId);
    }


    /**
     * http请求
     * @param $request
     * @param $response
     */
    public function onSwooleRequest($request, $response)
    {
        $server_port = $this->getServerPort($request->fd);
        $path = $request->server['path_info'];
        if ($path == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }

        //路由 控制器
        $route = $this->portManager->getRoute($server_port);
        try {
            try {
                $route->handleClientRequest($request);
                $controller_name = $route->getControllerName();
                $method_name = $this->portManager->getMethodPrefix($server_port) . $route->getMethodName();
                $path = $route->getPath();
                $controller_instance = ControllerFactory::getInstance()->getController($controller_name);
                if ($controller_instance != null) {
                    $controller_instance->setRequestResponse($request, $response, $controller_name, $method_name, $route->getParams());
                } else {
                    throw new SwooleNotFoundException('no controller');
                }
            } catch (\Throwable $e) {

                $errorMsg = 'Error on line '.$e->getLine().' in '.$e->getFile() . 'Message:'.$e->getMessage();
                LogEcho('onSwooleRequest:', $errorMsg);
                $route->errorHttpHandle($e, $request, $response);
            }

        } catch (Exception $e) {
            $errorMsg = 'Error on line '.$e->getLine().' in '.$e->getFile() . 'Message:'.$e->getMessage();
            LogEcho('onSwooleRequest:', $errorMsg);
            //被中断
        }



    }

}
