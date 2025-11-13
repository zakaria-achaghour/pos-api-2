<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
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
            'number'              => ['required', 'string', 'max:50'],
            'capacity'            => ['required', 'integer', 'min:1', 'max:50'],
            'status'              => ['nullable', 'in:available,occupied,reserved,maintenance,out-of-order'],
            'section'             => ['nullable', 'string', 'max:100'],
            'shape'               => ['nullable', 'in:round,square,rectangular,oval'],
            'grid_x'              => ['nullable', 'integer'],
            'grid_y'              => ['nullable', 'integer'],
            'qr_code'             => ['nullable', 'string', 'max:255'],
            'location'            => ['nullable', 'array'],
            'location.section'    => ['nullable', 'string', 'max:100'],
            'location.floor'      => ['nullable', 'integer'],
            'location.area'       => ['nullable', 'string', 'max:100'],
            'features'            => ['nullable', 'array'],
            'features.*'          => ['string', 'max:100'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // QR code will be auto-generated in the model if not provided
        // No need to merge section from location as model handles it
    }
}
