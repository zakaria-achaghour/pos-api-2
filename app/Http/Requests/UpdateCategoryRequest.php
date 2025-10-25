<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SuperAdmin', 'Owner', 'Manager']);
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes','string','max:120'],
            'description' => ['nullable','string','max:200'],
            'is_active'   => ['nullable','boolean'],
            'sort_order'  => ['nullable','integer','min:0']
        ];
    }
}
