<?php

/**
 * @Author: sink
 * @Date:   2019-08-09 10:42:06
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 14:06:12
 */

namespace Server\Process;

use Server\TimerTasks\TimerTask;

class TimerTaskProcess extends Process
{


    public function start($process)
    {
        new TimerTask();
    }


    public function shutdown()
    {

    }

}
