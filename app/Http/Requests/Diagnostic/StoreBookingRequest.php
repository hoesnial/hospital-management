<?php

namespace App\Http\Requests\Diagnostic;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'string'],
            'address' => ['required', 'string', 'max:500'],
            'diagnostic_service_id' => ['required', 'exists:diagnostic_services,id'],
            'appointment_booking_id' => ['nullable', 'string', 'exists:appointments,booking_id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'payment_method' => ['required', 'in:cash,card,online'],
            'additional_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
