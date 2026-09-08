<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('access-admin');
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'confirmed', Password::defaults()],
            'is_admin' => ['required', 'boolean', ...($this->route('user')?->is($this->user()) ? ['accepted'] : [])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['is_admin.accepted' => 'Bạn không thể tự gỡ quyền quản trị của mình.'];
    }
}
