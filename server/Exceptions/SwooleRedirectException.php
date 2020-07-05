<?php

/**
 * @Author: sink
 * @Date:   2019-08-10 14:33:52
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:00:03
 */

namespace Server\Exceptions;


class SwooleRedirectException extends \Exception
{

    public function __construct($location, $code, Exception $previous = null)
    {
        parent::__construct($location, $code, $previous);
    }

}
