# Bug Report - Security and Logic Issues

## Critical Security Vulnerabilities

### 1. SQL Injection Vulnerabilities (CRITICAL)

**Location**: Multiple files in root directory (legacy code)

**Examples**:
- `login2.php:10`: `$db->Execute("SELECT * FROM $dbtables[ships] WHERE email='$email'");`
- `login2.php:43`: `$db->Execute("SELECT * FROM $dbtables[ip_bans] WHERE '$ip' LIKE ban_mask OR '$playerinfo[ip_address]' LIKE ban_mask");`
- `login2.php:84`: `$db->Execute("UPDATE $dbtables[ships] SET last_login='$stamp',ip_address='$ip' WHERE ship_id=$playerinfo[ship_id]");`
- `global_funcs.php:197`: `$db->Execute("SELECT * FROM $dbtables[ships] WHERE email='$username' LIMIT 1");`
- `global_funcs.php:306`: `$db->Execute("INSERT INTO $dbtables[logs] VALUES(NULL, $sid, $log_type, NOW(), '$data')");`
- `global_funcs.php:213`: `$db->Execute("UPDATE $dbtables[ships] SET hull=0... where email='$username'");`
- `attack.php:22`: `$db->Execute("SELECT * FROM $dbtables[ships] WHERE email='$username'");`
- `attack.php:27`: `$db->Execute("SELECT * FROM $dbtables[ships] WHERE ship_id='$ship_id'");`
- `attack.php:64`: `$db->Execute("SELECT allow_attack,$dbtables[universe].zone_id FROM $dbtables[zones],$dbtables[universe] WHERE sector_id='$targetinfo[sector]'...");`
- `attack.php:73`: `$db->Execute("UPDATE $dbtables[ships] SET turns=turns-1... WHERE ship_id=$playerinfo[ship_id]");`
- `option2.php:56`: `$db->Execute("SELECT ship_id,password FROM $dbtables[ships] WHERE email='$username'");`
- `option2.php:64`: `$db->Execute("UPDATE $dbtables[ships] SET password='$newpass1' WHERE ship_id=$playerinfo[ship_id]");`
- `option2.php:76`: `$db->Execute("UPDATE $dbtables[ships] SET interface='$intrf' WHERE email='$username'");`
- `option2.php:86`: `$db->Execute("UPDATE $dbtables[ships] SET lang='$lang' WHERE email='$username'");`
- `option2.php:101`: `$db->Execute("UPDATE $dbtables[ships] SET dhtml='$dhtml' WHERE email='$username'");`

**Impact**: Attackers can execute arbitrary SQL commands, potentially:
- Stealing all user data
- Modifying game state
- Deleting data
- Escalating privileges

**Fix**: Use prepared statements. The new code in `src/` directory already does this correctly.

### 2. Plain Text Password Storage (CRITICAL)

**Location**: 
- `login2.php:76`: `if($playerinfo[password] == $pass)`
- `global_funcs.php:201`: `if($username == "" or $password == "" or $password != $playerinfo['password'])`
- `option2.php:25`: `if($newpass1 == $newpass2 && $password == $oldpass && $newpass1 != "")`
- `option2.php:46`: `elseif($password != $oldpass)`

**Impact**: Passwords are stored and compared in plain text. If the database is compromised, all passwords are immediately exposed.

**Fix**: The new code in `src/Models/Ship.php` correctly uses `password_hash()` and `password_verify()`. Migrate all legacy authentication to use the new system.

### 3. Register Globals Emulation (HIGH)

**Location**: `global_funcs.php:12-44`

**Issue**: The code manually emulates the deprecated `register_globals` feature, automatically creating variables from `$_POST`, `$_GET`, and `$_COOKIE`. This is a major security risk.

**Impact**: 
- Variables can be overwritten via URL parameters
- Unintended variable injection
- Makes code harder to audit

**Fix**: Remove this code and explicitly use `$_POST`, `$_GET`, `$_COOKIE` arrays.

### 4. Missing CSRF Protection (HIGH)

**Location**: All legacy PHP files in root directory

**Issue**: Forms in legacy code (e.g., `login.php`, `attack.php`, etc.) have no CSRF token protection.

**Impact**: Attackers can perform actions on behalf of authenticated users.

**Fix**: The new code in `src/Controllers/` correctly implements CSRF protection. Migrate legacy forms to use CSRF tokens.

### 5. XSS (Cross-Site Scripting) Vulnerabilities (HIGH)

**Location**: Multiple files

**Examples**:
- `login.php:53`: `VALUE="<?php echo "$username" ?>"` - User input directly in HTML
- `login.php:57`: `VALUE="<?php echo "$password" ?>"` - Password in HTML (also a security issue)
- `login2.php:129`: Direct echo of user input without escaping

**Impact**: Attackers can inject malicious JavaScript that executes in other users' browsers.

**Fix**: Use `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` for all user output.

### 6. Password Displayed in HTML (MEDIUM)

**Location**: `login.php:57`

**Issue**: Password is pre-filled in the HTML form, which is a security risk even if it's the user's own password.

**Fix**: Remove the `VALUE` attribute from password fields.

### 7. Error Reporting Disabled (MEDIUM)

**Location**: 
- `config.php:3`: `error_reporting(0);`
- `global_funcs.php:9-10`: Error reporting disabled

**Issue**: Errors are hidden, making debugging difficult and potentially hiding security issues.

**Fix**: Enable error reporting in development, use proper logging in production.

### 8. Insecure Cookie Handling (MEDIUM)

**Location**: Multiple files

**Issues**:
- Cookies set without `HttpOnly` flag
- Cookies set without `Secure` flag (if not using HTTPS)
- Long expiration times (365 days)
- Password stored in cookie (`login2.php:39`)

**Impact**: Cookies can be accessed by JavaScript (XSS risk) and intercepted if not using HTTPS.

**Fix**: 
- Set `HttpOnly` flag
- Set `Secure` flag when using HTTPS
- Reduce cookie expiration time
- Never store passwords in cookies

### 9. IP Address Injection in SQL (MEDIUM)

**Location**: `login2.php:43`

**Issue**: `$ip` variable (from `getenv("REMOTE_ADDR")`) is directly inserted into SQL without escaping.

**Impact**: If IP address can be spoofed or contains special characters, SQL injection is possible.

**Fix**: Use prepared statements or at least escape the IP address.

### 10. Missing Input Validation (MEDIUM)

**Location**: Multiple files

**Examples**:
- `login2.php`: No validation of `$email` or `$pass` before use
- `global_funcs.php`: Many functions accept user input without validation

**Impact**: Invalid data can cause errors or unexpected behavior.

**Fix**: Validate all user input before use.

### 15. Password Update Without Hashing (CRITICAL)

**Location**: `option2.php:64`

**Issue**: Password is updated directly without hashing: `UPDATE $dbtables[ships] SET password='$newpass1'`

**Impact**: New passwords are stored in plain text, even if the old password was hashed (though it appears old passwords are also plain text).

**Fix**: Use `password_hash($newpass1, PASSWORD_DEFAULT)` before updating.

### 16. SQL Injection in Attack System (CRITICAL)

**Location**: `attack.php`

**Issue**: Multiple SQL queries use unescaped user input:
- Line 22: `$username` directly in query
- Line 27: `$ship_id` directly in query (even though `stripnum()` is used, this is not sufficient)
- Line 64: `$targetinfo[sector]` directly in query
- Many more throughout the file

**Impact**: Attackers can manipulate attack system, potentially:
- Attacking any player regardless of sector
- Bypassing turn requirements
- Manipulating combat results

**Fix**: Use prepared statements for all database queries.

## Logic Bugs

### 11. Array Index Without Quotes (LOW-MEDIUM)

**Location**: Multiple files

**Examples**:
- `global_funcs.php:424`: `$sectors[$i] = $res->fields[sector_id];` (should be `$res->fields['sector_id']`)
- `global_funcs.php:892`: `$owners[winners][num]` (should be `$owners[$winner][num]`)

**Issue**: PHP will issue warnings and may not work as expected. In strict mode, this will cause errors.

**Fix**: Use proper array syntax with quotes: `$array['key']` or `$array[$variable]`.

### 12. Typo in Variable Name (LOW)

**Location**: `global_funcs.php:892`

**Issue**: `$owners[winners][num]` should be `$owners[$winner][num]` (missing `$` and wrong variable name).

**Fix**: Correct the variable name.

### 13. Inconsistent Database Result Checking (LOW)

**Location**: Multiple files

**Issue**: Some places check `if($result > 0)` which is incorrect for ADOdb result objects. Should check `if($result && $result->RecordCount() > 0)`.

**Examples**:
- `global_funcs.php:539`: `if($result3 > 0)`
- `global_funcs.php:567`: `if($result3 > 0)`
- `global_funcs.php:594`: `if($result3 > 0)`

**Fix**: Use proper result checking.

### 14. Missing Error Handling (LOW)

**Location**: Multiple files

**Issue**: Database operations don't check for errors consistently.

**Fix**: Add proper error handling for all database operations.

## Recommendations

### Immediate Actions (Critical Priority)
1. **Migrate all authentication to new system** - Use `src/Controllers/AuthController.php` and `src/Models/Ship.php`
2. **Fix SQL injection vulnerabilities** - Replace all direct SQL string concatenation with prepared statements
3. **Remove register_globals emulation** - Delete lines 12-44 in `global_funcs.php`
4. **Add CSRF protection** - Implement CSRF tokens for all forms
5. **Fix XSS vulnerabilities** - Escape all user output

### Short-term Actions (High Priority)
1. Migrate password storage to use bcrypt (already done in new code)
2. Fix cookie security settings
3. Add input validation
4. Enable proper error logging

### Long-term Actions
1. Complete migration from legacy code to new MVC architecture
2. Add comprehensive test coverage
3. Implement proper logging and monitoring
4. Security audit of all user-facing endpoints

## Notes

- The new code in the `src/` directory follows security best practices
- The legacy code in the root directory has numerous security vulnerabilities
- Consider deprecating legacy files and migrating functionality to the new architecture
- The codebase appears to be in transition from old to new architecture

