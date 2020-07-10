<?php

/**
 * Controller 控制器
 * @Author: sink
 * @Date:   2019-08-09 11:58:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 14:07:03
 */

namespace Server\Controllers;

use Server\SwooleConst;
use Server\ProxyServer;
use Server\CoreBase\CoreBase;
use Server\Exceptions\SwooleException;
use Server\Exceptions\SwooleNotFoundException;
use Server\Exceptions\SwooleRedirectException;
use Server\Exceptions\SwooleInterruptException;
use Throwable;

class Controller extends CoreBase
{

    /**
     * 是否来自http的请求不是就是来自tcp
     * @var string
     */
    public $request_type;

    /**
     * 名称
     * @var string
     */
    public $core_name;

   /**
     * 销毁标志
     * @var bool
     */
    public $is_destroy = false;

    /**
     * 上下文
     * @var [type]
     */
    protected $context;

    /**
     * Http是否已经end
     * @var bool
     */
    protected $isEnd;
    /**
     * fd
     * @var int
     */
    protected $fd;

    /**
     * @var Miner
     */
    public $db;

    /**
     * @var \Redis
     */
    protected $redis;


    /**
     * uid
     * @var int
     */
    protected $uid;
    /**
     * 用户数据
     * @var
     */
    protected $client_data;
    /**
     * http response
     * @var \swoole_http_request
     */
    protected $request;
    /**
     * http response
     * @var \swoole_http_response
     */
    protected $response;


    public $HttpResponse;

    public $HttpRequest;

    /**
     * [$instance description]
     * @var [type]
     */
    protected $instance;

    /**
     * Controller constructor.
     * @param string $proxy
     */
    public function __construct()
    {
        parent::__construct();
        $this->HttpResponse = new HttpResponse($this);
    	$this->instance = ProxyServer::getInstance();
    }


    /**
     * 来自Tcp
     * 设置客户端协议数据
     * @param $uid
     * @param $fd
     * @param $client_data
     * @param $controller_name
     * @param $method_name
     * @param $params
     * @return void
     * @throws \Exception
     * @throws Throwable
     */
    public function setClientData($uid, $fd, $client_data, $controller_name, $method_name, $params)
    {
        $this->uid = $uid;
        $this->fd = $fd;
        $this->client_data = $client_data;
        $this->request_type = SwooleConst::TCP_REQUEST;
        $this->execute($controller_name, $method_name, $params);
    }

    /**
     * 来自Http
     * set http Request Response
     * @param $request
     * @param $response
     * @param $controller_name
     * @param $method_name
     * @param $params
     * @return void
     * @throws \Exception
     * @throws Throwable
     */
    public function setRequestResponse($request, $response, $controller_name, $method_name, $params)
    {
        $this->HttpResponse->set($request,$response);
        $this->request = $request;
        $this->response = $response;
        $this->request_type = SwooleConst::HTTP_REQUEST;
        $this->fd = $request->fd;
        $this->execute($controller_name, $method_name, $params);
    }


    /**
     * @param $controller_name
     * @param $method_name
     * @param $params
     * @return void
     * @throws \Exception
     * @throws Throwable
     */
    protected function execute($controller_name, $method_name, $params)
    {
        //是否存在方法
        if (!is_callable([$this, $method_name])) {
            $this->context['raw_method_name'] = "$controller_name:$method_name";
            $method_name = 'defaultMethod';
        }
        //初始化
        try {
            $this->initialization($controller_name, $method_name);
        } catch (Throwable $e) {
            $this->onExceptionHandle($e);
            $this->destroy();
            return;
        }
        //运行
        try {
            if ($params == null) {
                $this->$method_name();
            } else {
                $params = array_values($params);
                $this->$method_name(...$params);
            }
        } catch (Throwable $e) {
            $this->onExceptionHandle($e);
        }
        $this->destroy();
    }

    /**
     * 初始化每次执行方法之前都会执行initialization
     * @param string $controller_name 准备执行的controller名称
     * @param string $method_name 准备执行的method名称
     * @throws \Exception
     */
    protected function initialization($controller_name, $method_name)
    {

        $tick_time = getMillisecond() - $this->instance::getStartMillisecond();
        $this->context['request_id'] = time() . crc32($controller_name . $method_name . $tick_time . rand(1, 10000000));
        $this->context['controller_name'] = $controller_name;
        $this->context['method_name'] = "$controller_name::$method_name";
        $this->context['ip'] = $this->getFdInfo()['remote_ip'];
        if (!empty($this->uid)) {
            $this->context['uid'] = $this->uid;
        }
        $this->db = $this->loader->mysql('mysqlPool',$this);
        $this->redis = $this->loader->redis('redisPool', $this);

    }

    /**
     * ws追加设置Request
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * 异常的回调(如果需要继承$autoSendAndDestroy传flase)
     * @param Throwable $e
     * @param callable $handle
     */
    public function onExceptionHandle(\Throwable $e, $handle = null)
    {

        //必须的代码
        if ($e instanceof SwooleRedirectException) {
            $this->response->status($code);
            $this->response->header('Location', $e->getMessage());
            $this->response->end('end');
            return;
        }

        //中断信号
        if ($e instanceof SwooleInterruptException) {
            return;
        }
        //方法未找到
        if($e instanceof SwooleNotFoundException) {

        }
        //程序异常
        if ($e instanceof SwooleException) {
            LogEcho("EX", "--------------------------[报错指南]----------------------------" . date("Y-m-d h:i:s"));
            LogEcho("EX", "异常消息：" . $e->getMessage());
            LogEcho("EX", "运行链路:");
            foreach ($this->context as $key => $value) {
                LogEcho("EX", "$key# $value");
            }
            LogEcho("EX", "--------------------------------------------------------------");
        }

        $this->context['error_message'] = $e->getMessage();
        //如果是HTTP传递request过去
        if ($this->request_type == SwooleConst::HTTP_REQUEST) {
            $e->request = $this->request;
            //生成错误数据页面
            //...

        }
    }

    /**
     * 销毁
     */
    public function destroy()
    {
        if ($this->is_destroy) {
            return;
        }
        if ($this->request_type == SwooleConst::HTTP_REQUEST) {
            $this->response->end('');
        }
        $this->isEnd = false;
        $this->fd = null;
        $this->uid = null;
        $this->client_data = null;
        $this->request = null;
        $this->response = null;
        //$this->db->release();
        ControllerFactory::getInstance()->revertController($this);
    }

    /**
     * 当控制器方法不存在的时候的默认方法
     * @throws SwooleNotFoundException
     */
    public function defaultMethod()
    {
        if ($this->request_type == SwooleConst::HTTP_REQUEST) {
            $this->response->status(404);
            $this->response->end('not found');
        } else {
            throw new SwooleNotFoundException($this->context['raw_method_name'] . ' method not exist');
        }
    }

    /**
     * 向当前客户端发送消息
     * @param $data
     * @throws \Exception
     */
    protected function send($data)
    {
        $this->instance->send($this->fd, $data, true);
    }

    /**
     * sendToUid
     * @param $uid
     * @param $data
     * @throws \Exception
     */
    protected function sendToUid($uid, $data)
    {
    	$this->instance->sendToUid($uid, $data);
    }

    /**
     * sendToUids
     * @param $uids
     * @param $data
     * @throws SwooleException
     */
    protected function sendToUids($uids, $data)
    {
    	$this->instance->sendToUids($uids, $data);
    }

    /**
     * sendToAll
     * @param $data
     * @throws SwooleException
     */
    protected function sendToAll($data)
    {
    	$this->instance->sendToAll($data);
    }

    /**
     * sendToAllFd
     * @param $data
     * @throws SwooleException
     */
    protected function sendToAllFd($data)
    {
    	$this->instance->sendToAllFd($data);
    }

    /**
     * 踢用户
     * @param $uid
     * @throws \Exception
     */
    protected function kickUid($uid)
    {
    	$this->instance->kickUid($uid);
    }

    /**
     * bindUid
     * @param $uid
     * @param bool $isKick
     * @throws \Exception
     */
    protected function bindUid($uid, $isKick = true)
    {
        if (!empty($this->uid)) {
            throw new SwooleException("已经绑定过uid");
        }

        $this->instance->bindUid($this->fd, $uid, $isKick);
        $this->uid = $uid;
    }

    /**
     * unBindUid
     * @throws \Server\Asyn\MQTT\Exception
     */
    protected function unBindUid()
    {
        if (empty($this->uid)) return;
        $this->instance->unBindUid($this->uid, $this->fd);
    }

    /**
     * 断开链接
     */
    protected function close()
    {
    	$this->instance->close($this->fd);
    }

    /**
     * Http重定向
     * @param $location
     * @param int $code
     * @throws SwooleException
     * @throws SwooleRedirectException
     */
    protected function redirect($location, $code = 302)
    {
        if ($this->request_type == SwooleConst::HTTP_REQUEST) {
            throw new SwooleRedirectException($location, $code);
        } else {
            throw new SwooleException('重定向只能在http请求中使用');
        }
    }

    /**
     * 重定向到404
     * @param int $code
     * @throws SwooleException
     * @throws SwooleRedirectException
     */
    protected function redirect404($code = 302)
    {
        $location = 'http://' . $this->request->header['host'] . "/" . '404';
        $this->redirect($location, $code);
    }

    /**
     * 重定向到控制器，这里的方法名不填前缀
     * @param $controllerName
     * @param $methodName
     * @param int $code
     * @throws SwooleException
     * @throws SwooleRedirectException
     */
    protected function redirectController($controllerName, $methodName, $code = 302)
    {
        $location = 'http://' . $this->request->header['host'] . "/" . $controllerName . "/" . $methodName;
        $this->redirect($location, $code);
    }

    /**
     * 获取fd的信息
     * @return mixed
     */
    protected function getFdInfo()
    {
        return $this->instance->getFdInfo($this->fd);
    }


    /**
     * 中断
     * @throws SwooleInterruptException
     */
    public function interrupt()
    {
        if ($this->request_type == SwooleConst::HTTP_REQUEST) {
            $this->response->end("");
        }
        throw new SwooleInterruptException('interrupt');
    }

    /**
     * @return bool
     */
    public function canEnd()
    {
        return !$this->isEnd;
    }

    /**
     * endOver
     */
    public function endOver()
    {
        $this->isEnd = true;
    }
}
