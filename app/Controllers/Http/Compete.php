<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:22:27
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 15:12:59
 */
namespace App\Controllers\Http;
use Server\Controllers\Controller;
use App\Consts\CacheKey;

class Compete extends Controller
{

    /**
     * * 竞拍列表
     * @RequestMapping(path="compete\underway_list",methods="GET")
     * @return array
     */
    public function underway_list()
    {

        $page = 1;
        $limit = 10;
        if(!empty($this->request->post['page']) && $this->request->post['page'] > 0 ){
            $page = $this->request->post['page'];
        }
        if(!empty($this->request->post['limit']) && $this->request->post['limit'] > 0){
            $limit = $this->request->post['limit'];
        }
        $fields = $this->redisHashPaginateHelp($page, $limit, CacheKey::COMPETE_UNDERWAY);
        $data = $this->redis->hmget(CacheKey::COMPETE_UNDERWAY,$fields);
        if(empty($data)){
            $this->HttpResponse->error('无记录');
        }
        $result = [];
        foreach ($data as $key => $value) {
            $tmp = json_decode($value, true);
            $goods_info = $this->redis->hget(CacheKey::GOODS_INFO, $tmp['goods_id']);
            $goods_info = json_decode($goods_info, true);
            $result[$key] = array_merge($tmp,[
                'title'           => $goods_info['title'],
                'thumb'           => $goods_info['thumb'],
                'show_price'      => $goods_info['price'],
            ]);
            if(!empty($tmp['end_time'])){
                $result[$key]['end_time_second'] = $tmp['end_time'] - time();
            }
        }

        $this->HttpResponse->success($result);
    }

    /**
     * * 竞拍详情
     * @RequestMapping(path="compete\details",methods="GET")
     * @return array
     */
    public function details()
    {
        if(empty(intval($this->request->post['goods_id'])) || empty(intval($this->request->post['issue']))){
            $this->HttpResponse->error('商品标识或期数错误.');
        }
        $goods_id         = $this->request->post['goods_id'];
        $issue            = $this->request->post['issue'];
        $customize_status = 0;
        $compete          = [];
        $compete_underway = $this->redis->hget(CacheKey::COMPETE_UNDERWAY, $goods_id);
        if(!empty($compete_underway)){
            $compete_underway = json_decode($compete_underway, true);
            $customize_status = $compete_underway['status'];
            $compete          = $compete_underway;
        }
        $compete_record = $this->redis->hget(CacheKey::COMPETE_RECORD.$goods_id, $issue);
        if(!empty($compete_record)){
            $compete_record = json_decode($compete_record, true);
            $customize_status = 3;
            $compete = $compete_record;
        }

        if(empty($compete)){
            $this->HttpResponse->error('无记录');
        }

        $result = [];
        //初始状态
        if($customize_status == 1){
            if ($compete['end_time'] != 0) {
                $compete['end_time'] = $compete['initial_down_time'] + $compete['start_time'];
                //更新倒计时时间
                $this->redis->hset(CacheKey::COMPETE_UNDERWAY, $goods_id, json_encode($compete));
            }
        }
        //进行中
        if($customize_status == 2){
            //返回竞拍信息参数
            $result['competes_info'] = $compete['last_join'];
            if($this->request->post['uid']){
                //消耗
                $result['consumed'] = ['count' => 0, 'use_balance' => 0];
                $join_record_key = CacheKey::COMPETE_JOIN_RECORD.$goods_id.':'.$issue;
                $join_record = $this->redis->hget($join_record_key, $this->request->post['uid']);
                if(!empty($join_record)){
                    $join_record = json_decode($join_record, true);
                    $result['consumed'] = [
                        'count'     => $join_record['join_count'],
                        'use_money' => $join_record['use_money']
                    ];
                }

                //自动竞拍
                $result['auto_join'] = ['status' => 0, 'set_count' => 0, 'remain_count' => 0];
                $auto_data_key = CacheKey::COMPETE_AUTO_DATA.$goods_id .':'.$issue;
                $auto_join = $this->redis->hget($auto_data_key, $this->request->post['uid']);
                if(!empty($auto_join)){
                    $auto_join = json_decode($auto_join, true);
                    $result['auto_join'] = [
                        'status'       => 1,
                        'set_count'    => $auto_join['count'],
                        'remain_count' => $auto_join['remain_count']
                    ];
                }
            }

        }
        if($customize_status == 1 or $customize_status == 2){
            $result['competes_info']['end_time_second'] = $compete['end_time'] - time();
        }
        //已结束
        if($customize_status == 3){
            $user_info = $this->redis->hget(CacheKey::USER_INFO, $$compete['deal_uid']);
            $user_info = json_decode($user_info, true);
            $result['competes_info'] = [
                'uid'      => $compete['deal_uid'],
                'nickname' => $user_info['nickname'], //redis select
                'avatar'   => $user_info['avatar'], //redis select
                'price'    => $compete['deal_price'],
            ];

            $result['newest_issue'] = $compete_underway['issue'];//最新一期期号
        }

        //参与记录
        $join_record_log_key = CacheKey::COMPETE_JOIN_RECORD_LOG.$goods_id.':'.$issue;
        $result['join_list'] = [];
        $join_list = $this->redis->lrange($join_record_log_key,1,3);
        if($join_list){
            foreach ($join_list as  $value) {
                $result['join_list'][] = json_decode($value, true);
            }
        }
        //参与人数
        $user_join_record_key = CacheKey::COMPETE_JOIN_RECORD.$goods_id.':'.$issue;

        $robot_join_record_key = CacheKey::ROBOT_COMPETE_JOIN_RECORD.$goods_id.':'.$issue;
        $user_join_count = $this->redis->hlen($user_join_record_key);
        $robot_join_count = $this->redis->hlen($robot_join_record_key);
        $result['join_list_number'] = intval($user_join_count + $robot_join_count);
        //页面访问人数
        $this->redis->hincrby(CacheKey::COMPETE_DETAIL_VISIT, $goods_id, 1);
        $result['visit'] = $this->redis->hget(CacheKey::COMPETE_DETAIL_VISIT, $goods_id);




        $goods = $this->redis->hget(CacheKey::GOODS_INFO,$goods_id);
        $compete_data = $this->redis->hget(CacheKey::COMPETE_DATA, $goods_id);
        $compete_data = json_decode($compete_data, true);
        $goods = json_decode($goods, true);
        $result['goods_info'] = [
            'goods_id'          => $goods_id,
            'show_price'        => $goods['price'],
            'thumb'             => $goods['thumb'],
            'initial_price'     => $compete_data['initial_price'],
            'range'             => $compete_data['range'],
            'cost'              => $compete_data['cost'],
            'default_count'     => $compete_data['default_count'],
            'initial_down_time' => $compete_data['initial_down_time'],
            'photo_list'        => explode(',', $goods['pics']),
        ];

        $this->HttpResponse->success($result);
    }

    /**
     * * 最新一期进行中活动
     * @RequestMapping(path="compete\newest_issue",methods="GET")
     * @return array
     */
    public function newest_issue()
    {
        if(empty($this->request->post['goods_id'])){
            $this->HttpResponse->error('商品标识不能为空.');
        }
        $result = [];
        $this->HttpResponse->success($result);
    }


    /**
     * * 竞拍规则
     * @RequestMapping(path="compete\rules",methods="GET")
     * @return array
     */
    public function rules()
    {
        $result = [];
        $this->HttpResponse->success($result);
    }

    /**
     * * 成交列表
     * @RequestMapping(path="compete\deal_list",methods="GET")
     * @return array
     */
    public function deal_list()
    {
        $result = [];
        $this->HttpResponse->success($result);
    }

    /**
     * * 往期成交列表
     * @RequestMapping(path="compete\past_deal_list",methods="GET")
     * @return array
     */
    public function past_deal_list()
    {
        $result = [];
        $this->HttpResponse->success($result);
    }

    /**
     * * 往期竞拍列表
     * @RequestMapping(path="compete\historical_trend",methods="GET")
     * @return array
     */
    public function historical_trend()
    {
        $result = [];
        $this->HttpResponse->success($result);
    }


    /**
     * * 我的竞拍记录
     * @RequestMapping(path="compete\my_join_record",methods="POST")
     * @return array
     */
    public function my_join_record()
    {
        $result = [];
        $this->HttpResponse->success($result);
    }

    /**
     * * redis 列表数据分页
     * * 默认倒序
     * @return [array]
     */
    private function redisHashPaginateHelp($page, $limit, $key)
    {
        $count = $this->redis->hlen($key);
        $fields = [];
        if($count <= $limit){
            $end = $count;
            $start = 1;
        }else{
            $start = $count - $limit;
            $end = $count;
        }
        if($page >= 2){
            $start = $count - ($limit * $page) - $limit;
            $end = $count - ($limit * $page);
        }
        for ($i=$start; $i <= $end; $i++) {
            $fields[] = $i;
        }
        return $fields;
    }

}

