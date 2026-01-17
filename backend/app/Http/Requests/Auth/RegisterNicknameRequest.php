<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterNicknameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * nickname 유효성 검사 규칙 설정
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                // users 테이블의 name 컬럼 고유
                'unique:users,name',
            ],
        ];
    }

    /**
     * 사용자 정의 에러 메시지
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => '닉네임은 필수 입력 항목입니다.',
            'name.min' => '닉네임은 최소 :min자 이상이어야 합니다.',
            'name.max' => '닉네임은 최대 :max자 이하여야 합니다.',
            'name.unique' => '이미 사용 중인 닉네임입니다.',
        ];
    }
}