<?php

/**
 * * 机器人参与
 * @Author: sink
 * @Date:   2020-07-09 10:18:18
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 15:14:19
 */

namespace App\Tasks;
use Server\Tasks\Task;
use App\Consts\CacheKey;
use App\Service\Compete\JoinService;
use App\Service\Compete\RobotService;


class Join extends Task
{

	/**
	 * * 自动参与
	 * @return [type] [description]
	 */
    public function Action()
    {
        $parameter = json_encode($this->parameter);
        //LogEcho('Timer:Task:Join=',$parameter);
    	if(empty($this->parameter['goods_id']) && empty($this->parameter['timing'])){
    		return;
    	}
        $goods_id     = $this->parameter['goods_id'];
        $timing       = $this->parameter['timing'];
        //出价
        $result = JoinService::automatic_offer($goods_id, $timing, $this->redis);
        if(empty($result)){
            return;
        }
        //LogEcho('出价成功:', json_encode($result['data']));
        if($result['error'] > 0){
            //log
        }
        if($result['error'] == 0){
            $this->sendToAllFd($result['data']);
        }

    }

}
