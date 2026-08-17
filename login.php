<?php
session_start();

require_once "config/config.php";

$error = null;

// MySQL Database Authentication Handler
if (isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $conn = getDBConnection();

        // Authenticate user against MySQL database (`users` table schema in database/schema.sql)
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role, department, status FROM users WHERE username = ? OR email = ?");
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
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = strtolower($user['role']);
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['department'] = $user['department'];

                    // Log activity
                    $actionText = "User logged in";
                    $moduleText = "Authentication";
                    $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, ?, NOW())");
                    if ($logStmt) {
                        $logStmt->bind_param("sss", $user['username'], $actionText, $moduleText);
                        @$logStmt->execute();
                        @$logStmt->close();
                    }

                    header("Location: dashboard.php");
                    exit();
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

            <?php

            if(isset($error))
            {

            ?>

            <div class="mt-6 bg-red-100 border border-red-300 text-red-700 rounded-lg p-3">

                <?php echo $error; ?>

            </div>

            <?php

            }

            ?>

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