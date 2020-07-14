<?php

/**
 * @Author: sink
 * @Date:   2019-08-13 19:40:01
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-13 17:32:30
 */
namespace App\Controllers;
use Server\Controllers\Controller;
use App\Consts\CacheKey;
use App\Service\Compete\RobotService;

class Test extends Controller
{

    /**
     * @throws \App\Swoole\Exception\SwooleException
     */
    public function index()
    {
        //机器人用户
        // $robot_user = $this->db->select('select * from xq_compete_robot;');
        // $robot_data = [];
        // foreach ($robot_user as $key => $value) {
        //     $robot_data[$value->id] = json_encode([
        //         'nickname' => $value->nickname,
        //         'avatar'   => $value->avatar,
        //         'place'    => $value->place,
        //         'uid'      => $value->id,
        //     ]);
        // }
        // $result = $this->redis->hmset(CacheKey::ROBOT_USER,$robot_data);


        // //竞拍数据
        // $data = [
        //     'goods_id'          => 1,
        //     'down_time'         => 10,
        //     'initial_down_time' => 120,
        //     'initial_price'     => 0.00,
        //     'range'             => 0.1,
        //     'cost'              => 100,
        //     'visit'             => 0,
        //     'default_count'     => 1,
        //     'ensure'            => 100,
        //     'is_next'           => 1,
        //     'start_time'        => 0,
        //     'end_time'          => 0,
        //     'issue_end'         => 0,
        //     'min_deal'          => 1000,
        //     'max_deal'          => 8000,
        //     'stock'             => 100,
        //     'created_at'        => '1594344418'
        // ];
        // $count           = $this->redis->hlen(CacheKey::COMPETE_DATA);
        // $data['goods_id'] = $data['goods_id'] + $count;
        // $redis            = $this->redis->hset(CacheKey::COMPETE_DATA, $data['goods_id'], json_encode($data));

        // //进行中竞拍数据
        // $data = [
        //     'goods_id'          => 1,
        //     'issue'             => 1,
        //     'now_price'         => 0.00,
        //     'end_time'          => 0,
        //     'down_time'         => '10',
        //     'initial_down_time' => '120',
        //     'initial_price'     => '0.00',
        //     'range'             => 0.1,
        //     'cost'              => 100,
        //     'start_time'        => '1594352752',
        //     'status'            => 1,
        //     'sort'              => '100',
        //     'last_buy_uid'      => 0,
        // ];
        // $count            = $this->redis->hlen(CacheKey::COMPETE_UNDERWAY);
        // $count            = $count + 1;
        // $data['goods_id'] = $count;
        // $redis            = $this->redis->hset(CacheKey::COMPETE_UNDERWAY,$count,json_encode($data));

        // //商品数据
        // $title = '商品名称:';
        // $data = [
        //     'goods_id' => 1,
        //     'title'    => $title,
        //     'desc'     => '商品描述。。。。。。。。。。',
        //     'thumb'    => '/goods/1.png',
        //     'pics'     => '/goods/2.png,/goods/3.png,/goods/4.png',
        //     'attr'     => '粉色 128G',
        //     'price'    => '1889.00',
        // ];
        // $count            = $this->redis->hlen(CacheKey::GOODS_INFO);
        // $count            = $count + 1;
        // $data['goods_id'] = $count;
        // $data['title']    = $title . $count;
        // $redis            = $this->redis->hset(CacheKey::GOODS_INFO,$count,json_encode($data));
        // //$rs             = $this->redis->hget(CacheKey::GOODS_INFO,$count);


        // //用户数据
        // $nickname = '用户:';
        // $data = [
        //     'uid'      => 100000,
        //     'nickname' => $nickname,
        //     'avatar'   => '/avatar/1.png',
        //     'place'    => '湖北武汉',
        // ];
        // $count            = $this->redis->hlen(CacheKey::USER_INFO);
        // $data['uid']      = $data['uid'] + $count;
        // $data['nickname'] = $nickname . $count;
        // $redis            = $this->redis->hset(CacheKey::USER_INFO,$data['uid'],json_encode($data));


        // //竞拍机器人设置信息
        // $data = [
        //     'goods_id'         => 1,
        //     'is_reward'        => 1,
        //     'min_join_time'    => 7,
        //     'max_join_time'    => 9,
        //     'a_min_join_robot' => 10,
        //     'a_max_join_robot' => 30,
        //     'a_min_join_count' => 10,
        //     'a_max_join_count' => 30,
        //     'b_min_join_robot' => 20,
        //     'b_max_join_robot' => 50,
        //     'b_min_join_count' => 20,
        //     'b_max_join_count' => 50,
        //     'c_min_join_robot' => 10,
        //     'c_max_join_robot' => 30,
        //     'c_join_count'     => '100,200,300,400,500,150,250,350,450,550',
        //     'status'           => 1
        // ];
        // $count = $this->redis->hlen(CacheKey::ROBOT_SETING);
        // $data['goods_id'] = $data['goods_id'] + $count;
        // $redis = $this->redis->hset(CacheKey::ROBOT_SETING, $data['goods_id'], json_encode($data));

        //$reslut = RobotService::generate_join_list(1, $this->redis);
        //$reslut = ['message' => 'hello world'];
        //$this->HttpResponse->success($reslut);

        // $data = $this->db->select('select * from init_user_idcard;');
        //$this->response->header("Content-Type", "text/html; charset=utf-8");
        //$this->response->header("Content-Type", "application/json");
        // $this->response->header("Content-Type", "application/x-www-form-urlencoded;charset=utf-8");
        //$this->response->end("<h1>Hello Swoole. #". rand(1,1000) ."</h1>");
        //$this->response->end($res);
    }

}
