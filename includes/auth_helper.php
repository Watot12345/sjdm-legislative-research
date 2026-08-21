<?php
/**
 * Authentication & 2FA Security Helper
 * Legislative Research System - City of San Jose Del Monte
 *
 * Features:
 * - 6-Digit OTP Generation & Database Persistence
 * - PHPMailer Email Dispatch with HTML Templates & Local Dev Fallback
 * - 12-Hour Trusted Device Grace Token Validation (No Repetitive OTPs)
 * - 12-Hour Hard Session Expiration Enforcement
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure session lifetimes for 12 hours (43200 seconds)
    $lifetime = 43200;
    ini_set('session.gc_maxlifetime', (string)$lifetime);
    ini_set('session.cookie_lifetime', (string)$lifetime);
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Load Composer autoloader for PHPMailer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Check whether OTP / 2FA verification is enabled system-wide.
 * Defaults to true if not explicitly set to false.
 *
 * @return bool
 */
function isOTPEnabled() {
    return Environment::getBool('OTP_ENABLED', true) && 
           Environment::getBool('AUTH_OTP_ENABLED', true) && 
           Environment::getBool('ENABLE_2FA', true);
}

/**
 * Enforce 12-Hour Session Expiration across all authenticated pages.
 * If expired, cleans session and redirects to login with timeout notice.
 */
function enforceSessionTimeout($maxSeconds = 43200) {
    if (isset($_SESSION['user_id'])) {
        if (!isset($_SESSION['login_time'])) {
            $_SESSION['login_time'] = time();
        }

        $sessionAge = time() - (int)$_SESSION['login_time'];
        if ($sessionAge > $maxSeconds) {
            // Log timeout activity
            if (isset($_SESSION['username'])) {
                $conn = getDBConnection();
                $user = $_SESSION['username'];
                $action = "Session expired after 12 hours";
                $module = "Authentication";
                $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, ?, NOW())");
                if ($logStmt) {
                    $logStmt->bind_param("sss", $user, $action, $module);
                    @$logStmt->execute();
                    @$logStmt->close();
                }
            }

            // Clear session data
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Determine relative redirect to login.php
            $redirectPath = 'login.php?expired=1';
            if (strpos($_SERVER['REQUEST_URI'], '/modules/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
                $redirectPath = '../login.php?expired=1';
            }

            header("Location: " . $redirectPath);
            exit();
        }
    }
}

/**
 * Generate and store a secure 6-digit OTP for a given user.
 * Dispatches the OTP via PHPMailer to their registered email.
 *
 * @param int $userId User ID
/**
 * Generate and store a secure 6-digit OTP for a given user.
 * Dispatches the OTP via PHPMailer to their registered email.
 *
 * @param int $userId User ID
 * @param string $email Recipient Email
 * @param string $fullName User Full Name
 * @return array ['success' => bool, 'mail_sent' => bool, 'message' => string, 'otp_code' => string]
 */
function generateAndSendOTP($userId, $email, $fullName = '') {
    $conn = getDBConnection();
    $email = trim($email);
    
    // Invalidate previous active OTPs for this user
    $invStmt = $conn->prepare("UPDATE user_otps SET is_used = 1 WHERE user_id = ? AND is_used = 0");
    if ($invStmt) {
        $invStmt->bind_param("i", $userId);
        $invStmt->execute();
        $invStmt->close();
    }

    // Generate cryptographically secure 6-digit OTP code
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
    
    // Expire in 5 minutes
    $expiresAt = date('Y-m-d H:i:s', time() + 300);

    // Save to user_otps table
    $stmt = $conn->prepare("INSERT INTO user_otps (user_id, otp_code, expires_at, is_used, created_at) VALUES (?, ?, ?, 0, NOW())");
    if (!$stmt) {
        return ['success' => false, 'mail_sent' => false, 'message' => 'Database error generating OTP.', 'otp_code' => ''];
    }
    $stmt->bind_param("iss", $userId, $otpHash, $expiresAt);
    $saved = $stmt->execute();
    $stmt->close();

    if (!$saved) {
        return ['success' => false, 'mail_sent' => false, 'message' => 'Failed to save OTP record.', 'otp_code' => ''];
    }

    // Send via PHPMailer
    $mailResult = sendOTPEmailPHPMailer($email, $fullName, $otpCode);

    // Development fallback log
    if (Environment::getBool('APP_DEBUG', false) || Environment::get('APP_ENV') === 'development') {
        error_log("[2FA OTP DEBUG] User ID: {$userId} ({$email}) | OTP: {$otpCode} | Sent: " . ($mailResult['sent'] ? 'YES' : 'NO - ' . $mailResult['message']));
    }

    return [
        'success' => true,
        'mail_sent' => (bool)$mailResult['sent'],
        'message' => $mailResult['message'],
        'otp_code' => $otpCode
    ];
}

/**
 * Send OTP Verification Code using PHPMailer with an HTML email template.
 */
function sendOTPEmailPHPMailer($recipientEmail, $recipientName, $otpCode) {
    $recipientEmail = trim($recipientEmail);

    if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'sent' => false,
            'message' => "Recipient email address '{$recipientEmail}' is invalid or empty."
        ];
    }

    // Ensure autoloader is loaded
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'sent' => false,
            'message' => 'PHPMailer library not found. Please run composer install.'
        ];
    }

    $smtpEnabled = filter_var(Environment::get('SMTP_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    $smtpHost    = Environment::get('SMTP_HOST', 'smtp.gmail.com');
    $smtpPort    = (int)Environment::get('SMTP_PORT', 587);
    $smtpSecure  = strtolower(trim(Environment::get('SMTP_SECURE', 'tls')));
    $smtpUser    = trim(Environment::get('SMTP_USERNAME', ''));
    $smtpPass    = trim(Environment::get('SMTP_PASSWORD', ''));
    $fromEmail   = trim(Environment::get('SMTP_FROM_EMAIL', ''));
    $fromName    = Environment::get('SMTP_FROM_NAME', 'Legislative Research System (SJDM)');

    // For Gmail SMTP, the From address must align with authenticated Gmail account to prevent SPF/DMARC spam drops
    if (empty($fromEmail) || (strpos($smtpHost, 'gmail.com') !== false && !empty($smtpUser))) {
        $fromEmail = $smtpUser ?: 'noreply@sjdm.gov.ph';
    }

    $mail = new PHPMailer(true);

    try {
        if ($smtpEnabled && !empty($smtpUser) && !empty($smtpPass)) {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = ($smtpSecure === 'ssl' || $smtpPort === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;
            $mail->Timeout    = 12;

            // XAMPP on Windows OpenSSL CA fallback to prevent local certificate verify failures
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        } else {
            // Fallback to PHP mail() if SMTP credentials not fully configured
            $mail->isMail();
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($fromEmail, $fromName);
        $mail->addAddress($recipientEmail, $recipientName ?: 'Legislative System User');
        $mail->isHTML(true);
        $mail->Subject = 'Your 2FA Login Verification Code: ' . $otpCode;

        $safeName = htmlspecialchars($recipientName ?: 'User', ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>2FA Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e2e8f0;">
          <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 32px 30px; text-align: center; color: #ffffff;">
              <h1 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; text-transform: uppercase;">
                City of San Jose Del Monte
              </h1>
              <p style="margin: 6px 0 0 0; font-size: 13px; color: #bfdbfe; font-weight: 500;">
                Legislative Research & Policy Analysis System
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding: 36px 32px;">
              <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #0f172a;">
                Two-Factor Login Verification
              </h2>
              <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                Hello <strong>{$safeName}</strong>,<br>
                A sign-in request was made for your account. Please use the 6-digit authentication code below to complete your sign-in:
              </p>
              
              <div style="background-color: #eff6ff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0;">
                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #1d4ed8; font-family: monospace;">
                  {$otpCode}
                </span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;">
                  (This code is valid for 5 minutes)
                </p>
              </div>

              <p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.5; color: #64748b;">
                🛡️ <strong>Security Tip:</strong> If you select <em>"Remember this device for 12 hours"</em> during verification, you will not need to enter an OTP on this browser again until your 12-hour session window expires.
              </p>

              <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                If you did not request this verification code, please ignore this email or notify your system administrator immediately.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f8fafc; padding: 18px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
              &copy; {$currentYear} Legislative Research System &bull; All Rights Reserved
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $mail->Body    = $htmlBody;
        $mail->AltBody = "Your Legislative Research System 2FA verification code is: {$otpCode}. This code expires in 5 minutes.";

        $mail->send();
        return ['sent' => true, 'message' => 'Verification code sent to your email.'];
    } catch (PHPMailerException $e) {
        return ['sent' => false, 'message' => 'Email dispatch note: ' . ($mail->ErrorInfo ?: $e->getMessage())];
    } catch (\Throwable $t) {
        return ['sent' => false, 'message' => 'Mail error: ' . $t->getMessage()];
    }
}

/**
 * Verify a 6-digit OTP submitted by the user.
 *
 * @param int $userId User ID
 * @param string $inputCode 6-Digit Code
 * @return bool True if valid and verified
 */
function verifyUserOTP($userId, $inputCode) {
    $conn = getDBConnection();
    $inputCode = trim($inputCode);

    if (empty($inputCode) || strlen($inputCode) !== 6 || !ctype_digit($inputCode)) {
        return false;
    }

    // Retrieve active, unexpired, unused OTPs for this user
    $stmt = $conn->prepare("SELECT id, otp_code, expires_at FROM user_otps WHERE user_id = ? AND is_used = 0 AND expires_at >= NOW() ORDER BY created_at DESC LIMIT 5");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $verifiedId = null;
    while ($row = $result->fetch_assoc()) {
        if (password_verify($inputCode, $row['otp_code']) || $inputCode === $row['otp_code']) {
            $verifiedId = (int)$row['id'];
            break;
        }
    }
    $stmt->close();

    if ($verifiedId !== null) {
        // Mark OTP as used
        $upd = $conn->prepare("UPDATE user_otps SET is_used = 1 WHERE id = ?");
        if ($upd) {
            $upd->bind_param("i", $verifiedId);
            $upd->execute();
            $upd->close();
        }
        return true;
    }

    return false;
}

/**
 * Create and register a 12-Hour Trusted Device Token.
 * Sets an HTTP-only cookie and records hash in user_trusted_devices.
 *
 * @param int $userId User ID
 * @param int $hours Lifetime in hours (default 12)
 * @return bool Success status
 */
function createTrustedDeviceToken($userId, $hours = 12) {
    $conn = getDBConnection();
    
    // Generate secure random 64-character token
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);
    $expiresSeconds = $hours * 3600;
    
    // Insert into user_trusted_devices table
    $stmt = $conn->prepare("INSERT INTO user_trusted_devices (user_id, device_token, ip_address, user_agent, expires_at, created_at) 
                           VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())");
    if ($stmt) {
        $stmt->bind_param("isssi", $userId, $tokenHash, $ip, $ua, $hours);
        $stmt->execute();
        $stmt->close();
    }

    // Set secure HTTP-only device cookie
    $cookieName = 'remember_device_' . $userId;
    setcookie($cookieName, $rawToken, [
        'expires'  => time() + $expiresSeconds,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    return true;
}

/**
 * Check if the current browser/device is recognized as trusted (< 12 hours old).
 *
 * @param int $userId User ID
 * @return bool True if device trust token is valid and unexpired
 */
function isDeviceTrusted($userId) {
    $cookieName = 'remember_device_' . $userId;
    if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
        return false;
    }

    $rawToken = $_COOKIE[$cookieName];
    $tokenHash = hash('sha256', $rawToken);

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM user_trusted_devices WHERE user_id = ? AND device_token = ? AND expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $userId, $tokenHash);
    $stmt->execute();
    $res = $stmt->get_result();
    $isTrusted = ($res && $res->num_rows > 0);
    $stmt->close();

    return $isTrusted;
}

/**
 * Revoke all trusted devices for a user (forces OTP on next login from all browsers).
 *
 * @param int $userId User ID
 * @return bool Success
 */
function revokeTrustedDevices($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM user_trusted_devices WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Clear local device cookie
    $cookieName = 'remember_device_' . $userId;
    if (isset($_COOKIE[$cookieName]) && !headers_sent()) {
        setcookie($cookieName, '', time() - 3600, '/');
    }

    return true;
}

/**
 * Helper to mask an email address for security UI preview (e.g. ad***@sjdm.gov.ph).
 */
function maskEmailPreview($email) {
    if (empty($email) || strpos($email, '@') === false) {
        return 'your registered email';
    }
    list($local, $domain) = explode('@', $email, 2);
    $len = strlen($local);
    if ($len <= 2) {
        $maskedLocal = substr($local, 0, 1) . '***';
    } else {
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(3, $len - 2));
    }
    return $maskedLocal . '@' . $domain;
}

// ============================================
// ROLE-BASED ACCESS CONTROL (RBAC) HELPERS
// ============================================

// Defined Roles
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
    define('ROLE_RESEARCHER', 'researcher');
    define('ROLE_DATA_ENCODER', 'data_encoder');
    define('ROLE_VIEWER', 'viewer');
}

/**
 * Get current authenticated user's role.
 *
 * @return string ('admin', 'researcher', 'data_encoder', or 'viewer')
 */
function getCurrentUserRole() {
    return strtolower($_SESSION['role'] ?? ROLE_VIEWER);
}

/**
 * Check if the authenticated user has one of the specified allowed roles.
 *
 * @param string|array $allowedRoles Single role or array of allowed roles
 * @return bool
 */
function hasRole($allowedRoles) {
    if (!isset($_SESSION['username'])) {
        return false;
    }
    $currentRole = getCurrentUserRole();
    if (is_array($allowedRoles)) {
        return in_array($currentRole, array_map('strtolower', $allowedRoles), true);
    }
    return $currentRole === strtolower((string)$allowedRoles);
}

/**
 * Enforce RBAC page access: aborts or redirects if user lacks required role.
 *
 * @param string|array $allowedRoles Single role or array of allowed roles
 * @param string|null $redirectUrl Custom redirect destination
 */
function requireRole($allowedRoles, $redirectUrl = null) {
    if (!isset($_SESSION['username'])) {
        header("Location: " . ($redirectUrl ?: 'login.php'));
        exit();
    }

    if (!hasRole($allowedRoles)) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Access Denied',
            'message' => 'You do not have permission to access this administrative resource.'
        ];

        $target = $redirectUrl;
        if (!$target) {
            $is_sub = (strpos($_SERVER['REQUEST_URI'], '/modules/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $target = $is_sub ? '../dashboard.php' : 'dashboard.php';
        }

        header("Location: " . $target);
        exit();
    }
}

/**
 * Helper permission checkers
 */
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

function isResearcher() {
    return hasRole(ROLE_RESEARCHER);
}

function isDataEncoder() {
    return hasRole(ROLE_DATA_ENCODER);
}

function isViewer() {
    return hasRole(ROLE_VIEWER);
}

function canManageUsers() {
    return isAdmin();
}

function canEditPolicy() {
    return hasRole([ROLE_ADMIN, ROLE_RESEARCHER]);
}

function canDeletePolicy() {
    return isAdmin();
}

function canUploadData() {
    return hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_DATA_ENCODER]);
}

function canRunAI() {
    return hasRole([ROLE_ADMIN, ROLE_RESEARCHER]);
}

function canGenerateReports() {
    return hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_VIEWER]);
}

/**
 * Format a human-readable display label for a role.
 *
 * @param string $role
 * @return string
 */
function getRoleLabel($role) {
    $map = [
        'admin' => 'System Administrator',
        'researcher' => 'Legislative Researcher',
        'data_encoder' => 'Data Encoder',
        'viewer' => 'Viewer / Reviewer'
    ];
    return $map[strtolower($role)] ?? ucfirst(str_replace('_', ' ', $role));
}
