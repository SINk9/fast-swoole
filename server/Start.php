<?php

/**
 * @Author: sink
 * @Date:   2020-07-05 17:52:10
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:56:06
 */

namespace Server;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Start
{
    /**
     *
     * @var 是否守护进程
     */
    protected static $daemonize = false;


    /**
     * @var 开始时间
     */
    protected static $startTime;

    /**
     * @var 当前的时间(毫秒)
     */
    protected static $startMillisecond;

    /**
     * @var 集群
     */
    protected static $leader;

   /**
     * 单元测试
     * @var bool
     */
    public static $testUnity = false;
    /**
     * 单元测试文件目录
     * @var string
     */
    public static $testUnityDir = '';

    /**
     * @var SymfonyStyle
     */
    public static $io;

    /**
     * @var
     */
    protected static $debug;
    /**
     * Run all worker instances.
     *
     * @return void
     * @throws \Exception
     */
    public static function run()
    {

        self::$debug = new \swoole_atomic(0);
        self::$leader = new \swoole_atomic(0);
        self::$startTime = date('Y-m-d H:i:s');
        self::$startMillisecond = getMillisecond();
        //self::setProcessTitle('demaxiya~');
        $application = new Application();
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        self::$io = new SymfonyStyle($input, $output);
        self::addDirCommand(SERVER_DIR, "Server", $application);
        self::addDirCommand(APP_DIR, "App", $application);
        $application->run($input, $output);
    }

    /**
     * @param $root
     * @param $namespace
     * @param $application
     */
    private static function addDirCommand($root, $namespace, $application)
    {
        $path = $root . "/Console";
        if (!file_exists($path)) {
            return;
        }
        $file = scandir($path);
        foreach ($file as $value) {
            list($name, $ex) = explode('.', $value);
            if (!empty($name) && $ex == 'php') {
                $class = "$namespace\\Console\\$name";
                if (class_exists($class)) {
                    $instance = new $class($name);
                    if ($instance instanceof Command) {
                        $application->add($instance);
                    }
                }
            }
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
        if (isDarwin()) {
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
     * 设置守护进程
     */
    public static function setDaemonize()
    {
        self::$daemonize = true;
    }

    /**
     * 获取是否守护进程
     */
    public static function getDaemonize()
    {
        return self::$daemonize ? 1 : 0;
    }


    /**
     * *
     */
    public static function isLeader()
    {
        return self::$leader->get() == 1 ? true : false;
    }


    /**
     * *
     */
    public static function setLeader($bool)
    {
        self::$leader->set($bool ? 1 : 0);
    }


    /**
     * * 获取开始时间
     */
    public static function getStartTime()
    {
        return self::$startTime;
    }


    /**
     * * 获取开始时间(毫秒)
     */
    public static function getStartMillisecond()
    {
        return self::$startMillisecond;
    }


    /**
     * *
     */
    public static function getDebug()
    {
        return self::$debug->get() == 1 ? true : false;
    }

    /**
     * *
     */
    public static function setDebug($debug)
    {
        self::$debug->set($debug ? 1 : 0);
        if ($debug) {
            LogEcho("SYS", "DEBUG开启");
        } else {
            LogEcho("SYS", "DEBUG关闭");
        }
    }

}
