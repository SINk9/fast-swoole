<?php

/**
 * @Author: sink
 * @Date:   2020-07-13 09:12:20
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 15:53:51
 */


namespace App\Service\Compete;
use App\Consts\CacheKey;

class DealService
{


	/**
	 * * 成交检测
	 * @return [type]
	 */
	private static function check($param)
	{

	}


	/**
	 * * 检测保底值条件
	 * @return [type]
	 */
	private static function check_base_value()
	{
        $is_true    = 0;
        $compete_data = $redis->hget(CacheKey::COMPETE_DATA, $param['goods_id']);
        $compete_data = json_decode($compete_data, true);
        //保底值条件
        if ($compete_data['ensure']) {
            $compete_ensure = $redis->hget(CacheKey::COMPETE_ENSURE,$param['goods_id']);
            if($compete_ensure >= $compete_data['ensure']){
                $data['ensure'] = $compete_ensure;
                $is_true = 1;
            }
        }

        //条件满足清空保底值
        if ($is_true == 1) {
            $redis->hdel(CacheKey::COMPETE_ENSURE, $param['goods_id']);
            return true;
        }
        return false;
	}


	/**
	 * * 成交
	 * @return [type]
	 */
	public static function handler($param)
	{

        //USER_JOIN_RECORD
        /**
         * [$user_join_record 用户参与记录]
         * [
         *    'award', 'no_award'
         * ]
         */

        $goods_info = $redis->hget(CacheKey::GOODS_info, $param['goods_id']);
        if(empty($goods_info)){
        	return ['error'=> -1, 'message' => '商品数据不存在'];
        }
        $goods_info = json_decode($goods_info, true);
        //开始事务提交

        //获取当前竞拍信息
        $compete_underway = $redis->hget(CacheKey::COMPETE_UNDERWAY, $param['goods_id']);
        if(empty($compete_underway)){
        	return ['error'=>1, 'message'=>'竞拍信息有误'];
        }
        $compete_underway = json_decode($compete_underway, true);
        if($compete_underway['end_time'] > time()){
        	return ['error'=>2, 'message'=>'成交时间有误'];
        }
        //获取最后参与记录
        $join_record_log_key = CacheKey::COMPETE_JOIN_RECORD_LOG.$param['goods_id'].':'.$compete_underway['issue'];
        $last_join_record = $redis->lindex($join_record_log_key, 1);
        $last_join_record = json_decode($last_join_record, true);

        if ($last_join_record['uid'] != $compete_underway['last_buy_uid']
        	|| $last_join_record['offer'] != $compete_underway['now_price']){
            return ['error'=>3, 'message'=>'成交用户有误'];
        }


        //获取竞拍成功用户参与次数
        if ($last_join_record['uid'] < 100000) {
	        $key = CacheKey::ROBOT_COMPETE_JOIN_RECORD.$compete_underway['goods_id'].':'.$compete_underway['issue'];
	        $deal_join_count = $redis->hget($key, $last_join_record['uid']);
        } else {
        	$key = CacheKey::COMPETE_JOIN_RECORD.$compete_underway['goods_id'].':'.$compete_underway['issue'];
        	$tmp = $redis->hget($key, $last_join_record['uid']);
        	$tmp = json_decode($tmp, true);
            $deal_join_count = $tmp['join_count'];
        }
        //保存竞拍记录
        $data = array(
            'goods_id'        => $param['goods_id'],
            'issue'           => $compete_underway['issue'],
            'goods_price'     => $goods_info['price'],
            'deal_uid'        => $compete_underway['last_buy_uid'],
            'deal_time'       => time(),
            'deal_price'      => $compete_underway['now_price'],
            'deal_join_count' => $deal_join_count,
            'deal_type'       => $param['deal_type'], //1.指定 2.保底值 3.机器人or正常
            'create_time'     => time(),
        );
        //.....


        //更新竞拍用户竞拍记录
        $join_record_where = array(
            'goods_id' => $param['goods_id'],
            'issue'    => $compete_underway['issue'],
        );
        $join_record_data = array('compete_status' => 3);
        //.....


        //更新成交用户竞拍记录
        if($last_join_record['uid'] >= 100000){
            $join_record_where = array(
                'goods_id' => $param['goods_id'],
                'issue'    => $compete_underway['issue'],
                'uid'      => $last_join_record['uid'],
            );
            $join_record_data = array('compete_status' => 2);
            //.....
        }

        //删除设置自动竞拍数据与进行中商品
        //.....


        //结束正在进行中竞拍
        //.....

        //结束事务

        //删除设置自动竞拍缓存数据
        $key = "compete_auto_join_{$param['goods_id']}";
        $redis->del($key);
        //删除待插入机器人缓存数据
        $key = 'wait_join_list';
        $redis->hdel($key, $param['goods_id']);



        //创建新一期竞拍 交给队列执行
        Queue::add('Next',$param);


        //返回成交信息
        $result = array(
            'goods_id'      => $param['goods_id'],
            'issue'         => $compete_underway['issue'],
            'deal_uid'      => $deal_uid['uid'],
            'deal_nickname' => ,
            'goods_title'   => $goods_info['title'],
            'deal_price'    => $compete_underway['now_price'],
        );
        return ['error'=>0,'data'=>$result];

	}

	/**
	 * * 新一期创建
	 * @return [type]
	 */
	public static function newIssue()
	{

	}


}
