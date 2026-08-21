<?php
require_once "config/config.php";

// If already authenticated and session is valid, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

$error = null;
$notice = null;

if (isset($_GET['expired'])) {
    $notice = "Your login session expired after 12 hours. Please sign in again.";
} elseif (isset($_GET['logged_out'])) {
    $notice = "You have been securely signed out.";
}

// MySQL Database Authentication Handler
if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $conn = getDBConnection();

        // Authenticate user against MySQL database (`users` table schema in database/schema.sql)
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role, department, status, two_factor_enabled, two_factor_type FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if ($user['status'] !== 'active') {
                $error = "Your account is deactivated. Please contact an administrator.";
            } else {
                // Verify password (supports bcrypt hash with legacy plaintext fallback)
                $passwordValid = false;
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } else if ($password === $user['password']) {
                    // Upgrade plaintext to secure password hash
                    $passwordValid = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upStmt->bind_param("si", $newHash, $user['id']);
                    $upStmt->execute();
                    $upStmt->close();
                }

                if ($passwordValid) {
                    $twoFactorRequired = isOTPEnabled() && (bool)($user['two_factor_enabled'] ?? 1);

                    // Check if current device has an active 12-hour trusted device token
                    $isTrusted = isDeviceTrusted($user['id']);

                    if ($twoFactorRequired && !$isTrusted) {
                        // Setup pending 2FA authentication state
                        $_SESSION['pending_2fa_user_id']   = (int)$user['id'];
                        $_SESSION['pending_2fa_username']  = $user['username'];
                        $_SESSION['pending_2fa_full_name'] = $user['full_name'];
                        $_SESSION['pending_2fa_role']      = strtolower($user['role']);
                        $_SESSION['pending_2fa_email']     = $user['email'];
                        $_SESSION['pending_2fa_department']= $user['department'];
                        $_SESSION['pending_2fa_started']   = time();

                        // Generate and dispatch 6-digit OTP via PHPMailer
                        generateAndSendOTP((int)$user['id'], $user['email'], $user['full_name']);

                        header("Location: verify_2fa.php");
                        exit();
                    } else {
                        // Log user directly in (Trusted Device or 2FA disabled)
                        $_SESSION['user_id']     = $user['id'];
                        $_SESSION['username']    = $user['username'];
                        $_SESSION['full_name']   = $user['full_name'];
                        $_SESSION['role']        = strtolower($user['role']);
                        $_SESSION['email']       = $user['email'];
                        $_SESSION['department']  = $user['department'];
                        $_SESSION['login_time']  = time();

                        // Success Toast Notification
                        $_SESSION['toast'] = [
                            'type' => 'success',
                            'title' => 'Login Successful',
                            'message' => 'Welcome back, ' . htmlspecialchars($user['full_name'] ?: $user['username']) . '!'
                        ];

                        // Log activity
                        $actionText = $isTrusted ? "User logged in (12h Trusted Device)" : "User logged in";
                        $moduleText = "Authentication";
                        $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, ?, NOW())");
                        if ($logStmt) {
                            $logStmt->bind_param("sss", $user['username'], $actionText, $moduleText);
                            @$logStmt->execute();
                            @$logStmt->close();
                        }

                        // Update last login
                        $llStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        if ($llStmt) {
                            $llStmt->bind_param("i", $user['id']);
                            @$llStmt->execute();
                            @$llStmt->close();
                        }

                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid Username or Password.";
                }
            }
        } else {
            $error = "Invalid Username or Password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Legislative Research System</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
font-family:'Inter',sans-serif;
}

</style>

</head>

<body class="bg-slate-100">

<div class="min-h-screen flex">

    <!-- LEFT PANEL -->

    <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-900 via-blue-700 to-blue-500 text-white items-center justify-center">

        <div class="max-w-lg p-10">

            <h1 class="text-5xl font-extrabold mb-6 leading-tight">

                Legislative Research,
                Policy Analysis &
                Impact Evaluation System

            </h1>

            <p class="text-blue-100 text-lg leading-8">

                A modern government platform designed for
                Local Government Units to manage legislative
                research, analyze policies, evaluate impacts,
                compare ordinances, generate reports,
                and visualize decision-making data.

            </p>

            <div class="mt-12 grid grid-cols-2 gap-5">

                <div class="bg-white/10 rounded-xl p-5">

                    <i class="fa-solid fa-book-open text-3xl mb-3"></i>

                    <h2 class="font-semibold">
                        Policy Research
                    </h2>

                </div>

                <div class="bg-white/10 rounded-xl p-5">

                    <i class="fa-solid fa-database text-3xl mb-3"></i>

                    <h2 class="font-semibold">
                        Data Collection
                    </h2>

                </div>

                <div class="bg-white/10 rounded-xl p-5">

                    <i class="fa-solid fa-chart-line text-3xl mb-3"></i>

                    <h2 class="font-semibold">
                        Impact Assessment
                    </h2>

                </div>

                <div class="bg-white/10 rounded-xl p-5">

                    <i class="fa-solid fa-chart-pie text-3xl mb-3"></i>

                    <h2 class="font-semibold">
                        Visualization
                    </h2>

                </div>

            </div>

        </div>

    </div>

    <!-- RIGHT PANEL -->

    <div class="w-full lg:w-1/2 flex items-center justify-center">

        <div class="bg-white w-full max-w-md shadow-2xl rounded-2xl p-10">

            <div class="text-center">

                <a href="index.php" class="inline-block w-20 h-20 rounded-full bg-white mx-auto flex items-center justify-center overflow-hidden border-4 border-blue-900 shadow-lg">
                    <img src="City.jpg" alt="San Jose Del Monte City Logo" class="w-full h-full object-cover">
                </a>

                <h2 class="text-3xl font-bold text-slate-800 mt-6">

                    Welcome Back

                </h2>

                <p class="text-slate-500 mt-2">

                    Sign in to continue

                </p>

            </div>

            <?php if (isset($error)): ?>
            <div class="mt-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-3.5 flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-base shrink-0"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php renderToast('error', 'Sign In Failed', $error); ?>
            <?php endif; ?>

            <?php if (isset($notice)): ?>
            <div class="mt-6 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3.5 flex items-center gap-3 text-sm">
                <i class="fa-solid fa-shield-halved text-amber-600 text-base shrink-0"></i>
                <div><?php echo htmlspecialchars($notice); ?></div>
            </div>
            <?php renderToast('info', 'Notice', $notice); ?>
            <?php endif; ?>

            <?php renderToast(); ?>

            <form method="POST" class="mt-8 space-y-5">

                <!-- USERNAME -->

                <div>

                    <label class="block text-sm font-semibold mb-2">

                        Username

                    </label>

                    <div class="relative">

                        <span class="absolute left-4 top-3 text-slate-400">

                            <i class="fa-solid fa-user"></i>

                        </span>

                        <input
                        type="text"
                        name="username"
                        required
                        placeholder="Enter username"
                        class="w-full border rounded-xl pl-12 pr-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    </div>

                </div>

                <!-- PASSWORD -->

                <div>

                    <label class="block text-sm font-semibold mb-2">

                        Password

                    </label>

                    <div class="relative">

                        <span class="absolute left-4 top-3 text-slate-400">

                            <i class="fa-solid fa-lock"></i>

                        </span>

                        <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Enter password"
                        class="w-full border rounded-xl pl-12 pr-12 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                        <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-4 top-3 text-slate-500">

                            <i id="eye" class="fa-solid fa-eye"></i>

                        </button>

                    </div>

                </div>

                <div class="flex justify-between text-sm">

                    <a href="#" class="text-blue-700 hover:underline">

                        Forgot Password?

                    </a>

                    <a href="#" class="text-blue-700 hover:underline">

                        Request Access

                    </a>

                </div>

                <button
                type="submit"
                name="login"
                class="w-full bg-blue-900 hover:bg-blue-800 transition rounded-xl py-3 text-white font-semibold">

                    Sign In

                </button>

            </form>

            <div class="mt-8 text-center text-slate-500 text-sm">

                &copy; 2026 Legislative Research System

            </div>

        </div>

    </div>

</div>

<script>

function togglePassword(){

let password=document.getElementById("password");
let eye=document.getElementById("eye");

if(password.type==="password"){

password.type="text";
eye.classList.replace("fa-eye","fa-eye-slash");

}else{

password.type="password";
eye.classList.replace("fa-eye-slash","fa-eye");

}

}

</script>

</body>
</html>