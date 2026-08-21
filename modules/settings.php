<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

$user_id  = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'];
$pageTitle = "Settings";

// Fetch full user record
$stmt = $conn->prepare("SELECT id,username,full_name,email,role,department,status,two_factor_enabled,two_factor_type,created_at,last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $stmt2 = $conn->prepare("SELECT id,username,full_name,email,role,department,status,two_factor_enabled,two_factor_type,created_at,last_login FROM users WHERE username = ?");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $user = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
}

$success_msg = '';
$error_msg   = '';
$active_tab  = 'profile';

// ─── Handle Profile Update ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $active_tab = 'profile';
    $new_full   = trim($_POST['full_name'] ?? '');
    $new_email  = trim($_POST['email'] ?? '');
    $new_dept   = trim($_POST['department'] ?? '');

    if (empty($new_full) || empty($new_email)) {
        $error_msg = "Full name and email are required.";
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, department=?, updated_at=NOW() WHERE id=?");
        $upd->bind_param("sssi", $new_full, $new_email, $new_dept, $user['id']);
        if ($upd->execute()) {
            $_SESSION['full_name']   = $new_full;
            $_SESSION['email']       = $new_email;
            $_SESSION['department']  = $new_dept;
            $user['full_name']       = $new_full;
            $user['email']           = $new_email;
            $user['department']      = $new_dept;
            $success_msg = "Profile updated successfully.";
        } else {
            $error_msg = "Failed to update profile. Please try again.";
        }
        $upd->close();
    }
}

// ─── Handle Password Change ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $active_tab = 'password';
    $cur_pass   = $_POST['current_password'] ?? '';
    $new_pass   = $_POST['new_password'] ?? '';
    $conf_pass  = $_POST['confirm_password'] ?? '';

    $ph = $conn->prepare("SELECT password FROM users WHERE id=?");
    $ph->bind_param("i", $user['id']);
    $ph->execute();
    $pw_row = $ph->get_result()->fetch_assoc();
    $ph->close();

    $valid_cur = password_verify($cur_pass, $pw_row['password']) || ($cur_pass === $pw_row['password']);

    if (!$valid_cur) {
        $error_msg = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $conf_pass) {
        $error_msg = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upw = $conn->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
        $upw->bind_param("si", $new_hash, $user['id']);
        $success_msg = $upw->execute() ? "Password changed successfully." : "Failed to change password.";
        $upw->close();
    }
}

// ─── Handle 2FA Update ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_2fa'])) {
    $active_tab = 'security';
    $enable2FA  = isset($_POST['two_factor_enabled']) ? 1 : 0;
    $up2 = $conn->prepare("UPDATE users SET two_factor_enabled=?, updated_at=NOW() WHERE id=?");
    $up2->bind_param("ii", $enable2FA, $user['id']);
    if ($up2->execute()) {
        $user['two_factor_enabled'] = $enable2FA;
        $actionText = $enable2FA ? "Enabled Two-Factor Authentication (2FA)" : "Disabled Two-Factor Authentication (2FA)";
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, ?, 'Security', NOW())");
        if ($logStmt) {
            $logStmt->bind_param("ss", $username, $actionText);
            @$logStmt->execute();
            @$logStmt->close();
        }
        $success_msg = $enable2FA ? "Two-Factor Authentication has been enabled." : "Two-Factor Authentication has been disabled.";
    } else {
        $error_msg = "Failed to update 2FA configuration.";
    }
    $up2->close();
}

// ─── Handle Revoke Trusted Devices ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_trusted_devices'])) {
    $active_tab = 'security';
    revokeTrustedDevices($user['id']);
    $logStmt = $conn->prepare("INSERT INTO activity_logs (user, action, module, timestamp) VALUES (?, 'Revoked all 12-hour trusted devices', 'Security', NOW())");
    if ($logStmt) {
        $logStmt->bind_param("s", $username);
        @$logStmt->execute();
        @$logStmt->close();
    }
    $success_msg = "All 12-hour trusted devices have been revoked. An OTP will be required on your next login.";
}

// ─── Trusted Devices Count ───────────────────────────────────────────────────
$dev_res = $conn->query("SELECT COUNT(*) as cnt FROM user_trusted_devices WHERE user_id = " . (int)$user['id'] . " AND expires_at > NOW()");
$trusted_devices_count = (int)($dev_res ? $dev_res->fetch_assoc()['cnt'] : 0);

// ─── 12-Hour Session Expiration Stats ────────────────────────────────────────
$session_start_time = $_SESSION['login_time'] ?? time();
$session_lifetime = (int)Environment::get('AUTH_SESSION_LIFETIME_SECONDS', 43200);
$session_expires_at = $session_start_time + $session_lifetime;
$session_remaining_seconds = max(0, $session_expires_at - time());
$session_remaining_hours = floor($session_remaining_seconds / 3600);
$session_remaining_minutes = floor(($session_remaining_seconds % 3600) / 60);

// ─── Activity count ───────────────────────────────────────────────────────────
$act_res = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE user='" . $conn->real_escape_string($username) . "'");
$activity_count = (int)($act_res ? $act_res->fetch_assoc()['cnt'] : 0);

$role_colors = ['admin' => 'blue', 'researcher' => 'purple', 'viewer' => 'slate'];
$rc = $role_colors[$user['role'] ?? 'viewer'] ?? 'slate';

$initials = strtoupper(substr($user['full_name'] ?? $username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> – SJDM Legislative Research</title>
    <meta name="description" content="Manage your personal account settings, appearance, and notification preferences.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* ── Tab system ── */
        .settings-tab-panel { display: none; }
        .settings-tab-panel.active { display: block; }

        /* ── Sidebar active pill ── */
        .settings-nav-item.active {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(29,78,216,.35);
        }
        .settings-nav-item.active i { color: #93c5fd; }
        .settings-nav-item:not(.active):hover { background: #f1f5f9; color: #1d4ed8; }

        /* ── Animated gradient avatar ── */
        .avatar-ring {
            background: conic-gradient(from 180deg at 50% 50%, #3b82f6 0deg, #1d4ed8 72deg, #7c3aed 144deg, #3b82f6 216deg, #1d4ed8 288deg, #1d4ed8 360deg);
            animation: spin-slow 8s linear infinite;
        }
        @keyframes spin-slow { to { transform: rotate(360deg); } }

        /* ── Toggle Switch ── */
        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1; border-radius: 24px; transition: .3s;
        }
        .toggle-slider:before {
            position: absolute; content: ""; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background-color: white;
            border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        input:checked + .toggle-slider { background-color: #1d4ed8; }
        input:checked + .toggle-slider:before { transform: translateX(20px); }

        /* ── Colour swatch selector ── */
        .color-swatch { width: 32px; height: 32px; border-radius: 50%; cursor: pointer; transition: transform .15s, box-shadow .15s; }
        .color-swatch:hover { transform: scale(1.15); }
        .color-swatch.selected { box-shadow: 0 0 0 3px #fff, 0 0 0 5px currentColor; transform: scale(1.1); }

        /* ── Font size preview ── */
        .font-preview { transition: font-size .2s; }

        /* ── Password strength bars ── */
        .strength-bar { height: 6px; border-radius: 3px; transition: background .3s; }

        /* ── Card hover lift ── */
        .settings-card { transition: box-shadow .2s; }
        .settings-card:hover { box-shadow: 0 4px 24px rgba(30,64,175,.08); }

        /* ── Input focus ring ── */
        .settings-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }

        /* ── Compact sidebar compat ── */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content-wrapper ml-72 flex flex-col min-h-screen transition-all duration-300">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 p-6 md:p-8">

        <!-- ── Page Header ── -->
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-blue-700 flex items-center justify-center shadow">
                        <i class="fa-solid fa-gear text-white text-sm"></i>
                    </span>
                    Personal Settings
                </h2>
                <p class="text-sm text-slate-500 mt-1 ml-0.5">Manage your profile, security, notifications & appearance</p>
            </div>
        </div>

        <!-- ── Alert Messages ── -->
        <?php if ($success_msg): ?>
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium animate-pulse-once">
            <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

            <!-- ══════════════════════════════════════════════════════════
                 LEFT PANEL: User card + Settings navigation
            ══════════════════════════════════════════════════════════ -->
            <div class="xl:col-span-1 space-y-5">

                <!-- Avatar Card -->
                <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="h-20 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-800 relative">
                        <div class="absolute inset-0 opacity-20" style="background: radial-gradient(circle at 70% 50%, #60a5fa 0%, transparent 60%);"></div>
                    </div>
                    <div class="px-5 pb-5 -mt-9 text-center">
                        <!-- Animated avatar ring -->
                        <div class="inline-flex items-center justify-center w-[72px] h-[72px] rounded-full p-[3px] avatar-ring mx-auto">
                            <div class="w-full h-full rounded-full bg-gradient-to-br from-blue-800 to-blue-600 flex items-center justify-center border-2 border-white">
                                <span class="text-white text-2xl font-bold leading-none" id="avatarInitial"><?php echo $initials; ?></span>
                            </div>
                        </div>
                        <h3 class="mt-3 text-base font-bold text-slate-800" id="sideCardName"><?php echo htmlspecialchars($user['full_name'] ?? $username); ?></h3>
                        <p class="text-xs text-slate-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold bg-<?php echo $rc; ?>-100 text-<?php echo $rc; ?>-700 capitalize">
                            <?php echo htmlspecialchars($user['role'] ?? 'viewer'); ?>
                        </span>

                        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-center">
                            <div>
                                <p class="text-lg font-bold text-slate-800"><?php echo number_format($activity_count); ?></p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wide">Activities</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-slate-800">
                                    <?php echo $user['status'] === 'active' ? '<span class="text-emerald-600">●</span>' : '<span class="text-red-500">●</span>'; ?>
                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                </p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wide">Status</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-2 space-y-1">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 pt-1 pb-1">Settings</p>

                    <button onclick="switchSettingsTab('profile')" id="nav-profile"
                        class="settings-nav-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-700">
                        <i class="fa-solid fa-user-pen w-5 text-center text-blue-600"></i>
                        <span>Profile Info</span>
                    </button>

                    <button onclick="switchSettingsTab('password')" id="nav-password"
                        class="settings-nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-700">
                        <i class="fa-solid fa-lock w-5 text-center text-blue-600"></i>
                        <span>Change Password</span>
                    </button>

                    <button onclick="switchSettingsTab('security')" id="nav-security"
                        class="settings-nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-700">
                        <i class="fa-solid fa-shield-halved w-5 text-center text-blue-600"></i>
                        <span>Security & 2FA</span>
                    </button>

                    <button onclick="switchSettingsTab('notifications')" id="nav-notifications"
                        class="settings-nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-700">
                        <i class="fa-solid fa-bell w-5 text-center text-blue-600"></i>
                        <span>Notifications</span>
                    </button>

                    <button onclick="switchSettingsTab('appearance')" id="nav-appearance"
                        class="settings-nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-700">
                        <i class="fa-solid fa-palette w-5 text-center text-blue-600"></i>
                        <span>Appearance</span>
                    </button>

                    <div class="border-t border-slate-100 pt-1 mt-1">
                        <a href="profile.php"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-all">
                            <i class="fa-solid fa-id-card w-5 text-center"></i>
                            <span>Full Profile</span>
                        </a>
                    </div>
                </nav>

                <!-- Account Info Card -->
                <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Account Info</p>
                    <div class="flex items-center gap-2.5 text-sm">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-calendar-plus text-blue-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400">Member since</p>
                            <p class="text-xs font-semibold text-slate-700"><?php echo $user['created_at'] ? date('M j, Y', strtotime($user['created_at'])) : '—'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock text-amber-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400">Last login</p>
                            <p class="text-xs font-semibold text-slate-700"><?php echo $user['last_login'] ? date('M j, g:i A', strtotime($user['last_login'])) : 'First session'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-envelope text-slate-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400">Email</p>
                            <p class="text-xs font-semibold text-slate-700 truncate max-w-[140px]"><?php echo htmlspecialchars($user['email'] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>

            </div><!-- /left panel -->

            <!-- ══════════════════════════════════════════════════════════
                 RIGHT PANEL: Settings panels
            ══════════════════════════════════════════════════════════ -->
            <div class="xl:col-span-3 space-y-5">

                <!-- ─────────────────────────────────────────────────────
                     TAB 1: Profile Info
                ───────────────────────────────────────────────────── -->
                <div id="panel-profile" class="settings-tab-panel active">
                    <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">

                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <i class="fa-solid fa-user-pen text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Profile Information</h4>
                                <p class="text-xs text-slate-500">Update your personal details</p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-5" id="profileForm">
                            <input type="hidden" name="update_profile" value="1">

                            <!-- Avatar preview row -->
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-800 to-blue-600 flex items-center justify-center shadow-md">
                                    <span class="text-white text-xl font-bold" id="profileAvatarPreview"><?php echo $initials; ?></span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Profile Avatar</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Your initials are used as your avatar</p>
                                </div>
                            </div>

                            <!-- Name + Username -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input id="fullNameInput" type="text" name="full_name"
                                        value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required
                                        class="settings-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm transition"
                                        oninput="updateAvatarPreview(this.value)">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Username</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                        class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm text-slate-400 cursor-not-allowed">
                                    <p class="text-xs text-slate-400 mt-1"><i class="fa-solid fa-lock text-xs mr-1"></i>Username cannot be changed</p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-envelope absolute left-4 top-3 text-slate-400 text-sm pointer-events-none"></i>
                                    <input type="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required
                                        class="settings-input w-full border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm transition">
                                </div>
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Department / Division</label>
                                <div class="relative">
                                    <i class="fa-solid fa-building absolute left-4 top-3 text-slate-400 text-sm pointer-events-none"></i>
                                    <input type="text" name="department"
                                        value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>"
                                        placeholder="e.g. Legislative Research Division"
                                        class="settings-input w-full border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm transition">
                                </div>
                            </div>

                            <!-- Read-only fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Role</label>
                                    <div class="flex items-center gap-2 border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span class="text-sm text-slate-400"><?php echo ucfirst(htmlspecialchars($user['role'] ?? 'viewer')); ?></span>
                                        <span class="ml-auto text-[10px] text-slate-400 bg-slate-200 px-2 py-0.5 rounded-full">Read-only</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Account Status</label>
                                    <div class="flex items-center gap-2 border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5">
                                        <span class="w-2 h-2 rounded-full <?php echo $user['status'] === 'active' ? 'bg-emerald-500' : 'bg-red-400'; ?>"></span>
                                        <span class="text-sm text-slate-400"><?php echo ucfirst(htmlspecialchars($user['status'] ?? 'active')); ?></span>
                                        <span class="ml-auto text-[10px] text-slate-400 bg-slate-200 px-2 py-0.5 rounded-full">Read-only</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end gap-3">
                                <button type="reset" class="px-5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                    Reset
                                </button>
                                <button type="submit"
                                    class="bg-blue-700 hover:bg-blue-800 active:scale-95 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-sm shadow-blue-200">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────
                     TAB 2: Change Password
                ───────────────────────────────────────────────────── -->
                <div id="panel-password" class="settings-tab-panel">
                    <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">

                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <i class="fa-solid fa-lock text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Change Password</h4>
                                <p class="text-xs text-slate-500">Keep your account secure with a strong password</p>
                            </div>
                        </div>

                        <!-- Security tips banner -->
                        <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl mb-6 text-sm text-blue-800">
                            <i class="fa-solid fa-shield-halved text-blue-500 mt-0.5 shrink-0"></i>
                            <div>
                                <p class="font-semibold text-sm">Password Tips</p>
                                <ul class="mt-1 space-y-0.5 text-xs text-blue-700 list-disc list-inside">
                                    <li>Use at least 8 characters</li>
                                    <li>Mix uppercase, lowercase, numbers & symbols</li>
                                    <li>Avoid using your name or birthday</li>
                                </ul>
                            </div>
                        </div>

                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="change_password" value="1">

                            <!-- Current password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    Current Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-key absolute left-4 top-3 text-slate-400 text-sm pointer-events-none"></i>
                                    <input type="password" name="current_password" id="cur_pass" required
                                        class="settings-input w-full border border-slate-300 rounded-xl pl-11 pr-11 py-2.5 text-sm transition">
                                    <button type="button" onclick="togglePw('cur_pass', this)"
                                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 transition">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    New Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-lock absolute left-4 top-3 text-slate-400 text-sm pointer-events-none"></i>
                                    <input type="password" name="new_password" id="new_pass" required minlength="6"
                                        class="settings-input w-full border border-slate-300 rounded-xl pl-11 pr-11 py-2.5 text-sm transition"
                                        oninput="checkPasswordStrength(this.value)">
                                    <button type="button" onclick="togglePw('new_pass', this)"
                                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 transition">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                </div>

                                <!-- Strength meter -->
                                <div class="mt-2.5 space-y-1.5">
                                    <div class="flex gap-1.5">
                                        <div id="ps1" class="strength-bar flex-1 bg-slate-200"></div>
                                        <div id="ps2" class="strength-bar flex-1 bg-slate-200"></div>
                                        <div id="ps3" class="strength-bar flex-1 bg-slate-200"></div>
                                        <div id="ps4" class="strength-bar flex-1 bg-slate-200"></div>
                                    </div>
                                    <p id="ps-label" class="text-xs text-slate-400"></p>
                                </div>

                                <!-- Requirements checklist -->
                                <div class="mt-3 grid grid-cols-2 gap-1.5" id="pwChecks">
                                    <div id="chk-len" class="flex items-center gap-1.5 text-xs text-slate-400">
                                        <i class="fa-solid fa-circle text-[8px]"></i> At least 6 chars
                                    </div>
                                    <div id="chk-upper" class="flex items-center gap-1.5 text-xs text-slate-400">
                                        <i class="fa-solid fa-circle text-[8px]"></i> Uppercase letter
                                    </div>
                                    <div id="chk-num" class="flex items-center gap-1.5 text-xs text-slate-400">
                                        <i class="fa-solid fa-circle text-[8px]"></i> Number
                                    </div>
                                    <div id="chk-sym" class="flex items-center gap-1.5 text-xs text-slate-400">
                                        <i class="fa-solid fa-circle text-[8px]"></i> Symbol
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    Confirm New Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-lock-open absolute left-4 top-3 text-slate-400 text-sm pointer-events-none"></i>
                                    <input type="password" name="confirm_password" id="conf_pass" required
                                        class="settings-input w-full border border-slate-300 rounded-xl pl-11 pr-11 py-2.5 text-sm transition"
                                        oninput="checkPasswordMatch()">
                                    <button type="button" onclick="togglePw('conf_pass', this)"
                                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 transition">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                </div>
                                <p id="match-label" class="text-xs mt-1 hidden"></p>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="submit"
                                    class="bg-blue-700 hover:bg-blue-800 active:scale-95 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-sm shadow-blue-200">
                                    <i class="fa-solid fa-key"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────
                     TAB: Security & 2FA (Two-Factor Authentication & 12h Session)
                ───────────────────────────────────────────────────── -->
                <div id="panel-security" class="settings-tab-panel">
                    <div class="space-y-5">
                        
                        <!-- 2FA Master Switch Card -->
                        <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
                            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                        <i class="fa-solid fa-shield-halved text-blue-700"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-bold text-slate-800">Two-Factor Authentication (2FA)</h4>
                                        <p class="text-xs text-slate-500">Secure sign-in with 6-digit email verification</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo ($user['two_factor_enabled'] ?? 1) ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                                    <i class="fa-solid <?php echo ($user['two_factor_enabled'] ?? 1) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-slate-400'; ?> mr-1"></i>
                                    <?php echo ($user['two_factor_enabled'] ?? 1) ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>

                            <form method="POST" class="space-y-5">
                                <input type="hidden" name="update_2fa" value="1">
                                
                                <div class="flex items-start justify-between p-4 bg-slate-50 rounded-xl border border-slate-200 gap-4">
                                    <div class="space-y-1">
                                        <label for="two_factor_toggle" class="text-sm font-semibold text-slate-800 cursor-pointer">
                                            Require 2FA Code on Login
                                        </label>
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            When enabled, signing in requires a 6-digit OTP sent to your registered email (<strong class="text-slate-700"><?php echo htmlspecialchars($user['email'] ?? 'email'); ?></strong>) via PHPMailer.
                                        </p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
                                        <input type="checkbox" name="two_factor_enabled" id="two_factor_toggle" value="1" <?php echo ($user['two_factor_enabled'] ?? 1) ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-sm shadow-blue-200">
                                        <i class="fa-solid fa-floppy-disk"></i> Save 2FA Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 12-Hour Session Expiration Status Card -->
                        <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                    <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-800">12-Hour Session Expiration Policy</h4>
                                    <p class="text-xs text-slate-500">Continuous session security lifecycle</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Session Started</p>
                                    <p class="text-sm font-bold text-slate-800 mt-1"><?php echo date('M j, g:i A', $session_start_time); ?></p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Hard Expiration</p>
                                    <p class="text-sm font-bold text-slate-800 mt-1"><?php echo date('M j, g:i A', $session_expires_at); ?></p>
                                </div>
                                <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Time Remaining</p>
                                    <p class="text-sm font-bold text-blue-900 mt-1"><?php echo $session_remaining_hours; ?>h <?php echo $session_remaining_minutes; ?>m</p>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                                <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                                For data protection and compliance, all active sessions strictly expire after <strong>12 hours</strong>, terminating background cookies and requiring re-authentication.
                            </p>
                        </div>

                        <!-- 12-Hour Trusted Devices Manager -->
                        <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                                        <i class="fa-solid fa-laptop-code text-purple-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-bold text-slate-800">12-Hour Trusted Devices</h4>
                                        <p class="text-xs text-slate-500">Browsers that bypass OTP verification</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <?php echo $trusted_devices_count; ?> Active
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                                When you check <em>"Remember this device for 12 hours"</em> upon sign-in, an encrypted device token is saved to prevent repeated OTP requests for 12 hours.
                            </p>

                            <form method="POST" onsubmit="return confirm('Revoke all trusted devices? You will be prompted for an OTP on your next login.');">
                                <input type="hidden" name="revoke_trusted_devices" value="1">
                                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs font-semibold px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                                    <i class="fa-solid fa-ban"></i> Revoke All Trusted Devices
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────
                     TAB 3: Notification Preferences
                ───────────────────────────────────────────────────── -->
                <div id="panel-notifications" class="settings-tab-panel">
                    <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">

                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <i class="fa-solid fa-bell text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Notification Preferences</h4>
                                <p class="text-xs text-slate-500">Control what alerts you receive</p>
                            </div>
                        </div>

                        <div class="space-y-2">

                            <!-- In-app notifications -->
                            <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">In-App</p>
                                <div class="space-y-4">
                                    <?php
                                    $notif_items = [
                                        ['id' => 'n_new_policy',   'icon' => 'fa-book-open',     'color' => 'blue',   'label' => 'New Policy Research added',         'sub' => 'Get notified when a new policy document is published'],
                                        ['id' => 'n_data_upload',  'icon' => 'fa-database',       'color' => 'purple', 'label' => 'Data collection uploads',            'sub' => 'Alerts when data is uploaded to the system'],
                                        ['id' => 'n_assessment',   'icon' => 'fa-chart-line',     'color' => 'emerald','label' => 'Impact assessment updates',          'sub' => 'Notifications for assessment status changes'],
                                        ['id' => 'n_report',       'icon' => 'fa-file-lines',     'color' => 'amber',  'label' => 'Report generation complete',         'sub' => 'Alert when a report finishes generating'],
                                        ['id' => 'n_benchmark',    'icon' => 'fa-scale-balanced', 'color' => 'indigo', 'label' => 'Benchmarking analysis ready',        'sub' => 'When a comparison analysis is available'],
                                        ['id' => 'n_user_activity','icon' => 'fa-users-gear',     'color' => 'slate',  'label' => 'User activity (Admin only)',         'sub' => 'Track team member actions in the system'],
                                    ];
                                    foreach ($notif_items as $ni):
                                    ?>
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-lg bg-<?php echo $ni['color']; ?>-50 flex items-center justify-center shrink-0">
                                                <i class="fa-solid <?php echo $ni['icon']; ?> text-<?php echo $ni['color']; ?>-600 text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-700"><?php echo $ni['label']; ?></p>
                                                <p class="text-xs text-slate-400 truncate"><?php echo $ni['sub']; ?></p>
                                            </div>
                                        </div>
                                        <label class="toggle-switch shrink-0">
                                            <input type="checkbox" id="<?php echo $ni['id']; ?>"
                                                <?php echo in_array($ni['id'], ['n_new_policy','n_data_upload','n_assessment','n_report']) ? 'checked' : ''; ?>
                                                onchange="saveNotifPref('<?php echo $ni['id']; ?>', this.checked)">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Bell notification frequency -->
                            <div class="p-4 rounded-xl border border-slate-100 bg-slate-50">
                                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Bell Notification Display</p>
                                <div class="space-y-2">
                                    <?php
                                    $freq = [
                                        ['val' => 'all',       'label' => 'Show all activities', 'sub' => 'Every action appears in the bell'],
                                        ['val' => 'important', 'label' => 'Important only',      'sub' => 'Only high-priority updates'],
                                        ['val' => 'none',      'label' => 'Mute all',             'sub' => 'No bell notifications'],
                                    ];
                                    foreach ($freq as $f):
                                    ?>
                                    <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-white border border-transparent hover:border-slate-200 cursor-pointer transition-all">
                                        <input type="radio" name="notif_freq" value="<?php echo $f['val']; ?>"
                                            <?php echo $f['val'] === 'all' ? 'checked' : ''; ?>
                                            class="accent-blue-700" onchange="saveNotifFreq(this.value)">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-700"><?php echo $f['label']; ?></p>
                                            <p class="text-xs text-slate-400"><?php echo $f['sub']; ?></p>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>

                        <div class="pt-4 flex justify-end">
                            <button onclick="showToast('Notification preferences saved!')"
                                class="bg-blue-700 hover:bg-blue-800 active:scale-95 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-sm shadow-blue-200">
                                <i class="fa-solid fa-floppy-disk"></i> Save Preferences
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────
                     TAB 4: Appearance
                ───────────────────────────────────────────────────── -->
                <div id="panel-appearance" class="settings-tab-panel">
                    <div class="settings-card bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-8">

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <i class="fa-solid fa-palette text-blue-700"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">Appearance</h4>
                                <p class="text-xs text-slate-500">Customize how the app looks for you</p>
                            </div>
                        </div>

                        <!-- Sidebar Compact -->
                        <div class="flex items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <i class="fa-solid fa-sidebar text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Compact Sidebar</p>
                                    <p class="text-xs text-slate-400">Collapse sidebar to icon-only mode</p>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="toggle-compact" onchange="toggleSidebarFromSettings(this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <!-- Font Size -->
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                                    <i class="fa-solid fa-text-height text-indigo-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Font Size</p>
                                    <p class="text-xs text-slate-400">Adjust text size for readability</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-slate-400 font-medium">A</span>
                                <input type="range" id="fontSizeRange" min="12" max="18" value="14" step="1"
                                    class="flex-1 accent-blue-700 cursor-pointer" oninput="applyFontSize(this.value)">
                                <span class="text-lg text-slate-400 font-bold">A</span>
                                <span id="fontSizeLabel" class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full min-w-[38px] text-center">14px</span>
                            </div>
                            <div class="p-3 bg-white rounded-lg border border-slate-200">
                                <p class="font-preview text-slate-700 font-medium" id="fontPreviewText">
                                    The quick brown fox jumps over the lazy dog. — SJDM Legislative Research System
                                </p>
                            </div>
                        </div>

                        <!-- Accent Color -->
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-pink-50 flex items-center justify-center">
                                    <i class="fa-solid fa-droplet text-pink-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Accent Color</p>
                                    <p class="text-xs text-slate-400">Choose your preferred highlight color</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-wrap" id="colorSwatches">
                                <?php
                                $colors = [
                                    ['name' => 'Blue',   'hex' => '#1d4ed8', 'tw' => 'bg-blue-700'],
                                    ['name' => 'Indigo', 'hex' => '#4338ca', 'tw' => 'bg-indigo-700'],
                                    ['name' => 'Violet', 'hex' => '#7c3aed', 'tw' => 'bg-violet-700'],
                                    ['name' => 'Emerald','hex' => '#059669', 'tw' => 'bg-emerald-600'],
                                    ['name' => 'Teal',   'hex' => '#0d9488', 'tw' => 'bg-teal-600'],
                                    ['name' => 'Slate',  'hex' => '#475569', 'tw' => 'bg-slate-600'],
                                    ['name' => 'Rose',   'hex' => '#e11d48', 'tw' => 'bg-rose-600'],
                                ];
                                foreach ($colors as $c):
                                ?>
                                <button onclick="selectAccentColor('<?php echo $c['hex']; ?>', this)"
                                    title="<?php echo $c['name']; ?>"
                                    style="background-color: <?php echo $c['hex']; ?>; color: <?php echo $c['hex']; ?>;"
                                    class="color-swatch <?php echo $c['name'] === 'Blue' ? 'selected' : ''; ?>">
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-slate-200">
                                <div class="w-6 h-6 rounded-full" id="accentPreviewDot" style="background:#1d4ed8;"></div>
                                <p class="text-xs text-slate-600">Selected: <span id="accentColorName" class="font-semibold">Blue</span></p>
                                <div class="ml-auto px-3 py-1 rounded-lg text-white text-xs font-semibold transition" id="accentBtnPreview" style="background:#1d4ed8;">
                                    Sample Button
                                </div>
                            </div>
                        </div>

                        <!-- Layout Density -->
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 space-y-3">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                                    <i class="fa-solid fa-table-cells text-amber-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Layout Density</p>
                                    <p class="text-xs text-slate-400">Control spacing between elements</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <?php
                                $densities = [
                                    ['val' => 'compact',     'label' => 'Compact',     'icon' => 'fa-compress'],
                                    ['val' => 'comfortable', 'label' => 'Comfortable', 'icon' => 'fa-arrows-up-down', 'active' => true],
                                    ['val' => 'spacious',    'label' => 'Spacious',    'icon' => 'fa-expand'],
                                ];
                                foreach ($densities as $d):
                                ?>
                                <button onclick="setDensity('<?php echo $d['val']; ?>', this)"
                                    class="density-btn flex flex-col items-center gap-2 p-3 rounded-xl border transition-all text-sm font-semibold
                                    <?php echo isset($d['active']) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-slate-200 text-slate-600 hover:border-blue-200'; ?>">
                                    <i class="fa-solid <?php echo $d['icon']; ?> text-lg"></i>
                                    <?php echo $d['label']; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <button onclick="resetAppearance()"
                                class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition flex items-center gap-2">
                                <i class="fa-solid fa-rotate-left text-xs"></i> Reset to Default
                            </button>
                            <button onclick="saveAppearance()"
                                class="bg-blue-700 hover:bg-blue-800 active:scale-95 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-sm shadow-blue-200">
                                <i class="fa-solid fa-floppy-disk"></i> Save Appearance
                            </button>
                        </div>

                    </div>
                </div>
                <!-- /panels -->

            </div><!-- /right panel -->
        </div><!-- /grid -->

    </main>

    <footer class="text-center py-4 text-xs text-slate-400 border-t border-slate-200 bg-white">
        &copy; <?php echo date('Y'); ?> San Jose Del Monte Legislative Research System. All rights reserved.
    </footer>
</div>

<!-- ── Toast Notification ──────────────────────────────────────────────────── -->
<div id="toastMsg"
    class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-2xl text-sm font-medium opacity-0 translate-y-4 transition-all duration-300 pointer-events-none">
    <i class="fa-solid fa-circle-check text-emerald-400"></i>
    <span id="toastText">Saved!</span>
</div>

<script>
// ─── Tab Switching ────────────────────────────────────────────────────────────
const TABS = ['profile', 'password', 'security', 'notifications', 'appearance'];

function switchSettingsTab(tab) {
    TABS.forEach(t => {
        document.getElementById('panel-' + t).classList.remove('active');
        const nav = document.getElementById('nav-' + t);
        if (nav) nav.classList.remove('active');
    });
    document.getElementById('panel-' + tab).classList.add('active');
    const activeNav = document.getElementById('nav-' + tab);
    if (activeNav) activeNav.classList.add('active');
    localStorage.setItem('settings_active_tab', tab);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Restore last active tab
(function() {
    const saved = localStorage.getItem('settings_active_tab');
    const phpTab = '<?php echo $active_tab; ?>';
    const tab = phpTab !== 'profile' ? phpTab : (saved || 'profile');
    if (TABS.includes(tab)) switchSettingsTab(tab);
})();

// ─── Avatar live preview ──────────────────────────────────────────────────────
function updateAvatarPreview(val) {
    const initial = val.trim().charAt(0).toUpperCase() || '?';
    document.getElementById('profileAvatarPreview').textContent = initial;
    document.getElementById('avatarInitial').textContent = initial;
    document.getElementById('sideCardName').textContent = val.trim() || 'Your Name';
}

// ─── Password helpers ─────────────────────────────────────────────────────────
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? '<i class="fa-solid fa-eye text-sm"></i>'
        : '<i class="fa-solid fa-eye-slash text-sm"></i>';
}

function checkPasswordStrength(val) {
    let score = 0;
    const checks = {
        len:   val.length >= 6,
        upper: /[A-Z]/.test(val),
        num:   /[0-9]/.test(val),
        sym:   /[^A-Za-z0-9]/.test(val),
    };
    Object.values(checks).forEach(v => { if (v) score++; });

    const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-emerald-500'];
    const labels = ['Weak', 'Fair', 'Good', 'Strong'];
    for (let i = 1; i <= 4; i++) {
        const bar = document.getElementById('ps' + i);
        bar.className = 'strength-bar flex-1 transition-colors ' + (i <= score ? colors[score - 1] : 'bg-slate-200');
    }
    document.getElementById('ps-label').textContent = score > 0 ? labels[score - 1] : '';
    document.getElementById('ps-label').className = 'text-xs mt-1 ' + (score >= 3 ? 'text-emerald-600 font-semibold' : 'text-orange-500');

    // Per-check indicators
    updateCheck('chk-len',   checks.len);
    updateCheck('chk-upper', checks.upper);
    updateCheck('chk-num',   checks.num);
    updateCheck('chk-sym',   checks.sym);
}

function updateCheck(id, pass) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'flex items-center gap-1.5 text-xs transition-colors ' + (pass ? 'text-emerald-600' : 'text-slate-400');
    el.querySelector('i').className = 'fa-solid text-[8px] ' + (pass ? 'fa-circle-check' : 'fa-circle');
}

function checkPasswordMatch() {
    const newP  = document.getElementById('new_pass').value;
    const confP = document.getElementById('conf_pass').value;
    const lbl   = document.getElementById('match-label');
    if (!confP) { lbl.classList.add('hidden'); return; }
    lbl.classList.remove('hidden');
    if (newP === confP) {
        lbl.textContent = '✓ Passwords match';
        lbl.className = 'text-xs mt-1 text-emerald-600 font-semibold';
    } else {
        lbl.textContent = '✗ Passwords do not match';
        lbl.className = 'text-xs mt-1 text-red-500 font-semibold';
    }
}

// ─── Notification Preferences ─────────────────────────────────────────────────
function saveNotifPref(id, checked) {
    let prefs = JSON.parse(localStorage.getItem('notif_prefs') || '{}');
    prefs[id] = checked;
    localStorage.setItem('notif_prefs', JSON.stringify(prefs));
    showToast(checked ? 'Notification enabled' : 'Notification disabled');
}

function saveNotifFreq(val) {
    localStorage.setItem('notif_freq', val);
    showToast('Display preference updated');
}

// Restore notification preferences
(function() {
    const prefs = JSON.parse(localStorage.getItem('notif_prefs') || '{}');
    Object.keys(prefs).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.checked = prefs[id];
    });
})();

// ─── Appearance: Sidebar Compact ─────────────────────────────────────────────
(function() {
    const isCompact = localStorage.getItem('sidebar_compact') === 'true';
    const chk = document.getElementById('toggle-compact');
    if (chk) chk.checked = isCompact;
})();

function toggleSidebarFromSettings(checked) {
    if (typeof toggleSidebarCompact === 'function') {
        const current = document.body.classList.contains('sidebar-compact');
        if (current !== checked) toggleSidebarCompact();
    } else {
        document.body.classList.toggle('sidebar-compact', checked);
        localStorage.setItem('sidebar_compact', checked ? 'true' : 'false');
    }
    showToast(checked ? 'Compact sidebar enabled' : 'Full sidebar enabled');
}

// ─── Appearance: Font Size ────────────────────────────────────────────────────
function applyFontSize(size) {
    document.getElementById('fontPreviewText').style.fontSize = size + 'px';
    document.getElementById('fontSizeLabel').textContent = size + 'px';
    localStorage.setItem('app_font_size', size);
}

(function() {
    const saved = localStorage.getItem('app_font_size');
    if (saved) {
        const range = document.getElementById('fontSizeRange');
        if (range) { range.value = saved; applyFontSize(saved); }
    }
})();

// ─── Appearance: Accent Color ─────────────────────────────────────────────────
const colorNames = {
    '#1d4ed8': 'Blue', '#4338ca': 'Indigo', '#7c3aed': 'Violet',
    '#059669': 'Emerald', '#0d9488': 'Teal', '#475569': 'Slate', '#e11d48': 'Rose'
};

function selectAccentColor(hex, btn) {
    document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('accentPreviewDot').style.background = hex;
    document.getElementById('accentBtnPreview').style.background = hex;
    document.getElementById('accentColorName').textContent = colorNames[hex] || hex;
    localStorage.setItem('accent_color', hex);
}

(function() {
    const saved = localStorage.getItem('accent_color');
    if (saved) {
        document.querySelectorAll('.color-swatch').forEach(btn => {
            if (btn.style.backgroundColor === hexToRgb(saved) || btn.title === colorNames[saved]) {
                selectAccentColor(saved, btn);
            }
        });
    }
})();

function hexToRgb(hex) {
    const r = parseInt(hex.slice(1,3),16);
    const g = parseInt(hex.slice(3,5),16);
    const b = parseInt(hex.slice(5,7),16);
    return `rgb(${r}, ${g}, ${b})`;
}

// ─── Appearance: Density ──────────────────────────────────────────────────────
function setDensity(val, btn) {
    document.querySelectorAll('.density-btn').forEach(b => {
        b.className = b.className.replace(/bg-blue-50 border-blue-300 text-blue-700/, 'bg-white border-slate-200 text-slate-600 hover:border-blue-200');
    });
    btn.className = btn.className.replace('bg-white border-slate-200 text-slate-600 hover:border-blue-200', 'bg-blue-50 border-blue-300 text-blue-700');
    localStorage.setItem('layout_density', val);
}

// ─── Save / Reset Appearance ──────────────────────────────────────────────────
function saveAppearance() {
    showToast('Appearance settings saved!');
}

function resetAppearance() {
    localStorage.removeItem('app_font_size');
    localStorage.removeItem('accent_color');
    localStorage.removeItem('layout_density');
    const range = document.getElementById('fontSizeRange');
    if (range) { range.value = 14; applyFontSize(14); }
    showToast('Appearance reset to defaults');
}

// ─── Toast notification ───────────────────────────────────────────────────────
function showToast(msg) {
    const toast = document.getElementById('toastMsg');
    document.getElementById('toastText').textContent = msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    toast.style.pointerEvents = 'auto';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(1rem)';
        toast.style.pointerEvents = 'none';
    }, 2800);
}

<?php if ($success_msg || $error_msg): ?>
// Auto activate correct tab after form submission
switchSettingsTab('<?php echo $active_tab; ?>');
<?php endif; ?>
</script>

</body>
</html>
