<?php

/**
 * @Author: sink
 * @Date:   2019-08-13 19:40:01
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:34:20
 */
namespace App\Controllers;
use Server\Controllers\Controller;

class Test extends Controller
{

    /**
     * @throws \App\Swoole\Exception\SwooleException
     */
    public function index()
    {
        $this->response->header("Content-Type", "text/html; charset=utf-8");
        $this->response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
    }

}
