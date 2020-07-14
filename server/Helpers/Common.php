<?php

/**
 * @Author: sink
 * @Date:   2020-07-05 17:41:09
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-11 12:36:01
 */
    /**
     * 获取毫秒时间戳
     * @return string
     */
    function getMicrotime()
    {
        $time = microtime(true);
        return sprintf('%.3f', $time);
    }


    /**
     * 实例化服务层
     */
    function service($name = '')
    {
        static $_model = array();
        $class         = "App\Service\\{$name}Service";
        if (isset($_model[$class]) == false) {
            $_model[$class] = new $class();
        }
        return $_model[$class];
    }


   /**
     * 用户编号
     * @param  integer $uid          //原数
     * @param  integer $upper_count  //上层增加数
     * @return integer               //返回总增加数
     */
    function buildNumber($uid)
    {
        do {
            $upper_count = empty($count) ? 0 : $count;
            $num_digit   = strlen($uid);
            $num         = [];
            $repeat      = [];

            for ($i = $num_digit; $i > 0; $i--) {
                $num[$i]   = floor($uid / pow(10, $i));
                $remainder = $uid % pow(10, $i);
                if ($remainder >= (4 * pow(10, $i - 1))) {
                    $num[$i]++;
                }
                $repeat[$i] = 0;
                for ($j = $num_digit; $j > $i; $j--) {
                    $repeat[$i] += ($num[$j] - $repeat[$j]) * pow(10, $j - $i - 1);
                }
            }

            $count = 0;
            foreach ($num as $key => $value) {
                $count += ($value - $repeat[$key]) * pow(10, $key - 1);
            }

            $uid += $count - $upper_count;
        } while ($count - $upper_count);

        if (floor($uid / 100000) % 10 == 3) {
            $uid += 200000;
        } else {
            $uid += 100000;
        }

        return $uid;
    }


    /**
     * 获取字符串转小写后crc32值
     * @return int
     */
    function str_crc32($str)
    {
        $str = strtolower($str);
        return sprintf('%u', crc32($str));
    }


    /**
     * ** 商户订单号
     * @return [type] [description]
     */
    function trade_no()
    {
        //生成商户单号
        return md5(uniqid() . rand(100000000, 999999999));
    }

    /**
     *  作用：格式化参数，签名过程需要使用
     */
    function format_array_string($array, $urlencode)
    {
        $string = "";
        ksort($array);
        foreach ($array as $k => $v)
        {
            if(is_array($v)){
                $v = json_encode($v);
            }
            if($urlencode)
            {
               $v = urlencode($v);
            }
            //$string .= strtolower($k) . "=" . $v . "&";
            $string .= $k . "=" . $v . "&";
        }
        $result;
        if (strlen($string) > 0)
        {
            $result = substr($string, 0, strlen($string)-1);
        }
        return $result;
    }

    /**
     *  作用：递归格式化参数，签名过程需要使用
     */
    function format_arr_str($array, $urlencode)
    {
        $string = "";
        ksort($array);
        foreach ($array as $k => $v)
        {
            if (is_array($v)) {
                $string .= format_arr_str($v, $urlencode);
            } else {
                if($urlencode)
                {
                   $v = urlencode($v);
                }
                //$string .= strtolower($k) . "=" . $v . "&";
                $string .= $k . "=" . $v . "&";
            }

        }
        /*$result;
        if (strlen($string) > 0)
        {
            $result = substr($string, 0, strlen($string)-1);
        }*/
        return $string;
    }

    /**
     * 检查扩展
     * @return bool
     */
    function checkSwooleExtension()
    {
        $check = true;
        if (!extension_loaded('swoole')) {
            LogEcho("STA", "[扩展依赖]缺少swoole扩展");
            $check = false;
        }
        if (extension_loaded('xhprof')) {
            LogEcho("STA", "[扩展错误]不允许加载xhprof扩展，请去除");
            $check = false;
        }
        if (extension_loaded('xdebug')) {
            LogEcho("STA", "[扩展错误]不允许加载xdebug扩展，请去除");
            $check = false;
        }
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            LogEcho("STA", "[版本错误]PHP版本必须大于7.0.0\n");
            $check = false;
        }
        if (version_compare(SWOOLE_VERSION, '4.0.3', '<')) {
            LogEcho("STA", "[版本错误]Swoole版本必须大于4.0.3\n");
            $check = false;
        }

        // if (!class_exists('swoole_redis')) {
        //     LogEcho("STA", "[编译错误]swoole编译缺少--enable-async-redis,具体参见文档http://docs.sder.xin/%E7%8E%AF%E5%A2%83%E8%A6%81%E6%B1%82.html");
        //     $check = false;
        // }

        if (!extension_loaded('redis')) {
            LogEcho("STA", "[扩展依赖]缺少redis扩展");
            $check = false;
        }
        if (!extension_loaded('pdo')) {
            LogEcho("STA", "[扩展依赖]缺少pdo扩展");
            $check = false;
        }
        return $check;
    }


    /**
     * * cliEcho
     */
    function LogEcho($tile, $message)
    {
        ob_start();
        if (is_string($message)) {
            $message = ltrim($message);
            $message = str_replace(PHP_EOL, '', $message);
        }
        print_r($message);
        $content = ob_get_contents();
        ob_end_clean();

        $could = true;
        $content = explode("\n", $content);
        $send = "";
        foreach ($content as $value) {
            if (!empty($value)) {
                $echo = "[$tile] $value";
                $send = $send . $echo . "\n";
                if ($could) {
                    echo " > $echo\n";
                }
            }
        }
    }




    /**
     * 获取当前的时间(毫秒)
     * @return float
     */
    function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }


    /**
     * * 格式化时间
     * @param  [type] $time [description]
     * @return [type]       [description]
     */
    function format_date($time)
    {
        $day = (int)($time / 60 / 60 / 24);
        $hour = (int)($time / 60 / 60) - 24 * $day;
        $mi = (int)($time / 60) - 60 * $hour - 60 * 24 * $day;
        $se = $time - 60 * $mi - 60 * 60 * $hour - 60 * 60 * 24 * $day;
        return "$day 天 $hour 小时 $mi 分 $se 秒";
    }



    /**
     * 是否是mac系统
     * @return bool
     */
    function isDarwin()
    {
        if (PHP_OS == "Darwin") {
            return true;
        } else {
            return false;
        }
    }

?>
