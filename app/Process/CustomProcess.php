<?php

/**
 * 自定义进程 目前用来跑秒级定时器
 * @Author: sink
 * @Date:   2020-07-09 14:33:00
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 09:11:46
 */

namespace App\Process;
use App\Timer\Timing;
use Server\Memory\Pool;
use Server\Process\Process;
use Server\TimerTasks\Timer;
class CustomProcess extends Process
{
    public function start($process)
    {
    	$timing = Pool::getInstance()->get(Timing::class);
        //自动参与
    	 Timer::getInstance()->addTick('TimingJoin', 1000, function () use ($timing) {
            $timing->join();
        });
         //成交
        //  Timer::getInstance()->addTick('TimingDeal', 1000, function () use ($timing) {
        //     $timing->deal();
        // });
    }



    protected function shutdown()
    {
        // TODO: Implement onShutDown() method.
    }
}
