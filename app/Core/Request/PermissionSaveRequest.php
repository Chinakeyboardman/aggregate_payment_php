<?php
/**
 * user:cjw
 * time:2022/5/12 14:08
 */

namespace App\Core\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\Rule;

class PermissionSaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64'],
            'level' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 5])],
            'status' => ['required', 'integer', Rule::in([0, 1])],
            'is_menu' => ['required', 'integer', Rule::in([0, 1])],
            'menu_name' => ['string', 'max:64'],
            'menu_status' => ['integer', Rule::in([0, 1])],
            'menu_level' => ['required', 'integer', Rule::in([0, 1, 2])],
            'menu_parent_id' => ['integer', 'max:9999999999'],
            'parent_id' => ['integer', 'max:9999999999'],
            'is_login' => ['integer', Rule::in([0, 1])],
            'key' => ['string', 'max:64'],
            'route' => ['string', 'max:64'],
            'comment' => ['string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '权限名称必填',
            'name.string' => '权限名称必须为字符串',
            'name.max' => '权限名称超过长度',
            'level.required' => '组织架构等级必选',
            'level.integer' => '组织架构等级必须是数字',
            'level.in' => '组织架构等级不允许设置',
            'status.required' => '状态设置必选',
            'status.in' => '状态设置异常',
            'is_menu.required' => '必须选择是否为菜单栏',
            'is_menu.in' => '菜单栏设置异常',
            'menu_name.string' => '菜单名称必须设置为字符串',
            'menu_name.max' => '菜单名称超长',
            'menu_status.in' => '菜单开关设置异常',
            'menu_level.required' => '菜单等级设置异常',
            'menu_level.in' => '菜单等级设置异常',
            'menu_parent_id.max' => '菜单父级id异常',
            'parent_id.max' => '父级id异常',
            'is_login.in' => '是否需要登录设置参数异常',
            'key.max' => '权限key值字符超长',
            'route.max' => '权限路由字符超长',
            'comment.max' => '描述过长',
        ];
    }

}