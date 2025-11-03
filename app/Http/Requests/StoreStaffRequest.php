<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'employee_id' => 'required|string|max:50|unique:staff,employee_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email|unique:staff,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:Manager,Cashier,Waiter,Kitchen',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'hourly_rate' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'sometimes|in:active,inactive,vacation',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ];
    }
}
