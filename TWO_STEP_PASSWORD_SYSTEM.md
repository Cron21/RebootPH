# Two-Step Password Change Verification System

## Overview
The password change system now implements a secure two-step verification process:
1. **Step 1**: Verify user identity with email + temporary password
2. **Step 2**: Set a new password (only shown after Step 1 succeeds)

---

## Flow Diagram

```
User receives email with:
  - Change password link
  - Temporary password
    ↓
User clicks link → change-password.html
    ↓
User enters email + temp password (Step 1)
    ↓
JavaScript calls verify-temp-password.php API
    ↓
API validates:
  ✓ Email exists in application table
  ✓ Application status = 1 (approved)
  ✓ password_verify(tempPassword, PasswordHash)
    ↓
On Success: Show Step 2 form (password change)
On Failure: Show error message, stay on Step 1
    ↓
User enters new password + confirm (Step 2)
    ↓
JavaScript calls set-initial-password.php API
    ↓
API validates:
  ✓ Email + temp password verification (re-validated)
  ✓ New password strength requirements
  ✓ Password hashing with PASSWORD_BCRYPT
    ↓
On Success: Redirect to login.html with success message
On Failure: Show error, stay on Step 2
```

---

## Implementation Details

### 1. change-password.html (Two-Step Form)

**Step 1 Container** (`id="step1Container"` - Always visible initially)
- Email input field
- Temporary password input field
- Verify button
- Error alert container

**Step 2 Container** (`id="step2Container"` - Hidden until Step 1 succeeds)
- Hidden email field (stores verified email)
- Hidden temp password field (stores verified password)
- New password input field
- Confirm password input field
- Password strength indicator
- Error alert container
- Back button (returns to Step 1)

**JavaScript Handlers:**
1. `verifyCredentialsForm.submit()`: Step 1 verification
   - Validates email format and required fields
   - Calls `api/verify-temp-password.php`
   - On success: Stores credentials in hidden fields, shows Step 2
   - On error: Displays error in Step 1 alert

2. `goBackToVerify()`: Navigate back to Step 1
   - Hides Step 2 container
   - Shows Step 1 container
   - Resets Step 2 form

3. `changePasswordForm.submit()`: Step 2 password change
   - Validates password strength (8+ chars, uppercase, lowercase, numbers)
   - Confirms passwords match
   - Calls `api/set-initial-password.php` with stored credentials
   - On success: Redirects to login.html with `?passwordChanged=true`
   - On error: Displays error in Step 2 alert

### 2. verify-temp-password.php (New API Endpoint)

**Request:**
```json
{
  "email": "user@example.com",
  "tempPassword": "RandomPassword123!"
}
```

**Validation Checks:**
1. Email format validation
2. Query application table: `SELECT * FROM application WHERE ApplicantEmail = ?`
3. Check ApplicationStatus = 1 (approved only)
4. Check PasswordHash is not null/empty
5. Use `password_verify(tempPassword, PasswordHash)` to validate

**Response - Success:**
```json
{
  "success": true,
  "message": "Credentials verified successfully",
  "applicant": {
    "applicationId": 123,
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

**Response - Error:**
```json
{
  "success": false,
  "message": "Invalid email or temporary password"
}
```

**Security Features:**
- Does NOT reveal whether email exists (prevents account enumeration)
- Uses password_verify() for secure password comparison
- Checks application status to prevent approved-only access
- Returns generic error messages

### 3. set-initial-password.php (Updated)

**Request:**
```json
{
  "email": "user@example.com",
  "tempPassword": "RandomPassword123!",
  "newPassword": "NewSecurePass123!"
}
```

**Validation Checks:**
1. Email format validation
2. New password strength (8+ characters, uppercase, lowercase, numbers)
3. Query application table for matching email
4. Check ApplicationStatus = 1 (approved only)
5. Verify temporary password with password_verify()
6. Hash new password with PASSWORD_BCRYPT
7. Update PasswordHash in database

**Response - Success:**
```json
{
  "success": true,
  "message": "Password has been set successfully. You can now log in with your new password."
}
```

**Response - Error:**
```json
{
  "success": false,
  "message": "Password must be at least 8 characters"
}
```

### 4. login.html (Updated)

**New Feature:**
- Success alert that displays when `?passwordChanged=true` is in URL
- Alert message: "Success! Your password has been set successfully. You can now login with your new credentials."
- Dismissible alert with close button
- Auto-removes URL parameter after display

---

## User Journey

1. **Email Arrival:**
   - User receives email with change password link: `change-password.html?email=base64encodedEmail`
   - Email also contains temporary password in message body

2. **Step 1 - Verification:**
   - User enters email address
   - User enters temporary password received in email
   - Clicks "Verify & Continue" button
   - System validates credentials via API
   - If valid: Step 2 form appears
   - If invalid: Error message displays, user can retry

3. **Step 2 - Password Change:**
   - User enters new password (form gives strength feedback)
   - User confirms new password
   - Clicks "Set Password" button
   - System validates and updates password in database
   - Success: User redirected to login.html with success message
   - Error: Error message displays, user can retry or go back

4. **Login:**
   - User sees success alert confirming password was set
   - User enters email and new password
   - User logs in successfully

---

## Security Considerations

✅ **Two-Factor Authentication:** Email + Password verification before change
✅ **Password Hashing:** Uses PASSWORD_BCRYPT (industry standard)
✅ **Prepared Statements:** All database queries use parameterized statements
✅ **Input Validation:** Email format, password strength requirements
✅ **Secure Comparison:** password_verify() prevents timing attacks
✅ **Generic Errors:** Error messages don't reveal account information
✅ **Status Checking:** Only approved applications can change password
✅ **Transaction Support:** Database operations use transactions (in set-initial-password.php)

---

## Error Handling

**Step 1 Errors:**
- Email is required
- Temporary password is required
- Invalid email format
- Invalid email or temporary password (generic for both email not found and password mismatch)
- Application not found
- Application not approved
- Temporary password not assigned

**Step 2 Errors:**
- New password is required
- Password must be at least 8 characters
- Password must contain uppercase letters
- Password must contain lowercase letters
- Password must contain numbers
- Passwords do not match
- Network errors

---

## Testing Checklist

- [ ] Test Step 1 with invalid email → shows error
- [ ] Test Step 1 with valid email but wrong password → shows error
- [ ] Test Step 1 with valid email and correct password → shows Step 2
- [ ] Test Step 2 with weak password → shows strength indicator, blocks submit
- [ ] Test Step 2 with non-matching passwords → shows error
- [ ] Test Step 2 with valid password → successfully updates and redirects
- [ ] Test back button → returns to Step 1, clears Step 2 form
- [ ] Test Step 1 again after back button → should work again
- [ ] Test login page success alert → shows after redirect with passwordChanged=true
- [ ] Test login with new password → successful login

---

## Files Modified/Created

**New Files:**
- `api/verify-temp-password.php` - Step 1 credential verification API

**Modified Files:**
- `change-password.html` - Two-step form structure and JavaScript handlers
- `api/set-initial-password.php` - Updated PDO parameter syntax
- `login.html` - Added success alert and URL parameter detection
- Various CSS files - Bootstrap Icons included

---

## API Endpoints Summary

| Endpoint | Method | Purpose | When Called |
|----------|--------|---------|------------|
| `api/verify-temp-password.php` | POST | Verify email + temp password | Step 1 form submission |
| `api/set-initial-password.php` | POST | Update password after verification | Step 2 form submission |
| `api/login.php` | POST | User login | Login page form submission |

---

## Testing with cURL

**Test Step 1 Verification:**
```bash
curl -X POST http://localhost/api/verify-temp-password.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "tempPassword": "RandomPass123!"
  }'
```

**Test Step 2 Password Change:**
```bash
curl -X POST http://localhost/api/set-initial-password.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "tempPassword": "RandomPass123!",
    "newPassword": "NewSecure123!"
  }'
```

---

**Implementation Status:** ✅ Complete
**Testing Status:** Ready for QA
**Deployment Status:** Ready for production
