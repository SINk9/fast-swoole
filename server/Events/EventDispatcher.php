<?php

/**
 * 事件派发器
 * @Author: 不再迟疑
 * @Date:   2019-08-12 13:37:05
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 21:19:33
 */

namespace Server\Events;

use Server\Memory\Pool;
use Server\SwooleConst;
use Server\ProxyServer;


class EventDispatcher
{
    /**
     * @var EventDispatcher
     */
    public static $instance;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new EventDispatcher();
        }
        return self::$instance;
    }

    private $_eventListeners = array();

    /**
     * Registers an event listener at a certain object.
     *
     * @param string $type
     * @param callable $listener
     */
    public function add($type, $listener)
    {
        if (!array_key_exists($type, $this->_eventListeners)) {
            $this->_eventListeners [$type] = [];
        }
        array_push($this->_eventListeners[$type], $listener);
    }

    /**
     * Removes an event listener from the object.
     *
     * @param string $type
     * @param callable $listener
     */
    public function remove($type, $listener)
    {
        if (array_key_exists($type, $this->_eventListeners)) {
            $index = array_search($listener, $this->_eventListeners [$type]);
            if ($index !== null) {
                unset ($this->_eventListeners [$type] [$index]);
            }
            $numListeners = count($this->_eventListeners [$type]);
            if ($numListeners == 0) {
                unset ($this->_eventListeners [$type]);
            }
        }
    }

    /**
     * Removes all event listeners with a certain type, or all of them if type is null.
     * Be careful when removing all event listeners: you never know who else was listening.
     *
     * @param string $type
     */
    public function removeAll($type = null)
    {
        if ($type) {
            unset ($this->_eventListeners [$type]);
        } else {
            $this->_eventListeners = array();
        }
    }

    /**
     * Dispatches an event to all objects that have registered listeners for its type.
     * If an event with enabled 'bubble' property is dispatched to a display object, it will
     * travel up along the line of parents, until it either hits the root object or someone
     * stops its propagation manually.
     *
     * @param Event $event
     */
    protected function dispatchEvent($event)
    {
        if (!array_key_exists($event->type, $this->_eventListeners)) {
            return; // no need to do anything
        }
        $this->invokeEvent($event);
    }

    /**
     * @private
     * Invokes an event on the current object.
     * This method does not do any bubbling, nor
     * does it back-up and restore the previous target on the event. The 'dispatchEvent'
     * method uses this method internally.
     *
     * @param Event $event
     */
    private function invokeEvent($event)
    {
        LogEcho('invokeEvent:',json_encode($this->_eventListeners));
        if (array_key_exists($event->type, $this->_eventListeners)) {
            $listeners = $this->_eventListeners [$event->type];
        } else {
            return;
        }
        foreach ($listeners as $listener) {
		    if(is_callable($listener)){
		        call_user_func($listener,$event);
		    }
        }
    }

    /**
     * Dispatches an event with the given parameters to all objects that have registered
     * listeners for the given type.
     * The method uses an internal pool of event objects to
     * avoid allocations.
     *
     * @param string $type
     * @param null $data
     * @throws \Exception
     */
    public function dispatch($type, $data = null)
    {
    	self::workerDispatchEventWith([$type, $data]);
    }

    /**
     * 派发给指定进程
     * @param $workerId
     * @param $type
     * @param null $data
     */
    public function appointDispath($workerId, $type, $data = null)
    {
        ProxyServer::getInstance()->sendToOneWorker($workerId, SwooleConst::DISPATCHER_NAME, [$type, $data], EventDispatcher::class . "::workerDispatchEventWith");
    }

    /**
     * 派发给随机进程
     * @param $type
     * @param null $data
     */
    public function randomDispatch($type, $data = null)
    {
        ProxyServer::getInstance()->sendToRandomWorker(SwooleConst::DISPATCHER_NAME, [$type, $data], EventDispatcher::class . "::workerDispatchEventWith");
    }

    /**
     * @param $data
     */
    public static function workerDispatchEventWith($data)
    {
        $event = Pool::getInstance()->get(Event::class)->reset($data[0], $data[1]);
        self::getInstance()->dispatchEvent($event);
        Pool::getInstance()->push($event);
    }

}
