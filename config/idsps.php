<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IDS/IPS Mode
    |--------------------------------------------------------------------------
    |
    | 'detect' - Log only (Intrusion Detection)
    | 'prevent' - Block malicious requests (Intrusion Prevention)
    | 'off' - Disabled
    |
    */
    'mode' => env('IDSPS_MODE', 'detect'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of suspicious requests before temporary IP block.
    |
    */
    'max_suspicious_requests' => 10,

    /*
    |--------------------------------------------------------------------------
    | Block Duration (minutes)
    |--------------------------------------------------------------------------
    |
    | Duration to temporarily block an IP after exceeding max suspicious requests.
    |
    */
    'block_duration' => 30,

    /*
    |--------------------------------------------------------------------------
    | Maximum Input Length
    |--------------------------------------------------------------------------
    |
    | Reject inputs exceeding this length (prevents buffer overflow / DoS).
    |
    */
    'max_input_length' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Suspicious User-Agents
    |--------------------------------------------------------------------------
    |
    | User-Agent strings commonly used by scanners, bots, and attack tools.
    |
    */
    'suspicious_user_agents' => [
        'sqlmap',
        'nmap',
        'nikto',
        'wpscan',
        'acunetix',
        'burpsuite',
        'openvas',
        'nessus',
        'owasp',
        'dirbuster',
        'gobuster',
        'wfuzz',
        'hydra',
        'medusa',
        'aircrack',
        'metasploit',
        'masscan',
        'zmap',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'ruby',
        'curl',
        'wget',
        'libwww-perl',
        'scrapy',
        'masscan',
        'fuck',
        'havij',
        'pyloader',
        'crawl',
        'scanner',
        'nikto',
        'paros',
        'webinspect',
        'appscan',
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL Injection Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expressions to detect SQL injection attempts.
    |
    */
    'sql_injection_patterns' => [
        '/\b(UNION\s+ALL\s+SELECT|UNION\s+SELECT)\b/i',
        '/\bSELECT\s+.*\s+FROM\s+.*\s+WHERE/i',
        '/\b(INSERT\s+INTO|DELETE\s+FROM|UPDATE\s+.*\s+SET|DROP\s+TABLE|DROP\s+DATABASE|TRUNCATE|ALTER\s+TABLE|CREATE\s+TABLE)\b/i',
        '/(\'?\s*)?\bOR\s+\d+\s*=\s*\d+/i',
        '/(\'?\s*)?\bOR\s+\'[^\']*\'\s*=\s*\'/i',
        '/(\'?\s*)?\bAND\s+\d+\s*=\s*\d+/i',
        '/(\'?\s*)?\bAND\s+\'[^\']*\'\s*=\s*\'/i',
        '/\b(SLEEP|BENCHMARK|WAITFOR\s+DELAY|PG_SLEEP)\s*\(/i',
        '/\b(EXEC\s*\(|EXECUTE\s*\(|EXEC\s+sp_)/i',
        '/\b(INFORMATION_SCHEMA|MYSQL\.PROC|SYSOBJECTS|SYSCOLUMNS)\b/i',
        '/\b(LOAD_FILE|INTO\s+OUTFILE|INTO\s+DUMPFILE)\b/i',
        '/\b(CHAR\s*\(|CONCAT\s*\(|CONCAT_WS\s*\(|GROUP_CONCAT\s*\()/i',
        '/\b(0x[0-9a-f]{4,})\b/i',
        '/[\'"]\s*\bOR\b\s*[\'"]\s*/i',
        '/\b(HAVING|OFFSET)\b.*\bOR\b/i',
        '/;\s*DROP\s/i',
        '/\b(DBMS_|UTL_|SYS\.|ALL_|USER_|DBA_|V\$)/i',
        '/\b(EXTRACTVALUE|UPDATEXML)\s*\(/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | XSS Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expressions to detect Cross-Site Scripting attempts.
    |
    */
    'xss_patterns' => [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/javascript\s*:/i',
        '/onerror\s*=/i',
        '/onload\s*=/i',
        '/onclick\s*=/i',
        '/onmouseover\s*=/i',
        '/onfocus\s*=/i',
        '/onblur\s*=/i',
        '/onchange\s*=/i',
        '/onsubmit\s*=/i',
        '/onreset\s*=/i',
        '/onselect\s*=/i',
        '/onkeydown\s*=/i',
        '/onkeypress\s*=/i',
        '/onkeyup\s*=/i',
        '/<iframe\b[^>]*>/i',
        '/<embed\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/<applet\b[^>]*>/i',
        '/<meta\b[^>]*http-equiv\s*=/i',
        '/<link\b[^>]*href\s*=/i',
        '/<img\b[^>]*src\s*=/i',
        '/<svg\b[^>]*onload\s*=/i',
        '/alert\s*\(/i',
        '/confirm\s*\(/i',
        '/prompt\s*\(/i',
        '/document\.(cookie|domain|location|write|writeln)/i',
        '/window\.(location|name|status)/i',
        '/fromCharCode/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
        '/<[^>]*style\s*=[^>]*expression\s*\(/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/<base\b[^>]*href\s*=/i',
        '/<form\b[^>]*action\s*=[^>]*https?:\/\//i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Traversal Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expressions to detect directory traversal attempts.
    |
    */
    'path_traversal_patterns' => [
        '/\.\.\//',
        '/\.\.\\\\/',
        '/%2e%2e%2f/i',
        '/%2e%2e\\\\/i',
        '/%252e%252e%252f/i',
        '/\.\.%00/',
        '/\.\.\\\\(\\\\)+/',
        '/%c0%ae%c0%ae/i',
        '/%c0\.\./i',
        '/\.\.\\/\.\.\\//',
        '/~root/',
        '/etc\/passwd/',
        '/etc\/shadow/',
        '/windows\\\\(system32|config|temp)/i',
        '/boot\.ini/',
        '/win\.ini/',
        '/\.\.\/\.\.\/\.\.\/\.\.\//',
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Injection Patterns
    |--------------------------------------------------------------------------
    |
    | Regular expressions to detect shell command injection attempts.
    |
    */
    'command_injection_patterns' => [
        '/;\s*(ls|dir|cat|tac|more|less|head|tail|whoami|id|pwd|uname|ifconfig|ipconfig|netstat|ps|top|kill|chmod|chown|wget|curl|nc|ncat|bash|sh|powershell|cmd|python|perl|php|ruby)(\s|$)/i',
        '/`[^`]+`/',
        '/\$\s*\([^)]+\)/',
        '/\|(\s*(ls|dir|cat|tac|more|less|head|tail|whoami|id|pwd|uname|ifconfig|ipconfig|netstat))+/i',
        '/&&(\s*(ls|dir|cat|tac|more|less|head|tail|whoami|id))+/i',
        '/\|\|\s*(ls|dir|cat|tac|more|less|head|tail|whoami|id)/i',
        '/>\s*\/dev\/(tcp|udp)/i',
        '/>\s*\/(tmp|var\/tmp)\//i',
        '/2>&1/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths to Exclude from IDS/IPS
    |--------------------------------------------------------------------------
    |
    | These URL patterns will be skipped by the middleware.
    | Useful for excluding legitimate file uploads or API endpoints.
    |
    */
    'excluded_paths' => [
        '/api/auth/login',
        '/api/auth/refresh',
        '/api/auth/register',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/storage/',
        '/images/',
        '/build/',
        '/_debugbar/',
        '/telescope/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelisted IPs
    |--------------------------------------------------------------------------
    |
    | These IPs will never be blocked or inspected.
    |
    */
    'whitelisted_ips' => [
        '127.0.0.1',
        '::1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklisted IPs
    |--------------------------------------------------------------------------
    |
    | These IPs will always be blocked.
    |
    */
    'blacklisted_ips' => [],

    /*
    |--------------------------------------------------------------------------
    | Alert Threshold
    |--------------------------------------------------------------------------
    |
    | Send Telegram alert only after this many detections within the window.
    |
    */
    'alert_threshold' => 3,

    /*
    |--------------------------------------------------------------------------
    | Maximum Request Rate (requests per minute)
    |--------------------------------------------------------------------------
    |
    | If an IP exceeds this request rate, trigger a detection event.
    | Set to 0 to disable this check.
    |
    */
    'max_request_rate' => 120,

];
