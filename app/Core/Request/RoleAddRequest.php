<?php

declare(strict_types=1);

namespace App\Core\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\Rule;

class RoleAddRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6])],
            'comment' => ['string', 'max:1024'],
            'permissions' => ['string', 'required', 'max:10240']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名称不能为空',
            'name.string' => '名称必须是字符串',
            'name.max' => '名称不能超过255长度',
            'level.required' => '必须选择单位级别',
            'level.integer' => '单位级别参数异常',
            'level.in' => '级别选择错误',
            'comment.max' => '输入备注过长',
            'permissions.required' => '缺少权限参数',
            'permissions.max' => '权限设置超长'
        ];
    }
}
