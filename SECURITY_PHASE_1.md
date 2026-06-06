# Security Phase 1 — Implementation Report

## Overview

Security hardening for the Laravel 12 + Vue 3 (Inertia.js) Hospital Management System. All changes are additive — no existing functionality was removed or redesigned.

---

## 1. Password Security Hardening

### Implemented

| Control | Detail |
|---------|--------|
| **Hashing Algorithm** | Switched from Bcrypt to **Argon2id** via `config/hashing.php` |
| **Argon2id Parameters** | Memory: 64MB, Time: 4, Threads: 3 |
| **Minimum Length** | 12 characters |
| **Complexity Rules** | Uppercase, Lowercase, Number, Special character |
| **Validation Points** | Registration, Password change, Admin doctor/staff creation |

### Files Modified

| File | Change |
|------|--------|
| `config/hashing.php` | **NEW** — Argon2id driver configuration |
| `.env` | Removed `BCRYPT_ROUNDS=12`, added `HASHING_DRIVER=argon2id` |
| `app/Rules/StrongPassword.php` | **NEW** — Custom validation rule enforcing 12+ chars, uppercase, lowercase, number, special char |
| `app/Http/Requests/Auth/RegisterRequest.php` | **NEW** — Replaces inline validation with Form Request using `StrongPassword` |
| `app/Http/Requests/Auth/PasswordUpdateRequest.php` | **NEW** — Replaces inline validation with Form Request using `StrongPassword` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Uses `RegisterRequest` |
| `app/Http/Controllers/Auth/PasswordController.php` | Uses `PasswordUpdateRequest` |
| `app/Http/Controllers/Admin/DoctorController.php` | Uses `StrongPassword` rule |
| `app/Http/Controllers/Admin/StaffController.php` | Uses `StrongPassword` rule |

### Risk Mitigated

- **OWASP Top 10: A2 — Cryptographic Failures**
- Weak passwords that are easily brute-forced or guessed
- Bcrypt lacks memory-hardness properties of Argon2id

### Pentesting Scenarios

- Attempt registration with `password`, `Password1`, `Pass1234` — all should be rejected
- Verify password hashes in database use `$argon2id$` prefix
- Time password hashing operations for timing analysis

---

## 2. Session Security

### Implemented

| Control | Detail |
|---------|--------|
| **Session Driver** | `database` (already configured) |
| **Session Timeout** | Reduced from 120 minutes to **15 minutes** of inactivity |
| **Session Regeneration** | After login: `session()->regenerate()` + `session()->regenerateToken()` |
| **Other Device Logout** | `Auth::logoutOtherDevices()` on login |
| **Session Fixation** | Mitigated by regeneration on login |

### Files Modified

| File | Change |
|------|--------|
| `.env` | `SESSION_LIFETIME=120` → `SESSION_LIFETIME=15` |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Added `Auth::logoutOtherDevices()` after successful login |

### Risk Mitigated

- **OWASP Top 10: A7 — Identification and Authentication Failures**
- Session fixation attacks
- Stale sessions from compromised devices
- Concurrent session management

### Pentesting Scenarios

- Login, capture session cookie, wait 16 minutes — session should be invalid
- Login from two different browsers — verify `logoutOtherDevices` invalidates the first session
- Attempt session reuse after logout or password change

---

## 3. Role-Based Access Control

### Implemented

| Control | Detail |
|---------|--------|
| **Role Middleware** | Existing `EnsureUserRole` middleware with `role:` prefix |
| **Route Protection** | All admin, doctor, staff, diagnostic, patient routes protected |
| **Route Audit** | Every route reviewed for proper middleware assignment |
| **Owner Check** | Patient can only view own appointments; doctor only own schedules/patients |

### Files Reviewed

| File | Notes |
|------|-------|
| `routes/web.php` | All role groups reviewed — patient, admin, doctor, staff, diagnostic |
| `app/Http/Middleware/EnsureUserRole.php` | Existing middleware — aborts 403 on role mismatch |
| `app/Http/Controllers/Patient/AppointmentController.php` | Ownership checks on `show()`, `downloadPdf()` |
| `app/Http/Controllers/Doctor/AppointmentController.php` | Ownership checks on `show()`, `update()`, `storePrescription()` |
| `app/Http/Controllers/Doctor/ScheduleController.php` | Scope queries by `doctor_id` |

### Risk Mitigated

- **OWASP Top 10: A1 — Broken Access Control**
- Horizontal privilege escalation (patient accessing other patient's data)
- Vertical privilege escalation (patient accessing admin routes)

### Pentesting Scenarios

- Attempt to access `/admin/doctors` as patient/doctor role
- Attempt to view another patient's appointment by ID
- Attempt doctor to access schedules belonging to another doctor
- Attempt to access dashboard without verified email

---

## 4. Input Validation

### Implemented

All controller validation converted to dedicated **Form Request** classes. Every request is validated for type, format, and constraints.

### Form Requests Created

| Form Request | Validates |
|-------------|-----------|
| `Auth\RegisterRequest` | Registration fields |
| `Auth\PasswordUpdateRequest` | Current + new password |
| `Auth\LoginRequest` | Email + password (existing, enhanced) |
| `Admin\StoreDoctorRequest` | Doctor creation with photo upload |
| `Admin\UpdateDoctorRequest` | Doctor update with optional password |
| `Admin\StoreStaffRequest` | Staff creation with photo upload |
| `Admin\UpdateStaffRequest` | Staff update with optional password |
| `Admin\StoreNewsRequest` | News creation with image |
| `Admin\UpdateNewsRequest` | News update with image |
| `Admin\StoreHealthCheckRequest` | Health check creation |
| `Admin\UpdateHealthCheckRequest` | Health check update |
| `Admin\UpdatePackageBookingRequest` | Package booking status update |
| `Admin\StoreMentionRequest` | Doctor mention/message |
| `Admin\UpdateAdminAppointmentRequest` | Appointment status update (admin) |
| `Admin\UploadImageRequest` | News editor image upload |
| `Doctor\StoreScheduleRequest` | Schedule creation |
| `Doctor\UpdateScheduleRequest` | Schedule update |
| `Doctor\UpdateAppointmentRequest` | Appointment status update (doctor) |
| `Doctor\StorePrescriptionRequest` | Prescription text |
| `Patient\StoreAppointmentRequest` | Public appointment booking |
| `Diagnostic\StoreDiagnosticServiceRequest` | Diagnostic service creation |
| `Diagnostic\UpdateDiagnosticServiceRequest` | Diagnostic service update |
| `Diagnostic\StoreBookingRequest` | Diagnostic booking |
| `Api\StorePackageBookingRequest` | Package booking (API) |
| `Api\EmailCheckRequest` | Email uniqueness check |
| `ProfileUpdateRequest` | Profile update (enhanced) |

### Controllers Updated

All controllers that previously used inline `$request->validate()` now type-hint their Form Request.

### Risk Mitigated

- **OWASP Top 10: A3 — Injection**
- Invalid data types reaching the database
- Mass assignment vulnerabilities

### Pentesting Scenarios

- Submit requests with oversized strings, invalid dates, non-existent foreign keys
- Attempt SQL metacharacters in string fields
- Bypass validation by manipulating content-type headers

---

## 5. SQL Injection Protection

### Audit Results

All raw SQL usage in the codebase was reviewed:

| Location | Raw SQL | User Input | Verdict |
|----------|---------|------------|---------|
| `Doctor/ScheduleController.php:32` | `orderByRaw("FIELD(day_of_week,...)")` | No — hardcoded | Safe |
| `AppointmentController.php:32` | `DB::raw('count(*) as count')` | No — hardcoded | Safe |
| `AppointmentController.php:137` | `DB::raw('count(*) as booked')` | No — hardcoded | Safe |
| `Admin/AdminScheduleController.php:17` | `orderByRaw("FIELD(day_of_week,...)")` | No — hardcoded | Safe |
| `Admin/AdminScheduleController.php:23` | `where('name','like',"%$s%")` | Yes — parameterized binding | Safe |

All queries use Eloquent or the parameterized query builder. The `like` clause with `%$s%` uses Laravel's parameter binding — the value `$s` is bound as a prepared statement parameter.

### Risk Mitigated

- **OWASP Top 10: A3 — Injection**
- SQL injection via unsanitized user input

### Pentesting Scenarios

- Attempt SQL injection via search fields, date fields, and ID parameters
- Test with `' OR 1=1 --`, `'; DROP TABLE users; --`, etc.

---

## 6. XSS Protection

### Implemented

| Control | Detail |
|---------|--------|
| **Blade Templates** | All user output uses `{{ }}` (escaped) |
| **Vue Components** | Installed **DOMPurify** for rich text rendering |
| **News Content** | Sanitized via `DOMPurify.sanitize()` before `v-html` |
| **`{!! !!}` Audit** | Only in QR code generation (PDFs) and Laravel vendor views (`__()`) — safe |

### Files Modified

| File | Change |
|------|--------|
| `resources/js/Pages/NewsDetail.vue` | Added `DOMPurify.sanitize()` to news content rendering |

### Risk Mitigated

- **OWASP Top 10: A3 — Injection** (XSS)
- Stored XSS via news rich text content
- Reflected XSS via URL parameters

### Pentesting Scenarios

- Inject `<script>alert(1)</script>` in news content field
- Inject `"><img onerror=alert(1) src=x>` in any text field
- Test reflected XSS in search parameters

---

## 7. CSRF Protection

### Verification Results

| Check | Status |
|-------|--------|
| `@csrf` meta tag in `app.blade.php` | Present |
| Axios CSRF token header in `bootstrap.js` | Configured |
| Sanctum XSRF cookie handling in `api.js` | Configured |
| `VerifyCsrfToken` middleware in web group | Enabled |
| All state-changing routes | Protected |

### Risk Mitigated

- **OWASP Top 10: A1 — Broken Access Control** (CSRF)
- Cross-site request forgery attacks on state-changing operations

### Pentesting Scenarios

- Submit a POST/PUT/DELETE request without CSRF token — expect 419
- Craft a cross-origin form submission targeting the application

---

## 8. Rate Limiting

### Implemented

| Control | Detail |
|---------|--------|
| **Login Throttling** | 5 attempts per email+IP combination |
| **Lockout Duration** | 15 minutes (900 seconds) |
| **API Rate Limiting** | 60 requests per minute |
| **Rate Limiter Scope** | Per authenticated user ID or IP for guests |

### Files Modified

| File | Change |
|------|--------|
| `app/Http/Requests/Auth/LoginRequest.php` | Added decay seconds constant (900), max attempts constant (5), uses constants |
| `app/Providers/AppServiceProvider.php` | Registered `api` (60/min) and `login` (5/min) rate limiters |

### Risk Mitigated

- **OWASP Top 10: A7 — Identification and Authentication Failures**
- Brute force password guessing
- API abuse / DoS

### Pentesting Scenarios

- Send rapid login requests (6+ attempts per minute) — verify 429 response
- Wait 15 minutes — verify lockout clears
- Send 61+ API requests per minute — verify throttling
- Verify that successful login resets the attempt counter

---

## 9. Secure File Upload

### Implemented

| Control | Detail |
|---------|--------|
| **Allowed Types** | Restricted to `jpg`, `jpeg`, `png` (images); `pdf` where applicable; `gif`, `webp` for news |
| **Maximum Size** | 2 MB (2048 KB) |
| **Filename Randomization** | Already handled by Laravel's `store()` method |
| **MIME Type Validation** | `image` rule validates actual MIME type server-side |
| **Form Request Validation** | All upload paths validated through dedicated Form Requests |

### Form Requests With Upload Validation

- `StoreDoctorRequest` — `mimes:jpg,jpeg,png`
- `UpdateDoctorRequest` — `mimes:jpg,jpeg,png`
- `StoreStaffRequest` — `mimes:jpg,jpeg,png`
- `UpdateStaffRequest` — `mimes:jpg,jpeg,png`
- `StoreNewsRequest` — `mimes:jpg,jpeg,png,gif,webp`
- `UpdateNewsRequest` — `mimes:jpg,jpeg,png,gif,webp`
- `UploadImageRequest` — `mimes:jpg,jpeg,png,gif,webp`
- `StoreDiagnosticServiceRequest` — `mimes:jpeg,png,jpg,gif`
- `UpdateDiagnosticServiceRequest` — `mimes:jpeg,png,jpg,gif`

### Risk Mitigated

- **OWASP Top 10: A3 — Injection** (Malicious file upload)
- PHP shell upload via double extension (`file.php.jpg`)
- Unrestricted file upload leading to RCE

### Pentesting Scenarios

- Attempt to upload `.php`, `.php5`, `.phtml`, `.shtml` files
- Attempt to upload files with double extensions (`evil.php.jpg`)
- Attempt files > 2MB
- Verify stored filenames are randomized UUIDs, not original names

---

## Summary: OWASP Top 10 (2021) Mapping

| OWASP Category | Controls Applied |
|----------------|-----------------|
| **A1: Broken Access Control** | RBAC middleware, Form Request `authorize()`, ownership checks |
| **A2: Cryptographic Failures** | Argon2id hashing |
| **A3: Injection** | Parameterized queries, Form Request validation, XSS sanitization, file upload restrictions |
| **A4: Insecure Design** | Rate limiting on login/API |
| **A5: Security Misconfiguration** | Session timeout reduction |
| **A6: Vulnerable Components** | N/A (current scope) |
| **A7: Identification & Auth Failures** | Session regeneration, `logoutOtherDevices`, password complexity, rate limiting |
| **A8: Software Integrity Failures** | N/A (current scope) |
| **A9: Logging Failures** | N/A (current scope) |
| **A10: SSRF** | N/A (current scope) |

---

## Files Modified Summary

### New Files (32)

```
config/hashing.php
app/Rules/StrongPassword.php
app/Rules/SecureFileUpload.php
app/Http/Requests/Auth/RegisterRequest.php
app/Http/Requests/Auth/PasswordUpdateRequest.php
app/Http/Requests/Admin/StoreDoctorRequest.php
app/Http/Requests/Admin/UpdateDoctorRequest.php
app/Http/Requests/Admin/StoreStaffRequest.php
app/Http/Requests/Admin/UpdateStaffRequest.php
app/Http/Requests/Admin/StoreNewsRequest.php
app/Http/Requests/Admin/UpdateNewsRequest.php
app/Http/Requests/Admin/StoreHealthCheckRequest.php
app/Http/Requests/Admin/UpdateHealthCheckRequest.php
app/Http/Requests/Admin/UpdatePackageBookingRequest.php
app/Http/Requests/Admin/StoreMentionRequest.php
app/Http/Requests/Admin/UpdateAdminAppointmentRequest.php
app/Http/Requests/Admin/UploadImageRequest.php
app/Http/Requests/Doctor/StoreScheduleRequest.php
app/Http/Requests/Doctor/UpdateScheduleRequest.php
app/Http/Requests/Doctor/UpdateAppointmentRequest.php
app/Http/Requests/Doctor/StorePrescriptionRequest.php
app/Http/Requests/Patient/StoreAppointmentRequest.php
app/Http/Requests/Diagnostic/StoreDiagnosticServiceRequest.php
app/Http/Requests/Diagnostic/UpdateDiagnosticServiceRequest.php
app/Http/Requests/Diagnostic/StoreBookingRequest.php
app/Http/Requests/Api/StorePackageBookingRequest.php
app/Http/Requests/Api/EmailCheckRequest.php
```

### Modified Files (18)

```
.env
bootstrap/app.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/RegisteredUserController.php
app/Http/Controllers/Auth/PasswordController.php
app/Http/Controllers/Admin/DoctorController.php
app/Http/Controllers/Admin/StaffController.php
app/Http/Controllers/Admin/NewsController.php
app/Http/Controllers/Admin/AdminHealthCheckController.php
app/Http/Controllers/Admin/AdminPackageBookingController.php
app/Http/Controllers/Admin/AdminScheduleController.php
app/Http/Controllers/AppointmentController.php
app/Http/Controllers/ProfileController.php
app/Http/Controllers/Doctor/ScheduleController.php
app/Http/Controllers/Doctor/AppointmentController.php
app/Http/Controllers/Diagnostic/DiagnosticServiceController.php
app/Http/Controllers/Diagnostic/DiagnosticBookingController.php
app/Http/Controllers/Api/EmailCheckController.php
app/Http/Controllers/Api/PackageBookingController.php
app/Http/Requests/Auth/LoginRequest.php
app/Http/Requests/ProfileUpdateRequest.php
resources/js/Pages/NewsDetail.vue
package.json
```
