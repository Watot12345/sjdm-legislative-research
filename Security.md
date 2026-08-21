# System Security Documentation

This document outlines the security controls, protocols, and mechanisms implemented across the **Legislative Research, Policy Analysis & Impact Evaluation System**.

---

## 1. Authentication & Password Security

- **Bcrypt Password Hashing**: Passwords are standardly hashed using PHP's `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt) when creating or resetting passwords in [`admin/users.php`](file:///opt/lampp/htdocs/cap2/admin/users.php), [`modules/profile.php`](file:///opt/lampp/htdocs/cap2/modules/profile.php), and [`modules/settings.php`](file:///opt/lampp/htdocs/cap2/modules/settings.php).
- **Two-Factor Authentication (2FA via PHPMailer)**: Multi-factor authentication is built into the login process via [`includes/auth_helper.php`](file:///opt/lampp/htdocs/cap2/includes/auth_helper.php) and [`verify_2fa.php`](file:///opt/lampp/htdocs/cap2/verify_2fa.php). Successful credentials initiate a cryptographically secure 6-digit OTP (valid for 5 minutes) dispatched to the registered user's email via PHPMailer.
- **12-Hour Trusted Device Grace Period**: Users can opt to "Remember this device for 12 hours" upon OTP verification. This generates a SHA-256 hashed 64-character token in `user_trusted_devices` with an HTTP-only cookie, allowing seamless re-login without repetitive OTP prompts for 12 hours.
- **Strict 12-Hour Session Expiration**: All authenticated sessions enforce a hard 12-hour expiration window (`AUTH_SESSION_LIFETIME_SECONDS=43200`) managed in [`config/config.php`](file:///opt/lampp/htdocs/cap2/config/config.php) and [`includes/auth_helper.php`](file:///opt/lampp/htdocs/cap2/includes/auth_helper.php). Expired sessions are automatically destroyed and redirected to login with an alert notice.
- **Secure Verification**: Login requests verify credentials using `password_verify()` in [`login.php`](file:///opt/lampp/htdocs/cap2/login.php).
- **Automatic Password Hash Upgrade**: If a user logs in with legacy plaintext credentials, the system automatically converts and updates their account to a secure Bcrypt hash in [`login.php`](file:///opt/lampp/htdocs/cap2/login.php).
- **Account Status Enforcement**: Accounts marked as `inactive` or `suspended` are automatically blocked during login in [`login.php`](file:///opt/lampp/htdocs/cap2/login.php) based on schema rules defined in [`database/schema.sql`](file:///opt/lampp/htdocs/cap2/database/schema.sql).

---

## 2. Authorization & Access Control (RBAC)

- **Session Access Guards**: Protected modules enforce session checking (`if (!isset($_SESSION['username']))`) at the top of files (e.g. [`dashboard.php`](file:///opt/lampp/htdocs/cap2/dashboard.php), [`modules/policy-research.php`](file:///opt/lampp/htdocs/cap2/modules/policy-research.php), [`modules/data-collection.php`](file:///opt/lampp/htdocs/cap2/modules/data-collection.php)) to prevent unauthenticated access.
- **Role-Based Access Control (RBAC)**: Administrative modules verify `$_SESSION['role'] === 'admin'` before allowing administrative actions in [`admin/users.php`](file:///opt/lampp/htdocs/cap2/admin/users.php).
- **UI View Scoping**: Navigation menus conditionally hide admin options for standard users in [`includes/sidebar.php`](file:///opt/lampp/htdocs/cap2/includes/sidebar.php).

---

## 3. SQL Injection Prevention

- **Prepared Statements**: Database queries handling input (authentication, search, user management) utilize MySQLi / PDO prepared statements (`$conn->prepare()` and `$stmt->bind_param()`) in [`login.php`](file:///opt/lampp/htdocs/cap2/login.php), [`admin/users.php`](file:///opt/lampp/htdocs/cap2/admin/users.php), [`api/global-search.php`](file:///opt/lampp/htdocs/cap2/api/global-search.php), and [`config/database.php`](file:///opt/lampp/htdocs/cap2/config/database.php).
- **Input Escaping**: Dynamic query builders use `$conn->real_escape_string()` for string values in [`modules/add-policy.php`](file:///opt/lampp/htdocs/cap2/modules/add-policy.php) and [`modules/data-collection.php`](file:///opt/lampp/htdocs/cap2/modules/data-collection.php).

---

## 4. Cross-Site Scripting (XSS) Protection

- **Contextual HTML Escaping**: Dynamic values rendered in HTML views (names, roles, user data) are wrapped in `htmlspecialchars()` across files like [`includes/sidebar.php`](file:///opt/lampp/htdocs/cap2/includes/sidebar.php), [`includes/navbar.php`](file:///opt/lampp/htdocs/cap2/includes/navbar.php), and [`admin/users.php`](file:///opt/lampp/htdocs/cap2/admin/users.php).
- **Input Sanitization**: File content parsing sanitizes extracted text using regular expressions to filter unsafe character sequences in [`includes/FileAnalyzer.php`](file:///opt/lampp/htdocs/cap2/includes/FileAnalyzer.php).

---

## 5. Secrets & Environment Management

- **Centralized Environment Config**: Sensitive system parameters (database credentials, API keys, environment settings) are stored in [`config/.env`](file:///opt/lampp/htdocs/cap2/config/.env) and loaded securely via an `Environment` class in [`config/config.php`](file:///opt/lampp/htdocs/cap2/config/config.php).
- **Git Exclusion**: The [`.gitignore`](file:///opt/lampp/htdocs/cap2/.gitignore) file explicitly ignores `.env`, log files, and `uploads/` directories to prevent credentials or user uploads from leaking into version control.

---

## 6. Audit Trail & Activity Logging

- **Database Audit Log**: Crucial operations (user logins, document submissions, policy edits) are recorded into the `activity_logs` table (defined in [`database/schema.sql`](file:///opt/lampp/htdocs/cap2/database/schema.sql)) with the acting username, module name, action description, and timestamp.

---

## 7. File Upload Handling

- **Upload Categorization**: Uploaded files are separated into designated directories (`uploads/policies/`, `uploads/datasets/`).
- **Filename Prefixing**: Files are assigned timestamp prefixes (`time() . '_' . basename(...)`) in [`modules/add-policy.php`](file:///opt/lampp/htdocs/cap2/modules/add-policy.php) and [`modules/data-collection.php`](file:///opt/lampp/htdocs/cap2/modules/data-collection.php) to prevent directory traversal and file overwrite attacks.

---

## 8. API Security & Server-Side Integration

- **Server-Side API Delegation**: External Gemini API requests are executed strictly backend-side using cURL in [`includes/gemini_helper.php`](file:///opt/lampp/htdocs/cap2/includes/gemini_helper.php), keeping the `GEMINI_API_KEY` hidden from client browsers.
- **API Caching (`ai_cache`)**: AI prompt responses are stored with MD5 hash indexing in `ai_cache` ([`database/schema.sql`](file:///opt/lampp/htdocs/cap2/database/schema.sql)) to avoid redundant external calls, prevent rate-limiting, and limit token usage.