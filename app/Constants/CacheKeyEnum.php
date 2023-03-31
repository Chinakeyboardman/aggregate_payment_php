<?php
/**
 * user:cjw
 * time:2022/5/14 15:11
 */

namespace App\Constants;

class CacheKeyEnum
{
    // ws服务，userId=>fd
    public const WS_USER_FD_MAPPER = 'ws_user_fd_mapper';
    // ws服务，fd=>userJson
    public const WS_FD_USER_HASH = 'ws_fd_user_hash';
    // ws消息通知队列
    public const WS_SEND_LIST = 'ws_send_list';
}