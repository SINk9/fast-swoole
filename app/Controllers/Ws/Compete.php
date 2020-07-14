<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:16:34
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 14:18:49
 */

namespace App\Controllers\Ws;
use Server\Controllers\Controller;
use App\Consts\CacheKey;
use App\Service\Compete\JoinService;

class Compete extends Controller
{


    /**
     * 出价
     * @return [type]
     */
    public function offer()
    {
        $param = [
            'uid'      => $this->client_data->params['uid'],
            'goods_id' => $this->client_data->params['goods_id'],
            'issue'    => $this->client_data->params['issue'],
            'count'    => intval($this->client_data->params['count']),
        ];
        //获取活动信息
        $underway_compete = $this->redis->hget(CacheKey::COMPETE_UNDERWAY, $param['goods_id']);
        if(empty($underway_compete)){
            $this->send(['cmd'=>'error','message'=>'无记录']);
            return;
        }
        $underway_compete = json_decode($underway_compete,true);
        if($param['uid'] == $underway_compete['last_buy_uid']){
            $this->send(['cmd'=>'error','message'=>'您当前已是领先状态']);
            return;
        }

        if($underway_compete['status'] == 0){
            $this->send(['cmd'=>'error','message'=>'暂未开始竞拍']);
            return;
        }
        //检测是否设置自动竞拍
        $auto_join = $this->redis->hget(CacheKey::COMPETE_AUTO_JOIN.$param['goods_id'], $param['uid']);
        if(!empty($auto_join)){
            $this->send(['cmd'=>'error','message'=>'您已设置自动竞拍']);
            return;
        }
        $balance = $this->redis->hget(CacheKey::USER_BALANCE, $param['uid']); //获取用户资金信息
        $use_balance = $this->redis->hget(CacheKey::USER_USE_BALANCE, $param['uid']);//获取用户使用中资金
        $can_balance = $balance - $use_balance; //可使用资金
        if($can_balance < ($param['count'] * $underway_compete['cost'])){
            $this->send(['cmd'=>'error','message'=>'您的资金不足']);
            return;
        }
        $result = JoinService::join($param, $this->redis);
        if($result['error'] > 0){
            $this->send(['cmd'=>'error','message'=>$result['message']]);
            return;
        }
        //$this->send('cmd' => 'offer', 'message' => '参与成功');
        $this->sendToAllFd(['cmd'=>'offer','message'=>'竞拍参与','data'=>$result['data']]);
    }

    /**
     * 取消出价
     * @return [type]
     */
    public function cancel_offer()
    {
        $param = [
            'uid'      => $this->client_data['uid'],
            'goods_id' => $this->client_data['goods_id'],
            'issue'    => $this->client_data['issue'],
        ];
        $result = JoinService::cancel_auto_join($param, $this->redis);
        if($result['error'] > 0){
            $this->send(['cmd'=>'error','message'=>$result['message']]);
        }
        $this->send(['cmd'=>'success','message'=>'取消成功']);
    }

}
