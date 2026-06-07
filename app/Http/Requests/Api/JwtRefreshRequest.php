<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * JWT Refresh Request Validation
 *
 * Validates refresh token for token refresh endpoint.
 */
class JwtRefreshRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, no authorization check needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'refresh_token.required' => 'Refresh token is required',
            'refresh_token.string' => 'Refresh token must be a string',
            'refresh_token.min' => 'Refresh token is invalid',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'refresh_token' => 'Refresh Token',
        ];
    }
}
