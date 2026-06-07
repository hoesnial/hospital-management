<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * MFA Setup Request
 *
 * Validates MFA setup with initial verification code.
 */
class SetupMfaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'verification_code' => ['required', 'string', 'regex:/^\d{6}$/', 'digits:6'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'verification_code.required' => 'Verification code is required',
            'verification_code.string' => 'Verification code must be a string',
            'verification_code.regex' => 'Verification code must be a 6-digit number',
            'verification_code.digits' => 'Verification code must be exactly 6 digits',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'verification_code' => 'Verification Code',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'verification_code' => trim($this->verification_code ?? ''),
        ]);
    }
}
