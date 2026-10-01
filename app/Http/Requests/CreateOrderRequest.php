<?php
// filepath: app/Http/Requests/CreateOrderRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Infrastructure\Tenancy\Tenant;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', Rule::exists('tables', 'id')->where('restaurant_id', Tenant::id())->withoutTrashed()],
            'waiter_id' => ['nullable', Rule::exists('staff', 'id')->where('restaurant_id', Tenant::id())->withoutTrashed()],
            'type' => 'nullable|in:dine-in,takeout,delivery',
            'priority' => 'nullable|in:normal,rush,urgent',
            'notes' => 'nullable|string|max:500',
            'customer_name' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.menu_item_id' => ['required', Rule::exists('menu_items', 'id')->where('restaurant_id', Tenant::id())],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string|max:500',
            'items.*.removed_ingredients' => 'nullable|array',
            'items.*.removed_ingredients.*' => 'string|max:100',
            'items.*.added_extras' => 'nullable|array',
            'items.*.added_extras.*' => 'string|max:100',
        ];
    }
}