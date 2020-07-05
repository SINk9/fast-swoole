<?php

/**
 * @Author: sink
 * @Date:   2019-08-12 13:36:38
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:00:08
 */


namespace Server\Events;

class Event
{

    public $type;

    public $data;

    /**
     *
     * @param string $type
     * @param * $data
     * @return $this
     */
    public function reset($type, $data = null)
    {
        $this->type = $type;
        $this->data = $data;
        return $this;
    }
}
