<?php

/**
 * json
 * @Author: sink
 * @Date:   2019-08-09 11:15:36
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:31
 */
namespace Server\Packs;

use Server\ProxyServer;
use Server\Exceptions\SwooleException;

class NonJsonPack implements IPack
{
    protected $last_data;
    protected $last_data_result;


    public function pack($data, $topic = null)
    {
        if ($this->last_data != null && $this->last_data == $data) {
            return $this->last_data_result;
        }
        $this->last_data = $data;
        $this->last_data_result = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this->last_data_result;
    }

    public function unPack($data)
    {
        $value = json_decode($data);
        if (empty($value)) {
            throw new SwooleException('json unPack 失败');
        }
        return $value;
    }

    function encode($buffer)
    {

    }

    function decode($buffer)
    {

    }

    public function getProbufSet()
    {
        return null;
    }

    public function errorHandle(\Throwable $e, $fd)
    {
        ProxyServer::getInstance()->close($fd);
    }
}
