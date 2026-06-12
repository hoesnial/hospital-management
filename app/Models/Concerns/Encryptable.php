<?php

namespace App\Models\Concerns;

use App\Services\EncryptionService;

trait Encryptable
{
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable ?? []) && $value !== null) {
            $value = app(EncryptionService::class)->decrypt($value);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable ?? []) && $value !== null) {
            $value = app(EncryptionService::class)->encrypt($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function toArray()
    {
        $attributes = parent::toArray();

        foreach ($this->encryptable ?? [] as $field) {
            if (isset($attributes[$field]) && $attributes[$field] !== null) {
                $attributes[$field] = app(EncryptionService::class)->decrypt($attributes[$field]);
            }
        }

        return $attributes;
    }

    public function getPlainAttribute(string $key): mixed
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptable ?? []) && $value !== null) {
            return app(EncryptionService::class)->decrypt($value);
        }

        return $value;
    }

    public function setEncryptedAttribute(string $key, mixed $value): void
    {
        parent::setAttribute($key, app(EncryptionService::class)->encrypt($value));
    }
}
