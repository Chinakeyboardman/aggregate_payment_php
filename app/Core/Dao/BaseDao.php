<?php

declare(strict_types=1);

namespace App\Core\Dao;

use App\Plugin\Log\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use App\Constants\StatusCode;
use Psr\Container\NotFoundExceptionInterface;

/**
 * BaseDao
 * 仓库基类
 *
 * @package App\Core\Dao
 */
class BaseDao
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 可以实现自动注入的业务容器
     */
    protected $businessContainerKey = ['auth', 'adminPermission'];

    /**
     * __get
     * 隐式注入服务类
     *
     * @param $key
     *
     * @return \Psr\Container\ContainerInterface|void
     */
    public function __get($key)
    {
        if ($key == 'app') {
            return $this->container;
        } elseif (in_array($key, $this->businessContainerKey)) {
            return $this->getBusinessContainerInstance($key);
        } elseif (substr($key, -5) == 'Model') {
            return $this->getModelInstance($key);
        } else {
            throw new \RuntimeException("服务{$key}不存在，书写错误！", StatusCode::ERR_SERVER);
        }
    }

    /**
     * getBusinessContainerInstance
     * 获取业务容器实例
     *
     * @param $key
     *
     * @return mixed
     */
    public function getBusinessContainerInstance($key)
    {
        $key = ucfirst($key);

//        Log::get('app')->info($key);

        $fileName  = BASE_PATH . "/app/Plugin/Common/Container/{$key}.php";
        $className = "App\\Plugin\\Common\\Container\\{$key}";

        if (file_exists($fileName)) {
            try {
                return $this->container->get($className);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                throw new \RuntimeException("容器调取服务{$key}失败！", StatusCode::ERR_SERVER);
            }
        } else {
            throw new \RuntimeException("通用容器{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
        }
    }

    /**
     * getModelInstance
     * 获取数据模型类实例
     * @param $key
     * @return mixed
     */
    public function getModelInstance($key)
    {
        $key = ucfirst($key);
        $fileName = BASE_PATH."/app/Model/{$key}.php";
        $className = "App\\Model\\{$key}";

        if (file_exists($fileName)) {
            //model一般不要常驻内存
            //return $this->container->get($className);
            return make($className);
        } else {
            throw new \RuntimeException("服务/模型{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
        }
    }


}