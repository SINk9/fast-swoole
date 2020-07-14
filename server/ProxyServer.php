<?php

/**
 * 根节点
 * @Author: sink
 * @Date:   2019-08-05 11:53:03
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 12:06:45
 */
namespace Server;
use Server\Memory\Container;
use Server\Asyn\Mysql\MysqlPool;
use Server\Asyn\Redis\RedisPool;
use Server\Process\ProcessManager;
use Server\Process\TimerTaskProcess;
use Server\TimerTasks\TimerTask;
use Server\TimerTasks\Timer;
use Server\Exceptions\SwooleException;
use Server\Tasks\TaskProxy;

class ProxyServer extends WebSocketServer
{


    const SERVER_NAME = "SWOOLE";

    /**
     * 实例
     * @var SwooleServer
     */
    private static $instance;


    /**
     * 多少人启用task进行发送
     * @var
     */
    private $send_use_task_num;

    /**
     * 生成task_id的原子
     */
    public $task_atomic;
    /**
     * task_id和pid的映射
     */
    public $tid_pid_table;

    /**
     * 中断task的id内存锁
     */
    public $task_lock;

    /**
     * 初始化的锁
     * @var \swoole_lock
     */
    private $initLock;

   /**
     * @var RedisAsynPool
     */
    public $redis_pool;
    /**
     * @var Mysql Connection
     */
    public $mysql_pool;

    /**
     * @var \Redis
     */
    protected $redis_client;
    /**
     * @var Miner
     */
    protected $mysql_client;

    /**
     * 连接池.
     *
     * @var
     */
    private $asynPools = [];


    /**
     * 重载锁
     * @var array
     */
    private $reloadLockMap = [];

   /**
     * @var 开始时间
     */
    protected static $startTime;

    /**
     * @var 当前的时间(毫秒)
     */
    protected static $startMillisecond;

    /**
     * SwooleProxyServer 构造函数.
     */
    public function __construct()
    {
        parent::__construct();
        self::$instance =& $this;
        self::$startTime = date('Y-m-d H:i:s');
        self::$startMillisecond = getMillisecond();
        $this->name = $this->config['name'];
        $this->send_use_task_num = $this->config['server']['send_use_task_num'];
        //检查扩展
        if (!checkSwooleExtension()) {
            exit(-1);
        }
    }

    /**
     * * 开始服务
     *
     */
    public function start()
    {
        parent::start();
    }

    /**
     * 获取实例
     * @return SwooleProxyServer
     */
    public static function &getInstance()
    {
        return self::$instance;
    }


    /**
     * *  获取服务名称
     */
    public static function getServerName()
    {
        return self::$instance->name;
    }


    /**
     * * 获取开始时间(毫秒)
     */
    public static function getStartMillisecond()
    {
        return self::$startMillisecond;
    }


    /**
     * 获取同步mysql.
     *
     * @return Miner
     */
    public function getMysql()
    {
        return $this->mysql_pool->getSync();
    }

    /**
     * 开始前创建共享内存保存USID值
     * @throws \Exception
     */
    public function beforeSwooleStart()
    {
        parent::beforeSwooleStart();
        //创建task用的taskid
        $this->task_atomic = new \swoole_atomic(0);
        //创建task用的id->pid共享内存表不至于同时超过1024个任务吧
        $this->tid_pid_table = new \swoole_table(1024);
        $this->tid_pid_table->column('pid', \swoole_table::TYPE_INT, 8);
        $this->tid_pid_table->column('des', \swoole_table::TYPE_STRING, 100);
        $this->tid_pid_table->column('start_time', \swoole_table::TYPE_INT, 8);
        $this->tid_pid_table->create();
        //创建task用的锁
        $this->task_lock = new \swoole_lock(SWOOLE_MUTEX);
        //init锁
        $this->initLock = new \swoole_lock(SWOOLE_RWLOCK);
        //Timer
        Timer::init();

        //开启用户进程
        $this->startProcess();
        //reload锁
        for ($i = 0; $i < $this->worker_num; $i++) {
            $lock = new \swoole_lock(SWOOLE_MUTEX);
            $this->reloadLockMap[$i] = $lock;
        }
    }


    /**
     * 创建用户进程
     * @throws \Exception
     */
    public function startProcess()
    {
        //timerTask
        ProcessManager::getInstance()->addProcess(TimerTaskProcess::class);
    }


    /**
     * 发送给所有的进程，$callStaticFuc为静态方法,会在每个进程都执行
     * @param $type
     * @param $uns_data
     * @param string $callStaticFuc
     */
    public function sendToAllWorks($type, $uns_data, string $callStaticFuc)
    {
        $send_data = $this->packMessage($type, $uns_data, $callStaticFuc);
        for ($i = 0; $i < $this->worker_num + $this->task_num; $i++) {
            if ($this->server->worker_id == $i) continue;
            $this->server->sendMessage($send_data, $i);
        }
        //自己的进程是收不到消息的所以这里执行下
        if(is_callable($callStaticFuc)){
            return $callStaticFuc(...$uns_data);
        }
    }

    /**
     * 发送给所有的异步进程，$callStaticFuc为静态方法,会在每个进程都执行
     * @param $type
     * @param $uns_data
     * @param string $callStaticFuc
     */
    public function sendToAllAsynWorks($type, $uns_data, string $callStaticFuc)
    {
        $send_data = $this->packMessage($type, $uns_data, $callStaticFuc);
        for ($i = 0; $i < $this->worker_num; $i++) {
            if ($this->server->worker_id == $i) continue;
            $this->server->sendMessage($send_data, $i);
        }
        ProcessManager::getInstance()->sendToAllProcess($send_data);
        //自己的进程是收不到消息的所以这里执行下
        if(is_callable($callStaticFuc)){
            return $callStaticFuc(...$uns_data);
        }
    }

    /**
     * 发送给随机进程
     * @param $type
     * @param $uns_data
     * @param string $callStaticFuc
     */
    public function sendToRandomWorker($type, $uns_data, string $callStaticFuc)
    {
        $send_data = $this->packMessage($type, $uns_data, $callStaticFuc);
        $id = rand(0, $this->worker_num - 1);
        if ($this->server->worker_id == $id) {
            //自己的进程是收不到消息的所以这里执行下
            if(is_callable($callStaticFuc)){
                return $callStaticFuc(...$uns_data);
            }
        } else {
            $this->server->sendMessage($send_data, $id);
        }
    }

    /**
     * 发送给指定进程
     * @param $workerId
     * @param $type
     * @param $uns_data
     * @param string $callStaticFuc
     */
    public function sendToOneWorker($workerId, $type, $uns_data, string $callStaticFuc)
    {
        $send_data = $this->packMessage($type, $uns_data, $callStaticFuc);
        if ($this->server->worker_id == $workerId) {
            //自己的进程是收不到消息的所以这里执行下
            if(is_callable($callStaticFuc)){
                return $callStaticFuc(...$uns_data);
            }
        } else {
            $this->server->sendMessage($send_data, $workerId);
        }
    }


    /**
     * 重写onSwooleWorkerStart方法
     * @param $serv
     * @param $workerId
     * @throws \Exception
     */
    public function onSwooleWorkerStart($serv, $workerId)
    {
        parent::onSwooleWorkerStart($serv, $workerId);
        $this->initAsynPools($workerId);
        //进程锁保证只有一个进程会执行以下的代码,reload也不会执行
        if (!$this->isTaskWorker() && $this->initLock->trylock()) {
            //进程启动后进行开服的初始化
            $this->onOpenServiceInitialization();
            $this->initLock->lock_read();
        }

        if (!$this->isTaskWorker()) {
            TimerTask::start();
        }
    }



    /**
     * 初始化各种连接池.
     *
     * @param $workerId
     *
     * @throws SwooleException
     */
    public function initAsynPools($workerId)
    {
        $this->asynPools = [];
        // if ($this->config->get('redis.enable', true)) {
        //     $this->addAsynPool('redisPool', new RedisAsynPool($this->config, $this->config->get('redis.active')));
        // }
        if ($this->config->get('redis.enable', true)) {
            $this->addAsynPool('redisPool', new RedisPool($this->config->get('redis'), Container::getInstance()));
        }

        if ($this->config->get('mysql.enable', true)) {
            $this->addAsynPool('mysqlPool', new MysqlPool($this->config->get('mysql'), Container::getInstance()));
        }

        $this->redis_pool = $this->asynPools['redisPool'] ?? null;
        $this->mysql_pool = $this->asynPools['mysqlPool'] ?? null;
    }


  /**
     * 添加AsynPool.
     *
     * @param $name
     * @param $pool
     *
     * @throws SwooleException
     */
    public function addAsynPool($name, $pool)
    {
        if (array_key_exists($name, $this->asynPools)) {
            throw  new SwooleException('pool key is exists!');
        }
        $this->asynPools[$name] = $pool;
    }

    /**
     * 获取连接池.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getAsynPool($name)
    {
        $pool = $this->asynPools[$name] ?? null;

        return $pool;
    }


    /**
     * 开服初始化(支持协程)
     * @return mixed
     */
    public function onOpenServiceInitialization()
    {
       // if ($this->mysql_pool != null) {
       //      $this->mysql_pool->installDbBuilder();
       //  }
    }



    /**
     * task异步任务
     * @param $serv
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return mixed|null
     * @throws SwooleException
     * @throws \Exception
     */
    public function onSwooleTask($serv, $task_id, $from_id, $data)
    {
        $type = $data['type'] ?? '';
        $message = $data['message'] ?? '';
        switch ($type) {
            case SwooleConst::MSG_TYPE_SEND_BATCH://发送消息
                foreach ($message['fd'] as $fd) {
                    $this->send($fd, $message['data'], true);
                }
                return null;
            case SwooleConst::MSG_TYPE_SEND_ALL://发送广播
                foreach ($this->uid_fd_table as $row) {
                    $this->send($row['fd'], $message['data'], true);
                }
                return null;
            case SwooleConst::MSG_TYPE_SEND_ALL_FD://发送广播
                foreach ($serv->connections as $fd) {
                    $this->send($fd, $message['data'], true);
                }
                return null;
            case SwooleConst::SERVER_TYPE_TASK://task任务
                $task_name = $message['task_name']; //任务名
                $task = TaskProxy::getInstance()->loader($task_name);
                $task_fuc_name = $message['task_fuc_name']; //任务方法名
                $task_data = $message['task_fuc_data']; //任务方法传参
                $task_context = $message['task_context']; //上下文
                //给task做初始化操作
                $task->initialization($task_id, $from_id, $this->server->worker_pid, $task_name, $task_fuc_name, $task_data, $task_context);
                $task->execute($task_fuc_name);
                return true;
            default:
                return parent::onSwooleTask($serv, $task_id, $from_id, $data);
        }
    }


    /**
     * uid发送失败
     * @param $uid
     * @param $data
     * @param $topic
     */
    public function onUidSendFail($uid, $data, $topic)
    {

    }

    /**
     * 是否是重载
     */
    protected function isReload()
    {
        $lock = $this->reloadLockMap[$this->workerId];
        $result = $lock->trylock();
        return !$result;
    }


    /**
     * 广播(全部FD)
     * @param $data
     * @param bool $fromDispatch
     * @throws SwooleException
     * @throws \Exception
     */
    public function sendToAllFd($data, $fromDispatch = false)
    {
        $send_data = $this->packMessage(SwooleConst::MSG_TYPE_SEND_ALL_FD, ['data' => $data]);
        if ($this->isTaskWorker()) {
            $this->onSwooleTask($this->server, 0, 0, $send_data);
        } else {
            if ($this->task_num > 0) {
                $this->server->task($send_data);
            } else {
                foreach ($this->server->connections as $fd) {
                    $this->send($fd, $data, true);
                }
            }
        }
        if ($fromDispatch) return;

    }

    /**
     * 广播
     * @param $data
     * @param bool $fromDispatch
     * @throws SwooleException
     * @throws \Exception
     */
    public function sendToAll($data, $fromDispatch = false)
    {
        $send_data = $this->packMessage(SwooleConst::MSG_TYPE_SEND_ALL, ['data' => $data]);
        if ($this->isTaskWorker()) {
            $this->onSwooleTask($this->server, 0, 0, $send_data);
        } else {
            if ($this->task_num > 0) {
                $this->server->task($send_data);
            } else {
                foreach ($this->uid_fd_table as $row) {
                    $this->send($row['fd'], $data, true);
                }
            }
        }
        if ($fromDispatch) return;

    }

    /**
     * 向uid发送消息
     * @param $uid
     * @param $data
     * @param $fromDispatch
     * @throws \Exception
     */
    public function sendToUid($uid, $data, $fromDispatch = false)
    {
        if ($this->uid_fd_table->exist($uid)) {//本机处理
            $fd = $this->uid_fd_table->get($uid)['fd'];
            $this->send($fd, $data, true);
        } else {
            if ($fromDispatch) {
                $this->onUidSendFail($uid, $data, null);
                return;
            }

            $this->onUidSendFail($uid, $data, null);
        }
    }

    /**
     * 批量发送消息
     * @param $uids
     * @param $data
     * @param $fromDispatch
     * @throws SwooleException
     * @throws \Exception
     */
    public function sendToUids($uids, $data, $fromDispatch = false)
    {
        $current_fds = [];
        foreach ($uids as $key => $uid) {
            if ($this->uid_fd_table->exist($uid)) {
                $current_fds[] = $this->uid_fd_table->get($uid)['fd'];
                unset($uids[$key]);
            }
        }
        if (count($current_fds) > $this->send_use_task_num && $this->task_num > 0) {//过多人就通过task
            $task_data = $this->packMessage(SwooleConst::MSG_TYPE_SEND_BATCH, ['data' => $data, 'fd' => $current_fds]);
            if ($this->isTaskWorker()) {
                $this->onSwooleTask($this->server, 0, 0, $task_data);
            } else if ($this->isWorker()) {
                $this->server->task($task_data);
            } else {
                foreach ($current_fds as $fd) {
                    $this->send($fd, $data, true);
                }
            }
        } else {
            foreach ($current_fds as $fd) {
                $this->send($fd, $data, true);
            }
        }
        if ($fromDispatch) return;

    }


    /**
     * @param $uid
     * @param $data
     * @param $topic
     * @throws \Exception
     */
    public function pubToUid($uid, $data, $topic)
    {
        if ($this->uid_fd_table->exist($uid)) {
            $fd = $this->uid_fd_table->get($uid)['fd'];
            $result = $this->send($fd, $data, true, $topic);
            if(!$result){
                $this->onUidSendFail($uid, $data, $topic);
            }
        }else{
            $this->onUidSendFail($uid, $data, $topic);
        }
    }



    /**
     * 连接断开
     * @param $serv
     * @param $fd
     * @throws Asyn\MQTT\Exception
     */
    public function onSwooleClose($serv, $fd)
    {
        parent::onSwooleClose($serv, $fd);
        $uid = $this->getUidFromFd($fd);
        $this->unBindUid($uid, $fd);
    }

    /**
     * WS连接断开
     * @param $serv
     * @param $fd
     */
    public function onSwooleWSClose($serv, $fd)
    {
        parent::onSwooleWSClose($serv, $fd);
        $uid = $this->getUidFromFd($fd);
        $this->unBindUid($uid, $fd);
    }

    /**
     * 正常关服操作
     * @param $serv
     */
    public function onSwooleShutdown($serv)
    {
        parent::onSwooleShutdown($serv);
        LogEcho('Swoole:','Shutdown success.') ;
    }

    /**
     * 踢用户下线
     * @param $uid
     * @param bool $fromDispatch
     * @throws \Exception
     */
    public function kickUid($uid, $fromDispatch = false)
    {
        if ($this->uid_fd_table->exist($uid)) {//本机处理
            $fd = $this->uid_fd_table->get($uid)['fd'];
            $this->close($fd);
        } else {
            if ($fromDispatch) return;
        }
    }

    /**
     * 将fd绑定到uid,uid不能为0
     * @param $fd
     * @param $uid
     * @param bool $isKick 是否踢掉uid上一个的链接
     * @throws \Exception
     */
    public function bindUid($fd, $uid, $isKick = true)
    {
        if (!is_string($uid) && !is_int($uid)) {
            throw new \Exception("uid必须为string或者int");
        }
        //这里转换成string型的uid，不然ds/Set有bug
        $uid = (string)$uid;
        if ($isKick) {
            $this->kickUid($uid, false);
        }
        $this->uid_fd_table->set($uid, ['fd' => $fd]);
        $this->fd_uid_table->set($fd, ['uid' => $uid]);
    }

    /**
     * 解绑uid，链接断开自动解绑
     * @param $uid
     * @param $fd
     * @throws Asyn\MQTT\Exception
     * @throws \Exception
     */
    public function unBindUid($uid, $fd)
    {
        //更新共享内存
        $this->uid_fd_table->del($uid);
        $this->fd_uid_table->del($fd);
    }


    /**
     * 向task发送强制停止命令
     * @param $task_id
     */
    public function stopTask($task_id)
    {
        $task_pid = $this->tid_pid_table->get($task_id)['pid'];
        if ($task_pid != null) {
            posix_kill($task_pid, SIGKILL);
            $this->tid_pid_table->del($task_id);
        }
    }

    /**
     * 获取服务器上正在运行的Task
     * @return array
     */
    public function getServerAllTaskMessage()
    {
        $tasks = [];
        foreach ($this->tid_pid_table as $id => $row) {
            $row['task_id'] = $id;
            $row['run_time'] = time() - $row['start_time'];
            $tasks[] = $row;
        }
        return $tasks;
    }

    /**
     * 发布uid信息
     * @param $uid
     * @return mixed|null
     * @throws \Exception
     */
    public function getUidInfo($uid)
    {
        $fd = $this->getFdFromUid($uid);
        if (empty($fd)) {
 			return [];
        } else {
            $fdInfo = $this->getFdInfo($fd);
            $fdInfo['node'] = getNodeName();
            return $fdInfo;
        }
    }

    /**
     * 获取port
     * @param $port_num
     * @return mixed
     */
    public function getPort($port_num)
    {
        $ports = $this->server->ports;
        foreach ($ports as $port) {
            if ($port->port == $port_num) {
                return $port;
            }
        }
        return null;
    }


   /**
     * 获得服务器状态
     *
     * @return mixed
     *
     * @throws Asyn\MQTT\Exception
     * @throws \Exception
     */
    public function getStatus()
    {
        $status = $this->getInstance()->server->stats();

        $qps = (int) ($status['request_count'] / $now_time * 1000);
        $this->lastTime = $now_time;
        $this->lastReqTimes = $status['request_count'];
        $status['qps'] = $qps;
        $status['system'] = PHP_OS;
        $status['swoole_version'] = SWOOLE_VERSION;
        $status['php_version'] = PHP_VERSION;
        $status['worker_num'] = $this->worker_num;
        $status['task_num'] = $this->task_num;
        $status['max_connection'] = $this->max_connection;
        $status['start_time'] = $this->getStartMillisecond();
        $status['run_time'] = getMillisecond() - $this->getStartMillisecond();
        $poolStatus = $this->getPoolStatus();
        $status['pool'] = $poolStatus['pool'];
        $status['model_pool'] = $poolStatus['model_pool'];
        $status['controller_pool'] = $poolStatus['controller_pool'];
        $status['ports'] = $this->portManager->getPortStatus();
        return $status;
    }



    /**
     * *
     * @return [type] [description]
     */
    public function getPoolStatus()
    {

    }


    /**
     * 可以在这验证WebSocket连接,return true代表可以握手，false代表拒绝
     * @param HttpInput $httpInput
     * @return bool
     */
    public function onWebSocketHandCheck()
    {
        return true;
    }

    /**
     * @return string
     * 设置默认的事件控制器名称，长连接（TCP，WS，WSS）需要配置
     * 通过此函数可以将客户端的connect和close的回调路由到控制器中。
     */
    function getEventControllerName()
    {
        return "ActionController";
    }

    /**
     * @return string
     * 设置默认的事件Connect方法名称，长连接（TCP，WS，WSS）需要配置
     */
    function getConnectMethodName()
    {
        return "onConnect";
    }

    /**
     * @return string
     * 设置默认的事件Close方法名称，长连接（TCP，WS，WSS）需要配置
     */
    function getCloseMethodName()
    {
        return "onClose";
    }



}
