<?php
/**
 * user:cjw
 * time:2022/5/14 13:28
 */

namespace App\Core\Service;

use App\Constants\CacheKeyEnum;
use App\Controller\Http\SenderController;
use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;

class WebSocketService
{
    public static $userFdMapper = CacheKeyEnum::WS_USER_FD_MAPPER;
    public static $fdUserHash = CacheKeyEnum::WS_FD_USER_HASH;

    ##############   客户端连接操作   ############

    /**
     * @param Response|Server $server
     */
    public function outAllOfflineFd($server): bool
    {
        //查看ws在线列表  目前量小，超大量（十万？）级用户需要分页操作
        $connectionList = $server->connection_list(0);
        if (empty($connectionList)) {
            return false;
        }
        $flip = array_flip($connectionList);

        $container = ApplicationContext::getContainer();
        try {
            $redis = $container->get(Redis::class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return false;//make IDE happy
        }
        $allFdArr = $redis->hGetAll(self::$userFdMapper);
        foreach ($allFdArr as $id => $value) {
            $value = json_decode($value, true);
            $allFdArr[$id] = $value;
            // 把不在线的用户从redis中删掉
            if (!isset($flip[$value['fd']])) {
                $redis->hDel(self::$userFdMapper, $id);
                $redis->hDel(self::$fdUserHash, $value['fd']);
            }
        }
        return true;
    }


    /**
     * ws客户端下线，如果传server可主动踢下线
     * user:cjw
     * time:2022/5/16 21:05
     * @param $fd
     * @param Response|Server $server
     * @return bool
     */
    public function offlineFd($fd, $server = null): bool
    {
        $container = ApplicationContext::getContainer();
        try {
            $redis = $container->get(Redis::class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return false;
        }
//        $connectionList = $server->connection_list(0);
        $userId = $redis->hGet(self::$fdUserHash, (string)$fd);
        if (!empty($userId)) {
            $redis->del(self::$fdUserHash, $fd);
            $redis->del(self::$userFdMapper, $userId);
        }
        if (empty($server)) {
            return true;
        } else {
            $server->disconnect($fd);
        }
        return true;
    }

    /**
     * 根据用户id删除缓存，并下线
     * user:cjw
     * time:2022/5/16 20:55
     * @param $userId
     * @param null $server
     * @return bool
     */
    public function offlineUser($userId, $server = null): bool
    {
        $container = ApplicationContext::getContainer();
        try {
            $redis = $container->get(Redis::class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return false;
        }
        $wsArr = $redis->hGet(self::$userFdMapper, (string)$userId);
        $wsArr = empty($wsArr) ? [] : json_decode($wsArr, true);
        if (isset($wsArr['fd'])) {
            $redis->hDel(self::$userFdMapper, $userId);
            $redis->hDel(self::$fdUserHash, $wsArr['fd']);
        } else {
            return false;
        }
        //踢下线
        if ($server !== null) {
            $server->disconnect($wsArr['fd']);
        }
        return true;
    }


    ##############   无状态操作   ############

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;


    public function onlineUserFd($userToken, $wsArr): bool
    {
        $fd = $wsArr['fd'];
        /** 没有token就尝试从上下文拿出请求体中的token */
        if (empty($userToken)) {
            $userToken = Context::get(ServerRequestInterface::class)->getQueryParams('token');
            if (empty($userToken)) {
                return false;//要报错出去报错
            }
        }
        $userInfo = getUserInfo($userToken);
        $key = self::$userFdMapper . '_' . $userInfo['id'];
        /** 查看上下文中是否有fd值 */
        Context::set($key, json_encode($wsArr, JSON_UNESCAPED_UNICODE));
        /** 查看redis是否存在fd值 */
        $container = ApplicationContext::getContainer();
        try {
            $redis = $container->get(Redis::class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return false;
        }
        $redis->hSet(self::$userFdMapper, (string)$userInfo['id'], json_encode($wsArr, JSON_UNESCAPED_UNICODE));
        $redis->hSet(self::$fdUserHash, (string)$fd, $userToken);
//        $redis->set($key, json_encode($wsArr, JSON_UNESCAPED_UNICODE), 1800);

        return true;
    }

    /**
     * user:cjw
     * time:2022/5/14 21:58
     * @param $userToken
     * @return array|mixed
     */
    public function getUserFd($userToken): array
    {
        $wsArr = [];
        /** 没有token就尝试从上下文拿出请求体中的token */
        if (empty($userToken)) {
            $userToken = Context::get(ServerRequestInterface::class)->getQueryParams('token');
            if (empty($userToken)) {
                return [];//要报错出去报错
            }
        }
        $userInfo = getUserInfo($userToken);
        $userId = (string)$userInfo['id'];
        $key = self::$userFdMapper . '_' . $userId;
        /** 查看上下文中是否有fd值 */
        $wsArr = Context::get($key);
        $wsArr = empty($wsArr) ? [] : json_decode($wsArr, true);
        /** 查看redis是否存在fd值 */
        if (empty($wsArr)) {
            $container = ApplicationContext::getContainer();
            try {
                $redis = $container->get(Redis::class);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                return [];
            }
            $wsArr = $redis->hGet(self::$userFdMapper, (string)$userId);
            $wsArr = empty($wsArr) ? [] : json_decode($wsArr, true);
        }
        /** 返回结果 */
        return $wsArr;
    }

    public function deleteUserFd($userToken): bool
    {
        /** 没有token就尝试从上下文拿出请求体中的token */
        if (empty($userToken)) {
            $userToken = Context::get(ServerRequestInterface::class)->getQueryParams('token');
            if (empty($userToken)) {
                return false;//要报错出去报错
            }
        }
        $userInfo = getUserInfo($userToken);
        $userId = (string)$userInfo['id'];
        $key = self::$userFdMapper . '_' . $userId;
        /** 删除上下文 */
        Context::destroy($key);
        /** 删除redis缓存 */
        $container = ApplicationContext::getContainer();
        try {
            $redis = $container->get(Redis::class);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return false;
        }
        $fdArr = $redis->hGet(self::$userFdMapper, (string)$userId);
        if (!empty($fdArr)) {
            $fdArr = json_decode($fdArr, true);
            $redis->hDel(self::$fdUserHash, (string)$fdArr['fd']);
        }
        $redis->hDel(self::$userFdMapper, (string)$userId);
        /** 返回结果 */
        return true;
    }

    //TODO 离线业务用到再写
    public function getOnlineListByCache()
    {
        //
    }

    //TODO 离线业务用到再写
    public function checkOnlineByCache($fd)
    {
        //
    }

    // 测试接口
    public function test($wsMsg): bool
    {
        $applicationContext = ApplicationContext::getContainer();
        $serverController = $applicationContext->get(SenderController::class);
        $serverController::instance()->send((int)1, json_encode([123123312312]));
        return true;
    }

}