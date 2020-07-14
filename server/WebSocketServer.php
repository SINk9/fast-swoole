<?php

/**
 * @Author: sink
 * @Date:   2019-08-05 11:53:51
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 14:46:56
 */

namespace Server;
use Server\Exceptions\SwooleException;
use Server\Controllers\ControllerFactory;

abstract class WebSocketServer extends HttpServer
{
    /**
     * @var array
     */
    protected $fdRequest = [];
    protected $custom_handshake = false; //握手处理

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
        if (!$this->portManager->websocket_enable) {
            parent::start();
            return;
        }
        $first_config = $this->portManager->getFirstTypePort(); //获取端口配置
        //ssl
        if (array_key_exists('ssl_cert_file', $first_config)) {
            $set['ssl_cert_file'] = $first_config['ssl_cert_file'];
        }
        if (array_key_exists('ssl_key_file', $first_config)) {
            $set['ssl_key_file'] = $first_config['ssl_key_file'];
        }
        $socket_ssl = $first_config['socket_ssl'] ?? false;
        //开启一个websocket服务器
        if ($socket_ssl) {
            $this->server = new \swoole_websocket_server($first_config['socket_name'], $first_config['socket_port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        } else {
            $this->server = new \swoole_websocket_server($first_config['socket_name'], $first_config['socket_port']);
        }

        //
        $set = $this->portManager->getProbufSet($first_config['socket_port']);
        $this->setServerParameter($set);

        $this->server->on('Start', [$this, 'onSwooleStart']);
        $this->server->on('WorkerStart', [$this, 'onSwooleWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onSwooleWorkerStop']);
        $this->server->on('Task', [$this, 'onSwooleTask']);
        $this->server->on('finish', [$this, 'onSwooleFinish']);
        $this->server->on('WorkerError', [$this, 'onSwooleWorkerError']);
        $this->server->on('PipeMessage', [$this, 'onSwoolePipeMessage']);
        $this->server->on('ManagerStart', [$this, 'onSwooleManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onSwooleManagerStop']);
        $this->server->on('request', [$this, 'onSwooleRequest']);
        $this->server->on('open', [$this, 'onSwooleWSOpen']);
        $this->server->on('message', [$this, 'onSwooleWSMessage']);
        $this->server->on('close', [$this, 'onSwooleWSClose']);
        $this->server->on('Shutdown', [$this, 'onSwooleShutdown']);
        //是否进行握手处理
        if ($this->custom_handshake) {
            $this->server->on('handshake', [$this, 'onSwooleWSHandShake']);
        }
        //构建端口
        $this->portManager->buildPort($this, $first_config['socket_port']);
        $this->beforeSwooleStart();
        $this->server->start();
    }

    /**
     * @param $serv
     */
    public function onSwooleWorkerStop($serv, $workerId)
    {
        parent::onSwooleWorkerStart($serv, $workerId);

    }

    /**
     * @param $serv
     * @throws \Exception
     */
    public function onSwooleWorkerStart($serv, $workerId)
    {
        parent::onSwooleWorkerStart($serv, $workerId);

    }

    /**
     * websocket连接上时
     * @param $server
     * @param $request
     * @throws \Throwable
     */
    public function onSwooleWSOpen($server, $request)
    {
        $this->portManager->eventConnect($request->fd, $request);
    }

    /**
     * websocket收到消息时
     * @param $server
     * @param $frame
     */
    public function onSwooleWSMessage($server, $frame)
    {
        $this->onSwooleWSAllMessage($server, $frame->fd, $frame->data);
    }

    /**
     * websocket合并后完整的消息
     * @param $serv
     * @param $fd
     * @param $data
     * @return CoreBase\Controller
     */
    public function onSwooleWSAllMessage($serv, $fd, $data)
    {
        $server_port = $this->getServerPort($fd);
        $uid = $this->getUidFromFd($fd);


        $pack = $this->portManager->getPack($server_port);


        //反序列化，出现异常断开连接
        try {
            $client_data = $pack->unPack($data);
        } catch (\Throwable $e) {
            $pack->errorHandle($e, $fd);
            return null;
        }
        //路由 控制器
        $route = $this->portManager->getRoute($server_port);
        try {
            $client_data = $route->handleClientData($client_data);
            $controller_name = $route->getControllerName();
            $method_name = $this->portManager->getMethodPrefix($server_port) . $route->getMethodName();
            $path = $route->getPath();
            $controller_instance = ControllerFactory::getInstance()->getController($controller_name);
            if ($controller_instance != null) {
                $request = $this->fdRequest[$fd] ?? null;
                if ($request != null) {
                    $controller_instance->setRequest($request);
                }
                $controller_instance->setClientData($uid, $fd, $client_data, $controller_name, $method_name, $route->getParams());
            } else {
                throw new SwooleException('no controller');
            }
        } catch (\Throwable $e) {
            $route->errorHandle($e, $fd);
        }
    }

    /**
     * websocket断开连接
     * @param $serv
     * @param $fd
     * @throws \Throwable
     */
    public function onSwooleWSClose($serv, $fd)
    {
        unset($this->fdRequest[$fd]);
        $this->portManager->eventClose($fd);
    }

    /**
     * 可以在这验证WebSocket连接,return true代表可以握手，false代表拒绝
     * @param HttpInput $httpInput
     * @return bool
     */
    abstract public function onWebSocketHandCheck();



    /**
     * 是否自定义handshake
     * @param bool $custom_handshake
     */
    public function setCustomHandshake(bool $custom_handshake)
    {
        $this->custom_handshake = $custom_handshake;
    }


    /**
     * @var HttpInput
     */
    protected $webSocketHttpInput;


    /**
     * ws握手
     * @param $request
     * @param $response
     * @return bool
     */
    public function onSwooleWSHandShake(\swoole_http_request $request, \swoole_http_response $response)
    {
        if ($this->webSocketHttpInput == null) {
            $this->webSocketHttpInput = new HttpInput();
        }
        $this->webSocketHttpInput->set($request);
        $result = $this->onWebSocketHandCheck($this->webSocketHttpInput);
        if (!$result) {
            $response->end();
            return false;
        }
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $key = base64_encode(sha1(
            $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
            true
        ));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $this->fdRequest[$request->fd] = $this->transformationRequest($request);
        $response->end();

        $this->server->defer(function () use ($request) {
            go(function () use ($request) {
                $this->onSwooleWSOpen($this->server, $request);
            });
        });
        return true;
    }


    /**
     * 判断这个fd是不是一个WebSocket连接，用于区分tcp和websocket
     * 握手后才识别为websocket
     * @param $fdinfo
     * @return bool
     * @throws \Exception
     * @internal param $fd
     */
    public function isWebSocket($fdinfo)
    {
        if (empty($fdinfo)) {
            throw new SwooleException('fd not exist');
        }
        if (array_key_exists('websocket_status', $fdinfo) && $fdinfo['websocket_status'] == WEBSOCKET_STATUS_FRAME) {
            return $fdinfo['server_port'];
        }
        return false;
    }

    /**
     * 判断是tcp还是websocket进行发送
     * @param $fd
     * @param $data
     * @param bool $ifPack
     * @param $topic
     * @return bool
     * @throws \Exception
     */
    public function send($fd, $data, $ifPack = false, $topic = null)
    {
        if (!$this->portManager->websocket_enable) {
            return parent::send($fd, $data, $ifPack, $topic);
        }
        $fdinfo = $this->server->connection_info($fd);
        if (empty($fdinfo)) return false;
        $server_port = $fdinfo['server_port'];

        if ($ifPack) {
            $pack = $this->portManager->getPack($server_port);
            if ($pack != null) {
                $data = $pack->pack($data, $topic);
            }
        }
        if ($this->isWebSocket($fdinfo)) {
            return $this->server->push($fd, $data, $this->portManager->getOpCode($server_port));
        } else {
            return $this->server->send($fd, $data);
        }
    }

    /**
     * @param $request
     * @return \stdClass
     */
    private function transformationRequest($request)
    {
        $_arr = get_object_vars($request);
        $new_request = new \stdClass();
        foreach ($_arr as $key => $val) {
            $new_request->$key = $val;
        }
        return $new_request;
    }
}

