<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 11:35:20
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 11:26:48
 */
namespace App\Controllers\Http;
use Server\Controllers\Controller;

class Compete extends Controller
{

	/**
	 * * 订单确认
     * @RequestMapping(path="order\confirm",methods="GET")
     * @return array
	 */
	public function confirm()
	{
		$result = [];
		$this->HttpResponse->success($result);

	}

	/**
	 * * 订单支付
     * @RequestMapping(path="order\pay",methods="GET")
     * @return array
	 */
	public function pay()
	{
		$result = [];
		$this->HttpResponse->success($result);
	}

	/**
	 * * 订单详情
     * @RequestMapping(path="order\detail",methods="GET")
     * @return array
	 */
	public function detail()
	{
		$result = [];
		$this->HttpResponse->success($result);
	}

}
