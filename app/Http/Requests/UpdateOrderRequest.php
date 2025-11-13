<?php
// filepath: app/Http/Requests/UpdateOrderRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'waiter_id' => 'sometimes|exists:staff,id',
            'type' => 'sometimes|in:dine-in,takeout,delivery',
            'status' => 'sometimes|in:pending,accepted,preparing,ready,served,completed,cancelled',
            'priority' => 'sometimes|in:normal,rush,urgent',
            'notes' => 'nullable|string|max:500',
        ];
    }
}