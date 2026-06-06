<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureFileUpload implements ValidationRule
{
    private array $allowedMimes;

    private int $maxSize;

    public function __construct(array $allowedMimes = ['jpg', 'jpeg', 'png', 'pdf'], int $maxSize = 2048)
    {
        $this->allowedMimes = $allowedMimes;
        $this->maxSize = $maxSize;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_object($value) || !method_exists($value, 'getClientOriginalExtension')) {
            $fail('Invalid file upload.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedMimes, true)) {
            $fail('The file must be a file of type: ' . implode(', ', $this->allowedMimes) . '.');
            return;
        }

        if (str_contains($value->getClientOriginalName(), '..') || str_contains($value->getClientOriginalName(), '.php')) {
            $fail('Invalid file name.');
            return;
        }

        $mimeType = $value->getMimeType();
        $expectedMimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        foreach ($this->allowedMimes as $ext) {
            if (isset($expectedMimeMap[$ext]) && $mimeType === $expectedMimeMap[$ext]) {
                return;
            }
        }

        $fail('The file has an invalid MIME type.');
    }
}
