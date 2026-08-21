<?php
require_once "config/config.php";

// Ensure there is an active pending 2FA authentication attempt
if (!isset($_SESSION['pending_2fa_user_id']) || !isset($_SESSION['pending_2fa_username'])) {
    header("Location: login.php");
    exit();
}

$userId     = (int)$_SESSION['pending_2fa_user_id'];
$username   = $_SESSION['pending_2fa_username'];
$fullName   = $_SESSION['pending_2fa_full_name'] ?? 'User';
$email      = $_SESSION['pending_2fa_email'] ?? '';
$role       = $_SESSION['pending_2fa_role'] ?? 'viewer';
$department = $_SESSION['pending_2fa_department'] ?? '';

// If OTP is globally disabled, immediately log user in and redirect to dashboard
if (!isOTPEnabled()) {
    $_SESSION['user_id']    = $userId;
    $_SESSION['username']   = $username;
    $_SESSION['full_name']  = $fullName;
    $_SESSION['role']       = $role;
    $_SESSION['email']      = $email;
    $_SESSION['department'] = $department;
    $_SESSION['login_time'] = time();

    $_SESSION['toast'] = [
        'type' => 'success',
        'title' => 'Login Successful',
        'message' => 'Welcome back, ' . htmlspecialchars($fullName ?: $username) . '!'
    ];

    unset($_SESSION['pending_2fa_user_id']);
    unset($_SESSION['pending_2fa_username']);
    unset($_SESSION['pending_2fa_full_name']);
    unset($_SESSION['pending_2fa_role']);
    unset($_SESSION['pending_2fa_email']);
    unset($_SESSION['pending_2fa_department']);
    unset($_SESSION['pending_2fa_started']);

    header("Location: dashboard.php");
    exit();
}

$error = null;
$success = null;

// Handle Resend OTP Request
if (isset($_POST['resend_otp'])) {
    $now = time();
    $lastResend = $_SESSION['last_otp_resend_time'] ?? 0;
    
    if ($now - $lastResend < 30) {
        $error = "Please wait a moment before requesting another code.";
    } else {
        $_SESSION['last_otp_resend_time'] = $now;
        $genResult = generateAndSendOTP($userId, $email, $fullName);
        if ($genResult['success']) {
            $success = "A fresh 6-digit verification code has been sent to your email.";
        } else {
            $error = "Failed to resend code: " . $genResult['message'];
        }
    }
}

// Handle OTP Verification Submission
if (isset($_POST['verify_otp'])) {
    $digit1 = trim($_POST['digit_1'] ?? '');
    $digit2 = trim($_POST['digit_2'] ?? '');
    $digit3 = trim($_POST['digit_3'] ?? '');
    $digit4 = trim($_POST['digit_4'] ?? '');
    $digit5 = trim($_POST['digit_5'] ?? '');
    $digit6 = trim($_POST['digit_6'] ?? '');

    $submittedOtp = $digit1 . $digit2 . $digit3 . $digit4 . $digit5 . $digit6;
    $rememberDevice = isset($_POST['remember_device']);

    if (strlen($submittedOtp) !== 6 || !ctype_digit($submittedOtp)) {
        $error = "Please enter all 6 digits of your verification code.";
    } else {
        $isValid = verifyUserOTP($userId, $submittedOtp);

        if ($isValid) {
            // Establish authenticated session
            $_SESSION['user_id']    = $userId;
            $_SESSION['username']   = $username;
            $_SESSION['full_name']  = $fullName;
            $_SESSION['role']       = $role;
            $_SESSION['email']      = $email;
            $_SESSION['department'] = $department;
            $_SESSION['login_time'] = time();

            // If "Remember this device for 12 hours" is checked, generate 12-hour device trust token
            if ($rememberDevice) {
                createTrustedDeviceToken($userId, 12);
            }

            // Cleanup pending 2FA session variables
            unset($_SESSION['pending_2fa_user_id']);
            unset($_SESSION['pending_2fa_username']);
            unset($_SESSION['pending_2fa_full_name']);
            unset($_SESSION['pending_2fa_role']);
            unset($_SESSION['pending_2fa_email']);
            unset($_SESSION['pending_2fa_department']);
            unset($_SESSION['pending_2fa_started']);

            // Log authentication event
            $conn = getDBConnection();
            $actionText = $rememberDevice ? "2FA verified (12h Device Trust Granted)" : "2FA verified successfully";
            $moduleText = "Authentication";
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, ?, NOW())");
            if ($logStmt) {
                $logStmt->bind_param("sss", $username, $actionText, $moduleText);
                @$logStmt->execute();
                @$logStmt->close();
            }

            // Update last login
            $llStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            if ($llStmt) {
                $llStmt->bind_param("i", $userId);
                @$llStmt->execute();
                @$llStmt->close();
            }

            // Success Toast Notification
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Login Successful',
                'message' => 'Welcome back, ' . htmlspecialchars($fullName ?: $username) . '!'
            ];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "The verification code is invalid or has expired. Please try again.";
        }
    }
}

$maskedEmail = maskEmailPreview($email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Legislative Research System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .otp-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg">

        <!-- MAIN CARD -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border border-slate-200">

            <!-- HEADER -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-50 text-blue-900 shadow-inner mb-5 border border-blue-100">
                    <i class="fa-solid fa-shield-halved text-3xl text-blue-700"></i>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">
                    Two-Step Verification
                </h1>
                <p class="text-slate-500 text-sm mt-2 leading-relaxed">
                    We sent a 6-digit security code to<br>
                    <span class="font-semibold text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-md text-xs inline-block mt-1"><?php echo htmlspecialchars($maskedEmail); ?></span>
                </p>
            </div>

            <!-- ALERTS -->
            <?php if (isset($error)): ?>
            <div class="mt-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-lg shrink-0"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php renderToast('error', 'Verification Failed', $error); ?>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-check text-emerald-500 text-lg shrink-0"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
            <?php renderToast('success', 'Code Sent', $success); ?>
            <?php endif; ?>

            <?php renderToast(); ?>

            <!-- OTP FORM -->
            <form method="POST" id="otpForm" class="mt-8 space-y-6">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 text-center mb-3">
                        Enter 6-Digit Verification Code
                    </label>
                    <div class="flex justify-center gap-2 sm:gap-3" id="otpInputsContainer">
                        <input type="text" maxlength="1" name="digit_1" id="digit_1" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required autofocus>
                        <input type="text" maxlength="1" name="digit_2" id="digit_2" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required>
                        <input type="text" maxlength="1" name="digit_3" id="digit_3" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required>
                        <input type="text" maxlength="1" name="digit_4" id="digit_4" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required>
                        <input type="text" maxlength="1" name="digit_5" id="digit_5" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required>
                        <input type="text" maxlength="1" name="digit_6" id="digit_6" class="otp-input w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold font-mono rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white outline-none transition" autocomplete="off" inputmode="numeric" required>
                    </div>
                </div>

                <!-- REMEMBER DEVICE CHECKBOX (12 HOURS) -->
                <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-4">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" name="remember_device" id="remember_device" value="1" checked class="w-5 h-5 mt-0.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                        <div class="text-xs text-slate-700">
                            <span class="font-semibold text-slate-900 block text-sm">Remember this device for 12 hours</span>
                        </div>
                    </label>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" name="verify_otp" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock text-sm"></i>
                    <span>Verify & Continue</span>
                </button>
            </form>

            <!-- RESEND & BACK OPTIONS -->
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <form method="POST" id="resendForm">
                    <button type="submit" name="resend_otp" id="resendBtn" class="text-blue-700 hover:text-blue-900 font-semibold flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Resend Code</span>
                    </button>
                </form>

                <a href="logout.php" class="text-slate-500 hover:text-slate-800 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Cancel & Sign In As Other</span>
                </a>
            </div>

        </div>

        <div class="mt-6 text-center text-slate-400 text-xs">
            &copy; <?php echo date('Y'); ?> Legislative Research System &bull; City of San Jose Del Monte
        </div>

    </div>

    <!-- SCRIPT FOR AUTOMATIC DIGIT ADVANCE, BACKSPACE, PASTE, & AUTOFILL -->
    <script>
        const inputs = Array.from(document.querySelectorAll('.otp-input'));
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value;
                if (val.length > 1) {
                    e.target.value = val.slice(0, 1);
                }
                if (e.target.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                if (/^\d{6}$/.test(pasteData)) {
                    pasteData.split('').forEach((char, i) => {
                        if (inputs[i]) inputs[i].value = char;
                    });
                    inputs[inputs.length - 1].focus();
                }
            });
        });
    </script>
</body>
</html>
