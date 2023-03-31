<?php
declare(strict_types=1);
/**
 * user:cjw
 * time:2022/4/27 14:16
 */

namespace App\Controller\Http;

use App\Constants\StatusCode;
use App\Exception\BusinessException;
use App\Core\Request\ModuleManageNewDragRequest;
use App\Core\Request\PermissionSaveRequest;
use App\Core\Request\RoleAddRequest;
use App\Core\Service\PermissionService;
use App\Core\Service\Response;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * PermissionController
 * @package \App\Controller\Http
 * @property PermissionService $permissionService
 */
class PermissionController extends BaseController
{


    ######### 角色 ###########

    public function roleIndex(RequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $userInfo = getUserInfo();
        $param = $request->all();
        if (!isset($param['level'])) {
            throw new BusinessException(StatusCode::EXECUTE_PARAMS);
        }
        $level = $param['level'];
        $userLevel = $userInfo['roleInfo']['level'];
        if ($level > $userLevel + 1 || $level < $userLevel) {
            throw new BusinessException(StatusCode::ERR_NOT_ACCESS);
        }
        $data = $this->permissionService->roleIndex($param, $userInfo);

        return $this->success(['data' => $data], "success!");
    }

    //新增角色
    public function addRole(RoleAddRequest $request): \Psr\Http\Message\ResponseInterface
    {
        $param = $request->validated();
//        $param = $request->all();

        // 拿到角色等级
        $userInfo = getUserInfo();
        $level = $userInfo['roleInfo']['level'];

        //不能越级创建角色 （2可以建2，2可以建3，但是2不能建4和1）
        if ($level > $param['level'] || $level < $param['level'] - 1 || isset($param['id'])) {
            return $this->error(StatusCode::ERR_NOT_ACCESS, "禁止跨级创建");
        }
        //检查是否已经创建过同样的name和level
        $info = $this->permissionService->selectRoleInfo(['name' => $param['name'], 'level' => $param['level']]);
        if ($info) {
            return $this->error(StatusCode::ERR_DATA_DUPLICATE, "已经创建过同名的角色");
        }
        //保存逻辑
        $data = $this->permissionService->saveRole($param);
        if (!$data) {
            return $this->error(StatusCode::ERR_DATA_SAVE_FAIL, "保存失败");
        }

        return $this->success([], 'ok');
    }

    public function roleInfo($roleId): \Psr\Http\Message\ResponseInterface
    {
        $userInfo = getUserInfo();
        $level = $userInfo['roleInfo']['level'];
        $strictLevel = false;
//        $data = $this->permissionService->selectRoleInfo($roleId);
        $data = $this->permissionService->roleInfo($roleId, $level, $strictLevel);
        return $this->success(['data' => $data], "success!");
    }

    //修改角色
    public function editRole($roleId, RoleAddRequest $request): \Psr\Http\Message\ResponseInterface
    {
        $param = $request->all();
        //TODO 拿到角色等级 check一下
        $userInfo = getUserInfo();
//        $userLevel = $userInfo['roleInfo']['level'];

        $param['id'] = $roleId;
        if (!$roleId) {
            return $this->error(StatusCode::ERR_PARAMETER, "非法操作！请带上id");
        }
        $res = $this->permissionService->editRole($param, $userInfo);
        if (!$res) {
            return $this->error(StatusCode::ERR_DATA_SAVE_FAIL, "修改失败");
        }

        return $this->success([], "保存成功！");
    }

    //删除角色
    public function deleteRole($roleId): \Psr\Http\Message\ResponseInterface
    {
        // 先检查角色是否有关联用户
        $users = $this->permissionService->getUsersByRoleId($roleId);
        if (!empty($users)) {
            return $this->error(StatusCode::ERR_EXCEPTION, "该角色存在用户绑定，请先取消所有绑定此角色的用户");
        }
        //其实是软删除，请检查model
        $res = $this->permissionService->destroyRole($roleId);
        if ($res) {
            return $this->success(['count' => $res], "删除成功");
        } else {
            return $this->error(StatusCode::ERR_DELETE_DATA, "删除失败");
        }
    }

    ######### 页面元素 ###########

    //

    /**
     * 获取可选权限
     * user:cjw
     * time:2022/4/27 18:17
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getPermissionList(RequestInterface $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $param = $request->all();
        // 根据用户id 获取角色id和角色级别
        $userInfo = getUserInfo();
        $userLevel = $userInfo['roleInfo']['level'];
        $level = $param['level'];
        $strictLevel = $param['strictLevel'] ?? 1;

        if ($level > $userLevel + 1 || $level < $userLevel) {
            throw new BusinessException(StatusCode::ERR_NOT_ACCESS);
        }

        // 严格查询同级别的权限 非严格能看到下级和无等级权限
        $data = $this->permissionService->getPermissionsTree(['level' => $level, 'strictLevel' => $strictLevel]);

        return $this->success(['list' => $data,], "ok");
    }

    // 保存权限（新增修改）
    public function createPermission(PermissionSaveRequest $request): \Psr\Http\Message\ResponseInterface
    {
        $params = $request->validated();
        $list = $this->permissionService->selectRoleInfo([['name', $params['name']], ['level', $params['level']]]);
        if (!empty($list)) {
            return $this->error(StatusCode::ERR_DATA_DUPLICATE, "权限名" . $params . "已经存在");
        }
        $res = $this->permissionService->createPermission($params);
        if ($res) {
            return $this->success([], "保存成功");
        } else {
            return $this->error(StatusCode::ERR_DATA_SAVE_FAIL, "保存失败");
        }
    }

    public function savePermission($id, PermissionSaveRequest $request): \Psr\Http\Message\ResponseInterface
    {
        $params = $request->validated();
        $res = $this->permissionService->savePermission($id, $params);
        if ($res) {
            return $this->success([], "保存成功");
        } else {
            return $this->error(StatusCode::ERR_DATA_SAVE_FAIL, "保存失败");
        }
    }

    public function deletePermission($id): \Psr\Http\Message\ResponseInterface
    {
        $res = $this->permissionService->deletePermission($id);
        if ($res) {
            return $this->success(['count' => $res], "删除成功");
        } else {
            return $this->error(StatusCode::ERR_DELETE_DATA, "删除失败");
        }
    }

    /**
     * 拖拽排序权限菜单
     * user:cjw
     * time:2022/4/29 10:46
     * @param ModuleManageNewDragRequest $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function drag(ModuleManageNewDragRequest $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $this->permissionService->drag($request->validated());
        return $this->success([], "ok");
    }


}