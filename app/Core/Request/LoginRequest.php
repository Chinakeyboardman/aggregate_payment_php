<?php


namespace App\Core\Request;


use Hyperf\Validation\Rule;

class LoginRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title'     => 'required|max:255|string',
            'content'   => 'required|string',
            'region_id' => 'required',
            'type'      => ['required', Rule::in([1, 2, 3])],
            'send_type' => ['required', Rule::in([1, 2])],
            'at_time'   => 'string',
        ];
    }


    public function messages(): array
    {
        return [
            'title.required'     => '标题不能为空',
            'title.string'       => '标题只能是字符串',
            'content.string'     => '数据类型不正确',
            'content.required'   => '内容不能为空',
            'region_id.required' => '请选择地区',
            'type.required'      => '发送方式不能为空',
            'type.in'            => '发送方式选择错误',
            'send_type.required' => '发送群体不能为空',
            'send_type.in'       => '发送群体错误',
            'at_time.filled'     => '开始时间不能为空',

        ];
    }
}