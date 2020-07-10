<?php

/**
 * * 机器人参与
 * @Author: sink
 * @Date:   2020-07-09 10:18:18
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 11:57:44
 */

namespace App\Tasks;
use Server\Tasks\Task;
use App\Consts\CacheKey;


class Robot extends Task
{

	/**
	 * * 机器人参与
	 * @return [type] [description]
	 */
    public function join()
    {
        LogEcho('Timer:Task:Join',time());


    	// if(empty($this->parameter['goods_id']) && empty($this->parameter['timing'])){
    	// 	return;
    	// }
     //    //获取最后购买用户
     //    $last_buy_uid = $this->redis->hget(CacheKey::COMPETE_LAST_BUY,$this->parameter['goods_id']);
     //    if(empty($last_buy_uid)){
     //        $underway = $this->redis->hget(CacheKey::COMPETE_UNDERWAY,$this->parameter['goods_id']);
     //        if(!empty($underway)){
     //        	$underway = json_decode($underway);
     //        }
     //        if(empty($underway['last_buy_uid']) && $underway['status'] == 0 or $underway['status'] == '-1'){
     //            return;
     //        }
     //    }
     //    //取待插入机器人
     //    $join_user = $this->take_wait_join_data($this->parameter['goods_id'],$underway['last_buy_uid'],$this->parameter['timing']);
     //    if(empty($join_user)){
     //    	//log 商品['.$this->parameter['goods_id'].']出价队列插入失败，无待插入用户
     //        return;
     //    }
     //    //出价
     //    $this->offer($join_user,$this->parameter['goods_id'],$underway['last_buy_uid']);
    }


    /**
     * * 取待插入机器人
     * @param  [type] $goods_id
     * @param  [type] $last_buy_uid
     * @param  [type] $timing
     * @return [type]
     */
    private function take_wait_join_data($goods_id, $last_buy_uid, $timing)
    {

        // $wait_join_list = $this->redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id);
        // $wait_join_list = json_decode($wait_join_list,true);
        // if(empty($wait_join_list)){
        //     //自动出价数据
        //     $auto_join_data = [];
        //     $auto_data = $this->redis->hvals(CacheKey::COMPETE_AUTO_JOIN.$goods_id);
        //     if(!empty($auto_data)){
        //       foreach ($auto_data as $value) {
        //         $auto_join_data_tmp = json_decode($value,true);
        //         if($auto_join_data_tmp['status'] == 1){
        //             $auto_join_data[] = $auto_join_data_tmp;
        //         }
        //       }
        //     }
        //     $robot_join_data = [];
        //     $robot_data = $this->redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id);
        //     if(!empty($robot_data)){
        //       $robot_join_data = json_decode($robot_data,true);
        //     }
        //     //合并数据
        //     $wait_join_list = array_merge($auto_join_data,$robot_join_data);
        //     $wait_join_list = array_values($wait_join_list);

        //     if(empty($wait_join_list[0])){
        //         return false;
        //     }
        //     $this->redis->HSET(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id,json_encode($wait_join_list));
        // }
        // //取插入数据
        // $i = 0;
        // $join_user = $wait_join_list[$i];
        // //不需重复插入
        // if($join_user['uid'] == $last_buy_uid && count($wait_join_list) > 1){
        //     $i = 1;
        //     $join_user = $wait_join_list[$i];
        // }
        // //更新待插入记录
        // $join_user['join_count'] = $join_user['join_count'] - 1;
        // //参与次数用完清除
        // if($join_user['join_count'] <= 0){
        //     unset($wait_join_list[$i]);
        // }
        // //位置交换
        // if(count($wait_join_list) > 1 && $join_user['join_count'] >= 1){
        //     //默认1-3
        //     $index = rand(1,4);
        //     //查看自动出价次数
        //     $auto_data = $this->redis->hvals(CacheKey::COMPETE_AUTO_JOIN.$goods_id);
        //     $auto_count = count($auto_data);
        //     if($auto_count > 0){
        //         $index = rand(1,$auto_count+2);
        //     }
        //     $wait_join_list[$i] = $wait_join_list[$index];
        //     $wait_join_list[$index] = $join_user;
        //     if(empty($wait_join_list[$i])){
        //         unset($wait_join_list[$i]);
        //     }
        // }
        // $wait_join_list = array_values($wait_join_list);
        // $this->redis->HSET(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id,json_encode($wait_join_list));
        // if(count($wait_join_list) == 1 ){
        //     $this->redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id);
        // }
        // //设置下次插入时间
        // if(!empty($timing)){

        // 	$robot_setting = $this->redis->hget(CacheKey::ROBOT_SETTING,$goods_id);
        //     if($wait_join_list[0]['uid'] >= 100000){
        //     	$goods_info = $this->redis->hget(CacheKey::GOODS_INFO,$goods_id);
        //         $join_time = $goods_info['down_time'] - 1;
        //         $tm = $timing + $join_time;
        //         if($tm > 59){
        //             $tm = $tm - 59;
        //         }
        //         $this->redis->SADD(CacheKey::ROBOT_TIMING_JOIN.$tm,$goods_id);
        //     }else{
        //         if($robot_setting['status'] == 1){
        //             $join_time = rand($robot_setting['min_join_time'],$robot_setting['max_join_time']);
        //             $tm = $timing + $join_time;
        //             if($tm > 59){
        //                 $tm = $tm - 59;
        //             }
        //             $this->redis->SADD(CacheKey::ROBOT_TIMING_JOIN.$tm,$goods_id);
        //         }
        //     }
        // }
        // return $join_user;

    }



    /**
     * * 出价
     * @param  [type] $join_user
     * @param  [type] $goods_id
     * @param  [type] $last_buy_uid
     * @return [type]
     */
    private function offer($join_user, $goods_id, $last_buy_uid)
    {


    }

}
