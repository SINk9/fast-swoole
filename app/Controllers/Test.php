<?php

/**
 * @Author: sink
 * @Date:   2019-08-13 19:40:01
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 18:25:49
 */
namespace App\Controllers;
use Server\Controllers\Controller;
use App\Consts\CacheKey;

class Test extends Controller
{

    /**
     * @throws \App\Swoole\Exception\SwooleException
     */
    public function index()
    {
        //竞拍数据
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
        //     'user_one_ensure'   => 80,
        //     'user_many_ensure'  => 90,
        //     'is_next'           => 1,
        //     'start_time'        => 0,
        //     'end_time'          => 0,
        //     'issue_end'         => 0,
        //     'min_deal'          => 100,
        //     'max_deal'          => 1000,
        //     'content'           => '',
        //     'stock'             => 100,
        //     'created_at'        => '1594344418'
        // ];
        // $$count = $this->redis->hlen(CacheKey::COMPETE_DATA);
        // $$count = $$count + 1;
        // $redis = $this->redis->hset(CacheKey::COMPETE_DATA,$$count,json_encode($data));
        // //$rs = $this->redis->hget(CacheKey::COMPETE_DATA,$$count);

        //进行中竞拍数据
        // $data = [
        //     'goods_id'          => 1,
        //     'issue'             => 1,
        //     'now_price'         => 1.12,
        //     'end_time'          => '1594352644',
        //     'last_buy_uid'      => '1001',
        //     'down_time'         => '10',
        //     'initial_down_time' => '120',
        //     'initial_price'     => '0.00',
        //     'range'             => 0.1,
        //     'cost'              => 1,
        //     'start_time'        => '1594352752',
        //     'status'            => '1',
        //     'sort'              => '100',
        //     'nickname'          => '摆渡人',
        //     'title'             => '啦啦啦啦德玛西亚',
        //     'thumb'             => '/goods/2222.png',
        // ];
        // $count = $this->redis->hlen(CacheKey::COMPETE_UNDERWAY);
        // $count = $count + 1;
        // $data['goods_id'] = $count;
        // $redis = $this->redis->hset(CacheKey::COMPETE_UNDERWAY,$count,json_encode($data));
        // $rs = $this->redis->hget(CacheKey::COMPETE_UNDERWAY,$count);
        // $this->HttpResponse->success(json_decode($rs));


        // //商品数据
        // $data = [
        //     'goods_id' => 1,
        //     'title'    => '阿拉啦啦啦德玛西亚~',
        //     'desc'     => '商品描述。。。。。。。。。。',
        //     'cover'    => '/goods/1.png',
        //     'pics'     => '/goods/2.png,/goods/3.png,/goods/4.png',
        //     'attr'     => '粉色 128G',
        //     'price'    => '188.00',
        // ];
        // $count = $this->redis->hlen(CacheKey::GOODS_INFO);
        // $count = $count + 1;
        // $data['goods_id'] = $count;
        // $redis = $this->redis->hset(CacheKey::GOODS_INFO,$count,json_encode($data));
        // $rs = $this->redis->hget(CacheKey::GOODS_INFO,$count);


        //用户数据
        $data = [1,2,3,4,5,6,7,8,9,10];
        $rs = $this->redis->lpush(CacheKey::COMPETE_JOIN_RECODE_LOG,json_encode($data));

        $this->HttpResponse->success($rs);



        //$data = $this->db->table('user_idcard')->get();
        // $data = $this->db->select('select * from init_user_idcard;');
        // $this->HttpResponse->success($data);
        //$data = [1,2,2];
        //$this->response->header("Content-Type", "text/html; charset=utf-8");
        //$this->response->header("Content-Type", "application/json");
        // $this->response->header("Content-Type", "application/x-www-form-urlencoded;charset=utf-8");
        //$this->response->end("<h1>Hello Swoole. #". rand(1,1000) ."</h1>");
        //$this->response->end($res);
    }

}
