<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 15:00:55
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 11:41:38
 */

use Server\Ports\PortManager;

return [

    'ports' => [
        [
            'socket_type'           => PortManager::SOCK_WS,
            'socket_name'           => '0.0.0.0',
            'socket_port'           => 8081,
            'route_tool'            => 'WsRoute',
            'pack_tool'             => 'NonJsonPack', //数据包格式
            'opcode'                => PortManager::WEBSOCKET_OPCODE_TEXT,
            'middlewares'           => [], //中间件
            //事件触发默认值
            'event_controller_name' => 'Ws\Connect', //服务链接事件控制器
            'method_prefix'         => null, //方法前缀
            'close_method_name'     => 'onClose', //服务关闭链接事件控制器
            'connect_method_name'   => 'onConnect', //服务打开链接事件控制器
        ],
        [
            'socket_type'           => PortManager::SOCK_HTTP,
            'socket_name'           => '0.0.0.0',
            'socket_port'           => 8082,
            'route_tool'            => 'NormalRoute', //路由
            'middlewares'           => [], //中间件
        ],
        [
            'socket_type'           => PortManager::SOCK_HTTP,
            'socket_name'           => '0.0.0.0',
            'socket_port'           => 8083,
            'route_tool'            => 'HttpRoute', //路由
            'middlewares'           => [], //中间件
        ],
    ],
];


