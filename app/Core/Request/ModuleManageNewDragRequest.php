<?php

declare(strict_types=1);

namespace App\Core\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\Rule;

class ModuleManageNewDragRequest extends FormRequest
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
            'drag_side_id' => ['required', 'integer', 'min:0'],
            'pre_id' => ['required', 'integer', 'min:0'],
            'after_id' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'drag_side_id.required' => '模块拖拽方不能为空',
            'drag_side_id.integer' => '模块拖拽方必须是整型',
            'drag_side_id.min' => '模块拖拽方不能小于0',
            'pre_id.required' => '上个元素不能为空',
            'pre_id.integer' => '上个元素必须是整型',
            'pre_id.min' => '上个元素不能小于0',
            'after_id.required' => '下个元素不能为空',
            'after_id.integer' => '下个元素必须是整型',
            'after_id.min' => '下个元素不能小于0',
        ];
    }
}
