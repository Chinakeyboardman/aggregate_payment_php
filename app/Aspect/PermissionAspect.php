<?php
/**
 * 权限校验切面
 * user:cjw
 * time:2022/3/11 10:07
 */

namespace App\Aspect;

use App\Constants\StatusCode;
use App\Controller\Http\PermissionController;
use App\Exception\BusinessException;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @Aspect
 */
class PermissionAspect extends AbstractAspect
{

    // 需要权限控制的接口
    public $classes = [
        0 => PermissionController::class,
        1 => PermissionController::class . '::' . 'getPermissionList',
        2 => PermissionController::class . '::' . 'test',
    ];
    // 下标和aop控制接口保持一致，一个权限可能控制多个接口
    public $permissions = [
        0 => 'permission',
        1 => 'permission_list',
        2 => 'test',
    ];
    // 接口 => 元素（权限）  多对一
    public $classPermissionMapper = [];

    public function __construct()
    {
        // 设置的权限key => 服务接口具体权限点
        $classes = $this->classes;
        $permissionPoints = $this->permissions;
        foreach ($permissionPoints as $k => $point) {
            $this->classPermissionMapper[$classes[$k]] = $point;
        }
    }

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 往代码中植入权限判断逻辑
     * user:cjw
     * time:2022/4/29 14:24
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // TODO 拿到userInfo中的权限数组 这里是先写死的
        $userInfo = getUserInfo();
        $permissionsArr = $userInfo['roleInfo']['keys'];
//        $permissionsArr = [1,2,3,4,5,6,7,8,9,10,11,12,13];
        $permissionsArr = ["permission","permission_list", "case_manage", "case_list","test"];
//        $permissionsArr = ["permission", "permission_list", "b", "case", "case_list",];

        //数组反转查的快
        $permissionsFlip = array_flip($permissionsArr);

        // 拿出此对象的类名和方法名
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName ?? '';
        $serverPoint = $methodName ? $className . '::' . $methodName : $className;
        // 使用此接口需要的权限key
        $need = $this->classPermissionMapper[$serverPoint] ?? '';
        if (empty($need)){
            //检验一下是不是类级别的权限
            $need = $this->classPermissionMapper[$className];
        }

        if (isset($permissionsFlip[$need])) {
            //有权限
            return $proceedingJoinPoint->process();
        } else {
            throw new BusinessException(StatusCode::ERR_NOT_ACCESS);
        }

    }

}