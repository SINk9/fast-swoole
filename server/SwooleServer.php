<?php

/**
 * @Author: sink
 * @Date:   2019-08-05 11:54:18
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-08 14:32:34
 */

namespace Server;
use Noodlehaus\Config;
use Server\Ports\PortManager;
use Server\Exceptions\SwooleException;
use Server\Controllers\ControllerFactory;
use Server\CoreBase\ILoader;
use Server\CoreBase\Loader;

abstract class SwooleServer
{

    /**
     * @var \swoole_server
     */
    public $server;

    /**
     * @var Config
     */
    public $config;
    /**
     * 加载器
     * @var ILoader
     */
    public $loader;

    /**
     * 日志
     * @var Logger
     */
    public $logger;

    /**
     * Emitted when worker processes stoped.
     *
     * @var callback
     */
    public $onErrorHandel = null;

    /**
     * @var PortManager
     */
    public $portManager;

    /**
     * @var MiddlewareManager
     */
    protected $middlewareManager;

    /**
     * server name
     * @var string
     */
    public $name = 'demaxiya~';
    /**
     * server user
     * @var string
     */
    public $user = '';

    /**
     * @var int
     */
    public $workerId;

    /**
     * worker数量
     * @var int
     */
    public $worker_num = 0;
    public $task_num = 0;

    /**
     * 最大连接数
     * @var int
     */
    protected $max_connection;

    /**
     * 共享内存表
     * @var \swoole_table
     */
    protected $uid_fd_table;
    /**
     * @var \swoole_table
     */
    protected $fd_uid_table;


    /**
     * SwooleServer constructor.
     */
    public function __construct()
    {
        $this->onErrorHandel = [$this, 'onErrorHandel'];
        $this->config = new Config(CONFIG_DIR);
        //$this->logger = logger()->stack(['swoole']);
        //$this->middlewareManager = new MiddlewareManager();
        $this->portManager = new PortManager($this->config['ports']);
        if ($this->loader == null) {
            $this->loader = new Loader();
        }
    }


    /**
     * 启动
     */
    public function start()
    {
        if ($this->portManager->tcp_enable) {
            $first_config = $this->portManager->getFirstTypePort();
            $set = $this->portManager->getProbufSet($first_config['socket_port']);
            if (array_key_exists('ssl_cert_file', $first_config)) {
                $set['ssl_cert_file'] = $first_config['ssl_cert_file'];
            }
            if (array_key_exists('ssl_key_file', $first_config)) {
                $set['ssl_key_file'] = $first_config['ssl_key_file'];
            }
            $socket_ssl = $first_config['socket_ssl'] ?? false;
            if ($socket_ssl) {
                $this->server = new \swoole_server($first_config['socket_name'], $first_config['socket_port'], SWOOLE_PROCESS, $first_config['socket_type'] | SWOOLE_SSL);
            } else {
                $this->server = new \swoole_server($first_config['socket_name'], $first_config['socket_port'], SWOOLE_PROCESS, $first_config['socket_type']);
            }
            $this->setServerParameter($set);
            $this->server->on('Start', [$this, 'onSwooleStart']);
            $this->server->on('WorkerStart', [$this, 'onSwooleWorkerStart']);
            $this->server->on('connect', [$this, 'onSwooleConnect']);
            $this->server->on('receive', [$this, 'onSwooleReceive']);
            $this->server->on('close', [$this, 'onSwooleClose']);
            $this->server->on('PipeMessage', [$this, 'onSwoolePipeMessage']);
            $this->server->on('WorkerStop', [$this, 'onSwooleWorkerStop']);
            $this->server->on('Task', [$this, 'onSwooleTask']);
            $this->server->on('Finish', [$this, 'onSwooleFinish']);
            $this->server->on('WorkerError', [$this, 'onSwooleWorkerError']);
            $this->server->on('ManagerStart', [$this, 'onSwooleManagerStart']);
            $this->server->on('ManagerStop', [$this, 'onSwooleManagerStop']);
            $this->server->on('Packet', [$this, 'onSwoolePacket']);
            $this->server->on('Shutdown', [$this, 'onSwooleShutdown']);
            $this->portManager->buildPort($this, $first_config['socket_port']);
            $this->beforeSwooleStart();
            $this->server->start();

        } else {
            LogEcho("SYS", "没有任何服务启动");
            exit(0);
        }
    }

    /**
     * 设置服务器配置参数
     * @return mixed
     */
    public function setServerParameter($probuf_set)
    {
        $set = $this->config['server']['set'] ?? [];
        if ($probuf_set != null) {
            $set = array_merge($set, $probuf_set);
        }
        $this->worker_num = $set['worker_num'];
        $this->task_num = $set['task_worker_num'];
        $set['daemonize'] = true;
        $this->server->set($set);
        swoole_async_set([
            'socket_buffer_size' => 1024 * 1024 * 1024
        ]);
    }


    /**
     * start前的操作
     */
    public function beforeSwooleStart()
    {
        //创建uid->fd共享内存表
        $this->createUidTable();
    }

    /**
     * 创建uid->fd共享内存表
     */
    public function createUidTable()
    {
        $this->max_connection = $this->config['server']['set']['max_connection'] ?? 65536;
        $this->uid_fd_table = new \swoole_table($this->max_connection);
        $this->uid_fd_table->column('fd', \swoole_table::TYPE_INT, 8);
        $this->uid_fd_table->create();

        $this->fd_uid_table = new \swoole_table($this->max_connection);
        $this->fd_uid_table->column('uid', \swoole_table::TYPE_STRING, 50);
        $this->fd_uid_table->create();
    }


    /**
     * onSwooleStart
     * @param $serv
     */
    public function onSwooleStart($serv)
    {
        date_default_timezone_set('Asia/Shanghai');
        $this->setProcessTitle($this->config['name'] . '-Master');
    }

    /**
     * onSwooleWorkerStart
     * @param $serv
     * @param $workerId
     */
    public function onSwooleWorkerStart($serv, $workerId)
    {
        date_default_timezone_set('Asia/Shanghai');
        $this->workerId = $workerId;
        //清除apc缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        // 重新加载配置
        $this->config = $this->config->load(CONFIG_DIR);
        if (!$serv->taskworker) {//worker进程
            $this->setProcessTitle($this->config['name'] . "-Worker");
        } else {
            $this->setProcessTitle($this->config['name'] . "-Tasker");
        }
    }



    /**
     * 设置进程名称
     *
     * @param string $title
     * @return void
     */
    public static function setProcessTitle($title)
    {
        if (PHP_OS == "Darwin") {
            return;
        }
        // >=php 5.5
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        } // Need proctitle when php<=5.5 .
        else {
            @swoole_set_process_name($title);
        }
    }

    /**
     * onSwooleClose
     * @param $serv
     * @param $fd
     * @throws \Throwable
     */
    public function onSwooleClose($serv, $fd)
    {
        $this->portManager->eventClose($fd);
    }

    /**
     * onSwooleConnect
     * @param $serv
     * @param $fd
     * @throws \Throwable
     */
    public function onSwooleConnect($serv, $fd)
    {
        $this->portManager->eventConnect($fd);
    }

    /**
     * 客户端有消息时
     * @param $serv
     * @param $fd
     * @param $from_id
     * @param $data
     * @param null $server_port
     * @return CoreBase\Controller|void
     */
    public function onSwooleReceive($serv, $fd, $from_id, $data, $server_port = null)
    {
        $server_port = $this->getServerPort($fd);
        $uid = $this->getUidFromFd($fd);
        $pack = $this->portManager->getPack($server_port);
        //反序列化，出现异常断开连接
        try {
            $client_data = $pack->unPack($data);
        } catch (\Throwable $e) {
            $pack->errorHandle($e, $fd);
            return;
        }

        //路由 控制器
        $route = $this->portManager->getRoute($server_port);
        try {
            $client_data = $route->handleClientData($client_data);
            $controller_name = $route->getControllerName();
            $method_name = $this->portManager->getMethodPrefix($server_port) . $route->getMethodName();
            $path = $route->getPath();

            //控制器
            $controller_instance = ControllerFactory::getInstance()->getController($controller_name);
            if ($controller_instance != null) {
                $controller_instance->setClientData($uid, $fd, $client_data, $controller_name, $method_name, $route->getParams());
            } else {
                throw new SwooleException('no controller');
            }
        } catch (\Throwable $e) {
            $route->errorHandle($e, $fd);
        }

    }

    /**
     * @return string
     */
    abstract function getEventControllerName();

    /**
     * @return string
     */
    abstract function getCloseMethodName();

    /**
     * @return string
     */
    abstract function getConnectMethodName();


    /**
     * onSwooleWorkerStop
     * @param $serv
     * @param $worker_id
     */
    public function onSwooleWorkerStop($serv, $worker_id)
    {

    }

    /**
     * onSwooleShutdown
     * @param $serv
     */
    public function onSwooleShutdown($serv)
    {

    }

    /**
     * onSwooleTask
     * @param $serv
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return mixed
     */
    public function onSwooleTask($serv, $task_id, $from_id, $data)
    {

    }

    /**
     * onSwooleFinish
     * @param $serv
     * @param $task_id
     * @param $data
     */
    public function onSwooleFinish($serv, $task_id, $data)
    {

    }


    /**
     * onSwooleWorkerError
     * @param $serv
     * @param $worker_id
     * @param $worker_pid
     * @param $exit_code
     */
    public function onSwooleWorkerError($serv, $worker_id, $worker_pid, $exit_code)
    {
        $data = [
        	'worker_id' => $worker_id,
            'worker_pid' => $worker_pid,
            'exit_code' => $exit_code
        ];
        $log = "WORKER Error ";
        $log .= json_encode($data);
        //$this->logger->alert($log);
        $this->onErrorHandel('【！！！】服务器进程异常退出',$log);
    }

    /**
     * ManagerStart
     * @param $serv
     */
    public function onSwooleManagerStart($serv)
    {
        $this->setProcessTitle($this->getServerName() . '-Manager');
    }

    /**
     * ManagerStop
     * @param $serv
     */
    public function onSwooleManagerStop($serv)
    {

    }

    /**
     * onPacket(UDP)
     * @param $server
     * @param string $data
     * @param array $client_info
     */
    public function onSwoolePacket($server, $data, $client_info)
    {

    }

    /**
     * onSwoolePipeMessage
     * @param $serv
     * @param $from_worker_id
     * @param $message
     * @throws \Exception
     */
    public function onSwoolePipeMessage($serv, $from_worker_id, $message)
    {

        if (!empty($message['func'])) {
            $message['func']($message['message']);
        }

    }


    /**
     * 包装SerevrMessageBody消息
     * @param $type
     * @param $message
     * @param string $func
     * @return string
     */
    public function packMessage($type, $message, string $func = null)
    {
        $data['type'] = $type;
        $data['message'] = $message;
        $data['func'] = $func;
        return $data;
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $func = array($this->server, $name);
        return $func(...$arguments);
    }


    /**
     * @param $error
     * @param $error_string
     * @param $filename
     * @param $line
     * @param $symbols
     * @throws ErrorException
     */
    public function displayErrorHandler($error, $error_string, $filename, $line, $symbols)
    {
        throw new ErrorException($error_string, $error, 1, $filename, $line);
    }

    /**
     * Get socket name.
     *
     * @return string
     */
    public function getSocketName()
    {
        return $this->socket_name ? lcfirst($this->socket_name . ":" . $this->port) : 'none';
    }

    /**
     * 获取workerId
     * @return int
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }

    /**
     * 是不是worker进程
     * @param null $worker_id
     * @return bool
     */
    public function isWorker($worker_id = null)
    {
        if ($worker_id == null) {
            $worker_id = $this->workerId;
        }
        return $worker_id < $this->worker_num ? true : false;
    }

    /**
     * 是否是task进程
     * @return bool
     */
    public function isTaskWorker()
    {
        return $this->server->taskworker ?? false;
    }

    /**
     * 是不是用户进程
     * @param null $worker_id
     * @return bool
     */
    public function isUserProcess($worker_id = null)
    {
        //一定要===
        if ($worker_id === null) {
            $worker_id = $this->workerId;
        }
        return $worker_id >= $this->worker_num + $this->task_num ? true : false;
    }

    /**
     * 判断是tcp还是websocket进行发送
     * @param $fd
     * @param $data
     * @param bool $ifPack
     * @param null $topic
     * @return bool
     */
    public function send($fd, $data, $ifPack = false, $topic = null)
    {
        if (!$this->server->exist($fd)) {
            return false;
        }
        if ($ifPack) {
            $pack = $this->portManager->getPackFromFd($fd);
            if ($pack != null) {
                $data = $pack->pack($data, $topic);
            }
        }
        return $this->server->send($fd, $data);
    }

    /**
     * 服务器主动关闭链接
     * close fd
     * @param $fd
     */
    public function close($fd)
    {
        $this->server->close($fd);
    }


    /**
     * 错误处理函数
     * @param $msg
     * @param $log
     */
    public function onErrorHandel($msg, $log)
    {
        LogEcho("ERROR", $msg);
        LogEcho("ERROR", $log);
    }

    /**
     * @param $fd
     * @return mixed
     */
    public function getFdInfo($fd)
    {
        $fdinfo = $this->server->connection_info($fd);
        return $fdinfo;
    }

    /**
     * @param $fd
     * @return mixed
     */
    public function getServerPort($fd)
    {
        return $this->server->connection_info($fd)['server_port'];
    }

    /**
     *
     * @param $fd
     */
    public function protect($fd)
    {
        $this->server->protect($fd);
    }

    /**
     * 通过Uid获取fd
     * @param $uid
     * @return mixed
     */
    public function getFdFromUid($uid)
    {
        return $this->uid_fd_table->get($uid, 'fd');
    }

    /**
     * 通过fd获取uid
     * @param $fd
     * @return mixed
     */
    public function getUidFromFd($fd)
    {
        return $this->fd_uid_table->get($fd, 'uid');
    }


}
