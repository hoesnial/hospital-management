# Hospital Management System - Comprehensive Security Audit Report

**Project**: Laravel 12 Hospital Management System  
**Date**: June 7, 2026  
**Auditor**: Security Team  
**Status**: AUDIT COMPLETE - Ready for Implementation

---

## Executive Summary

The Hospital Management System has successfully implemented **Phase 1 Security Hardening** with the following controls:

✅ **Implemented**: Password Hashing, Session Management, RBAC, Input Validation, SQL Injection Protection, XSS Protection, CSRF Protection, Rate Limiting, Secure File Upload

❌ **Not Yet Implemented**: JWT Auth, MFA/2FA, Data Encryption, Security Logging, Telegram Alerts, Suricata, Wazuh, Nginx Hardening, ModSecurity WAF, Docker Security, Security Headers, Audit Trail

---

## 1. PROJECT STRUCTURE ANALYSIS

### 1.1 Technology Stack

| Component | Details |
|-----------|---------|
| **Framework** | Laravel 12.0 |
| **PHP Version** | ^8.2 (8.3 assumed) |
| **Frontend** | Vue 3 + Inertia.js |
| **Database** | MySQL |
| **API** | REST with Sanctum |
| **Session Driver** | Database |
| **Authentication** | Session-based (web) + Sanctum (API) |
| **Password Hashing** | Argon2id |
| **File Storage** | Local filesystem |

### 1.2 Database Schema

#### Users Table
```
- id (PK)
- name
- email (UNIQUE)
- email_verified_at
- password (hashed with Argon2id)
- remember_token
- role (admin, doctor, patient, staff, diagnostic) - ENUM
- created_at, updated_at
```

#### Related Models
- **Doctor** (has_one relationship with User)
  - user_id, designation, speciality, phone, about
  
- **Staff** (has_one relationship with User)
  - user_id, name, designation, phone, about
  
- **Appointment** (patient health records)
  - booking_id, first_name, last_name, email, phone, gender, age
  - preferred_date, preferred_time, speciality, doctor_id
  - additional_notes, status
  - **Sensitive Data**: diagnosis, medical_history, patient_notes (not yet encrypted)
  
- **Prescription** (medical prescriptions)
  - appointment_id, prescription_text (not yet encrypted)
  
- **HealthCheck** (diagnostic data)
  - **Sensitive Data**: test results and findings
  
- **BookingDiagnostic** (diagnostic bookings)
  - Additional medical information
  
- **Schedule** (doctor schedules)
  - doctor_id, day_of_week, start_time, end_time
  
- **News** (hospital news)
  
- **TestResult** (diagnostic test results)
  
- **DoctorMessage** (inter-doctor communication)

---

## 2. AUTHENTICATION MECHANISM

### 2.1 Current Implementation

| Aspect | Details |
|--------|---------|
| **Primary Guard** | `web` (session-based) |
| **API Guard** | `sanctum` |
| **Provider** | Eloquent (App\Models\User) |
| **Login Route** | `POST /login` (via auth.php routes) |
| **Logout Route** | `POST /logout` (via auth.php routes) |
| **Registration** | `POST /register` with email verification required |
| **Session Timeout** | 15 minutes (reduced from 120) |
| **Session Regeneration** | Enabled after login |
| **Other Device Logout** | Enabled via `Auth::logoutOtherDevices()` |
| **Password Reset** | Email-based token reset |
| **Email Verification** | Required before dashboard access |

### 2.2 Files Involved

| File | Purpose |
|------|---------|
| `routes/auth.php` | Authentication routes (Laravel Breeze) |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Login/Logout logic |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Registration with validation |
| `app/Http/Controllers/Auth/PasswordController.php` | Password update |
| `app/Http/Middleware/Authenticate.php` | Auth guard middleware |
| `config/auth.php` | Auth configuration (guards, providers, passwords) |
| `app/Http/Requests/Auth/LoginRequest.php` | Login validation with rate limiting |
| `app/Http/Requests/Auth/RegisterRequest.php` | Registration validation with strong password rules |

### 2.3 API Authentication

- **Method**: Laravel Sanctum tokens
- **Token Generation**: Via `createToken()` method on User model
- **Middleware**: `auth:sanctum` on protected API routes
- **Current Status**: Sanctum installed but not fully utilized
- **Issue**: No dedicated JWT implementation for modern API authentication

---

## 3. AUTHORIZATION & ROLE-BASED ACCESS CONTROL

### 3.1 Roles Identified

1. **admin** - Full system access, user management
2. **doctor** - Doctor profile, schedules, appointments, prescriptions
3. **patient** - Book appointments, view medical records
4. **staff** - Support staff management
5. **diagnostic** - Diagnostic center management

### 3.2 RBAC Implementation

**Middleware**: `app/Http/Middleware/EnsureUserRole.php`
- Usage: `middleware('role:admin')`, `middleware('role:doctor')`, etc.
- Behavior: Aborts with 403 if role doesn't match

**Route Protection**:
```
✅ Admin routes protected: /admin/*
✅ Doctor routes protected: (middleware not consistently applied - ISSUE)
✅ Patient routes protected: /patient/*
✅ Staff routes protected: (minimal routes)
✅ Diagnostic routes protected: /diagnostic/*
```

**Database Ownership Checks**:
- Patient can view own appointments only
- Doctor can view own schedules and patient appointments
- Some controllers missing ownership validation (ISSUE)

### 3.3 Risk Areas - RBAC

| Issue | Severity | Details |
|-------|----------|---------|
| Doctor routes not consistently middleware-protected | MEDIUM | Doctor\ScheduleController, Doctor\AppointmentController need explicit role:doctor |
| Missing ownership checks in some DELETE operations | HIGH | Admin can delete any doctor/staff/schedule |
| Staff CRUD operations lack ownership validation | MEDIUM | Staff can potentially modify other staff data |
| No multi-tenancy isolation | MEDIUM | All doctors/staff share same data unless code explicitly filters |

---

## 4. ROUTE ANALYSIS

### 4.1 Web Routes (auth required)

```
Dashboard Routes (all roles):
✅ /dashboard - Role-based redirects

Patient Routes (/patient/*):
✅ /patient/book-appointment
✅ /patient/health-packages
✅ /patient/medical-records
✅ /patient/emergency
✅ /patient/health-tips
✅ /patient/profile
✅ /patient/appointments/* (CRUD)
✅ /patient/package-bookings/* (VIEW only)

Admin Routes (/admin/*):
✅ /admin/doctors/* (CRUD)
✅ /admin/staff/* (CRUD)
✅ /admin/schedules/* (READ, MENTION)
✅ /admin/health-checks/* (CRUD)
✅ /admin/package-bookings/* (CRUD)
✅ /admin/appointments/* (READ, UPDATE)
✅ /admin/news/* (CRUD)
✅ /admin/diagnostic-services/* (CRUD)

Doctor Routes (/doctor/* - missing middleware):
❌ Doctor/ScheduleController - No role:doctor check
❌ Doctor/AppointmentController - No role:doctor check
❌ Doctor/MessageController - No role:doctor check

Public Routes (unauthenticated):
✅ / - Welcome page
✅ /find-doctor - Doctor listing
✅ /appointment-booking - Public appointment form
✅ /news-all - News listing
✅ /diagnostic/services/* - Public diagnostic services
```

### 4.2 API Routes (auth:sanctum)

```
✅ /api/user - Get authenticated user
✅ /api/bookings - Package bookings (auth required)
✅ /api/email-check - Email uniqueness check (public)
✅ /api/health-checks - Health checks (public)
✅ /api/news/* - News (public)
✅ /api/doctors/* - Doctors (public)
✅ /api/diagnostic-services/* - Diagnostic services (public)
```

---

## 5. DATA SENSITIVITY CLASSIFICATION

### High Sensitivity (PII + Medical)

| Field | Location | Current Protection | Status |
|-------|----------|-------------------|--------|
| password | users table | Argon2id hash | ✅ Protected |
| email | users table | Stored as plain text | ⚠️ Review |
| first_name | appointments | Plain text | ❌ Not encrypted |
| last_name | appointments | Plain text | ❌ Not encrypted |
| phone | appointments, doctors, staff | Plain text | ❌ Not encrypted |
| gender | appointments | Plain text | ❌ Not encrypted |
| age | appointments | Plain text | ❌ Not encrypted |
| **diagnosis** | appointments | Plain text | ❌ **Requires encryption** |
| **medical_history** | appointments | Plain text | ❌ **Requires encryption** |
| **prescription_text** | prescriptions | Plain text | ❌ **Requires encryption** |
| **patient_notes** | appointments | Plain text | ❌ **Requires encryption** |
| test results | test_results | Plain text | ❌ **Requires encryption** |

### Medium Sensitivity

| Field | Location | Status |
|-------|----------|--------|
| speciality | doctors | Plain text (informational) |
| designation | doctors, staff | Plain text (informational) |
| about | doctors, staff | Plain text (informational) |
| additional_notes | appointments | Plain text |

---

## 6. SECURITY CONTROLS ASSESSMENT

### 6.1 Authentication Controls

| Control | Status | Evidence |
|---------|--------|----------|
| Secure password hashing (Argon2id) | ✅ Implemented | config/hashing.php, HASHING_DRIVER=argon2id |
| Password complexity requirements | ✅ Implemented | StrongPassword rule (12+ chars, uppercase, lowercase, number, special) |
| Session regeneration on login | ✅ Implemented | AuthenticatedSessionController.php line 38-39 |
| Session timeout (15 min) | ✅ Implemented | .env SESSION_LIFETIME=15 |
| Logout from other devices | ✅ Implemented | AuthenticatedSessionController.php line 40 |
| Email verification required | ✅ Implemented | Route middleware 'verified' |
| Password reset mechanism | ✅ Implemented | Laravel's built-in password reset |
| JWT/API tokens | ❌ Missing | Only Sanctum basic tokens, no custom JWT implementation |
| MFA/2FA | ❌ Missing | No TOTP or SMS verification |
| Account lockout after failed attempts | ✅ Implemented | Rate limiting (5 attempts/15 min) |
| Brute force protection | ✅ Implemented | Rate limiter on login |

### 6.2 Authorization Controls

| Control | Status | Evidence |
|---------|--------|----------|
| Role-based access control | ✅ Implemented | EnsureUserRole middleware on routes |
| Route protection | ⚠️ Partial | Admin/Patient routes protected, Doctor routes inconsistent |
| Data ownership validation | ⚠️ Partial | Implemented in some controllers, missing in others |
| Resource policies | ❌ Missing | No Laravel Policies defined |
| API authorization | ✅ Implemented | auth:sanctum on API routes |

### 6.3 Data Protection Controls

| Control | Status | Evidence |
|---------|--------|----------|
| Data encryption (at rest) | ❌ Missing | No encryption for sensitive medical data |
| Data encryption (in transit) | ⚠️ Not configured | HTTPS/TLS not enforced in config |
| File upload restrictions | ✅ Implemented | MIME type validation, size limits |
| File storage security | ⚠️ Partial | Files stored locally, not protected from direct access |
| SQL injection protection | ✅ Implemented | Parameterized queries, Eloquent ORM |
| XSS protection | ✅ Implemented | Blade escaping, DOMPurify on rich text |
| CSRF protection | ✅ Implemented | CSRF token middleware, Sanctum XSRF cookie |

### 6.4 Monitoring & Logging Controls

| Control | Status | Evidence |
|---------|--------|----------|
| Security event logging | ❌ Missing | No dedicated security logs |
| Failed login tracking | ❌ Missing | Logged in Laravel logs, not dedicated audit log |
| Admin action audit trail | ❌ Missing | No audit log for create/update/delete operations |
| Unauthorized access attempts | ❌ Missing | No tracking of 403/401 errors |
| File upload tracking | ❌ Missing | No audit of file operations |
| Password change history | ❌ Missing | No record of password changes |
| Security alerts | ❌ Missing | No real-time alerting mechanism |

### 6.5 Infrastructure Controls

| Control | Status | Evidence |
|---------|--------|----------|
| HTTPS/TLS enforcement | ⚠️ Not configured | APP_URL=http://localhost (development mode) |
| Security headers | ❌ Missing | No X-Frame-Options, X-Content-Type-Options, CSP, etc. |
| CORS configuration | ✅ Implemented | config/cors.php present |
| Reverse proxy | ❌ Missing | No Nginx configuration documented |
| Web Application Firewall | ❌ Missing | No ModSecurity/WAF rules |
| Intrusion Detection | ❌ Missing | No Suricata rules |
| Security Monitoring | ❌ Missing | No Wazuh agent |
| Docker Security | ❌ Missing | No hardened Docker configuration |

---

## 7. FILE MODIFICATION CHECKLIST FOR IMPLEMENTATION

### Phase 2 - JWT Authentication
- [ ] Create `config/jwt.php` configuration file
- [ ] Create JWT middleware: `app/Http/Middleware/JwtAuthenticate.php`
- [ ] Create `app/Http/Controllers/Api/AuthController.php` with JWT login/logout
- [ ] Add JWT routes in `routes/api.php`
- [ ] Update `.env.example` with `JWT_SECRET`, `JWT_EXPIRATION`, `JWT_REFRESH_TTL`
- [ ] Create migration for `jwt_tokens` table (optional, for blacklist)
- [ ] Create tests for JWT endpoints

### Phase 3 - MFA/2FA (TOTP)
- [ ] Create migration: `add_mfa_to_users_table.php`
  - Columns: `mfa_enabled`, `mfa_secret`, `mfa_backup_codes`, `mfa_verified_at`
- [ ] Create `app/Http/Controllers/Auth/MfaController.php`
- [ ] Create `app/Http/Requests/Auth/VerifyMfaRequest.php`
- [ ] Install TOTP package: `spomky-labs/phar-qr-code` or similar
- [ ] Create Blade templates for MFA setup and verification
- [ ] Create Vue components for MFA frontend
- [ ] Update `AuthenticatedSessionController.php` to require MFA verification
- [ ] Add MFA routes in `routes/auth.php`

### Phase 4 - Data Encryption
- [ ] Create migration to add encrypted columns or change existing columns
  - Affected tables: `appointments`, `prescriptions`, `test_results`
- [ ] Create `app/Services/EncryptionService.php`
- [ ] Update models with encryption accessors/mutators:
  - `app/Models/Appointment.php`
  - `app/Models/Prescription.php`
  - `app/Models/TestResult.php`
- [ ] Create migration helper to encrypt existing data
- [ ] Create `.env` variables: `ENCRYPTION_KEY`, `ENCRYPTION_ALGORITHM`

### Phase 5 - Security Logging
- [ ] Create migration: `create_security_logs_table.php`
- [ ] Create `app/Models/SecurityLog.php`
- [ ] Create `app/Services/SecurityLoggerService.php`
- [ ] Create event listeners:
  - `app/Listeners/LogLoginEvent.php`
  - `app/Listeners/LogLogoutEvent.php`
  - `app/Listeners/LogFailedAuthenticationEvent.php`
- [ ] Register event listeners in `app/Providers/EventServiceProvider.php`
- [ ] Update controllers to log create/update/delete operations
- [ ] Create middleware to log unauthorized access attempts
- [ ] Create dashboard view to display security logs

### Phase 6 - Telegram Alerts
- [ ] Create `config/telegram.php` configuration
- [ ] Create `app/Services/TelegramAlertService.php`
- [ ] Update `SecurityLoggerService.php` to trigger Telegram alerts
- [ ] Add `.env` variables: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`
- [ ] Create alert rules:
  - 5x failed login attempts
  - Unauthorized access
  - Suspicious file uploads
  - SQL injection attempts (if detected)
  - XSS attempts (if detected)
  - Account lockouts

### Phase 7 - Suricata Integration
- [ ] Create `docker/suricata/` directory structure
- [ ] Create `docker/suricata/Dockerfile`
- [ ] Create `docker/suricata/suricata.yaml` configuration
- [ ] Create custom rules:
  - `docker/suricata/rules/sql-injection.rules`
  - `docker/suricata/rules/xss-detection.rules`
  - `docker/suricata/rules/directory-traversal.rules`
  - `docker/suricata/rules/brute-force.rules`
- [ ] Create documentation: `docs/SURICATA_INTEGRATION.md`
- [ ] Create log parser for Suricata → Laravel logs

### Phase 8 - Wazuh Integration
- [ ] Create `docker/wazuh/` directory structure
- [ ] Create `docker/wazuh/docker-compose.yml`
- [ ] Create `docker/wazuh/config/` configurations
- [ ] Configure:
  - Laravel log monitoring
  - Authentication log monitoring
  - Suricata alert ingestion
- [ ] Create `docker/wazuh/dashboard/` example dashboard configs
- [ ] Create documentation: `docs/WAZUH_INTEGRATION.md`

### Phase 9 - Nginx Hardening
- [ ] Create `deployment/nginx/` directory
- [ ] Create `deployment/nginx/default.conf` with security headers:
  - X-Frame-Options
  - X-Content-Type-Options
  - X-XSS-Protection
  - Referrer-Policy
  - Content-Security-Policy
  - Permissions-Policy
  - HSTS (Strict-Transport-Security)
- [ ] Create `deployment/nginx/ssl.conf` for HTTPS configuration
- [ ] Create documentation: `docs/NGINX_HARDENING.md`

### Phase 10 - ModSecurity WAF
- [ ] Create `deployment/modsecurity/` directory
- [ ] Create `deployment/modsecurity/modsecurity.conf`
- [ ] Enable OWASP CRS rules
- [ ] Create `deployment/modsecurity/rules/`:
  - `sql-injection.conf`
  - `xss-rules.conf`
  - `file-upload.conf`
- [ ] Create testing documentation: `docs/MODSECURITY_TESTING.md`

### Phase 11 - Docker Security
- [ ] Create `Dockerfile` with:
  - Non-root user
  - Multi-stage build
  - Security hardening flags
- [ ] Create `docker-compose.yml` with:
  - Service isolation
  - Resource limits
  - Health checks
  - Security options (read-only filesystem, no-new-privileges)
- [ ] Create `.dockerignore` file
- [ ] Create documentation: `docs/DOCKER_SECURITY.md`

### Phase 12 - Security Headers
- [ ] Update `app/Http/Middleware/` to add response headers
- [ ] Or create `app/Http/Middleware/SecurityHeaders.php`
- [ ] Register in `app/Http/Kernel.php`
- [ ] Headers to add:
  - X-Frame-Options: DENY
  - X-Content-Type-Options: nosniff
  - X-XSS-Protection: 1; mode=block
  - Referrer-Policy: strict-origin-when-cross-origin
  - Permissions-Policy: (define permissions)
  - Content-Security-Policy: (strict policy)
  - Strict-Transport-Security: (HTTPS only)

### Phase 13 - Testing & Documentation
- [ ] Create `tests/Security/` test directory
- [ ] Create security test files:
  - `tests/Security/SqlInjectionTest.php`
  - `tests/Security/XssTest.php`
  - `tests/Security/BruteForceTest.php`
  - `tests/Security/FileUploadTest.php`
  - `tests/Security/SessionTest.php`
  - `tests/Security/RbacTest.php`
  - `tests/Security/JwtTest.php`
  - `tests/Security/MfaTest.php`
- [ ] Create `SECURITY_IMPLEMENTATION.md` with:
  - Security architecture overview
  - Threat modeling
  - Security controls checklist
  - Testing results
  - Blue team explanation
  - Red team testing guide
- [ ] Create `docs/SECURITY_TESTING_GUIDE.md`
- [ ] Create `docs/INCIDENT_RESPONSE_PLAN.md`

---

## 8. EXISTING SECURITY GAPS & RECOMMENDATIONS

### Critical Issues

| Issue | Priority | Recommendation |
|-------|----------|-----------------|
| Sensitive medical data not encrypted | **CRITICAL** | Implement Phase 4 - Data Encryption |
| No audit logging for data access/modification | **CRITICAL** | Implement Phase 5 - Security Logging |
| No real-time security alerting | **CRITICAL** | Implement Phase 6 - Telegram Alerts |
| No security headers configured | **CRITICAL** | Implement Phase 12 - Security Headers |
| No dedicated API authentication (JWT) | **HIGH** | Implement Phase 2 - JWT Authentication |

### High Priority Issues

| Issue | Recommendation |
|-------|-----------------|
| Doctor routes lack role:doctor middleware | Add explicit `middleware('role:doctor')` to doctor route groups |
| Some controllers missing ownership validation | Implement complete ownership checks in all resource controllers |
| No MFA for high-privilege operations | Implement Phase 3 - MFA/2FA |
| HTTPS/TLS not enforced | Configure HTTPS in .env and nginx |
| No WAF rules configured | Implement Phase 10 - ModSecurity WAF |
| No infrastructure monitoring | Implement Phase 7 & 8 - Suricata/Wazuh |

### Medium Priority Issues

| Issue | Recommendation |
|-------|-----------------|
| Weak file upload MIME validation | Consider additional validation using file magic bytes |
| No rate limiting on API endpoints | Add rate limiting middleware to API routes |
| Session fixation still possible via cookie | Implement secure cookie settings in config/session.php |
| No CORS origin restriction | Configure specific allowed origins in config/cors.php |
| No Content Security Policy | Add CSP header in Phase 12 |

---

## 9. OWASP TOP 10 (2021) MAPPING

| OWASP Category | Current Status | Implementation Phase |
|---|---|---|
| A1: Broken Access Control | ⚠️ Partial | Phase 1 (RBAC ok), Phase 12 (improve) |
| A2: Cryptographic Failures | ⚠️ Partial | Phase 4 (data encryption), Phase 9 (TLS) |
| A3: Injection | ✅ Addressed | Phase 7 & 10 (enhanced detection) |
| A4: Insecure Design | ⚠️ Partial | Phase 2, 3, 5, 6 (security by design) |
| A5: Security Misconfiguration | ⚠️ Partial | Phase 9, 12 (harden config) |
| A6: Vulnerable Components | ✅ Not in scope | Regular dependency updates recommended |
| A7: Authentication Failures | ✅ Addressed | Phase 3 (add MFA) |
| A8: Software Integrity Failures | ⚠️ Not addressed | Outside scope for now |
| A9: Logging & Monitoring Failures | ❌ Missing | Phase 5, 8 (implement fully) |
| A10: SSRF | ✅ Not applicable | File storage is local |

---

## 10. RECOMMENDED IMPLEMENTATION ORDER

```
Phase 1: ✅ COMPLETED (Password security, Session security, RBAC, etc.)

Phase 2: JWT Authentication
  └─ Prerequisite for secure API development
  
Phase 3: MFA/2FA
  └─ Requires Phase 2 as foundation
  
Phase 4: Data Encryption
  └─ Can be done in parallel with Phase 2-3
  
Phase 5: Security Logging
  └─ Blocks Phase 6 (Telegram requires logging)
  
Phase 6: Telegram Alerts
  └─ Requires Phase 5
  
Phase 12: Security Headers
  └─ Can be done anytime, no dependencies
  
Phase 9: Nginx Hardening
  └─ Required before Docker deployment
  
Phase 11: Docker Security
  └─ Requires Phase 9
  
Phase 7: Suricata Integration
  └─ Requires Phase 11 (Docker ready)
  
Phase 8: Wazuh Integration
  └─ Requires Phase 7 (Suricata ready)
  
Phase 10: ModSecurity WAF
  └─ Requires Phase 9 (Nginx ready)
  
Phase 13: Testing & Documentation
  └─ Final comprehensive testing after all phases
```

---

## 11. SUMMARY

### ✅ Security Features Already Implemented
- Password hashing with Argon2id
- Session management with 15-min timeout
- Role-based access control (RBAC)
- Input validation via Form Requests
- SQL Injection protection (parameterized queries)
- XSS protection (Blade escaping, DOMPurify)
- CSRF protection
- Rate limiting on login/API
- Secure file upload (MIME validation)
- Email verification
- Password reset mechanism

### ❌ Security Features Not Yet Implemented
1. JWT Authentication for modern API access
2. Multi-Factor Authentication (TOTP)
3. Data encryption for sensitive medical records
4. Security event logging & audit trail
5. Real-time Telegram alerts for security events
6. Intrusion detection (Suricata rules)
7. Security monitoring (Wazuh)
8. HTTPS/TLS hardening (Nginx)
9. Web Application Firewall (ModSecurity)
10. Docker security hardening
11. Security response headers (CSP, HSTS, etc.)
12. Comprehensive security testing

### Risk Level: **MEDIUM-HIGH**
- Unencrypted medical data (PHI/PII) at rest
- No audit trail for data access
- No real-time security monitoring
- Missing security headers
- Infrastructure not hardened

---

## Next Steps

1. **Review this audit report** with your security team
2. **Prioritize implementations** based on risk assessment
3. **Begin Phase 2** with JWT Authentication
4. **Proceed through phases** in recommended order
5. **Test each phase** before moving to the next

**Estimated Timeline**: 3-4 weeks for all phases (Phase 2-13)

---

## Appendix: File Inventory

### Controllers (23)
- Auth: AuthenticatedSessionController, RegisteredUserController, PasswordController, etc.
- Admin: DoctorController, StaffController, NewsController, etc.
- Doctor: ScheduleController, AppointmentController, MessageController
- Patient: AppointmentController
- Diagnostic: DiagnosticServiceController, DiagnosticBookingController
- API: HealthCheckController, PackageBookingController, etc.

### Models (13)
- User, Doctor, Staff, Patient (implied via role)
- Appointment, Prescription, Schedule
- News, HealthCheck, PackageBooking
- DiagnosticService, BookingDiagnostic, TestResult
- DoctorMessage

### Middleware (11)
- Authenticate, EncryptCookies, EnsureUserRole, HandleInertiaRequests
- PreventRequestsDuringMaintenance, RedirectIfAuthenticated, TrimStrings
- TrustHosts, TrustProxies, ValidateSignature, VerifyCsrfToken

### Routes
- Web: ~40+ routes (dashboard, patient, admin, doctor, diagnostic)
- API: ~10 routes
- Auth: ~8 routes (login, logout, register, password reset, email verification)

---

**Audit Completed**: June 7, 2026  
**Next Review Date**: After Phase 13 completion
