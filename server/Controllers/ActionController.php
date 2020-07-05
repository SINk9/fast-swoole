<?php

/**
 * @Author: sink
 * @Date:   2019-08-12 10:12:39
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:00:29
 */

namespace Server\Controllers;

class ActionController extends Controller
{

	/**
	 * * 初始化
	 */
    protected function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name);
    }

    /**
     * 连接开启
     * @throws \Exception
     */
    public function onConnect()
    {
        $uid = time();
        $this->bindUid($uid);
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

    /**
     * @throws \App\Swoole\Exception\SwooleException
     */
    public function message()
    {
        $this->sendToAll(
            [
                'type' => 'message',
                'id' => $this->uid,
                'message' => $this->client_data->message,
            ]
        );
    }

}
