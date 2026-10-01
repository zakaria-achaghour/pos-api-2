<?php
// filepath: app/Http/Requests/UpdateOrderRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Infrastructure\Tenancy\Tenant;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'waiter_id' => ['sometimes', Rule::exists('staff', 'id')->where('restaurant_id', Tenant::id())->withoutTrashed()],
            'type' => 'sometimes|in:dine-in,takeout,delivery',
            'status' => 'sometimes|in:pending,accepted,preparing,ready,served,completed,cancelled',
            'priority' => 'sometimes|in:normal,rush,urgent',
            'notes' => 'nullable|string|max:500',
        ];
    }
}