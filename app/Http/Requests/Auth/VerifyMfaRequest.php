<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * MFA Code Verification Request
 *
 * Validates TOTP or backup code during login.
 */
class VerifyMfaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint during MFA verification
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:6', 'max:8'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'is_backup_code' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Verification code is required',
            'code.string' => 'Verification code must be a string',
            'code.min' => 'Verification code must be at least 6 characters',
            'code.max' => 'Verification code must not exceed 8 characters',
            'user_id.required' => 'User ID is required',
            'user_id.integer' => 'User ID must be an integer',
            'user_id.exists' => 'Invalid user ID',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => trim($this->code ?? ''),
            'is_backup_code' => (bool) ($this->is_backup_code ?? false),
        ]);
    }
}
