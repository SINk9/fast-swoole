<?php

/**
 * @Author: sink
 * @Date:   2019-08-07 14:20:43
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:27
 */

namespace Server\Packs;

interface IPack
{
    function encode($buffer);

    function decode($buffer);

    function pack($data, $topic = null);

    function unPack($data);

    function getProbufSet();

    function errorHandle(\Throwable $e, $fd);
}
