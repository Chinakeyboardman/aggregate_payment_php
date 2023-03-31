<?php
/**
 * Created by PhpStorm.
 *​
 * BaseService.php
 *
 * 服务基类
 *
 */


namespace App\Core\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use App\Constants\StatusCode;
use Psr\Container\NotFoundExceptionInterface;

/**
 * BaseService
 * 服务基类
 * @package Core\Service
 */
class BaseService
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Created by PhpStorm.
     * 可以实现自动注入的业务容器
     */
    protected $businessContainerKey = ['auth','adminPermission'];

    /**
     * __get
     * 隐式注入服务类
     * @param $key
     * @return \Psr\Container\ContainerInterface|void
     */
    public function __get($key)
    {
        if ($key == 'app') {
            return $this->container;
        } elseif (in_array($key, $this->businessContainerKey)) {
            return $this->getBusinessContainerInstance($key);
        } elseif (substr($key, -3) == 'Dao') {
            $key = strstr($key, 'Dap', true);
            return $this->getDaoInstance($key);
        } elseif (substr($key, -8) == 'Service') {
            return $this->getServiceInstance($key);
        } elseif (substr($key, -5) == 'Model') {
            $key = strstr($key, 'Model', true);
            return $this->getModelInstance($key);
        } else {
            throw new \RuntimeException("服务/模型{$key}不存在，书写错误！", StatusCode::ERR_SERVER);
        }
    }

    /**
     * getBusinessContainerInstance
     * 获取业务容器实例
     * @param $key
     * @return mixed
     */
    public function getBusinessContainerInstance($key)
    {
        $key = ucfirst($key);
        $fileName = BASE_PATH."/app/Core/Common/Container/{$key}.php";
        $className = "APP\\Core\\Common\\Container\\{$key}";

        if (file_exists($fileName)) {
            try {
                return $this->container->get($className);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                throw new \RuntimeException("通用容器{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
            }
        } else {
            throw new \RuntimeException("通用容器{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
        }
    }


    /**
     * getRepositoriesInstance
     * 获取仓库类实例，容器操作
     *
     * @param $key
     *
     * @return mixed
     */
    public function getDaoInstance($key)
    {
        $key    = ucfirst($key);
        $module = $this->getDaoModuleName();
        if (!empty($module)) {
            $module = "{$module}";
        } else {
            $module = "";
        }
        if ($module) {
            $filename = BASE_PATH."/app/Core/Dao/{$module}/{$key}.php";
            $className = "Core\\Dao\\{$module}\\{$key}";
        } else {
            $filename = BASE_PATH."/app/Core/Dao/{$key}.php";
            $className = "Core\\Dao\\{$key}";
        }
        if (file_exists($filename)) {
            try {
                return $this->container->get($className);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                throw new \RuntimeException("容器调用{$key}失败!！", StatusCode::ERR_SERVER);
            }
        } else {
            throw new \RuntimeException("仓库{$key}不存在，文件不存在!！", StatusCode::ERR_SERVER);
        }
    }

    /**
     * getModuleName
     * 获取所属模块
     *
     * @return string
     */
    private function getDaoModuleName(): string
    {
        $className = get_called_class();
        $name      = substr($className, 14);
        $space     = explode('\\', $name);
        if (count($space) > 1) {
            return $space[0];
        } else {
            return '';
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

    /**
     * getServiceInstance
     * 获取服务类实例
     * @param $key
     * @return mixed
     */
    public function getServiceInstance($key)
    {
        $key    = ucfirst($key);
        $module = $this->getServiceModuleName();

        if (!empty($module)) {
            $module = "{$module}";
        } else {
            $module = "";
        }
        if ($module) {
            $fileName  = BASE_PATH . "/app/Core/Service/{$module}/{$key}.php";
            $className = "App\\Core\\Service\\{$module}\\{$key}";
        } else {
            $fileName  = BASE_PATH . "/app/Core/Service/{$key}.php";
            $className = "App\\Core\\Service\\{$key}";
        }

        if (file_exists($fileName)) {
            try {
                return $this->container->get($className);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                throw new \RuntimeException("容器调取服务{$key}失败！", StatusCode::ERR_SERVER);
            }
        } else {
            throw new \RuntimeException("服务{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
        }
    }

    /**
     * getModuleName
     * 获取所属模块
     *
     * @return string
     */
    private function getServiceModuleName(): string
    {
        $className = get_called_class();
        $name      = substr($className, 18);
        $space     = explode('\\', $name);
        if (count($space) > 1) {
            return $space[0];
        } else {
            return '';
        }
    }

}