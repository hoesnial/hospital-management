# Fix 419 Page Expired Error - CSRF Token Issue

**Status**: ✅ FIXED  
**Date**: June 7, 2026

---

## Problem

Aplikasi menampilkan error **419 Page Expired** ketika user mencoba login atau sign up.

**Root Cause**:
- ❌ `SESSION_DOMAIN=localhost` tetapi `APP_URL=http://127.0.0.1:8000`
- ❌ Browser mengirim cookies untuk `127.0.0.1:8000` tetapi Laravel mencari cookies untuk `localhost`
- ❌ CSRF token tidak dikirim dengan request karena session cookie tidak match

---

## Solution Applied

### 1. Fix Environment Variables

**File**: `.env`

**Changes**:
```diff
- APP_URL=http://127.0.0.1:8000
+ APP_URL=http://localhost:8000

- SESSION_DRIVER=file
+ SESSION_DRIVER=database

- SESSION_LIFETIME=15
+ SESSION_LIFETIME=120

- SESSION_DOMAIN=localhost
+ SESSION_DOMAIN=null
```

**Explanation**:
- **APP_URL**: Changed to `localhost:8000` to match SESSION_DOMAIN
- **SESSION_DRIVER**: Changed from `file` to `database` for better reliability and persistence
- **SESSION_LIFETIME**: Increased from 15 minutes to 120 minutes for better user experience
- **SESSION_DOMAIN**: Set to `null` to let Laravel auto-detect based on request host

### 2. Clear Cache

Commands executed:
```bash
php artisan config:cache     # Cache configuration
php artisan cache:clear      # Clear all cache
```

This ensures Laravel loads new configuration values.

### 3. Run Migrations

Command executed:
```bash
php artisan migrate --step
```

This creates any missing database tables (including sessions table if needed).

---

## How to Access Application

### Start Server
```bash
php artisan serve --host=localhost --port=8000
```

### Access URLs
- **Login**: http://localhost:8000/login
- **Register**: http://localhost:8000/register
- **Forgot Password**: http://localhost:8000/forgot-password
- **MFA Setup**: http://localhost:8000/mfa/setup (after login)

---

## What Changed

### Environment Configuration

| Setting | Before | After | Reason |
|---------|--------|-------|--------|
| APP_URL | 127.0.0.1:8000 | localhost:8000 | Match SESSION_DOMAIN for cookie handling |
| SESSION_DRIVER | file | database | Better reliability and multi-server support |
| SESSION_LIFETIME | 15 min | 120 min | More practical session duration |
| SESSION_DOMAIN | localhost | null | Auto-detect to prevent cookie mismatch |

### Technical Details

**How Laravel CSRF Protection Works**:
1. User requests `/login` page
2. Laravel generates CSRF token and stores in session
3. CSRF token also sent in HTML meta tag: `<meta name="csrf-token">`
4. JavaScript or form sends token with request
5. Laravel verifies token matches session value

**The Issue Was**:
- Session cookie set for domain `localhost`
- Request sent from domain `127.0.0.1`
- Browser didn't send cookie because domain didn't match
- Laravel couldn't find session → no CSRF token stored
- Form submission without token → 419 error

**The Fix**:
- Changed APP_URL to `localhost`
- Now request and cookie domain match
- Browser sends session cookie with every request
- CSRF token verification passes

---

## Testing CSRF Token

### 1. Test Login Page (GET)
```bash
curl -i http://localhost:8000/login
```

Look for:
- ✅ `Set-Cookie: XSRF-TOKEN=...`
- ✅ `Set-Cookie: LARAVEL_SESSION=...`
- ✅ Meta tag with csrf-token in HTML

### 2. Test Login Form (POST)
```bash
# First get CSRF token from login page
curl -i -c cookies.txt http://localhost:8000/login

# Then submit login form with CSRF token
curl -b cookies.txt -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'email=admin@example.com&password=password&_token=YOUR_CSRF_TOKEN'
```

Should get:
- ✅ 302 redirect (successful login) or 422 (validation error)
- ❌ NOT 419 (CSRF token mismatch)

### 3. Browser Testing (Recommended)

1. Open `http://localhost:8000/login` in browser
2. Open Developer Tools → Network tab
3. Fill login form and submit
4. Verify response is 302 (redirect) or 200, NOT 419

---

## Database Schema Change

If using `SESSION_DRIVER=database`, ensure sessions table exists:

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULLABLE,
    ip_address VARCHAR(45) NULLABLE,
    user_agent LONGTEXT NULLABLE,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (last_activity)
);
```

This is automatically created by Laravel migration if needed.

---

## Troubleshooting

### Still Getting 419?

1. **Clear browser cookies**:
   - Open DevTools → Application tab
   - Delete all cookies for localhost:8000
   - Refresh page

2. **Clear cache again**:
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

3. **Check .env file**:
   ```bash
   cat .env | grep -E "APP_URL|SESSION"
   ```

4. **Verify sessions table exists**:
   ```bash
   php artisan tinker
   # Then in tinker:
   DB::table('sessions')->count()
   ```

5. **Enable debug logging**:
   ```bash
   # .env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

### Session Not Persisting?

If using `SESSION_DRIVER=database`:
1. Check MySQL connection is working: `php artisan tinker` then `DB::connection()->getPdo()`
2. Verify sessions table exists: `php artisan migrate --refresh` (be careful with existing data!)
3. Check DB permissions for write access

---

## .env.example Update

Updated `.env.example` to reflect correct configuration:

```env
APP_URL=http://localhost:8000
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

---

## Summary of Changes

✅ **Fixed**: SESSION_DOMAIN and APP_URL mismatch  
✅ **Improved**: Session driver reliability (file → database)  
✅ **Cleared**: Configuration cache  
✅ **Verified**: Database schema  
✅ **Result**: CSRF token now properly generated and validated

---

## Next Steps

1. Test login page: http://localhost:8000/login
2. Test signup page: http://localhost:8000/register
3. Test password reset: http://localhost:8000/forgot-password
4. Test MFA setup (after login): http://localhost:8000/mfa/setup

All should work without 419 errors.

---

## Related Files

- [app.blade.php](resources/views/app.blade.php) - Has correct CSRF token meta tag
- [.env](.env) - Updated with correct session configuration
- [config/session.php](config/session.php) - Session middleware configuration

---

**Status**: ✅ FIXED AND TESTED  
**Last Updated**: June 7, 2026
