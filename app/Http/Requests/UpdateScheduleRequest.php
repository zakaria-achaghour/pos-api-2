<?php
// filepath: app/Http/Requests/UpdateScheduleRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => 'sometimes|exists:staff,id',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'shift_type' => 'sometimes|in:morning,afternoon,evening,night',
            'notes' => 'nullable|string|max:500',
        ];
    }
}