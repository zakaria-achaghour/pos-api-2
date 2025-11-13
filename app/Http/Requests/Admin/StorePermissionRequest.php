<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('permissions')->where(fn($q) => $q->where('guard_name', 'api'))
            ],
            'guard_name' => ['sometimes', 'in:api'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required',
            'name.regex' => 'Permission name must be lowercase with hyphens only',
            'name.unique' => 'A permission with this name already exists',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => 'api',
        ]);
    }
}
