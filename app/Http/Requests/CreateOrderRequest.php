<?php
// filepath: app/Http/Requests/CreateOrderRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:tables,id',
            'waiter_id' => 'nullable|exists:staff,id',
            'type' => 'nullable|in:dine-in,takeout,delivery',
            'priority' => 'nullable|in:normal,rush,urgent',
            'notes' => 'nullable|string|max:500',
            'customer_name' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string|max:500',
            'items.*.removed_ingredients' => 'nullable|array',
            'items.*.removed_ingredients.*' => 'string|max:100',
            'items.*.added_extras' => 'nullable|array',
            'items.*.added_extras.*' => 'string|max:100',
        ];
    }
}