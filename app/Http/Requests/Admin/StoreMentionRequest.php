<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
