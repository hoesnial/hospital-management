<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'age' => ['required', 'integer', 'min:1', 'max:99'],
            'preferred_date' => ['required', 'date', 'after:today'],
            'preferred_time' => ['required', 'string'],
            'speciality' => ['required', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'additional_notes' => ['nullable', 'string'],
        ];
    }
}
