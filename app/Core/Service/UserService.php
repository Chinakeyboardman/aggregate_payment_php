<?php

namespace App\Core\Service;

use App\Core\Dao\UserDao;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Exception;



/**
 * UserService
 * 隐式注入
 * @package \App\Core\Service
 * @property UserDao $userDao
 */
class UserService
{


    const REDIS_PREFIX = 'user_login';

    /**
     * 通过token获取用户缓存数据
     * @param string $token
     * @return array|false|mixed|string|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getUserInfo(string $token = ''): array
    {
        if (empty($token)) {
            $token = Context::get(ServerRequestInterface::class)->getHeaderLine('Authorization');
            if (empty($token)) {
                return [];//这里报错也可以
            }
        }

        $userInfo = [];
        $result = Context::has(self::REDIS_PREFIX);
        if ($result) {
            $result = Context::get(self::REDIS_PREFIX);
            $userInfo = unserialize($result);
        }

        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);

        if (empty($userInfo)) { // 没有值 需要从redis中获取
            $result = $redis->hexists(self::REDIS_PREFIX, $token);
            if ($result) {
                $result = $redis->hGet(self::REDIS_PREFIX, $token);
                Context::set(self::REDIS_PREFIX, $result);
                $userInfo = unserialize($result);
            }
        }
        $userInfo = [];//TODO 测试代码 不走缓存
        if (empty($userInfo)) {
            // 这里做用户缓存的数据 想要什么可以自己替换下一句
            $user = $this->userDao->getUserInfoByToken($token);
            if (!empty($user)) {
                $userInfo = $user;
                $redis->hSet(self::REDIS_PREFIX, $token, serialize($userInfo));
                //设置键的过期时间
                $expireTime = mktime(23, 59, 59, (int)date("m"), (int)date("d"), (int)date("Y"));
//                $redis->expireAt(self::REDIS_PREFIX, $expireTime);
                Context::set(self::REDIS_PREFIX, serialize($userInfo));
            }
        }

        return $userInfo;

    }

    /**
     * 通过token换取用户信息
     *
     * @param string $token
     * @return bool|array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getUserInfoByToken(string $token)
    {
        if(empty($token))  return false;

        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        $user = $redis->hGet(self::REDIS_PREFIX, $token);
        if ($user) {
            return unserialize($user);
        }
        return false;
    }


    /**
     * token是否过期
     * @param string $token
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function checkTokenIsExpired(string $token): bool
    {
        try {
            if (empty($token)) {
                return false;
            }
            $container = ApplicationContext::getContainer();
            $redis = $container->get(Redis::class);
            return !$redis->hExists(self::REDIS_PREFIX, $token);
        } catch (Exception $exception) {
            return true;
        }
    }

}