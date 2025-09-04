<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes','integer','exists:menu_categories,id'],
            'name'        => ['sometimes','string','max:160'],
            'description' => ['nullable','string'],
            'price'       => ['sometimes','numeric','min:0.01'],
            'is_active'   => ['sometimes','boolean']
        ];
    }
}
