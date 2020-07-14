<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:20:05
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 11:36:03
 */
namespace App\Controllers\Ws;
use Server\Controllers\Controller;

class Connect extends Controller
{


    /**
     * 连接开启
     * @throws \Exception
     */
    public function onConnect()
    {
        $uid = time();
        //$this->bindUid($uid);
        echo 'welcome : '. $uid;
        $this->send(['type' => 'welcome', 'id' => $uid]);
    }


    /**
     * * 连接关闭
     * @return [type] [description]
     */
    public function onClose()
    {
        echo 'onClose : '. time();
    }


}


