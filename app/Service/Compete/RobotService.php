<?php

/**
 * @Author: sink
 * @Date:   2020-07-13 09:12:20
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 15:14:49
 */


namespace App\Service\Compete;
use App\Consts\CacheKey;
use Hyperf\Pool\Connection;
use App\Service\Compete\RobotService;


class RobotService
{

	/**
	 * $param = []
	 * @var [goods_id]   [商品标识]
	 * @var [uid]        [机器人标识]
	 * @var [timestamp]  [参与时间戳]
	 * @return []
	 */
	public static function join($param, Connection $redis)
    {

        if (empty($param['uid']) || empty($param['goods_id'])) {
            return ['error'=>1,'message'=>'参数错误'];
        }
        //获取当前竞拍信息
        $competes = $redis->hget(CacheKey::COMPETE_UNDERWAY, $param['goods_id']);
        if (empty($competes)) {
            //删除待插入机器人缓存数据
            $redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
            $redis->hdel(CacheKey::ROBOT_WAIT_JOIN, $param['goods_id']);
            return ['error'=>2,'message'=>'竞拍信息不存在'];
        }
        $competes = json_decode($competes, true);
        if ($competes['last_buy_uid'] == $param['uid']) {
        	return ['error'=>3,'message'=>'当前已是领先状态'];
        }
        if($competes['status'] == 0){
        	return ['error'=>4,'message'=>'竞拍未开始'];
        }

        // if($competes['end_time'] < time()){
        //     return ['error'=>5,'message'=>'竞拍已成交或不存在'];
        // }

        //开始事务提交
        $user_info = $redis->hget(CacheKey::ROBOT_USER, $param['uid']);
        $user_info = json_decode($user_info, true);
        //更新竞拍信息
        if($competes['status'] == 1){
            $competes['status'] =  2;
        }
        $competes['now_price']    = sprintf("%.2f",$competes['now_price'] + $competes['range']);
        $competes['end_time']     = time() + $competes['down_time'];
        $competes['last_buy_uid'] = $param['uid'];
        $competes['last_join']    = [
            'uid'             => $param['uid'],
            'nickname'        => $user_info['nickname'],
            'avatar'          => $user_info['avatar'],
            'price'           => $competes['now_price'],
        ];
        $redis->hset(CacheKey::COMPETE_UNDERWAY, $param['goods_id'], json_encode($competes)); //需同步mysql

        //添加机器人参与记录
        $join_record_key = CacheKey::ROBOT_COMPETE_JOIN_RECORD.$competes['goods_id'].':'.$competes['issue'];
        $redis->hincrby($join_record_key, $param['uid'], 1);

        //添加用户竞拍日志
        $join_record_log = [
            'uid'            => $param['uid'],
            'goods_id'       => $competes['goods_id'],
            'issue'          => $competes['issue'],
            'offer'          => $competes['now_price'],
            'join_microtime' => empty($param['timestamp']) ? getMicrotime() : $param['timestamp'],
            'place'          => $user_info['place'],
            'is_robot'       => 1,
        ];
        $join_record_log_key = CacheKey::COMPETE_JOIN_RECORD_LOG.$param['goods_id'].':'.$competes['issue'];
        $redis->lpush($join_record_log_key, json_encode($join_record_log)); //需写入mysql
        //结束事务

        //更新成交时间
        $redis->hset(CacheKey::COMPETE_DEAL_TIMING, $param['goods_id'], $competes['end_time']);


        //待插入机器人参与次数更新
        $wait_join_robot = $redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
        $wait_join_robot = json_decode($wait_join_robot, true);
        foreach ($wait_join_robot as $key => $value) {
            if ($value['uid'] == $param['uid']) {
                $wait_join_robot[$key]['join_count'] = $value['join_count'] - 1;
                if($wait_join_robot[$key]['join_count'] <= 0){
                    unset($wait_join_robot[$key]);
                }
                $redis->hset('wait_join_robot', $param['goods_id'], json_encode($wait_join_robot));
                break;
            }
        }

        //返回结果数据
        $result    = array(
            'offer'     => $competes['now_price'],
            'uid'       => $param['uid'],
            'nickname'  => $user_info['nickname'],
            'goods_id'  => $param['goods_id'],
            'issue'     => $competes['issue'],
            'avatar'    => $user_info['avatar'],
            'place'     => $user_info['place'],
            'auto_join' => ['status' => 0, 'set_count' => 0, 'remain_count' => 0], //自动出价信息
        );
        return ['error' => 0, 'data' => $result];
    }


    /**
     * * 生成机器人待插入列表
     * @param  int    $goods_id
     * @return []
     */
    public static function generate_join_list(int $goods_id, Connection $redis)
    {
        //获取商品机器人设置信息
        $robot_setting = $redis->hget(CacheKey::ROBOT_SETING, $goods_id);
        if (empty($robot_setting)) {
            return ['error' => 1, 'message' => '未设置机器人'];
        }
        $robot_setting = json_decode($robot_setting, true);

        $data         = [];
        $a_join_robot = rand($robot_setting['a_min_join_robot'], $robot_setting['a_max_join_robot']);
        $b_join_robot = rand($robot_setting['b_min_join_robot'], $robot_setting['b_max_join_robot']);
        $c_join_robot = rand($robot_setting['c_min_join_robot'], $robot_setting['c_max_join_robot']);
        $robot_number = $a_join_robot + $b_join_robot + $c_join_robot;
        $robot_count  = 0;

        //取mysql
        $robot_sum = $redis->hlen(CacheKey::ROBOT_USER);
        for ($i=1; $i <= $robot_number ; $i++) {
            $fields[] = rand(1,$robot_sum);
        }
        $robot        = $redis->hmget(CacheKey::ROBOT_USER,$fields);
        if (empty($robot)) {
            return ['error' => 2, 'message' => '获取插入机器人失败'];
        }
        foreach ($robot as $key => $value) {
            $robot[$key] = json_decode($value, true);
        }

        //a方案
        if (!empty($a_join_robot)) {
            //取机器人
            $a_robot = array_slice($robot, 0, $a_join_robot);
            foreach ($a_robot as $value) {
                $join_count = rand($robot_setting['a_min_join_count'], $robot_setting['a_max_join_count']);
                $robot_count += $join_count;
                $a_plan[] = array(
                    'join_count' => $join_count,
                    'uid'        => $value['uid'],
                );
            }
            $data = $a_plan;
        }
        //b方案
        if (!empty($b_join_robot)) {
            //取机器人
            $b_robot = array_slice($robot, $a_join_robot, $b_join_robot);
            foreach ($b_robot as $value) {
                $join_count = rand($robot_setting['b_min_join_count'], $robot_setting['b_max_join_count']);
                $robot_count += $join_count;
                $b_plan[] = array(
                    'join_count' => $join_count,
                    'uid'        => $value['uid'],
                );
            }
            $data = array_merge($data, $b_plan);
        }
        //c方案
        if (!empty($c_join_robot)) {
            //插入数量
            $join_count = explode(',', $robot_setting['c_join_count']);
            //取机器人
            $robot_start_count = $a_join_robot + $b_join_robot;
            $c_robot           = array_slice($robot, $robot_start_count, $c_join_robot);

            foreach ($c_robot as $value) {
                $count = array_rand($join_count);
                $robot_count += (int) $join_count[$count];
                $c_plan[] = array(
                    'join_count' => (int) $join_count[$count],
                    'uid'        => $value['uid'],
                );
            }
            $data = array_merge($data, $c_plan);
        }
        //更新成交限制值
        $competes = $redis->hget(CacheKey::COMPETE_DATA, $goods_id);
        $competes = json_decode($competes, true);
        $deal_limit = rand($competes['min_deal'], $competes['max_deal']);
        $redis->hset(CacheKey::COMPETE_SET_DEAL_VALUE, $goods_id, $deal_limit);


        //清理已存在插入机器人数据
        $wait_join_robot_setting = $redis->hexists(CacheKey::ROBOT_WAIT_JOIN, $goods_id);
        if ($wait_join_robot_setting == true) {
            $redis->hdel($key, $goods_id);
        }
        //删除待插入列表缓存
        $redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST, $goods_id);
        $compete_underway = $redis->hget(CacheKey::COMPETE_UNDERWAY, $goods_id);
        if(empty($compete_underway)){
            return ['error' => 3, 'message' => '竞拍不存在'];
        }
        $compete_underway = json_decode($compete_underway,true);
        //设置插入数据
        $redis->hset(CacheKey::ROBOT_WAIT_JOIN, $goods_id, json_encode($data));

        //竞拍状态为正常情况
        if($robot_setting['status'] == 1 && $compete_underway['status'] > 0){
            //设置插入时间
            $time      = $redis->get(CacheKey::TIMING);
            $join_time = rand($robot_setting['min_join_time'], $robot_setting['max_join_time']);
            //初始状态机器人插入时间1分钟后
            if ($compete_underway['status'] == 1) {
                $time = $time - $join_time;
            } else {
                $time = $time + $join_time;
            }
            if ($time <= 0) {
                $time = $join_time;
            }
            if ($time >= 60){
                $time = $time - 59;
            }
            $redis->sadd(CacheKey::TIMING_JOIN.$time, $goods_id);
        }
        return ['error' => 0, 'message' => '生成机器人插入列表成功'];

    }


}
