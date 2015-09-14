<?php

return [

    'component' => [
        // 新增一个route组件
        'route' => [
            // 指定类名
            'class'  => 'tourze\Route\Component\Route',
            // 指定传递参数
            'params' => [
                'config' => [
                    'default' => [
                        'uri'      => '(<controller>(/<action>(/<id>)))',
                        'defaults' => [
                            'controller' => 'Site',
                            'action'     => 'index',
                        ],
                    ],
                ],
            ],
        ],
    ],

];
