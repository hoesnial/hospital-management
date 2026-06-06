<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'health_check_id' => ['required', 'exists:health_checks,id'],
            'payment_type' => ['required', 'in:50%,100%'],
        ];
    }
}
