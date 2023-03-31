<?php

use App\Constants\StatusCode;
use App\Exception\BusinessException;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Utils\Coroutine;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Server as SwooleServer;
//use Hyperf\Utils\Context;
use Hyperf\Context\Context;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpMessage\Cookie\Cookie as HyperfCookie;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Arr;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Plugin\Common\Driver\CacheDriver;
use Hyperf\Server\ServerFactory;


if (!function_exists('container')) {
    function container(): \Psr\Container\ContainerInterface
    {
        return ApplicationContext::getContainer();
    }
}

if (!function_exists('getUserInfo')) {
    /**
     * 获取用户信息
     * user:cjw
     * time:2022/5/6 18:52
     * @param string $token
     * @return array|false|mixed|string|null
     */
    function getUserInfo(string $token = '')
    {
        try {
            $userService = make(App\Core\Service\UserService::class);
            $userInfo = $userService->getUserInfo($token);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new BusinessException(StatusCode::ERR_INVALID_TOKEN);
        }
        return $userInfo;
    }
}

if (!function_exists('getProviderArr')) {
    /**
     * hyperf分页数据转数组
     * user:cjw
     * time:2022/5/12 18:15
     * @param LengthAwarePaginatorInterface $provider
     * @param bool $isAll
     * @return array
     */
    function getProviderArr(LengthAwarePaginatorInterface $provider, bool $isAll = false): array
    {
        if ($isAll){
            return toArray($provider);
        }
        $data = [];
        $data['current_page'] = $provider->currentPage();
        $data['items'] = $provider->items();
        $data['per_page'] = $provider->perPage();
        $data['total'] = $provider->total();
        $data['last_page'] = $provider->lastPage();
        $data['from'] = $provider->firstItem();
        $data['to'] = $provider->lastItem();
        return $data;
    }
}


if (!function_exists('requestEntry')) {
    /**
     * 根据异常返回信息，获取请求入口（模块-控制器-方法）
     * requestEntry
     * @param array $backTrace
     *
     * @return string|string[]
     */
    function requestEntry(array $backTrace)
    {
        $moduleName = '';
        foreach ($backTrace as $v) {
            if (isset($v['file']) && stripos($v['file'], 'CoreMiddleware.php')) {
                $tmp = array_reverse(explode('\\', trim($v['class'])));
                if (substr(strtolower($tmp[0]), -10) == 'controller') {
                    $module     = str_replace('controller', '', strtolower($tmp[1]));
                    $class      = str_replace('controller', '', strtolower($tmp[0]));
                    $function   = $v['function'];
                    $moduleName = $class . '-' . $function;
                    if ($module) {
                        $moduleName = $module . '-' . $moduleName;
                    }
                    break;
                }
            }
        }
        if (!$moduleName) {
            $request    = ApplicationContext::getContainer()->get(RequestInterface::class);
            $uri        = $request->getRequestUri();
            $moduleName = str_replace('/', '-', ltrim($uri, '/'));
        }
        $moduleName = $moduleName ?? 'hyperf';
        return $moduleName;
    }
}

if (!function_exists('getCoId')) {
    /**
     * 获取当前协程id
     * getCoId
     * @return int
     */
    function getCoId(): int
    {
        return Coroutine::id();
    }
}

if (!function_exists('getClientInfo')) {
    /**
     * 获取请求客户端信息，获取连接的信息
     * getClientInfo
     * @return mixed
     */
    function getClientInfo()
    {
        // 得从协程上下文取出请求
        $request = Context::get(ServerRequestInterface::class);
        $server  = make(SwooleServer::class);
        return $server->getClientInfo($request->getSwooleRequest()->fd);
    }
}

if (!function_exists('getServerLocalIp')) {
    /**
     * getServerLocalIp
     * 获取服务端内网ip地址
     * @return string
     */
    function getServerLocalIp(): string
    {
        $ip  = '127.0.0.1';
        $ips = array_values(swoole_get_local_ip());
        foreach ($ips as $v) {
            if ($v && $v != $ip) {
                $ip = $v;
                break;
            }
        }

        return $ip;
    }
}

if (!function_exists('setCookies')) {
    /**
     * setCookie
     * 设置cookie
     *
     * @param string      $key
     * @param string      $value
     * @param int         $expire
     * @param string      $path
     * @param string      $domain
     * @param bool        $secure
     * @param bool        $httpOnly
     * @param bool        $raw
     * @param null|string $sameSite
     */
    function setCookies(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, bool $raw = false, ?string $sameSite = null)
    {
        // convert expiration time to a Unix timestamp
        if ($expire instanceof \DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
            if ($expire === false) {
                throw new \RuntimeException('The cookie expiration time is not valid.');
            }
        }
        $response = ApplicationContext::getContainer()->get(ResponseInterface::class);
        $cookie   = new HyperfCookie($key, (string)$value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
        $response = $response->withCookie($cookie);
        Context::set(PsrResponseInterface::class, $response);
        return;
    }
}

if (!function_exists('getCookie')) {
    /**
     * getCookie
     * 获取cookie
     * @param string $key
     * @param null|string $default
     * @return mixed
     */
    function getCookie(string $key, ?string $default = null)
    {
        try {
            $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        } catch (\Psr\Container\NotFoundExceptionInterface | \Psr\Container\ContainerExceptionInterface $e) {
            return null;
        }
        return $request->cookie($key, $default);
    }
}

if (!function_exists('hasCookie')) {
    /**
     * hasCookie
     * 判断cookie是否存在
     * @param string $key
     * @return mixed
     */
    function hasCookie(string $key)
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        return $request->hasCookie($key);
    }
}

if (!function_exists('delCookie')) {
    /**
     * delCookie
     * 删除cookie
     * @param string $key
     * @return bool
     */
    function delCookie(string $key): bool
    {
        if (!hasCookie($key)) {
            return false;
        }

        setCookies($key, '', time() - 1);

        return true;
    }
}

if (!function_exists('setSessionId')) {
    /**
     * setSessionId
     * 设置sessionid
     * @param string $id
     */
    function setSessionId(string $id)
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        $session->setId($id);
        return;
    }
}

if (!function_exists('getSessionId')) {
    /**
     * getSessionId
     * 获取sessionid
     */
    function getSessionId(): string
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->getId();
    }
}

if (!function_exists('setSession')) {
    /**
     * setSession
     * 设置session
     * @param string $k
     * @param $v
     */
    function setSession(string $k, $v)
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        $session->set($k, $v);
        return;
    }
}

if (!function_exists('getSession')) {
    /**
     * getSession
     * 获取session
     * @param string $k
     * @param null $default
     * @return mixed
     */
    function getSession(string $k, $default = null)
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->get($k, $default = 0);
    }
}

if (!function_exists('getAllSession')) {
    /**
     * getAllSession
     * 获取所有session
     * @return mixed
     */
    function getAllSession()
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->all();
    }
}


if (!function_exists('hasSession')) {
    /**
     * hasSession
     * 判断session是否存在
     * @param string $name
     * @return bool
     */
    function hasSession(string $name): bool
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->has($name);
    }
}

if (!function_exists('removeSession')) {
    /**
     * removeSession
     * 从 Session 中获取并删除一条数据
     * @param string $name
     * @return mixed
     */
    function removeSession(string $name)
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->remove($name);
    }
}

if (!function_exists('forgetSession')) {
    /**
     * forgetSession
     * 从session中删除一条或多条数据
     * @param $keys string|array
     */
    function forgetSession($keys)
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        $session->forget($keys);
        return;
    }
}

if (!function_exists('clearSession')) {
    /**
     * clearSession
     * 清空当前 Session 里的所有数据
     */
    function clearSession()
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->clear();
    }
}

if (!function_exists('destroySession')) {
    /**
     * destroySession
     * 销毁session
     */
    function destroySession(): bool
    {
        $session = ApplicationContext::getContainer()->get(SessionInterface::class);
        return $session->invalidate();
    }
}

if (!function_exists('verifyIp')) {
    function verifyIp($realip)
    {
        return filter_var($realip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}

if (!function_exists('getClientIp')) {
    function getClientIp()
    {
        /**
         * @var ServerRequestInterface $request
         */
        $request = Context::get(ServerRequestInterface::class);
        $ip_addr = $request->getHeaderLine('x-forwarded-for');
        if (verifyIp($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getHeaderLine('remote-host');
        if (verifyIp($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getHeaderLine('x-real-ip');
        if (verifyIp($ip_addr)) {
            return $ip_addr;
        }
        $ip_addr = $request->getServerParams()['remote_addr'] ?? '0.0.0.0';
        if (verifyIp($ip_addr)) {
            return $ip_addr;
        }
        return '0.0.0.0';
    }
}

if (!function_exists('getLogArguments')) {
    /**
     * getLogArguments
     * 获取要存储的日志部分字段，monolog以外的业务信息
     *
     * @param float|null $executionTime 程序执行时间，运行时才能判断这里初始化为0
     * @param int        $rbs           响应包体大小，初始化0，只有正常请求响应才有值
     * @param array      $data          响应内容
     *
     * @return array
     */
    function getLogArguments(float $executionTime = null, int $rbs = 0, array $data = []): array
    {
        $request        = ApplicationContext::getContainer()->get(RequestInterface::class);
        $requestHeaders = $request->getHeaders();
        $serverParams   = $request->getServerParams();
        $arguments      = $request->all();
        if (isset($arguments['password'])) {
            unset($arguments['password']);
        }

//        $auth   = ApplicationContext::getContainer()->get(Auth::class);
//        $userId = $auth->check(false);
        $uuid   = getCookie('HYPERF_SESSION_ID');
        $url    = $request->fullUrl();

//        $agent = new Agent();
//        $agent->setUserAgent($requestHeaders['user-agent'][0] ?? '');
        $ip = $requestHeaders['x-real-ip'][0] ?? $requestHeaders['x-forwarded-for'][0] ?? '';
        // ip转换地域
        if ($ip && ip2long($ip) != false) {
            $location = getIpLocation($ip);
            $cityId   = $location['city_id'] ?? 0;
        } else {
            $cityId = 0;
        }

        return [
            'qid'                => $requestHeaders['qid'][0] ?? '',
            'server_name'        => $requestHeaders['host'][0] ?? '',
            'server_addr'        => getServerLocalIp() ?? '',
            'remote_addr'        => $serverParams['remote_addr'] ?? '',
            'forwarded_for'      => $requestHeaders['x-forwarded-for'][0] ?? '',
            'real_ip'            => $ip,
            'city_id'            => $cityId,
            'user_agent'         => $requestHeaders['user-agent'][0] ?? '',
//            'platform'           => $agent->platform() ?? '',
//            'device'             => $agent->device() ?? '',
//            'browser'            => $agent->browser() ?? '',
            'url'                => $url,
            'uri'                => $serverParams['request_uri'] ?? '',
            'arguments'          => $arguments ? json_encode($arguments) : '',
            'method'             => $serverParams['request_method'] ?? '',
            'execution_time'     => $executionTime,
            'request_body_size'  => $requestHeaders['content-length'][0] ?? 0,
            'response_body_size' => $rbs,
            'uuid'               => $uuid,
            'user_id'            => $userId ?? '',
            'referer'            => $requestHeaders['referer'][0] ?? '',
            'unix_time'          => $serverParams['request_time'] ?? '',
            'time_day'           => isset($serverParams['request_time']) ? date('Y-m-d', $serverParams['request_time']) : '',
            'time_hour'          => isset($serverParams['request_time']) ? date('Y-m-d H:00:00', $serverParams['request_time']) : '',
            'code'               => isset($data['code']) ? $data['code'] : 0,
            'msg'                => isset($data['msg']) ? $data['msg'] : '',
            'data'               => isset($data['data']) ? json_encode($data['data']) : [],
        ];
    }
}


if (!function_exists('getIpLocation')) {
    /**
     * getIpLocation
     * 获取ip对应的城市信息
     * @param $ip
     * @return mixed
     */
    function getIpLocation($ip)
    {
        $dbFile       = BASE_PATH . '/app/Plugin/Common/Container/ip2region.db';
        $ip2regionObj = new Ip2Region($dbFile);
        $ret          = $ip2regionObj->binarySearch($ip);
        return $ret;
    }
}

if (!function_exists('isStdoutLog')) {
    /**
     * isStdoutLog
     * 判断日志类型是否允许输出
     * @param string $level
     * @return bool
     */
    function isStdoutLog(string $level): bool
    {
        $config = config(StdoutLoggerInterface::class, ['log_level' => []]);
        return in_array(strtolower($level), $config['log_level'], true);
    }
}


if (!function_exists('handleTreeList')) {
    /**
     * handleTreeList
     * 建立数组树结构列表
     *
     * @datetime 2019/1/8 下午5:56
     * @param $arr 数组
     * @param int $pid 父级id
     * @param int $depth 增加深度标识
     * @param string $p_sub 父级别名
     * @param string $d_sub 深度别名
     * @param string $c_sub 子集别名
     * @return array
     * @access public
     */
    function handleTreeList($arr, $pid = 0, $depth = 0, $p_sub = 'parent_id', $c_sub = 'children', $d_sub = 'depth'): array
    {
        $returnArray = [];
        if (is_array($arr) && $arr) {
            foreach ($arr as $k => $v) {
                if ($v[$p_sub] == $pid) {
                    $v[$d_sub] = $depth;
                    $tempInfo  = $v;
                    unset($arr[$k]); // 减少数组长度，提高递归的效率，否则数组很大时肯定会变慢
                    $temp = handleTreeList($arr, $v['id'], $depth + 1, $p_sub, $c_sub, $d_sub);
                    if ($temp) {
                        $tempInfo[$c_sub] = $temp;
                    }
                    $returnArray[] = $tempInfo;
                }
            }
        }
        return $returnArray;
    }
}

if (!function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     * 从数组中提取值组成新数组
     *
     * @param array $array
     * @param string|array $value
     * @param string|array|null $key
     * @return array
     */
    function array_pluck($array, $value, $key = null): array
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('flushAnnotationCache')) {
    /**
     * flushAnnotationCache
     * 刷新注解缓存，清除注解缓存
     * @param string $listener
     * @param array $keys
     * @return bool
     */
    function flushAnnotationCache($listener = '', $keys = [])
    {
        if (!$listener || !$keys) {
            throw new \RuntimeException('参数不正确！');
        }
        $keys       = is_array($keys) ? $keys : [$keys];
        $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        foreach ($keys as $key) {
            $dispatcher->dispatch(new DeleteListenerEvent($listener, [$key]));
        }
        return true;
    }
}

if (!function_exists('clearCache')) {
    /**
     * clearCache
     * 清空当前 缓存
     */
    function clearCache()
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        return $cache->clear();
    }
}

if (!function_exists('delCache')) {
    /**
     * delCache
     * 删除缓存，1条/多条
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function delCache($keys = []): bool
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        if (is_array($keys)) {
            $cache->deleteMultiple($keys);
        } else {
            $cache->delete($keys);
        }

        return true;
    }
}

if (!function_exists('clearPrefixCache')) {
    /**
     * clearPrefixCache
     * 根据前缀清除缓存
     * 函数的含义说明
     *
     * @param string $prefix
     *
     * @return bool
     */
    function clearPrefixCache(string $prefix = ''): bool
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        $cache->clearPrefix($prefix);
        return true;
    }
}

if (!function_exists('setCache')) {
    /**
     * setCache
     * 设置缓存
     * @param $key
     * @param $value
     * @param null $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function setCache($key, $value, $ttl = null): bool
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        return $cache->set($key, $value, $ttl);
    }
}

if (!function_exists('setMultipleCache')) {
    /**
     * setMultipleCache
     * 批量设置缓存
     * @param $values
     * @param null $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function setMultipleCache($values, $ttl = null): bool
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        return $cache->setMultiple($values, $ttl);
    }
}

if (!function_exists('getCache')) {
    /**
     * getCache
     * 获取缓存
     * @param $key
     * @param null $default
     * @return iterable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function getCache($key, $default = null): iterable
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        return $cache->get($key, $default);
    }
}

if (!function_exists('getMultipleCache')) {
    /**
     * getMultipleCache
     * 获取多个缓存
     * @param array $keys
     * @param null $default
     * @return iterable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    function getMultipleCache(array $keys, $default = null): iterable
    {
        $config = config('cache.default');
        $cache  = make(CacheDriver::class, ['config' => $config]);
        return $cache->getMultiple($keys, $default);
    }
}

if (!function_exists('formatBytes')) {
    /**
     * formatBytes
     * 字节->兆转换
     * 字节格式化
     * @param $bytes
     * @return string
     */
    function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            $bytes = round($bytes / 1073741824 * 100) / 100 . 'GB';
        } elseif ($bytes >= 1048576) {
            $bytes = round($bytes / 1048576 * 100) / 100 . 'MB';
        } elseif ($bytes >= 1024) {
            $bytes = round($bytes / 1024 * 100) / 100 . 'KB';
        } else {
            $bytes = $bytes . 'Bytes';
        }
        return $bytes;
    }
}

if (!function_exists('durationFormat')) {
    /**
     * durationFormat
     * 时间格式化，格式化秒
     * @param $number
     * @return string
     */
    function durationFormat($number): string
    {
        if (!$number) {
            return '0分钟';
        }
        $newTime = '';
        if (floor($number / 3600) > 0) {
            $newTime .= floor($number / 3600) . '小时';
            $number  = $number % 3600;
        }
        if ($number / 60 > 0) {
            $newTime .= floor($number / 60) . '分钟';
            $number  = $number % 60;
        }
        if ($number < 60) {
            $newTime .= $number . '秒';
        }
        return $newTime;
    }
}


if (!function_exists('filterParams')) {
    /**
     * 请求参数过滤
     * filterParams
     * @param array $data
     * @return array
     */
    function filterParams(array $data): array
    {
        if (empty($data)) {
            return $data;
        }

        /**
         * 兼容ok框架
         */
        if (isset($data['page'])) {
            $data['current_page'] = $data['page'];
            unset($data['page']);
        }
        if (isset($data['limit'])) {
            $data['page_size'] = $data['limit'];
            unset($data['limit']);
        }

        return $data;
    }

}

if (!function_exists('toArray')) {
    /**
     * 将对象转为数组
     * toArr
     * @param $arr
     * @return array
     */
    function toArray($arr): array
    {
        return json_decode(json_encode($arr), true);
    }
}

if (!function_exists('redis')) {
    /**
     * 获取redis连接池对象
     * @return \Hyperf\Redis\Redis|null
     */
    function redis(): ?\Hyperf\Redis\Redis
    {
        $container = ApplicationContext::getContainer();
        $redis     = $container->get(\Hyperf\Redis\Redis::class);
        return $redis;
    }
}

if (!function_exists('buildStringHash')) {
    /**
     * 将字串符生成hash
     * buildStringHash
     * @param string $data
     * @return string
     */
    function buildStringHash(string $data): string
    {
        return hash('ripemd160', base64_encode(trim($data)));
    }
}


if (!function_exists('setLog')) {
    /**
     * 打印单独的错误日志
     * @param string $path
     * @param string $content
     */
    function setLog(string $path, string $content): void
    {
        co(function () use ($path, $content) {
            co(function () use ($path, $content) {
                file_put_contents(
                    BASE_PATH . '/runtime/logs/' . $path,
                    '[' . date('Y-m-d H:i:s') . '] ' . $content . "\r\n",
                    FILE_APPEND
                );
            });
        });
    }
}

if (!function_exists('isHTTPS')) {
    /**
     * 判断url是否为https
     * isHTTPS
     * @param string|null $url
     * @return bool
     */
    function isHTTPS(string $url = null): bool
    {
        if ($url === null) {
            try {
                $request = ApplicationContext::getContainer()->get(RequestInterface::class);
                $url     = $request->fullUrl();
            } catch (\Psr\Container\NotFoundExceptionInterface | \Psr\Container\ContainerExceptionInterface $e) {
                return false;
            }
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        if (substr($url, 0, 5) !== 'https') {
            return false;
        }
        return true;
    }
}