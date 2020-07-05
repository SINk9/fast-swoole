<?php

/**
 * 定时器
 * @Author: sink
 * @Date:   2019-08-12 13:11:46
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:58:11
 */

namespace Server\TimerTasks;
use Server\Memory\Pool;
use Server\ProxyServer;
use Server\Exception\SwooleException;
use Server\CoreBase\Child;
use Server\Events\Event;
use Server\Events\EventDispatcher;

class Timer
{
    protected static $instance;
    protected static $table;
    protected $flag = "TimerClear";

    public static function init()
    {
        self::$table = new \swoole_table(65536);
        self::$table->column('wid', \swoole_table::TYPE_INT, 4);
        self::$table->column('tid', \swoole_table::TYPE_INT, 4);
        self::$table->create();
    }

    /**
     * @return Timer
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Timer();
        }
        return self::$instance;
    }

    /**
     * Timer constructor.
     */
    public function __construct()
    {
        EventDispatcher::getInstance()->add($this->flag, function (Event $object) {
            $data = self::$table->get($object->data);
            if ($data['wid'] == ProxyServer::getInstance()->getWorkerId()) {
                self::$table->del($object->data);
                \swoole_timer_clear($data['tid']);
            }
        });
    }

    /**
     * @param $name
     * @param int $ms
     * @param callable $callback
     * @throws App\Swoole\Exception\SwooleException
     */
    public function addTick($name, int $ms, callable $callback)
    {
        if (self::$table->exist($name)) {
            throw new SwooleException("存在相同名字的定时器");
        }
        $tid = \swoole_timer_tick($ms, function () use ($callback) {
            $child = Pool::getInstance()->get(Child::class);
            call_user_func($callback, $child);
            $child->destroy();
            Pool::getInstance()->push($child);
        });
        self::$table->set($name, ["wid" => ProxyServer::getInstance()->getWorkerId(), "tid" => $tid]);
    }

    /**
     * @param $name
     */
    public function clearTick($name)
    {
        if (self::$table->exist($name)) {
            EventDispatcher::getInstance()->dispatch($this->flag, $name, false, true);
        }
    }
}
