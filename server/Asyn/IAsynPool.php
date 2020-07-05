<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:47:20
 */

namespace Server\Asyn;


interface IAsynPool
{
    function getAsynName();

    function pushToPool($client);

    function getSync();

    function setName($name);
}
