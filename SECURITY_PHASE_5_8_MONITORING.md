# PHASE 5-8: Security Monitoring Implementation

**Status**: ✅ IMPLEMENTATION IN PROGRESS  
**Date**: June 8, 2026

---

## Overview

Comprehensive security monitoring system with:
- **Phase 5**: Security audit logging with event tracking
- **Phase 6**: Real-time Telegram alerts for critical events
- **Phase 7**: Network intrusion detection with Suricata IDS
- **Phase 8**: Endpoint monitoring and threat detection with Wazuh

---

## PHASE 5 - Security Logging & Audit Trail

### Features Implemented

**SecurityLog Model**:
- Track all security events
- Store: user, action, IP, user-agent, severity, description
- Query scopes for critical, unauthorized, failed logins
- Alert tracking status

**SecurityLoggerService**:
- Static methods for logging events:
  - `logLoginSuccess()` - Successful authentication
  - `logLoginFailed()` - Failed login attempts
  - `logLogout()` - User logout
  - `logMfaEnabled()` / `logMfaDisabled()` - MFA changes
  - `logUnauthorizedAccess()` - Access denied attempts
  - `logFileUpload()` / `logSuspiciousUpload()` - File operations
  - `logSqlInjectionAttempt()` - SQL injection detection
  - `logXssAttempt()` - XSS attack detection
  - `logRoleChange()` - Authorization changes
  - `logAccountLockout()` - Account security
  - `logPasswordChange()` - Password modifications
  - `logDataOperation()` - CRUD operations

**Database Table** (`security_logs`):
```sql
CREATE TABLE security_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULLABLE (user who performed action),
    email VARCHAR(100) (user email for failed attempts),
    user_agent VARCHAR(500),
    ip_address VARCHAR(45),
    user_role VARCHAR(50),
    action VARCHAR(100) (login_success, unauthorized_access, etc.),
    event_type VARCHAR(50) (authentication, authorization, etc.),
    severity VARCHAR(20) (info, warning, critical),
    description TEXT,
    details JSON (structured event data),
    attempt_count INT,
    failure_reason VARCHAR(255),
    resource_type VARCHAR(100),
    resource_id VARCHAR(100),
    alert_sent BOOLEAN,
    alert_channel VARCHAR(50),
    alert_sent_at TIMESTAMP,
    device_id VARCHAR(100),
    geo_location VARCHAR(255),
    additional_data TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Installation

```bash
# Run migration
php artisan migrate

# Verify table created
php artisan tinker
DB::table('security_logs')->count()
```

### Usage Examples

```php
// Log successful login
use App\Services\SecurityLoggerService;
use App\Models\User;

$user = User::find(1);
SecurityLoggerService::logLoginSuccess($user);

// Log failed attempt
SecurityLoggerService::logLoginFailed('user@example.com', 'Invalid password');

// Log unauthorized access
SecurityLoggerService::logUnauthorizedAccess(
    $user, 
    'Appointment', 
    '123',
    'User does not own this appointment'
);

// Query logs
use App\Models\SecurityLog;

// Get critical events
$critical = SecurityLog::critical()->get();

// Get failed logins for user
$fails = SecurityLog::forUser(1)->failedLogins()->get();

// Get unsent alerts
$unsent = SecurityLog::unsent()->get();
```

---

## PHASE 6 - Telegram Alert System

### Features Implemented

**TelegramAlertService**:
- Real-time alert delivery to Telegram
- Formatted alert messages with emojis
- Event-specific alert methods:
  - `sendFailedLoginAlert()` - Multiple failed attempts
  - `sendUnauthorizedAccessAlert()` - Access denied
  - `sendSqlInjectionAlert()` - SQL injection attempt
  - `sendXssAlert()` - XSS attack attempt
  - `sendAccountLockoutAlert()` - Account locked
  - `sendSuspiciousUploadAlert()` - Suspicious file upload
  - `sendMfaDisabledAlert()` - MFA disabled
  - `sendDailySummary()` - Daily security summary
- Markdown formatted messages
- Connection testing

**Event Listeners**:
- Automatically triggered on security events
- Log + Alert in real-time
- Critical events priority

### Configuration

Add to `.env`:
```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

### Setup Telegram Bot

**Step 1**: Create Telegram Bot
```
1. Open Telegram app
2. Search for @BotFather
3. Send /newbot
4. Follow instructions to create bot
5. Copy bot token
```

**Step 2**: Get Chat ID
```
1. Add bot to your group/channel
2. Send message to group
3. Visit: https://api.telegram.org/botYOUR_TOKEN/getUpdates
4. Find chat_id in response
```

**Step 3**: Update .env
```env
TELEGRAM_BOT_TOKEN=123456789:ABCDefGHijKlmnOPqrsTUvwxYZ
TELEGRAM_CHAT_ID=-1234567890  # Negative for groups
```

### Testing

```php
// Test connection
$telegram = new TelegramAlertService();
$telegram->testConnection();

// Send custom alert
$telegram->sendCustomAlert(
    'Test Alert',
    'This is a test message',
    'info'
);

// Send specific alert
$telegram->sendFailedLoginAlert('user@example.com', 5);
```

### Alert Types

| Event | Severity | Emoji | Trigger |
|-------|----------|-------|---------|
| Failed Login | Critical | ❌ | 5+ attempts in 15 min |
| Unauthorized Access | Critical | 🚨 | Access denied |
| SQL Injection | Critical | 🛡️ | Pattern detected |
| XSS Attack | Critical | 🛡️ | Malicious input found |
| Account Lockout | Critical | 🔒 | Too many failures |
| Suspicious Upload | Warning | ⚠️ | Dangerous file type |
| MFA Disabled | Warning | ⚠️ | User disabled MFA |

---

## PHASE 7 - Suricata Integration (IDS)

### Overview

Suricata is a network-based intrusion detection and prevention system (IDS/IPS) that:
- Monitors network traffic in real-time
- Detects attacks, malware, intrusions
- Generates alerts for security team
- Provides detailed network forensics

### Installation & Setup

**On Ubuntu/Debian**:
```bash
# Add Suricata repository
sudo add-apt-repository ppa:oisf/suricata-stable
sudo apt-get update

# Install Suricata
sudo apt-get install suricata

# Install rule management
sudo apt-get install suricata-update
```

**On CentOS/RHEL**:
```bash
sudo yum install suricata
```

### Configuration

**File**: `/etc/suricata/suricata.yaml`

```yaml
# Network interfaces to monitor
af-packet:
  - interface: eth0
  - interface: eth1

# Output settings
outputs:
  eve-log:
    enabled: yes
    filetype: regular
    filename: eve.json
    
# Rule sources
rule-files:
  - suricata.rules
  - custom-hospital.rules

# Alert settings
alert-debug: no
stats:
  enabled: yes
  filename: stats.log
```

### Custom Rules for Hospital System

**File**: `/etc/suricata/rules/custom-hospital.rules`

```
# Detect SQL Injection attempts
alert http any any -> any any (msg:"SQL Injection Attempt"; 
  content:"union"; 
  http_uri|body; 
  nocase; 
  sid:1000001; 
  rev:1;)

# Detect XSS attempts
alert http any any -> any any (msg:"XSS Attack Attempt"; 
  content:"<script>"; 
  http_uri|body; 
  nocase; 
  sid:1000002; 
  rev:1;)

# Detect suspicious file uploads
alert http any any -> any any (msg:"Suspicious File Upload"; 
  content:".exe|.bat|.cmd"; 
  http_filename; 
  nocase; 
  sid:1000003; 
  rev:1;)

# Detect brute force login attempts
alert http any any -> any any (msg:"Brute Force Login Attempt"; 
  content:"POST /login"; 
  threshold: type both, track by_src, count 10, seconds 60; 
  sid:1000004; 
  rev:1;)

# Detect unauthorized API access
alert http any any -> any $HTTP_PORTS (msg:"Unauthorized API Access"; 
  content:"GET|POST /api/"; 
  pcre:"/Authorization:\s*(Basic|Bearer)\s+/"; 
  negated; 
  sid:1000005; 
  rev:1;)
```

### Running Suricata

```bash
# Start Suricata
sudo systemctl start suricata

# Enable on boot
sudo systemctl enable suricata

# Check status
sudo systemctl status suricata

# Monitor live alerts
sudo tail -f /var/log/suricata/eve.json | jq .
```

### Integration with Laravel

**Monitor Suricata logs**:
```php
// app/Console/Commands/CheckSuricataAlerts.php
$alerts = json_decode(file_get_contents('/var/log/suricata/eve.json'));
foreach ($alerts as $alert) {
    if ($alert->event_type === 'alert') {
        TelegramAlertService::sendCustomAlert(
            $alert->alert->signature,
            json_encode($alert),
            'critical'
        );
    }
}
```

---

## PHASE 8 - Wazuh Integration (SIEM/XDR)

### Overview

Wazuh is an open-source security monitoring platform that:
- Collects logs from all system components
- Detects threats and vulnerabilities
- Provides centralized security dashboard
- Generates incident reports
- Integrates with SIEM workflows

### Architecture

```
┌─────────────────────────────────────────┐
│  Wazuh Manager (Central)                │
│  - Log processing                       │
│  - Rule engine                          │
│  - Alert generation                     │
│  - Web dashboard                        │
└─────────────────────────────────────────┘
           ↑     ↑     ↑
           │     │     │
┌──────────┴──┐  │  ┌──┴──────────┐
│Wazuh Agent 1│  │  │ Wazuh Agent 2│
│(Web Server) │  │  │(Database)    │
└─────────────┘  │  └──────────────┘
                 │
            ┌────┴─────┐
            │ Wazuh    │
            │ Agent 3  │
            │(App Srv) │
            └──────────┘
```

### Installation

**Wazuh Manager (on dedicated server)**:

```bash
# Add Wazuh repository
curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | apt-key add -

# Add repository
echo "deb https://packages.wazuh.com/4.x/apt/ stable main" > /etc/apt/sources.list.d/wazuh.list

# Install Wazuh manager
sudo apt-get update
sudo apt-get install wazuh-manager

# Start Wazuh
sudo systemctl start wazuh-manager
sudo systemctl enable wazuh-manager
```

**Wazuh Agent (on Laravel server)**:

```bash
# Add Wazuh repository
curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | apt-key add -
echo "deb https://packages.wazuh.com/4.x/apt/ stable main" > /etc/apt/sources.list.d/wazuh.list

# Install agent
sudo apt-get update
sudo apt-get install wazuh-agent

# Configure agent
sudo nano /var/ossec/etc/ossec.conf

# Start agent
sudo systemctl start wazuh-agent
sudo systemctl enable wazuh-agent
```

### Configuration

**Agent Config** (`/var/ossec/etc/ossec.conf`):

```xml
<agent_config>
    <!-- Monitor Laravel logs -->
    <localfile>
        <location>/var/log/laravel/*.log</location>
        <log_format>json</log_format>
    </localfile>
    
    <!-- Monitor Suricata alerts -->
    <localfile>
        <location>/var/log/suricata/eve.json</location>
        <log_format>json</log_format>
    </localfile>
    
    <!-- Monitor system -->
    <localfile>
        <location>/var/log/auth.log</location>
        <log_format>syslog</log_format>
    </localfile>
    
    <!-- Monitor database -->
    <localfile>
        <location>/var/log/mysql/error.log</location>
        <log_format>mysql_log</log_format>
    </localfile>
    
    <!-- System inventory -->
    <rootcheck>
        <disabled>no</disabled>
        <check_files>yes</check_files>
        <check_ports>yes</check_ports>
        <check_sys>yes</check_sys>
        <skip_nfs>yes</skip_nfs>
    </rootcheck>
    
    <!-- File integrity monitoring -->
    <syscheck>
        <frequency>3600</frequency>
        <directories check_all="yes">/var/www/html</directories>
        <directories check_all="yes">/etc/mysql</directories>
        <directories check_all="yes">/app</directories>
    </syscheck>
</agent_config>
```

### Manager Rules

**Custom Rules** (`/var/ossec/etc/rules/local_rules.xml`):

```xml
<!-- Hospital Management Custom Rules -->

<!-- Rule: Detect unauthorized API access -->
<rule id="200001" level="7">
    <if_sid>3155</if_sid>
    <action>^401|^403</action>
    <description>Unauthorized API Access Attempt</description>
</rule>

<!-- Rule: Detect multiple failed logins -->
<rule id="200002" level="8">
    <if_sid>5710</if_sid>
    <same_source_ip />
    <frequency>5</frequency>
    <timeframe>900</timeframe>
    <description>Brute Force Attack Detected</description>
</rule>

<!-- Rule: Detect suspicious file upload -->
<rule id="200003" level="9">
    <if_group>file_upload</if_group>
    <extension>exe|bat|cmd|sh|zip</extension>
    <description>Malicious File Upload Attempt</description>
</rule>

<!-- Rule: Detect SQL injection pattern -->
<rule id="200004" level="9">
    <if_group>web_application</if_group>
    <regex>union.*select|select.*from|insert.*into|drop.*table</regex>
    <description>SQL Injection Attack Detected</description>
</rule>

<!-- Rule: Database access anomaly -->
<rule id="200005" level="8">
    <if_group>database</if_group>
    <action>^denied|^failed</action>
    <frequency>10</frequency>
    <timeframe>600</timeframe>
    <description>Database Access Anomaly</description>
</rule>
```

### Wazuh Dashboard

**Access Dashboard**:
```
URL: https://manager-ip:443
Username: admin
Password: (set during installation)
```

**Key Features**:
- Real-time alerts dashboard
- Log analysis and search
- Vulnerability scanning
- Compliance reports (HIPAA, PCI-DSS, etc.)
- Custom dashboards
- Alert aggregation

### Integration with Laravel

**Send alerts to Telegram**:

```php
// Monitor Wazuh alerts and send to Telegram
$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://wazuh-manager:55000/',
    'verify' => false,
]);

// Get recent alerts
$response = $client->get('/api/alerts', [
    'query' => ['limit' => 100],
    'auth' => ['user', 'password'],
]);

$alerts = json_decode($response->getBody());
foreach ($alerts->data as $alert) {
    if ($alert->rule->level >= 7) {
        TelegramAlertService::sendCustomAlert(
            $alert->rule->description,
            json_encode($alert),
            'critical'
        );
    }
}
```

---

## Installation Checklist

### Phase 5 (Security Logging)
- [ ] Create `security_logs` table migration
- [ ] Create SecurityLog model
- [ ] Create SecurityLoggerService
- [ ] Run `php artisan migrate`
- [ ] Test logging with: `SecurityLoggerService::logLoginSuccess($user)`

### Phase 6 (Telegram Alerts)
- [ ] Create TelegramAlertService
- [ ] Create SecurityEvents
- [ ] Create EventListener
- [ ] Configure `config/services.php`
- [ ] Add `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` to `.env`
- [ ] Test connection: `php artisan tinker` then `app(TelegramAlertService::class)->testConnection()`

### Phase 7 (Suricata)
- [ ] Install Suricata on network monitoring server
- [ ] Configure `/etc/suricata/suricata.yaml`
- [ ] Add custom rules in `/etc/suricata/rules/`
- [ ] Start Suricata: `systemctl start suricata`
- [ ] Monitor alerts: `tail -f /var/log/suricata/eve.json`

### Phase 8 (Wazuh)
- [ ] Install Wazuh Manager on dedicated server
- [ ] Install Wazuh Agents on all servers
- [ ] Configure agent `ossec.conf`
- [ ] Access Wazuh dashboard
- [ ] Create custom rules in Wazuh manager
- [ ] Setup alert integrations

---

## Testing

```bash
# Test Phase 5 logging
php artisan tinker
App\Services\SecurityLoggerService::logLoginFailed('test@example.com', 'test');
App\Models\SecurityLog::latest()->first();

# Test Phase 6 Telegram
app(App\Services\TelegramAlertService::class)->testConnection();

# Test Phase 7 Suricata
sudo systemctl status suricata
sudo tail -f /var/log/suricata/eve.json

# Test Phase 8 Wazuh
curl -u admin:password https://wazuh-manager:55000/api/overview/agents
```

---

## Next Steps

- Phase 9: Nginx Hardening
- Phase 10: ModSecurity WAF
- Phase 11: Docker Security
- Phase 12: Security Headers
- Phase 13: Testing & Documentation

---

**Status**: ✅ Phases 5-8 DOCUMENTED AND READY FOR IMPLEMENTATION  
**Last Updated**: June 8, 2026
