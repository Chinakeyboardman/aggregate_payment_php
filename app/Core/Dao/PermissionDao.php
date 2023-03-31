<?php

namespace App\Core\Dao;

use App\Model\Permission;
use App\Model\Role;
use App\Model\User;
use Hyperf\Contract\LengthAwarePaginatorInterface;

class PermissionDao
{

    ####  角色  ####

    public function permissionListProvider($params, $userInfo, array $column = ['*']): LengthAwarePaginatorInterface
    {
//        $column = array_merge($column, ['COUNT(role_id) as userCount'], ['user.role_id']);
        foreach ($column as &$f){
            $f = 'role.'.$f;
        }
        $model = Role::query()
            ->select($column)->selectRaw('COUNT(role_id) as userCount');
//            ->where('status', '!=', 0);
        // 搜索条件
        if (isset($params['status']) && !empty($params['status'])) {
            $model = $model->whereRaw('status = ' . (int)$params['status']);
        }
        if (isset($params['level']) && !empty($params['level'])) {
            $model = $model->whereRaw('level = ' . (int)$params['level']);
            $model = $model->orWhereRaw('level = ' . 0);
        }

        // 联表查询
        $model = $model->leftJoin('user','user.role_id', '=', 'role.id');

        //排序
        if (isset($params['order']) && !empty($params['order'])) {
            $model = $params['sort'] ? $model->orderBy($params['order'], $params['sort']) : $model->orderBy($params['order']);
        } else {
            $model = $model->groupBy(['role.id']);
        }

        //默认分页
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 10;

        return $model->paginate((int)$pageSize, $column, 'page', (int)$page);
    }

    //获取角色列表
    public function roleList($where = [], $order = [], $offset = 0, $limit = 0, $column = ['*']): array
    {
        $model = Role::query()->select($column);
        // 查询条件
        $model = $model->where($where);
        // 排序
        if ($order && is_array($order)) {
            foreach ($order as $k => $v) {
                $model = $model->orderBy($k, $v);
            }
        }
        // 分页
        if ($limit) {
            $model = $model->offset($offset)->limit($limit);
        }
        $model = $model->get();
        return $model ? $model->toArray() : [];
    }

    /**
     * 保存数据，只能用于id是递增生成的情况
     * user:cjw
     * time:2022/4/28 11:48
     * @param $data
     * @return mixed|null
     */
    public function saveRole($data)
    {
        $model = new Role();
        return $model->saveInfo($data);
    }

    /**
     * 通过主键id/ids获取角色
     * user:cjw
     * time:2022/4/28 17:10
     * @param $id
     * @return array
     */
    public function roleInfo($idOrIds): array
    {
        $model = new Role();
        return $model->getInfo($idOrIds);
    }

    public function getRoleIdNameByIds($ids, array $columns = ['id', 'name'])
    {
        $model = new Role();
        if (is_array($ids)) {
            return $model::query()->select($columns)->whereIn('id', $ids)->get()->keyBy('id');
        } else {
            return $model::query()->find($ids);
        }

    }

    /**
     * 简单查询角色
     * user:cjw
     * time:2022/4/28 14:10
     * @param array $where
     * @return array
     */
    public function selectRoleInfo(array $where): array
    {
        $model = new Role();
//        $where['deleted_at'] = null;
        return $model->getInfoByWhere($where, false);
    }

    /**
     * 删除角色， Model配置软删除就是软删除
     * user:cjw
     * time:2022/4/28 14:47
     * @param $id
     * @return int
     */
    public function destroyRole($id): int
    {
        $model = new Role();
        return $model->deleteInfo([$id]);
    }

    ####  权限  ####

    public function savePermission($data)
    {
        $model = new Permission;
        return $model->saveInfo($data);
    }

    public function destroyPermission($id): int
    {
        $model = new Permission();
        return $model->deleteInfo([$id]);
    }

    public function getRowById(int $id, array $column = ['*'])
    {
        return Permission::select($column)
            ->where('id', '=', $id)
            ->first();
    }

    public function listNotDeleteModuleByIdsIndex(array $ids, array $column = ['*']): \Hyperf\Utils\Collection
    {
        return Permission::select($column)->whereIn('id', $ids)->get()->keyBy('id');
    }

    public function listExceptOneSelfSort(int $id, string $order = 'asc', array $column = ['*']): \Hyperf\Utils\Collection
    {
        return Permission::select($column)
            ->where('id', '!=', $id)
            ->orderBy('sort', $order)
            ->get();
    }

    public function listModuleBySort(int $id, string $symbol, int $sort, array $column = ['*']): \Hyperf\Utils\Collection
    {
        return Permission::select($column)
            ->where('id', '!=', $id)
            ->where('sort', $symbol, $sort)
            ->where('is_del', 0)
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function permissionListModel($where = [], $column = ['*'], $order = [], $offset = 0, $limit = 0)
    {
        $model = Permission::select($column);
        // 查询条件
        if (!empty($where)) {
            $model = $model->where($where);
        }
        // 排序
        if ($order && is_array($order)) {
            foreach ($order as $k => $v) {
                $model = $model->orderBy($k, $v);
            }
        }
        // 分页
        if ($limit) {
            $model = $model->offset($offset)->limit($limit);
        }
        return $model->get()->first();
    }

    //按条件搜索权限列表
    public function permissionLists($where = [], $column = ['*'], $order = [], $offset = 0, $limit = 0): array
    {
        $model = Permission::query()->select($column);
        // 查询条件
        if (!empty($where)) {
            $model = $model->where($where);
        }
        // 排序
        if ($order && is_array($order)) {
            foreach ($order as $k => $v) {
                $model = $model->orderBy($k, $v);
            }
        }
        // 分页
        if ($limit) {
            $model = $model->offset($offset)->limit($limit);
        }
        $model = $model->get();

        // TODO 如果有分页，协程获取count

        return $model ? $model->toArray() : [];
    }

    //根据角色获取全部权限
    public function getPermissionArr($roleId): array
    {
        $role = Role::select()->where('id', $roleId)->first()->toArray();
        $rolePermissionIds = $role['permissions'];
        if (!empty($rolePermissionIds)) {
            $rolePermissionIds = explode(',', $rolePermissionIds);
        } else {
            $rolePermissionIds = [];
        }
        return $rolePermissionIds;
    }

    /**
     * 根据ids获取所有的权限详情数据
     * user:cjw
     * time:2022/5/12 12:05
     * @param $ids
     * @return array
     */
    public function getPermissionListByIds($ids): array
    {
        $permissionList = Permission::select()->whereIn('id', $ids)->get();
        return $permissionList ? $permissionList->toArray() : [];
    }

    //根据id数组获取路由
    public function getRoutesByPermissionIds($rolePermissionIds): array
    {
        $permissionList = Permission::select()->whereIn('id', $rolePermissionIds)->get()->toArray();
        $routes = [];
        if (!empty($permissionList)) {
            foreach ($permissionList as $permission) {
                $routes[] = $permission['route'];
            }
        }
        return $routes;
    }

    //根据用户获取全部权限
    public function getUserPermissionList($roleId): array
    {
        $roleKeyMapper = $this->getPermissionArr($roleId);
        $model = Permission::whereIn('id', $roleKeyMapper)->get();
        return $model ? $model->toArray() : [];
    }

    public function getPermissionArrByRoleId($id)
    {
        //根据角色获取所有权限
        $role = Role::select()->where('id', $id)->first()->toArray();
        if (isset($role['permissions']) && $role['permissions']) {
            return explode(',', $role['permissions']);
        }
        return [];
    }

    //根据角色获取所有权限
    public function getPermissionsByRoleId(int $id): array
    {
        if ($id !== 0) {
            //根据角色获取所有权限
            $arr = $this->getPermissionArrByRoleId($id);
            if (empty($arr)) {
                return [];
            }
        }
        //查权限表
        $model = Permission::query()->select();
        // 查询条件
        if (isset($arr)) {
            $model = $model->whereIn('id', $arr);
        }
        $model = $model->get();
        return $model ? $model->toArray() : [];
    }

    /**
     * 根据等级获取所有权限
     * user:cjw
     * time:2022/5/5 15:03
     * @param $level
     * @param int $strictLevel
     * @return array
     */
    public function getPermissionsByLevel($level, int $strictLevel = 1): array
    {
        $model = Permission::query()->select();
        // 查询条件
        if ($strictLevel) {
            //严格查询
            $model = $model->where('level', '=', $level)
                ->orWhere('level', '=', 0);//等级0为全局权限
        } else {
            //非严格预览
            $model = $model->where('level', '=', $level)
                ->orWhere('level', '=', $level + 1)//下一级权限
                ->orWhere('level', '=', 0);//等级0为全局权限
        }

        $model = $model->get();
        return $model ? $model->toArray() : [];
    }

    public function getPermissionsByRole(int $roleId, $level, int $strictLevel = 1): array
    {
        $model = Permission::query()->select();
        // 查询条件
        if ($strictLevel) {
            //严格查询
            $model = $model->where('level', '=', $level)
                ->orWhere('level', '=', 0);//等级0为全局权限
        } else {
            //非严格预览
            $model = $model->where('level', '=', $level)
                ->orWhere('level', '=', $level + 1)//下一级权限
                ->orWhere('level', '=', 0);//等级0为全局权限
        }

        $data = $model->get()->toArray();

        //根据角色获取所有权限
//        $arr = $this->getPermissionArrByRoleId($roleId);
//        $flip = array_flip($arr);
//
//        foreach ($data as &$value){
//            if (isset($flip[$value['id']])){
//                $value['is_check'] = 1;
//            } else {
//                $value['is_check'] = 0;
//            }
//        }

        return $data;
    }

    /**
     * 清洗权限数组，去掉未开启或软删除的权限
     * user:cjw
     * time:2022/5/5 15:02
     * @param $permissionsArr
     * @return mixed
     */
    public function cleanOpenPermissionsArr($permissionsArr)
    {
        // 查询开启状态权限ids
        $openIds = $this->openedPermissionsIds();
        $flipOpenIds = array_flip($openIds);
        foreach ($permissionsArr as $k => $v) {
            if (!isset($flipOpenIds[$v])) {
                unset($permissionsArr[$k]);
            }
        }
        return $permissionsArr;
    }

    /**
     * 查询到所有开启状态的权限ids
     * user:cjw
     * time:2022/5/5 15:02
     * @return array
     */
    public function openedPermissionsIds(): array
    {
        // TODO 可以缓存优化
        $where = [
            ['status', '=', 1],
        ];
        $lists = $this->permissionLists($where, ['id', 'status']);
        $arr = [];
        foreach ($lists as $key => $value) {
            $arr[] = $value['id'];
        }
        return $arr;
    }


    ###### 用户相关 ########

    /**
     * 根据角色查关联用户列表
     * user:cjw
     * time:2022/4/28 17:04
     * @param $roleId
     * @return array
     */
    public function getUsersByRoleId($roleId): array
    {
        $users = User::select()
            ->where('role_id', '=', $roleId)
            ->get();
        return $users ? $users->toArray() : [];
    }

}