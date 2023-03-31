<?php

declare(strict_types=1);

namespace App\Plugin\Common\Driver;

use Psr\Container\ContainerInterface;
use Hyperf\ModelCache\Handler\HandlerInterface;
use Hyperf\ModelCache\Handler\RedisHandler;
use App\Plugin\Common\Handler\ModelCacheFileHandler;
use Hyperf\ModelCache\Config;

/**
 * 数据模型缓存驱动
 * package App\Plugin\Common\HF
 */
class ModelCacheDriver  implements HandlerInterface
{
    private $cacheInstance = null;

    public function __construct(ContainerInterface $container, Config $config)
    {
        if($this->cacheInstance == null){
            $driver = env('MODEL_CACHE_DRIVER', 'file');
            $this->cacheInstance = $this->getCacheInstance($driver, $container, $config);
        }
    }

    /**
     * getCacheInstance
     * 获取缓存驱动实例
     *
     * @param mixed                     $driver
     * @param ContainerInterface        $container
     * @param \Hyperf\ModelCache\Config $config
     * @return \App\Plugin\Common\Handler\ModelCacheFileHandler|\Hyperf\ModelCache\Handler\RedisHandler|mixed
     * @access private
     */
    private function getCacheInstance($driver, ContainerInterface $container, Config $config)
    {
        switch($driver){
            case 'file':
                return make(ModelCacheFileHandler::class, [$container, $config]);
            case 'redis':
                return make(RedisHandler::class, [$container, $config]);
            default:
                throw new \RuntimeException("model cache [$driver] not found");
        }
    }

    public function get($key, $default = null)
    {
        return $this->cacheInstance->get($key, $default);
    }

    public function set($key, $value, $ttl = null): bool
    {
        return $this->cacheInstance->set($key, $value, $ttl);
    }

    public function delete($key): bool
    {
        return $this->cacheInstance->delete($key);
    }

    public function clear(): bool
    {
        return $this->cacheInstance->clear();
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return $this->cacheInstance->getMultiple($keys, $default);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return $this->cacheInstance->setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys): bool
    {
        return $this->cacheInstance->deleteMultiple($keys);
    }

    public function has($key): bool
    {
        return $this->cacheInstance->has($key);
    }

    public function getConfig(): Config
    {
        return $this->cacheInstance->getConfig();
    }

    public function incr($key, $column, $amount): bool
    {
        return $this->cacheInstance->incr($key, $column, $amount);
    }

    public function __call($name, $arguments)
    {
        return $this->cacheInstance->$name(...$arguments);
    }
}
