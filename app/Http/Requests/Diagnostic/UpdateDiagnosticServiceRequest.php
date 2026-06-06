<?php

namespace App\Http\Requests\Diagnostic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosticServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'sample_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'home_collection_available' => ['boolean'],
            'report_time' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
