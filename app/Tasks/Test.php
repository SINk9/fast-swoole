<?php

/**
 * @Author: sink
 * @Date:   2019-08-12 18:51:32
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:34:06
 */

namespace App\Tasks;
use Server\Tasks\Task;

class Test extends Task
{


    public function action()
    {

        LogEcho('TimerTasks:Test:',time());

    }
}
