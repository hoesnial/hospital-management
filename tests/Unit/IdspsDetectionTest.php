<?php

namespace Tests\Unit;

use App\Services\IdspsService;
use Tests\TestCase;

class IdspsDetectionTest extends TestCase
{
    protected IdspsService $idsps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idsps = new IdspsService();
    }

    public function test_detects_sql_injection_union_select(): void
    {
        $result = $this->idsps->checkInput("1' UNION SELECT * FROM users--", 'search');
        $this->assertNotNull($result, 'Should detect UNION SELECT SQLi');
        $this->assertStringContainsString('SQL injection', $result);
    }

    public function test_detects_sql_injection_or_1_equals_1(): void
    {
        $result = $this->idsps->checkInput("' OR '1'='1", 'email');
        $this->assertNotNull($result, 'Should detect OR 1=1 SQLi');
        $this->assertStringContainsString('SQL injection', $result);
    }

    public function test_detects_sql_injection_drop_table(): void
    {
        $result = $this->idsps->checkInput('; DROP TABLE users;', 'input');
        $this->assertNotNull($result, 'Should detect DROP TABLE SQLi');
    }

    public function test_detects_sql_injection_sleep(): void
    {
        $result = $this->idsps->checkInput('SLEEP(5)', 'input');
        $this->assertNotNull($result, 'Should detect SLEEP SQLi');
    }

    public function test_detects_sql_injection_benchmark(): void
    {
        $result = $this->idsps->checkInput('BENCHMARK(1000000,MD5(1))', 'input');
        $this->assertNotNull($result, 'Should detect BENCHMARK SQLi');
    }

    public function test_detects_sql_information_schema(): void
    {
        $result = $this->idsps->checkInput('INFORMATION_SCHEMA.TABLES', 'input');
        $this->assertNotNull($result, 'Should detect INFORMATION_SCHEMA');
    }

    public function test_detects_xss_script_tag(): void
    {
        $result = $this->idsps->checkInput('<script>alert("xss")</script>', 'content');
        $this->assertNotNull($result, 'Should detect <script> XSS');
        $this->assertStringContainsString('XSS', $result);
    }

    public function test_detects_xss_javascript_protocol(): void
    {
        $result = $this->idsps->checkInput('javascript:alert(1)', 'url');
        $this->assertNotNull($result, 'Should detect javascript: XSS');
        $this->assertStringContainsString('XSS', $result);
    }

    public function test_detects_xss_onerror_handler(): void
    {
        $result = $this->idsps->checkInput('<img src=x onerror=alert(1)>', 'content');
        $this->assertNotNull($result, 'Should detect onerror XSS');
    }

    public function test_detects_xss_onload_handler(): void
    {
        $result = $this->idsps->checkInput('<body onload=alert(1)>', 'content');
        $this->assertNotNull($result, 'Should detect onload XSS');
    }

    public function test_detects_xss_iframe(): void
    {
        $result = $this->idsps->checkInput('<iframe src="http://evil.com"></iframe>', 'content');
        $this->assertNotNull($result, 'Should detect iframe XSS');
    }

    public function test_detects_xss_alert_function(): void
    {
        $result = $this->idsps->checkInput('alert(document.cookie)', 'text');
        $this->assertNotNull($result, 'Should detect alert() XSS');
    }

    public function test_detects_xss_eval(): void
    {
        $result = $this->idsps->checkInput('eval("malicious code")', 'code');
        $this->assertNotNull($result, 'Should detect eval() XSS');
    }

    public function test_detects_xss_document_cookie(): void
    {
        $result = $this->idsps->checkInput('document.cookie', 'input');
        $this->assertNotNull($result, 'Should detect document.cookie XSS');
    }

    public function test_detects_xss_window_location(): void
    {
        $result = $this->idsps->checkInput('window.location="http://evil.com"', 'url');
        $this->assertNotNull($result, 'Should detect window.location XSS');
    }

    public function test_detects_path_traversal_basic(): void
    {
        $result = $this->idsps->checkInput('../../../etc/passwd', 'file');
        $this->assertNotNull($result, 'Should detect path traversal');
        $this->assertStringContainsString('Path traversal', $result);
    }

    public function test_detects_path_traversal_windows(): void
    {
        $result = $this->idsps->checkInput('..\\..\\windows\\system32\\config', 'path');
        $this->assertNotNull($result, 'Should detect Windows path traversal');
    }

    public function test_detects_path_traversal_encoded(): void
    {
        $result = $this->idsps->checkInput('%2e%2e%2f%2e%2e%2fetc/passwd', 'file');
        $this->assertNotNull($result, 'Should detect URL-encoded path traversal');
    }

    public function test_detects_path_traversal_etc_passwd(): void
    {
        $result = $this->idsps->checkInput('/etc/passwd', 'path');
        $this->assertNotNull($result, 'Should detect /etc/passwd reference');
    }

    public function test_detects_command_injection_semicolon(): void
    {
        $result = $this->idsps->checkInput('; whoami', 'cmd');
        $this->assertNotNull($result, 'Should detect semicolon command injection');
        $this->assertStringContainsString('Command injection', $result);
    }

    public function test_detects_command_injection_backtick(): void
    {
        $result = $this->idsps->checkInput('`ls -la`', 'input');
        $this->assertNotNull($result, 'Should detect backtick command injection');
    }

    public function test_detects_command_injection_pipe(): void
    {
        $result = $this->idsps->checkInput('| cat /etc/passwd', 'input');
        $this->assertNotNull($result, 'Should detect pipe command injection');
    }

    public function test_detects_command_injection_and_operator(): void
    {
        $result = $this->idsps->checkInput('&& whoami', 'input');
        $this->assertNotNull($result, 'Should detect && command injection');
    }

    public function test_detects_suspicious_user_agent_sqlmap(): void
    {
        $result = $this->idsps->checkUserAgent('sqlmap/1.5.2.3 (http://sqlmap.org)');
        $this->assertNotNull($result, 'Should detect sqlmap User-Agent');
        $this->assertStringContainsString('Suspicious User-Agent', $result);
    }

    public function test_detects_suspicious_user_agent_nikto(): void
    {
        $result = $this->idsps->checkUserAgent('Mozilla/5.0 (Nikto/2.5.0)');
        $this->assertNotNull($result, 'Should detect Nikto User-Agent');
    }

    public function test_detects_suspicious_user_agent_nmap(): void
    {
        $result = $this->idsps->checkUserAgent('Nmap Scripting Engine');
        $this->assertNotNull($result, 'Should detect Nmap User-Agent');
    }

    public function test_clean_input_returns_null(): void
    {
        $result = $this->idsps->checkInput('John Doe', 'name');
        $this->assertNull($result, 'Clean input should not be flagged');
    }

    public function test_clean_email_returns_null(): void
    {
        $result = $this->idsps->checkInput('patient@example.com', 'email');
        $this->assertNull($result, 'Clean email should not be flagged');
    }

    public function test_clean_medical_notes_returns_null(): void
    {
        $result = $this->idsps->checkInput(
            'Patient has high blood pressure. Prescribed 50mg of Losartan daily.',
            'notes'
        );
        $this->assertNull($result, 'Clean medical notes should not be flagged');
    }

    public function test_non_string_input_returns_null(): void
    {
        $this->assertNull($this->idsps->checkInput(12345, 'id'));
        $this->assertNull($this->idsps->checkInput(['array', 'data'], 'items'));
    }

    public function test_detects_excessive_input_length(): void
    {
        $result = $this->idsps->checkInput(str_repeat('A', 50000), 'large_field');
        $this->assertNotNull($result, 'Should detect excessive input length');
    }
}
