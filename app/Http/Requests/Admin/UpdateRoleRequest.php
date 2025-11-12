<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('SuperAdmin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('roles')
                    ->where(fn($q) => $q->where('guard_name', 'api'))
                    ->ignore($roleId)
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                Rule::exists('permissions', 'name')->where(fn($q) => $q->where('guard_name', 'api'))
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'name.unique' => 'A role with this name already exists',
            'permissions.*.exists' => 'One or more permissions do not exist',
        ];
    }
}
