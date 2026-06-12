<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    protected EncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EncryptionService();
    }

    public function test_encrypts_and_decrypts_string(): void
    {
        $original = 'Sensitive patient diagnosis data';

        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertNotNull($encrypted);
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypted_output_differs_from_input(): void
    {
        $original = 'Medical record #12345';
        $encrypted = $this->service->encrypt($original);

        $this->assertStringNotContainsString($original, $encrypted);
        $this->assertStringStartsWith('eyJ', $encrypted);
    }

    public function test_returns_null_for_null_input(): void
    {
        $this->assertNull($this->service->encrypt(null));
        $this->assertNull($this->service->decrypt(null));
    }

    public function test_returns_null_for_empty_string(): void
    {
        $this->assertNull($this->service->encrypt(''));
        $this->assertNull($this->service->decrypt(''));
    }

    public function test_encryption_is_deterministically_different(): void
    {
        $input = 'Same data';

        $e1 = $this->service->encrypt($input);
        $e2 = $this->service->encrypt($input);

        $this->assertNotEquals($e1, $e2);
        $this->assertEquals($input, $this->service->decrypt($e1));
        $this->assertEquals($input, $this->service->decrypt($e2));
    }

    public function test_encrypts_and_decrypts_long_text(): void
    {
        $original = str_repeat('Patient data with lots of medical history details. ', 100);
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertNotNull($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypts_and_decrypts_special_characters(): void
    {
        $original = "Patient: O'Brien, Diagnosis: 100mg/mL, Notes: <script>alert('xss')</script>";
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypt_array(): void
    {
        $original = ['key1' => 'value1', 'key2' => 'value2'];
        $encrypted = $this->service->encryptArray($original);
        $decrypted = $this->service->decryptArray($encrypted);

        $this->assertNotNull($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypt_array_returns_null_for_empty(): void
    {
        $this->assertNull($this->service->encryptArray(null));
        $this->assertNull($this->service->encryptArray([]));
    }

    public function test_decrypt_array_returns_null_for_null(): void
    {
        $this->assertNull($this->service->decryptArray(null));
        $this->assertNull($this->service->decryptArray(''));
    }

    public function test_decrypt_invalid_data_returns_null(): void
    {
        $this->assertNull($this->service->decrypt('not-encrypted-data'));
    }

    public function test_round_trip_preserves_utf8(): void
    {
        $original = 'রোগীর বিবরণ ও চিকিৎসা সংক্রান্ত তথ্য';
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }
}
