<?php


namespace App\Core\Dao;


use App\Model\User;
use App\Core\Service\PermissionService;
use Hyperf\Di\Annotation\Inject;

class UserDao
{

    /**
     * @Inject()
     * @var PermissionDao
     */
    public $permissionDao;

    /**
     * 验证用户名是否存在
     * @param string $username
     * @return bool
     */
    public function checkUsername(string $username): bool
    {
        return User::query()->where('username', $username)->exists();
    }

    /**
     * 根据用户id获取 [id, name] 的数组
     * @param array $ids
     * @param array|string[] $columns
     * @return \Hyperf\Database\Model\Builder[]|\Hyperf\Database\Model\Collection|\Hyperf\Utils\Collection
     */
    public function getUserIdNameByIds(array $ids, array $columns = ['id', 'name'])
    {
        return User::query()->select($columns)->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * 根据用户id获取用户详情
     * @param int $id
     * @return \Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Builder[]|\Hyperf\Database\Model\Collection|\Hyperf\Database\Model\Model|null
     */
    public function getUserInfoById(int $id)
    {
        return User::query()->find($id);
    }

    /**
     * 根据用户id获取所属权限表的指定属性
     * @param int $id
     * @param string $column
     * @return mixed
     */
    public function getRoleColumnByUserId(int $id, string $column)
    {
        $user = User::query()->find($id);
        $roleColumn = $user->role->$column;
        return $roleColumn;
    }

    /**
     * 根据指定id删除用户
     * @param $ids
     * @return int
     */
    public function destroy($ids)
    {
        $result = User::destroy($ids);

        return $result;
    }

    public function getUserInfoByUsername(string $username)
    {
        $userInfo = User::query()->where('username', $username)->first();
        return $userInfo;
    }

    /**
     * 根据token获取用户信息，附带角色权限和菜单
     * @param string $token
     * @return array
     */
    public function getUserInfoByToken(string $token): array
    {
        $userInfo = User::query()->where('token', $token)->first();
        if (empty($userInfo)){
            return [];
        } else {
            $userInfo = $userInfo->toArray();
        }
        //查询角色信息
        if (isset($userInfo['role_id']) && !empty($userInfo['role_id'])) {
            $roleInfo = $this->permissionDao->roleInfo($userInfo['role_id']);
            $userInfo['roleInfo'] = $roleInfo;
        }
        //查询权限信息
        if (isset($userInfo['roleInfo']['permissions'])) {
            $userInfo['roleInfo']['permissions'] = explode(',', $userInfo['roleInfo']['permissions']);
            // 清洗，只保留开启状态的权限
            $userInfo['roleInfo']['permissions'] = $this->permissionDao->cleanOpenPermissionsArr($userInfo['roleInfo']['permissions']);
            // 根据权限id获取前端路由和后端key
            $permissionList = $this->permissionDao->getPermissionListByIds($userInfo['roleInfo']['permissions']);
            $userInfo['roleInfo']['routes'] = [];
            $userInfo['roleInfo']['keys'] = [];
            if (!empty($permissionList)) {
                foreach ($permissionList as $permission) {
                    if ($permission['route']){
                        $userInfo['roleInfo']['routes'][] = $permission['route'];
                    }
                    if ($permission['key']){
                        $userInfo['roleInfo']['keys'][] = $permission['key'];
                    }
                }
            }
        }
        //查询菜单信息
        $permissionService = new PermissionService();
        $menus = $permissionService->getUserMenus($userInfo);
        $userInfo['menus'] = $menus;

        return $userInfo;
    }

    public function createUser($userInfo)
    {
        $user = User::query()->create($userInfo);
        return $user;
    }

}