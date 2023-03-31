<?php
declare(strict_types=1);

namespace App\Core\Service;

use App\Constants\CommonEnum;
use App\Constants\StatusCode;
use App\Core\Dao\PermissionDao;
use App\Exception\BusinessException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PermissionService extends BaseService
{
    /**
     * @Inject()
     * @var PermissionDao
     */
    public $permissionDao;


    public function roleIndex($param, $userInfo): array
    {
        $listProvider = $this->permissionDao->permissionListProvider($param, $userInfo, ['*']);
        $data = getProviderArr($listProvider);
        return $data;
    }

    /**
     * 获取权限树状列表
     * user:cjw
     * time:2022/4/27 18:15
     * @param array $data
     * @return array
     */
    public function getPermissionsTree(array $data): array
    {
        $permissionList = [];
        if (empty($data)) {
            //获取所有的权限树
            $permissionList = $this->permissionDao->getPermissionsByRoleId(0);
        } elseif (isset($data['role_id'])) {
            //根据角色获取权限树
            $roleId = $data['role_id'];
            $level = $data['level'];
            $strictLevel = isset($data['strictLevel']) ?? 1;
//            $permissionList = $this->permissionDao->getPermissionsByRoleId($roleId);
            $permissionList = $this->permissionDao->getPermissionsByRole((int)$roleId, $level, (int)$strictLevel);
        } elseif (isset($data['level'])) {
            //根据等级获取权限树
            $level = $data['level'];
            $strictLevel = isset($data['strictLevel']) ?? 1;
            $permissionList = $this->permissionDao->getPermissionsByLevel($level, (int)$strictLevel);
        }

        return $permissionList ? $this->handlePermissionModuleTree($permissionList) : [];
    }

    /**
     * 按照parent_id划分list
     * user:cjw
     * time:2022/4/27 18:15
     * @param array $lists
     * @param bool $hasOther
     * @return array
     */
    private function handlePermissionModuleTree(array $lists, bool $hasOther = false): array
    {
        $arr = [];
        if ($hasOther) {
            $arr['0'] = [
                'name' => "其他",
                'is_menu' => 0,
                'menu_name' => "",
                'menu_parent_id' => 0,
                'parent_id' => 0,
                "is_login" => 0,
                "key" => "",
                "level" => 0,
                "comment" => "其他权限",
                "children" => [],
                "sort" => 0,
            ];
        }

        foreach ($lists as $value) {
            if (!$value['parent_id']) {
                //父权限
                $arr[$value['id']] = $value;
            } else {
                $arr[$value['parent_id']]['children'][] = $value;
            }
        }

        if ($hasOther) {
            //未归类的丢进”其他权限“
            foreach ($arr as $key => $value) {
                if ((isset($value['id']) && $value['id'] != 0) && !$value['is_menu']) {
                    $arr['0']['children'][$value['id']] = $value;
                    unset($arr[$key]);
                }
            }
            //”其他权限“为空则直接unset
            if (empty($arr['0']['children'])) {
                unset($arr['0']);
            }
        } else {
            // 直接删掉未归类权限
            foreach ($arr as $key => $value) {
                if (isset($value['is_menu']) && !$value['is_menu'] && (isset($value['id']) && $value['id'] != 0)) {
                    unset($arr[$key]);
                }
            }
        }


        return $arr;
    }

    /**
     * 查询用户权限
     * user:cjw
     * time:2022/4/28 15:58
     * @param $userInfo
     * @return array
     */
    public function getUserPermissions($userInfo): array
    {
        $roleId = $userInfo['role_id'];
        return $this->permissionDao->getUserPermissionList($roleId);
    }

    /**
     * 根据角色获取全部权限字符串数组
     * user:cjw
     * time:2022/4/28 15:58
     * @param $roleId
     * @return array
     */
    public function getPermissionArr($roleId): array
    {
        return $this->permissionDao->getPermissionArr($roleId);
    }

    //TODO 校验当前账号级别是否可以操作角色级别
    public function checkUserLevel($level, $userInfo): bool
    {
        if ($userInfo['level'] == $level || $userInfo['level'] == $level - 1) {
            return true;
        } else {
            return false;
        }
    }

    //查询角色
    public function selectRoleInfo($where): array
    {
        if (is_array($where)) {
            return $this->permissionDao->selectRoleInfo($where);
        } elseif (is_numeric($where)) {
            // 获取可选权限
//            return $this->getPermissionsTree(['role_id'=>$where]);
            return $this->permissionDao->roleInfo($where);
        }

        return [];//make IDE happy
    }

    // 指定角色的信息
    public function roleInfo($roleId, $level, $strictLevel): array
    {
        $roleInfo = $this->permissionDao->roleInfo($roleId);
        if (empty($roleInfo)) {
            return [];
        }
        //根据角色获取所有权限
        $permissions = $roleInfo['permissions'];
        $arr = explode(',', $permissions);
        $flip = array_flip($arr);

        $data = $this->getPermissionsTree([
            'role_id' => $roleId,
            'level' => $level,
            'strictLevel' => $strictLevel,
        ]);

        foreach ($data as &$value) {
            if (isset($flip[$value['id']])) {
                $value['is_check'] = 1;
            } else {
                $value['is_check'] = 0;
            }
            if (isset($value['children'])) {
                foreach ($value['children'] as &$v) {
                    if (isset($flip[$v['id']])) {
                        $v['is_check'] = 1;
                    } else {
                        $v['is_check'] = 0;
                    }
                }
            }
        }
        $roleInfo['permissionsLists'] = $data;
        return $roleInfo;
    }

    //保存角色
    public function saveRole(array $data)
    {
        $data = $this->cleanRoleFields($data);
        return $this->permissionDao->saveRole($data);
    }

    //修改角色
    public function editRole(array $data, $userInfo)
    {
        $roleInfo = $this->selectRoleInfo($data['id']);
        if (empty($roleInfo)) {
            return 0;
        }
        $level = $roleInfo['level'];
        $userLevel = $userInfo['roleInfo']['level'];
        if ($level > $userLevel + 1 || $level < $userLevel) {
            throw new BusinessException(StatusCode::ERR_NOT_ACCESS);
        }
        if (isset($data['id']) && $data['id']) {
            $data = $this->cleanRoleFields($data);
            return $this->permissionDao->saveRole($data);
        }
        //代表修改失败，本来应该返回id
        return 0;
    }

    public function destroyRole($id): int
    {
        return $this->permissionDao->destroyRole($id);
    }

    /**
     * 清洗角色入库参数
     * user:cjw
     * time:2022/4/28 15:59
     * @param $param
     * @return array
     */
    public function cleanRoleFields($param): array
    {
        $data = [];
        if (isset($param['id'])) {
            $data['id'] = $param['id'];
        }
        if (isset($param['level'])) {
            $data['level'] = $param['level'];
        }
        $data['name'] = $param['name'];
        $data['status'] = $param['status'] ?? 1;
        $data['creator_id'] = $param['creator_id'] ?? 0;
        $data['permissions'] = $param['permissions'] ?? '';
        $data['comment'] = $param['comment'] ?? '';
        return $data;
    }


    public function getUsersByRoleId($roleId): array
    {
        return $this->permissionDao->getUsersByRoleId($roleId);
    }

    ####################  菜单  ####################

    /**
     * 获取所有的菜单并排序
     * user:cjw
     * time:2022/4/29 9:43
     * @return array
     */
    public function getAllMenu(): array
    {
        //查出所有菜单
        $where = [
            ['is_menu', '=', 1],
        ];
        $lists = $this->permissionDao->permissionLists($where, ['*']);
        $lists = array_values($lists);

        // 一级菜单排序
        $cmf_arr = array_column($lists, 'sort');
        array_multisort($cmf_arr, SORT_DESC, $lists);

        $data = [];
        // 拿出父级菜单
        foreach ($lists as $key => $list){
            if (!$list['menu_parent_id']){
                $data[$list['id']] = $list;
            }
        }
        // 拿出子集菜单
        foreach ($lists as $key => $list){
            if ($list['menu_parent_id'] && isset($data[$list['menu_parent_id']])){
                $data[$list['menu_parent_id']]['children'][] = $list;
            }
        }
        $data = array_values($data);

        return $data;
    }

    /**
     * 获取当前账号有权访问的菜单， 其实菜单也是权限
     * user:cjw
     * time:2022/4/29 9:44
     * @param array $userInfo
     * @return array
     */
    public function getUserMenus(array $userInfo = []): array
    {
        // 获取当前用户所有权限数组
        $permissionArr = $userInfo['roleInfo']['permissions'];
        $permissionArr = array_flip($permissionArr);//数组反转，isset查找比较快
        //拿到所有的菜单，因为不多所以直接拿is_menu=1的
        $menusAll = $this->getAllMenu();
        //筛选菜单中有权限部分
        foreach ($menusAll as $key => &$menu) {
            if (!isset($permissionArr[$menu['id']])) {
                unset($menusAll[$key]);
                continue;
            }
            if (isset($menu['children']) && !empty($menu['children'])) {
                foreach ($menu['children'] as $childKey => $child) {
                    if (!isset($permissionArr[$child['id']])) {
                        unset($menu['children'][$childKey]);
                    }
                }
            }
        }
        return $menusAll;
    }

    public function createPermission($params)
    {
        $saveDatum = $this->cleanPermissionDatum($params);
        return $this->permissionDao->savePermission($saveDatum);
    }

    public function savePermission($id, $params)
    {
        $saveDatum = $this->cleanPermissionDatum($params);
        $saveDatum['id'] = $id;
        return $this->permissionDao->savePermission($saveDatum);
    }

    public function deletePermission($id): int
    {
        return $this->permissionDao->destroyPermission($id);
    }

    public function cleanPermissionDatum($param): array
    {
        $allColumn = ['name', 'level', 'status', 'is_menu', 'menu_name', 'menu_status', 'menu_level', 'menu_parent_id', 'parent_id', 'is_login', 'key', 'route', 'comment'];
        $data = [];
        foreach ($allColumn as $fieldName) {
            if (isset($param[$fieldName])) {
                $data[$fieldName] = $param[$fieldName];
            }
        }
        if (!isset($data['menu_status']) || empty($data['menu_status'])) {
            $data['menu_status'] = 0;
        }
        if (!isset($data['parent_id']) || empty($data['parent_id'])) {
            $data['parent_id'] = 0;
        }
        if (!isset($data['is_login']) || empty($data['is_login'])) {
            $data['is_login'] = 0;
        }
        return $data;
    }

    // 拖拽排序
    public function drag(array $params): bool
    {
        //拖拽方
        $dragSide = $this->permissionDao->getRowById((int)$params['drag_side_id']);
        if (empty($dragSide)) {
            throw new BusinessException(StatusCode::ERR_SERVER);
        }
        //拖拽方数组
        $dragSideArray = $dragSide->toArray();
        $modules = $this->permissionDao->listNotDeleteModuleByIdsIndex([
            $params['pre_id'],
            $params['after_id'],
        ])->toArray();
        if (empty($modules)) {
            throw new BusinessException(StatusCode::ERR_SERVER);
        }
        //等于0代表需要拖拽到第一个元素
        if ($params['pre_id'] == 0) {
            $minSortModule = $this->permissionDao->permissionListModel(
                [], ['*'], ['sort' => 'asc']
            );
            if (empty($minSortModule)) {
                throw new BusinessException(StatusCode::ERR_SERVER);
            }
            //第一个元素不等于拖拽方
            if ($dragSideArray['id'] !== $minSortModule->id) {
                $sort = $minSortModule->sort / 2;
                if (is_int($sort)) {
                    if (!isset($dragSide->sort)) {
                        throw new BusinessException(StatusCode::ERR_SERVER);
                    }
                    $dragSide->sort = $sort;
                    if (!$dragSide->save()) {
                        throw new BusinessException(StatusCode::ERR_SERVER);
                    }
                } else {
                    //不是整型
                    if (!isset($dragSide->id)) {
                        throw new BusinessException(StatusCode::ERR_SERVER);
                    }
                    $otherModules = $this->permissionDao->listExceptOneSelfSort((int)$dragSide->id)->toArray();
                    //事务
                    Db::beginTransaction();
                    try {
                        if (!empty($otherModules)) {
                            array_unshift($otherModules, $dragSideArray);
                            foreach ($otherModules as $key => $value) {
                                $num = $key + 1;
                                $newSort = $num * CommonEnum::MODULE_SORT_INCREMENT;
                                Db::table('permission')->where('id', $value['id'])->update(['sort' => $newSort]);
                            }
                        }
                        Db::commit();
                    } catch (\Exception $exception) {
                        Db::rollBack();
                        throw new BusinessException(-1, $exception->getMessage());
                    }

                }
            }
        }

        //等于0代表需要拖拽到最后一个元素
        if ($params['after_id'] == 0) {
            //固定增量
            $increment = CommonEnum::MODULE_SORT_INCREMENT;
            $maxSortModule = $this->permissionDao->permissionListModel(
                [], ['*'], ['sort' => 'desc']
            );
            if (empty($maxSortModule)) {
                throw new BusinessException(StatusCode::ERR_SERVER);
            }
            $sort = $maxSortModule->sort + $increment;
            if (!isset($dragSide->sort)) {
                throw new BusinessException(StatusCode::ERR_SERVER);
            }
            $dragSide->sort = $sort;
            if (!$dragSide->save()) {
                throw new BusinessException(StatusCode::ERR_SERVER);
            }
        }

        //两者之间
        if (!empty($params['pre_id']) && !empty($params['after_id'])) {
            $preItem = $this->permissionDao->getRowById((int)$params['pre_id']);
            $afterItem = $this->permissionDao->getRowById((int)$params['after_id']);
            if (empty($preItem) || empty($afterItem) || !isset($preItem->sort) || !isset($afterItem->sort) || !isset($dragSide->sort)) {
                throw new BusinessException(StatusCode::ERR_SERVER);
            }
            $sort = ($preItem->sort + $afterItem->sort) / 2;
            if (is_int($sort)) {
                $dragSide->sort = $sort;
                if (!$dragSide->save()) {
                    throw new BusinessException(500);
                }
            } else {
                //不是整型
                $preArray = $this->permissionDao->listModuleBySort((int)$dragSide->id, '<=',
                    (int)$dragSide->sort)->toArray();
                $afterArray = $this->permissionDao->listModuleBySort((int)$dragSide->id, '>=',
                    (int)$dragSide->sort)->toArray();
                $preArray[] = $dragSideArray;
                //合并数组
                $mergeArray = array_merge($preArray, $afterArray);
                //事务
                Db::beginTransaction();
                try {
                    if (!empty($mergeArray)) {
                        foreach ($mergeArray as $key => $value) {
                            $num = $key + 1;
                            $newSort = $num * CommonEnum::MODULE_SORT_INCREMENT;
                            Db::table('permission')->where('id', $value['id'])->update(['sort' => $newSort]);
                        }
                    }
                    Db::commit();
                } catch (\Exception $exception) {
                    Db::rollBack();
                    throw new BusinessException(-1, $exception->getMessage());
                }
            }
        }

        return true;
    }


}