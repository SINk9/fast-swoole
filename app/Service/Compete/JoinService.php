<?php

/**
 * @Author: sink
 * @Date:   2020-07-13 09:12:20
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 14:29:18
 */


namespace App\Service\Compete;
use App\Consts\CacheKey;
use Hyperf\Pool\Connection;

class JoinService
{

    /**
     * * 加入
     * @var  [goods_id]
     * @var  [issue]
     * @var  [uid]
     * @var  [count]
     * @return []
     */
    public static function join($param, Connection $redis)
    {
        $competes = $redis->hget(CacheKey::COMPETE_UNDERWAY, $param['goods_id']);
        if(empty($competes)){
            return ['error' => 1, 'message' => '无竞拍记录'];
        }
        $competes = json_decode($competes, true);
        //设置自动竞拍
        if($param['count'] > 1){
            self::set_auto_join($param, $competes, $redis);
        }
        /*[开启事务 待加入]*/
        $user_info = $redis->hget(CacheKey::USER_INFO, $param['uid']);
        $user_info = json_decode($user_info, true);
        //更新竞拍信息
        if($competes['status'] == 1){
            $competes['status'] =  2;
        }
        //测试需注释
        // if($competes['end_time'] < time()){
        //     return ['error'=>5,'message'=>'竞拍已成交或不存在'];
        // }

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

        //更新(添加)用户竞拍数据
        $join_record_key = CacheKey::COMPETE_JOIN_RECORD.$param['goods_id'].':'.$param['issue'];
        $join_record = $redis->hget($join_record_key, $param['uid']);
        if($join_record){
            $join_record = json_decode($join_record, true);
        }
        if(empty($join_record)){
            $join_record = [
                'uid'         => $param['uid'],
                'goods_id'    => $param['goods_id'],
                'issue'       => $param['issue'],
                'join_count'  => 0,
                'use_money'   => 0,
                'created_at'  => time(),
            ];
        }
        $join_record['join_count']     = $join_record['join_count'] + 1;
        $join_record['compete_status'] = 1;
        $join_record['use_money']      = $join_record['use_money'] + $competes['cost'];
        $join_record['updated_at']     = time();
        $redis->hset($join_record_key, $param['uid'],json_encode($join_record)); //需写入mysql

        $value = $param['goods_id'] .':'. $param['issue'];
        $sismember = $redis->sismember(CacheKey::USER_JOIN_RECORD_UNDERWAY.$param['uid'], $value);
        if(!$sismember){
            $redis->sadd(CacheKey::USER_JOIN_RECORD_UNDERWAY.$param['uid'], $value);
        }

        //添加用户竞拍日志
        $join_record_log = [
            'uid'            => $param['uid'],
            'goods_id'       => $param['goods_id'],
            'issue'          => $param['issue'],
            'offer'          => $competes['now_price'],
            'join_microtime' => getMicrotime(),
            'place'          => $user_info['place'],
            'is_robot'       => 0,
        ];
        $join_record_log_key = CacheKey::COMPETE_JOIN_RECORD_LOG.$param['goods_id'].':'.$param['issue'];
        $redis->lpush($join_record_log_key, json_encode($join_record_log)); //需写入mysql


        //扣除用户资金
        $balance_vars = array(
            'uid'        => $param['uid'],
            'amount'     => $competes['cost'],
            'type'       => '10001',
            'from_id'    => 'record_id',
        );
        // if(service('Expense\Money')->minus($balance_vars) == false){
        //  return ['error'=>1,'message'=>'扣除用户资金失败'];
        // }

        $balance = $redis->hget(CacheKey::USER_BALANCE, $param['uid']); //获取用户资金信息
        $balance = intval($balance - $competes['cost']);
        $redis->hset(CacheKey::USER_BALANCE, $param['uid'],$balance); //需写入mysql

        //更新自动出价
        $auto_join = ['status'=>0, 'set_count'=>0, 'remain_count'=>0];
        if($redis->hexists(CacheKey::COMPETE_AUTO_JOIN.$param['goods_id'], $param['uid'])){
            $auto_join_data = self::update_auto_join($param, $competes, $redis);
            if($auto_join_data['error'] > 0){
                return ['error'=>2, 'message'=>'更新自动出价失败'];
            }
            $auto_join = $auto_join_data;
        }

        /*[结束事务 待加入] */

        //更新成交时间
        $redis->hset(CacheKey::COMPETE_DEAL_TIMING, $param['goods_id'], $competes['end_time']);
        //更新保底值
        $redis->hincrby(CacheKey::COMPETE_ENSURE, $param['goods_id'], 1);

        $result = [
            'offer'     => $competes['now_price'],
            'uid'       => $param['uid'],
            'goods_id'  => $param['goods_id'],
            'issue'     => $param['issue'],
            'auto_join' => $auto_join,
            'avatar'    => $user_info['avatar'],
            'nickname'  => $user_info['nickname'],
            'place'     => $user_info['place'],
        ];
        return ['error'=>0, 'data' => $result];
    }

    /**
     * * 取消自动出价
     * $param = []
     * @var  [goods_id]
     * @var  [issue]
     * @var  [uid]
     * @return []
     */
    public static function cancel_auto_join($param, Connection $redis)
    {
        //检测是否设置自动竞拍
        $auto_data_key = CacheKey::COMPETE_AUTO_DATA.$param['goods_id'] .':' .$param['issue'];
        $auto_data = $redis->hget($auto_data_key, $param['uid']);
        if(empty($auto_data)){
            return ['error'=>1,'message'=>'自动竞拍数据不存在'];
        }
        $auto_data = json_decode($auto_data, true);
        if($auto_data['status'] != 1){
            return ['error'=>2,'message'=>'自动竞拍状态错误'];
        }
        $auto_data['status'] = 2;
        $auto_data['updated_at'] = time();
        $redis->hset($auto_data_key, $param['uid'], json_encode($auto_data));
        $redis->hdel(CacheKey::COMPETE_AUTO_JOIN.$param['goods_id'], $param['uid']);

        //更新待插入数据
        $wait_join = $redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
        if($wait_join){
            //$redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
            $wait_join = json_decode($wait_join, true);
            foreach ($wait_join as $key => $value) {
                if($value['uid'] == $param['uid']){
                    unset($wait_join[$key]);
                    $data = array_values($wait_join);
                    $redis->hset(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id'], json_encode($data));
                    break;
                }
            }
        }
        return ['error'=>0];
    }

    /**
     * * 自动出价
     * @param  int        $goods_id
     * @param  int        $timing
     * @param  Connection $redis
     * @return []
     */
    public static function automatic_offer(int $goods_id, int $timing, Connection $redis)
    {
        //获取竞拍信息a
        $competes = $redis->hget(CacheKey::COMPETE_UNDERWAY,$goods_id);
        if(empty($competes)){
            return ['error' => 1, 'message' => '无待插入用户'];
        }
        $competes = json_decode($competes, true);

        //取待插入用户
        $join_user = self::take_wait_join_user($competes, $timing, $redis);
        if(empty($join_user)){
            return ['error' => 2, 'message' => '无待插入用户'];
        }


        //用户自动出价
        if($join_user['uid'] >= 100000){
            $param = [
                'goods_id'   => $competes['goods_id'],
                'issue'      => $competes['issue'],
                'uid'        => $join_user['uid'],
                'count'      => 1,
            ];
            $serviceRs = self::join($param, $redis);
            if($serviceRs['error'] > 0){
                return ['error' => 3, 'message' => $serviceRs['message']];
            }
        }

        //机器人出价
        if($join_user['uid'] < 100000){
            $compete_data = $redis->hget(CacheKey::COMPETE_DATA, $competes['goods_id']);
            if(empty($compete_data)){
                //log 竞拍原数据不存在
                return false;
            }
            $compete_data = json_decode($compete_data, true);
            $auto_data = $redis->hvals(CacheKey::COMPETE_AUTO_JOIN.$competes['goods_id']);
            //指定获奖条件
            //
            if(empty($auto_data) && $competes['last_buy_uid'] >= 100000){
                //保底值条件
                if($compete_data['ensure']){
                    $compete_ensure = $redis->hget(CacheKey::COMPETE_ENSURE,$competes['goods_id']);
                    if($compete_ensure >= $compete_data['ensure']){
                        return false;
                    }
                }
            }

            if(count($auto_data) == 1){
                $auto_data = json_decode($auto_data[0],true);
                if($auto_data['uid'] == $competes['last_buy_uid']){
                     //保底值条件
                    if($compete_data['ensure']){
                        $compete_ensure = $redis->hget(CacheKey::COMPETE_ENSURE,$competes['goods_id']);
                        if($compete_ensure >= $compete_data['ensure']){
                            return false;
                        }
                    }
                }
            }
            //最小最大成交金额限制
            if($auto_data == null && $competes['last_buy_uid'] < 100000){
                $set_deal_value = $redis->hget(CacheKey::COMPETE_SET_DEAL_VALUE, $competes['goods_id']); //商品最小最大成交值
                if(!empty($competes['now_price'] && !empty($set_deal_value))){
                    if($competes['now_price'] > $set_deal_value){
                        $rand = mt_rand(10,30);
                        $value = sprintf("%.2f",$competes['now_price'] + $rand);
                        $redis->hset(CacheKey::COMPETE_SET_DEAL_VALUE, $competes['goods_id'], $value);
                    }elseif($competes['now_price'] == $set_deal_value){
                        // $data = [
                        //     'goods_id'       => $competes['goods_id'],
                        //     'issue'          => $competes['issue'],
                        //     'deal_value'     => $competes['now_price'],
                        //     'set_deal_value' => $set_deal_value,
                        //     'status'         => 0,
                        //     'created_at'     => time(),
                        // ];
                        // $key = CacheKey::COMPETE_DEAL_VALUE_RECORD;
                        // $redis->lpush($key, $data);

                        $redis->hdel(CacheKey::COMPETE_SET_DEAL_VALUE, $competes['goods_id']);
                        return false;
                    }
                }
            }
            //插入到商品
            $join_var = array(
                'goods_id'   => $competes['goods_id'],
                'uid'        => $join_user['uid'],
            );
            $serviceRs = RobotService::join($join_var, $redis);
            if($serviceRs['error'] > 0){
                return ['error' => 4, 'message' => $serviceRs['message']];
            }
        }

        $data = [
            'cmd'     => 'offer',
            'message' => '出价成功',
            'data'    => $serviceRs['data']
        ];
        return ['error' => 0, 'data' => $data];
    }

    /**
     * * 设置自动出价
     * @return []
     */
    private static function set_auto_join($param, $competes, Connection $redis)
    {
        //添加自动参与数据
        $data = array_merge($param, [
            'status'         => 1,
            'created_at'     => time(),
            'remain_count'   => $param['count'],
            'remain_balance' => $param['count'] * $competes['cost'],
        ]);
        $auto_data_key = CacheKey::COMPETE_AUTO_DATA.$param['goods_id'] .':' .$param['issue'];
        $auto_id = $redis->hset($auto_data_key, $param['uid'], json_encode($data));
        //添加自动参与缓存
        $cache_data = [
            'uid'        => $param['uid'],
            'issue'      => $param['issue'],
            'join_count' => $param['count'],
            'auto_id'    => $auto_id,
        ];
        $redis->hset(CacheKey::COMPETE_AUTO_JOIN.$param['goods_id'], $param['uid'], json_encode($cache_data));

        //更新待插入数据
        $wait_join = $redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
        if($wait_join){
            $wait_join_data = [
                'uid'        => $param['uid'],
                'join_count' => $param['count'],
            ];
            $wait_join = json_decode($wait_join, true);
            array_unshift($wait_join, $wait_join_data);
            $redis->hset(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id'], json_encode($wait_join));
        }
    }

    /**
     * * 更新自动出价
     * @return []
     */
    private static function update_auto_join($param, $competes, Connection $redis)
    {
        $auto_data_key = CacheKey::COMPETE_AUTO_DATA.$param['goods_id'] .':' .$param['issue'];
        $auto_data = $redis->hget($auto_data_key, $param['uid']); //需查询mysql
        if(empty($auto_data)){
            return ['error'=>1,'message'=>'自动竞拍数据不存在'];
        }
        $auto_data = json_decode($auto_data, true);
        if($auto_data['status'] != 1 && $auto_data['remain_count'] < 1){
            return ['error'=>2,'message'=>'自动竞拍状态错误'];
        }
        $auto_data['remain_balance'] = $auto_data['remain_balance'] - $competes['cost'];
        $auto_data['remain_count'] = $auto_data['remain_count'] -1;
        $auto_data['updated_at'] = time();
        $redis->hset($auto_data_key, $param['uid'], json_encode($auto_data)); //需同步mysql
        if($auto_data['remain_count'] >= 1){
            $cache_data = [
                'auto_id'    => 'ok',
                'uid'        => $param['uid'],
                'goods_id'   => $param['goods_id'],
                'issue'      => $param['issue'],
                'join_count' => $auto_data['remain_count'],
            ];
            $redis->hset(CacheKey::COMPETE_AUTO_JOIN.$param['goods_id'], $param['uid'], json_encode($cache_data));
            $auto_join = [
                'status'       => 1,
                'set_count'    => $auto_data['count'],
                'remain_count' => $auto_data['remain_count'],
            ];

        }
        if($auto_data['remain_count'] == 0){
            //更新待插入数据
            $wait_join = $redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
            if($wait_join){
                //$redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id']);
                $wait_join = json_decode($wait_join, true);
                foreach ($wait_join as $key => $value) {
                    if($value['uid'] == $param['uid']){
                        unset($wait_join[$key]);
                        $data = array_values($wait_join);
                        $redis->hset(CacheKey::ROBOT_WAIT_JOIN_LIST, $param['goods_id'], json_encode($data));
                        break;
                    }
                }
            }
            $auto_join = [];
        }
        return ['error'=>0, 'data'=>$auto_join];
    }

    /**
     * * 取待插入用户
     * @param  array      $competes
     * @param  int        $timing
     * @return []
     */
    private static function take_wait_join_user(array $competes, int $timing, Connection $redis)
    {
        $goods_id = $competes['goods_id'];
        $wait_join_list = $redis->hget(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id);
        $wait_join_list = json_decode($wait_join_list,true);
        if(empty($wait_join_list)){
            //自动出价数据
            $auto_join_data = [];
            $auto_data = $redis->hvals(CacheKey::COMPETE_AUTO_JOIN.$goods_id);
            if(!empty($auto_data)){
              foreach ($auto_data as $value) {
                $auto_join_data_tmp = json_decode($value,true);
                $auto_join_data[] = $auto_join_data_tmp;
              }
            }
            //机器人出价数据
            $robot_join_data = [];
            $robot_data = $redis->hget(CacheKey::ROBOT_WAIT_JOIN,$goods_id);
            if(!empty($robot_data)){
                $robot_join_data = json_decode($robot_data,true);
            }

            //合并数据
            $wait_join_list = array_merge($auto_join_data,$robot_join_data);
            $wait_join_list = array_values($wait_join_list);

            if(empty($wait_join_list[0])){
                return false;
            }
            $redis->hset(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id,json_encode($wait_join_list));
        }

        //取插入数据
        $i = 0;
        $join_user = $wait_join_list[$i];
        //不需重复插入
        if($join_user['uid'] == $competes['last_buy_uid'] && count($wait_join_list) > 1){
            $i = 1;
            $join_user = $wait_join_list[$i];
        }
        //更新待插入记录
        $join_user['join_count'] = $join_user['join_count'] - 1;
        //参与次数用完清除
        if($join_user['join_count'] <= 0){
            unset($wait_join_list[$i]);
        }
        //位置交换
        if(count($wait_join_list) > 1 && $join_user['join_count'] >= 1){
            //默认1-3
            $index = rand(1,4);
            //查看自动出价次数
            $auto_data = $redis->hvals(CacheKey::COMPETE_AUTO_JOIN.$goods_id);
            $auto_count = count($auto_data);
            if($auto_count > 0){
                $index = rand(1,$auto_count+2);
            }
            $wait_join_list[$i] = $wait_join_list[$index];
            $wait_join_list[$index] = $join_user;
            if(empty($wait_join_list[$i])){
                unset($wait_join_list[$i]);
            }
        }
        $wait_join_list = array_values($wait_join_list);
        $redis->hset(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id,json_encode($wait_join_list));
        if(count($wait_join_list) == 1 ){
            $redis->hdel(CacheKey::ROBOT_WAIT_JOIN_LIST,$goods_id);
        }
        //设置下次插入时间
        if(!empty($timing)){
            $robot_seting = $redis->hget(CacheKey::ROBOT_SETING,$goods_id);
            $robot_seting = json_decode($robot_seting, true);
            if($wait_join_list[0]['uid'] >= 100000){
                $join_time = $competes['down_time'] - 1;
                $tm = $timing + $join_time;
                if($tm > 59){
                    $tm = $tm - 59;
                }
                $redis->sadd(CacheKey::TIMING_JOIN.$tm,$goods_id);
            }else{
                if($robot_seting['status'] == 1){
                    $join_time = rand($robot_seting['min_join_time'],$robot_seting['max_join_time']);
                    $tm = $timing + $join_time;
                    if($tm > 59){
                        $tm = $tm - 59;
                    }
                    $redis->sadd(CacheKey::TIMING_JOIN.$tm,$goods_id);
                }
            }
        }
        return $join_user;
    }


}
