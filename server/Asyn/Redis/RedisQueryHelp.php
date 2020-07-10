<?php

/**
 * @Author: sink
 * @Date:   2020-07-10 13:33:09
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 13:37:20
 */
namespace Server\Asyn\Redis;

/**
 * @method bool select(int $db)
 */
class RedisQueryHelp
{

    public static function arguments(&$data)
    {
        $arguments = $data['arguments'];
        $dataName = strtolower($data['name']);
        //异步的时候有些命令不存在进行替换
        switch ($dataName) {
            case 'delete':
                $dataName = $data['name'] = 'del';
                break;
            case 'lsize':
                $dataName = $data['name'] = 'llen';
                break;
            case 'getmultiple':
                $dataName = $data['name'] = 'mget';
                break;
            case 'lget':
                $dataName = $data['name'] = 'lindex';
                break;
            case 'lgetrange':
                $dataName = $data['name'] = 'lrange';
                break;
            case 'lremove':
                $dataName = $data['name'] = 'lrem';
                break;
            case 'scontains':
                $dataName = $data['name'] = 'sismember';
                break;
            case 'ssize':
                $dataName = $data['name'] = 'scard';
                break;
            case 'sgetmembers':
                $dataName = $data['name'] = 'smembers';
                break;
            case 'zdelete':
                $dataName = $data['name'] = 'zrem';
                break;
            case 'zsize':
                $dataName = $data['name'] = 'zcard';
                break;
            case 'zdeleterangebyscore':
                $dataName = $data['name'] = 'zremrangebyscore';
                break;
            case 'zunion':
                $dataName = $data['name'] = 'zunionstore';
                break;
            case 'zinter':
                $dataName = $data['name'] = 'zinterstore';
                break;
        }
        //特别处理下M命令(批量)
        switch ($dataName) {
            case 'set':
                if (count($arguments) == 3) {
                    $harray = array_pop($arguments);
                    if (is_array($harray)) {
                        if (isset($harray['EX'])) {
                            $arguments[] = 'EX';
                            $arguments[] = $harray['EX'];
                        } elseif (isset($harray['PX'])) {
                            $arguments[] = 'PX';
                            $arguments[] = $harray['PX'];
                        }
                        if (in_array("NX", $harray)) {
                            $arguments[] = "NX";
                        } elseif (in_array("XX", $harray)) {
                            $arguments[] = "XX";
                        }
                    } elseif (is_numeric($harray)) {
                        $arguments[] = "EX";
                        $arguments[] = $harray;
                    }
                }
                break;
            case 'lpush':
            case 'srem':
            case 'zrem':
            case 'sadd':
                $key = $arguments[0];
                if (is_array($arguments[1])) {
                    $arguments = $arguments[1];
                    array_unshift($arguments, $key);
                }
                break;
            case 'del':
            case 'delete':
                if (is_array($arguments[0])) {
                    $arguments = $arguments[0];
                }
                break;
            case 'mset':
                $harray = $arguments[0];
                unset($arguments[0]);
                foreach ($harray as $key => $value) {
                    $arguments[] = $key;
                    $arguments[] = $value;
                }
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'hmset':
                $harray = $arguments[1];
                unset($arguments[1]);
                foreach ($harray as $key => $value) {
                    $arguments[] = $key;
                    $arguments[] = $value;
                }
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'mget':
                $harray = $arguments[0];
                unset($arguments[0]);
                $arguments = array_merge($arguments, $harray);
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'hmget':
                $harray = $arguments[1];
                unset($arguments[1]);
                $arguments = array_merge($arguments, $harray);
                $data['arguments'] = $arguments;
                $data['M'] = $harray;
                break;
            case 'lrem'://这里和redis扩展的参数位置有区别
                $value = $arguments[1];
                $arguments[1] = $arguments[2];
                $arguments[2] = $value;
                break;
            case 'zrevrange':
            case 'zrange':
                if (count($arguments) == 4) {//存在withscores
                    if ($arguments[3]) {
                        $arguments[3] = 'withscores';
                        $data['withscores'] = true;
                    } else {
                        unset($arguments[3]);
                    }
                }
                break;
            case 'zrevrangebyscore'://需要解析参数
            case 'zrangebyscore'://需要解析参数
                if (count($arguments) == 4) {//存在额外参数
                    $arg = $arguments[3];
                    unset($arguments[3]);
                    $data['withscores'] = $arg['withscores'] ?? false;
                    if ($data['withscores']) {
                        $arguments[] = 'withscores';
                    }
                    if (array_key_exists('limit', $arg)) {//存在limit
                        $arguments[] = 'limit';
                        $arguments[] = $arg['limit'][0];
                        $arguments[] = $arg['limit'][1];
                    }
                }
                break;
            case 'zinterstore':
            case 'zunionstore':
                $arg = $arguments;
                $argCount = count($arg);
                unset($arguments);
                $arguments[] = $arg[0];
                $arguments[] = count($arg[1]);
                foreach ($arg[1] as $value) {
                    $arguments[] = $value;
                }
                if ($argCount >= 3) {//有WEIGHT
                    $arguments[] = 'WEIGHTS';
                    foreach ($arg[2] as $value) {
                        $arguments[] = $value;
                    }
                }
                if ($argCount == 4) {//有AGGREGATE
                    $arguments[] = 'AGGREGATE';
                    $arguments[] = $arg[3];
                }
                break;
            case 'sort':
                $arg = $arguments;
                $argCount = count($arg);
                unset($arguments);
                $arguments[] = $arg[0];
                if ($argCount == 2) {
                    if (array_key_exists('by', $arg[1])) {
                        $arguments[] = 'by';
                        $arguments[] = $arg[1]['by'];
                    }
                    if (array_key_exists('limit', $arg[1])) {
                        $arguments[] = 'limit';
                        $arguments[] = $arg[1]['limit'][0];
                        $arguments[] = $arg[1]['limit'][1];
                    }
                    if (array_key_exists('get', $arg[1])) {
                        if (is_array($arg[1]['get'])) {
                            foreach ($arg[1]['get'] as $value) {
                                $arguments[] = 'get';
                                $arguments[] = $value;
                            }
                        } else {
                            $arguments[] = 'get';
                            $arguments[] = $arg[1];
                        }
                    }
                    if (array_key_exists('sort', $arg[1])) {
                        $arguments[] = $arg[1]['sort'];
                    }
                    if (array_key_exists('alpha', $arg[1])) {
                        $arguments[] = $arg[1]['alpha'];
                    }
                    if (array_key_exists('store', $arg[1])) {
                        $arguments[] = 'store';
                        $arguments[] = $arg[1]['store'];
                    }
                }
                break;
            case 'eval':
            case 'evalsha':
                $sha1 = $arguments[0];
                $keys = $arguments[1];
                $keynum = $arguments[2] ?? 0;
                $args = $arguments[3] ?? [];
                $arguments = $keys;
                array_unshift($arguments, $keynum);
                array_unshift($arguments, $sha1);
                foreach ($args as $value) {
                    $arguments[] = $value;
                }
                break;
        }
        return array_values($arguments);
    }


}
