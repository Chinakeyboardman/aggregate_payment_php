<?php
/**
 * user:cjw
 * time:2022/5/19 11:16
 */
declare(strict_types=1);

/**
 * websocket路由
 * 小惊喜，IDE可以直接跳转到路由对应的服务和方法里头去
 */
return [
    'ws_class_arr' => [
        '/test' => [App\Core\Service\WebSocketService::class, 'test']
    ],
];