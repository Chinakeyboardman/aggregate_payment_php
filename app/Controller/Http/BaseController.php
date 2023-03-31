<?php
declare(strict_types=1);

namespace App\Controller\Http;

use App\Constants\StatusCode;
use App\Controller\AbstractController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


/**
 * 控制器基类，控制器都继承这个基类
 *
 * @package App\Controller
 */
class BaseController extends AbstractController
{

    /**
     * __get
     * 隐式注入仓库类
     *
     * @param $key
     *
     * @return \Psr\Container\ContainerInterface|void
     */
    public function __get($key)
    {
        if ($key == 'app') {
            return $this->container;
        } elseif (substr($key, -3) == 'Dao') {
            $key = strstr($key,'Dap',true);
            return $this->getDaoInstance($key);
        } elseif (substr($key, -8) == 'Service') {
            return $this->getServiceInstance($key);
        } else {
            throw new \RuntimeException("服务/模型{$key}不存在，书写错误！", StatusCode::ERR_SERVER);
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

    public function getServiceInstance($key)
    {
        $key = ucfirst($key);
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
            return $this->container->get($className);
        } else {
            throw new \RuntimeException("服务/模型{$key}不存在，文件不存在！", StatusCode::ERR_SERVER);
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

    /**
     * 成功返回请求结果
     * success
     *
     * @param array       $data
     * @param string|null $msg
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function success(array $data = [], string $msg = null): \Psr\Http\Message\ResponseInterface
    {
        $msg  = $msg ?? StatusCode::getMessage(StatusCode::SUCCESS);
        $data = ['code' => StatusCode::SUCCESS, 'msg' => $msg, 'data' => $data];
        return $this->response->json($data);
    }

    /**
     * 业务相关错误结果返回
     * error
     *
     * @param int  $code
     * @param null $msg
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function error(int $code = StatusCode::ERR_EXCEPTION, $msg = null): \Psr\Http\Message\ResponseInterface
    {
        $msg  = $msg ?? StatusCode::getMessage(StatusCode::ERR_EXCEPTION);
        $data = ['code' => $code, 'msg' => $msg, 'data' => []];
        return $this->response->json($data);
    }

}