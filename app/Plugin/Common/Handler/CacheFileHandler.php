<?php

declare(strict_types=1);

namespace App\Plugin\Common\Handler;

use Hyperf\ModelCache\Config;
use Hyperf\Cache\Collector\FileStorage;
use Hyperf\Cache\Exception\CacheException;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Hyperf\Cache\Driver\Driver;
use Hyperf\ModelCache\Handler\HandlerInterface;

/**
 * CacheFileHandler
 * 文件缓存
 *
 * @uses    Driver
 * @package App\Plugin\Common\Handler
 */
class CacheFileHandler extends Driver
{
    /**
     * @var string
     * 文件缓存目录
     */
    protected $storePath = BASE_PATH . '/runtime/caches';

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);
        if (!file_exists($this->storePath)) {
            $results = mkdir($this->storePath, 0777, true);
            if (!$results) {
                throw new CacheException('Has no permission to create cache directory!');
            }
        }
    }

    /**
     * getCacheKey
     * 获取缓存完成路径文件名称
     *
     * @param string $key
     *
     * @access public
     * @return string
     */
    public function getCacheKey(string $key): string
    {
        return $this->getStorePathLevel($key) . $this->getPrefix() . $key . '.cache';
    }

    /**
     * get
     * 获取缓存内容
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @access public
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $file = $this->getCacheKey($key);
        if (!file_exists($file)) {
            return $default;
        }

        /** @var FileStorage $obj */
        $obj = $this->packer->unpack(file_get_contents($file));
        if ($obj->isExpired()) {
            return $default;
        }

        return $obj->getData();
    }

    public function fetch(string $key, $default = null): array
    {
        $file = $this->getCacheKey($key);
        if (!file_exists($file)) {
            return [false, $default];
        }

        /** @var FileStorage $obj */
        $obj = $this->packer->unpack(file_get_contents($file));
        if ($obj->isExpired()) {
            return [false, $default];
        }

        return [true, $obj->getData()];
    }

    public function set($key, $value, $ttl = null): bool
    {
        $seconds = $this->secondsUntil($ttl);
        $file    = $this->getCacheKey($key);
        $content = $this->packer->pack(new FileStorage($value, $seconds));

        $result = file_put_contents($file, $content, FILE_BINARY);

        return (bool)$result;
    }

    public function delete($key): bool
    {
        $file = $this->getCacheKey($key);
        if (file_exists($file)) {
            if (!is_writable($file)) {
                return false;
            }
            unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        return $this->clearPrefix('');
    }

    public function getMultiple($keys, $default = null): array
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException('The keys is invalid!');
        }

        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException('The values is invalid!');
        }
        $seconds = $this->secondsUntil($ttl);
        foreach ($values as $key => $value) {
            $this->set($key, $value, $seconds);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException('The keys is invalid!');
        }

        foreach ($keys as $index => $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key): bool
    {
        $file = $this->getCacheKey($key);

        return file_exists($file);
    }

    public function clearPrefix(string $prefix): bool
    {
        $storePath = $this->getStorePathLevel();
        $this->clearFileCache($storePath, $prefix);
        return true;
    }

    protected function clearFileCache($path, string $prefix): bool
    {
        $dirs = scandir($path);
        foreach ($dirs as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . $file)) {
                    $this->clearFileCache($path . $file . DIRECTORY_SEPARATOR, $prefix);
                } else {
                    if (fnmatch($this->getPrefix() . $prefix . '*', $file)) {
                        unlink($path . $file);
                    }
                }
            }
        }

        return true;
    }

    protected function getStorePathLevel($key = null): string
    {
        if ($key) {
            $file       = md5($key);
            $levelPath1 = substr($file, 0, 2);
            $levelPath2 = substr($file, 2, 2);
            $fullPath   = $this->storePath . DIRECTORY_SEPARATOR . $levelPath1 . DIRECTORY_SEPARATOR . $levelPath2 . DIRECTORY_SEPARATOR;
            if (!file_exists($fullPath)) {
                $results = mkdir($fullPath, 0777, true);
                if (!$results) {
                    throw new CacheException('Has no permission to create cache directory!');
                }
            }
        } else {
            return $this->storePath . DIRECTORY_SEPARATOR;
        }
        return $fullPath;
    }

    protected function getPrefix()
    {
        return $this->prefix;
    }
}
