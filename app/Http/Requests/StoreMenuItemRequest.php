<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SuperAdmin', 'Owner', 'Manager']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id'       => ['required','integer','exists:categories,id'],
            'name'              => ['required','string','max:160'],
            'description'       => ['nullable','string'],
            'price'             => ['required','numeric','min:0.01'],
            'cost'              => ['nullable','numeric','min:0'],
            'is_available'      => ['nullable','boolean'],
            'is_active'         => ['nullable','boolean'],
            'preparation_time'  => ['nullable','integer','min:0'],
            'image'             => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'],
            'image_url'         => ['nullable','string','url','max:500'],
            'allergens'         => ['nullable','array'],
            'ingredients'       => ['nullable','array'],
            'ingredients.*'     => ['string','max:255']
        ];
    }
}
