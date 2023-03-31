<?php
declare(strict_types=1);

namespace App\Plugin\Common\HF;

use Psr\Container\ContainerInterface;
use App\Plugin\Common\Handler\CacheFileHandler;
use App\Plugin\Common\Handler\CacheRedisHandler;
use Hyperf\Cache\Driver\Driver;

/**
 * CacheFactory
 * 缓存工厂
 * package App\Plugin\Common\HF
 */
class CacheFactory extends Driver
{
    private $cacheInstance = null;

    public function __construct(ContainerInterface $container, array $config)
    {
        if($this->cacheInstance == null){
            $driver = env('CACHE_DRIVER', 'file');
            $this->cacheInstance = $this->getCacheInstance($driver, $container, $config);
        }
    }

    /**
     * getCacheInstance
     * 获取缓存驱动实例
     *
     * @param mixed              $driver
     * @param ContainerInterface $container
     * @param array              $config
     *
     * @return \App\Plugin\Common\Handler\CacheFileHandler|\App\Plugin\Common\Handler\CacheRedisHandler|mixed
     * @access private
     */
    private function getCacheInstance($driver, ContainerInterface $container, array $config)
    {
        switch($driver){
            case 'file':
                return make(CacheFileHandler::class, [$container, $config]);
            case 'redis':
                return make(CacheRedisHandler::class, [$container, $config]);
            default:
                throw new \RuntimeException("cache [$driver] not found");
        }
    }

    public function getCacheKey(string $key): string
    {
        return $this->cacheInstance->getCacheKey($key);
    }

    public function get($key, $default = null)
    {
        return $this->cacheInstance->get($key, $default);
    }

    public function fetch(string $key, $default = null): array
    {
        return $this->cacheInstance->fetch($key, $default);
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

    public function getMultiple($keys, $default = null)
    {
        return $this->cacheInstance->getMultiple($keys, $default);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return $this->cacheInstance->setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys)
    {
        return $this->cacheInstance->deleteMultiple($keys);
    }

    public function has($key): bool
    {
        return $this->cacheInstance->has($key);
    }

    public function clearPrefix(string $prefix): bool
    {
        return $this->cacheInstance->clearPrefix($prefix);
    }

    public function __call($name, $arguments)
    {
        return $this->cacheInstance->$name(...$arguments);
    }
}
