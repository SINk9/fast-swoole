<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:22:27
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 18:53:00
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
		foreach ($data as $key => $value) {
			$result[] = json_decode($value);
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
		$compete          = $this->redis->hget(CacheKey::COMPETE_UNDERWAY, $goods_id);
		if(!empty($compete)){
			$compete = json_decode($compete);
			$customize_status = $compete['status'];
		}else{
			$field = $goods_id . ':' .$issue;
			$index = $this->redis->hget(CacheKey::COMPETE_RECORD_INDEX, $field);
			if(!empty($index)){
				$compete = $this->redis->hget(CacheKey::COMPETE_RECORD, $index);
				if(empty($compete)){
					$this->HttpResponse->error('无记录');
				}
				$customize_status = 3;
			}
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
            $result['competes_info'] = $compete['competes_info'];
		}
		if($customize_status == 1 or $customize_status == 2){
			$result['competes_info']['end_time_second'] = $compete['end_time'] - time();
		}
		//已结束
		if($customize_status == 3){
			$result['competes_info'] = [
				'uid'      => $compete['deal_uid'],
				'nickname' => '', //redis select
				'avatar'   => '', //redis select
				'price'    => $compete['deal_price'],
			];
		}

		$result['newest_issue'] = [];//新一期
		$result['join_list'] = []; //参与记录
		$result['join_list_number'] = []; //参与人数
		$result['visit'] = []; //访问人数
		$result['goods_info'] = [];

		$result['consumed'] = [];//消耗
		$result['auto_join'] = []; //自动竞拍


		$goods = $this->redis->hget(CacheKey::GOODS_INFO,$compete['goods_id']);
		$goods = json_decode($goods);



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

