<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

/** 多数据库配置 */
$databasesConfig = [];
/** 默认模板 */
$databasesConfig['default'] = [
    'driver'    => env('DB_DRIVER', 'mysql'),
//    'host'      => env('DB_HOST', 'localhost'),//读写不分离
    // read+write+sticky读写分离配置
    'read' => [
        'host' => [env('DB_HOST', 'localhost')],
    ],
    'write' => [
        'host' => [env('DB_HOST', 'localhost')],
    ],
    'sticky'    => true,
    'port'      => env('DB_PORT', 3306),
    'database'  => env('DB_DATABASE', 'hyperf'),
    'username'  => env('DB_USERNAME', 'root'),
    'password'  => env('DB_PASSWORD', ''),
    'charset'   => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix'    => env('DB_PREFIX', ''),
    'pool'      => [
        'min_connections' => 1,
        'max_connections' => 10,
        'connect_timeout' => 10.0,
        'wait_timeout'    => 3.0,
        'heartbeat'       => -1,
        'max_idle_time'   => (float)env('DB_MAX_IDLE_TIME', 60),
    ],
    'cache'     => [
        'handler'         => Hyperf\ModelCache\Handler\RedisHandler::class,
        'cache_key'       => '{mc:%s:m:%s}:%s:%s',
        'prefix'          => 'default',
        'ttl'             => 3600 * 24,
        'empty_model_ttl' => 600,
        'load_script'     => true,
    ],
    'commands'  => [
        'gen:model' => [
            'path'          => 'app/Model',
            'force_casts'   => true,
            'inheritance'   => 'Model',
            'uses'          => '',
            'table_mapping' => [],
        ],
    ],
];
/** 配置逻辑处理 */
foreach ($databasesConfig as $key => &$value) {
    //普通默认配置
    if (!isset($value['driver'])) $value['driver'] = env('DB_DRIVER', 'mysql');
    if (!isset($value['host'])) $value['host'] = env('DB_HOST', 'localhost');
    if (!isset($value['port'])) $value['port'] = env('DB_PORT', '3306');
    if (!isset($value['database'])) $value['database'] = env('DB_DATABASE', 'hyperf');
    if (!isset($value['username'])) $value['username'] = env('DB_USERNAME', 'root');
    if (!isset($value['password'])) $value['password'] = env('DB_PASSWORD', '');
    if (!isset($value['charset'])) $value['charset'] = env('DB_CHARSET', 'utf8mb4');
    if (!isset($value['collation'])) $value['collation'] = env('DB_COLLATION', 'utf8mb4_unicode_ci');
    if (!isset($value['prefix'])) $value['prefix'] = env('DB_PREFIX', '');
    // 连接池默认配置
    if (!isset($value['pool'])) {
        $value['pool'] = [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout'    => 3.0,
            'heartbeat'       => -1,
            'max_idle_time'   => (float)env('DB_MAX_IDLE_TIME', 60),
        ];
    }
    // 缓存默认配置
    if (!isset($value['cache'])) {
        $value['cache'] = [
            'handler'         => Hyperf\ModelCache\Handler\RedisHandler::class,
            'cache_key'       => '{mc:%s:m:%s}:%s:%s',
            'prefix'          => 'default',
            'ttl'             => 3600 * 24,
            'empty_model_ttl' => 600,
            'load_script'     => true,
        ];
    }
    // 模型基本配置
    if (!isset($value['commands'])) {
        $value['commands'] = [
            'gen:model' => [
                'path'          => 'app/Model',
                'force_casts'   => true,
                'inheritance'   => 'Model',
                'uses'          => '',
                'table_mapping' => [],
            ],
        ];
    }
}
/** 配置生效 */
return $databasesConfig;
