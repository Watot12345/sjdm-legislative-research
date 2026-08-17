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
$pageTitle = "My Profile";

// Fetch full user record
$stmt = $conn->prepare("SELECT id,username,full_name,email,role,department,status,created_at,last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // Fallback: look up by username
    $stmt2 = $conn->prepare("SELECT id,username,full_name,email,role,department,status,created_at,last_login FROM users WHERE username = ?");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $user = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
}

$success_msg = '';
$error_msg   = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_full  = trim($_POST['full_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_dept  = trim($_POST['department'] ?? '');

    if (empty($new_full) || empty($new_email)) {
        $error_msg = "Full name and email are required.";
    } else {
        $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, department=?, updated_at=NOW() WHERE id=?");
        $upd->bind_param("sssi", $new_full, $new_email, $new_dept, $user['id']);
        if ($upd->execute()) {
            $_SESSION['full_name'] = $new_full;
            $_SESSION['email']     = $new_email;
            $_SESSION['department'] = $new_dept;
            $user['full_name']  = $new_full;
            $user['email']      = $new_email;
            $user['department'] = $new_dept;
            $success_msg = "Profile updated successfully.";
        } else {
            $error_msg = "Failed to update profile. Please try again.";
        }
        $upd->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur_pass  = $_POST['current_password'] ?? '';
    $new_pass  = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    // Get current hash
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
        if ($upw->execute()) {
            $success_msg = "Password changed successfully.";
        } else {
            $error_msg = "Failed to change password. Please try again.";
        }
        $upw->close();
    }
}

// Activity count for this user
$act_res = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE user='" . $conn->real_escape_string($username) . "'");
$activity_count = (int)($act_res ? $act_res->fetch_assoc()['cnt'] : 0);

// Role badge color
$role_colors = ['admin' => 'blue', 'researcher' => 'purple', 'viewer' => 'slate'];
$rc = $role_colors[$user['role'] ?? 'viewer'] ?? 'slate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> – SJDM Legislative Research</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-btn.active { background: white; color: #1d4ed8; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content-wrapper ml-72 flex flex-col min-h-screen transition-all duration-300">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 p-6 md:p-8">

        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-800">My Profile</h2>
            <p class="text-sm text-slate-500 mt-1">Manage your account information and password</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_msg): ?>
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium">
            <i class="fa-solid fa-circle-check text-emerald-500"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: Profile Card -->
            <div class="lg:col-span-1 space-y-5">
                <!-- Avatar Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="h-24 bg-gradient-to-br from-blue-900 to-blue-600"></div>
                    <div class="px-6 pb-6 -mt-10 text-center">
                        <div class="w-20 h-20 rounded-full bg-white border-4 border-white shadow-md mx-auto flex items-center justify-center bg-gradient-to-br from-blue-800 to-blue-500">
                            <span class="text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($user['full_name'] ?? $username, 0, 1)); ?>
                            </span>
                        </div>
                        <h3 class="mt-3 text-lg font-bold text-slate-800"><?php echo htmlspecialchars($user['full_name'] ?? $username); ?></h3>
                        <p class="text-sm text-slate-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold bg-<?php echo $rc; ?>-100 text-<?php echo $rc; ?>-700 capitalize">
                            <?php echo htmlspecialchars($user['role'] ?? 'viewer'); ?>
                        </span>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Account Info</h4>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-envelope text-blue-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-400">Email</p>
                                <p class="font-medium text-slate-700 truncate"><?php echo htmlspecialchars($user['email'] ?? '—'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-building text-purple-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-400">Department</p>
                                <p class="font-medium text-slate-700 truncate"><?php echo htmlspecialchars($user['department'] ?? '—'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-calendar text-emerald-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-400">Member Since</p>
                                <p class="font-medium text-slate-700"><?php echo $user['created_at'] ? date('M j, Y', strtotime($user['created_at'])) : '—'; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-clock text-amber-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-400">Last Login</p>
                                <p class="font-medium text-slate-700"><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'First session'; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-list-check text-slate-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-400">Total Activities</p>
                                <p class="font-bold text-slate-800"><?php echo number_format($activity_count); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full <?php echo $user['status'] === 'active' ? 'bg-emerald-500' : 'bg-red-400'; ?> ring-4 ring-<?php echo $user['status'] === 'active' ? 'emerald' : 'red'; ?>-100"></div>
                    <div>
                        <p class="text-xs text-slate-400">Account Status</p>
                        <p class="text-sm font-semibold capitalize text-slate-700"><?php echo htmlspecialchars($user['status'] ?? 'active'); ?></p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Edit Forms -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Tab Nav -->
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="bg-slate-200 p-1 rounded-xl flex gap-1">
                        <button onclick="switchProfileTab('info')" id="tab-info" class="tab-btn active px-5 py-2 rounded-lg text-sm font-semibold text-slate-600 transition">
                            <i class="fa-solid fa-user mr-2"></i>Profile Info
                        </button>
                        <button onclick="switchProfileTab('password')" id="tab-password" class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold text-slate-600 transition">
                            <i class="fa-solid fa-lock mr-2"></i>Change Password
                        </button>
                    </div>
                    <a href="settings.php"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 text-sm font-semibold hover:bg-blue-100 transition-all shadow-xs">
                        <i class="fa-solid fa-gear"></i>
                        All Settings
                    </a>
                </div>

                <!-- Profile Info Form -->
                <div id="tab-content-info" class="tab-content active bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h4 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-blue-600"></i> Edit Profile Information
                    </h4>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required
                                    class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                    class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm text-slate-400 cursor-not-allowed">
                                <p class="text-xs text-slate-400 mt-1">Username cannot be changed</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Department / Division</label>
                            <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>"
                                placeholder="e.g. Legislative Research Division"
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Role</label>
                                <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'] ?? 'viewer')); ?>" disabled
                                    class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm text-slate-400 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Account Status</label>
                                <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['status'] ?? 'active')); ?>" disabled
                                    class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm text-slate-400 cursor-not-allowed">
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div id="tab-content-password" class="tab-content bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h4 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-lock text-blue-600"></i> Change Password
                    </h4>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="change_password" value="1">

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Current Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="current_password" id="cur_pass" required
                                    class="w-full border border-slate-300 rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                <button type="button" onclick="togglePw('cur_pass', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">New Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_pass" required minlength="6"
                                    class="w-full border border-slate-300 rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                <button type="button" onclick="togglePw('new_pass', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Minimum 6 characters</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Confirm New Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="conf_pass" required
                                    class="w-full border border-slate-300 rounded-xl px-4 py-2.5 pr-11 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                                <button type="button" onclick="togglePw('conf_pass', this)" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Strength Indicator -->
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Password Strength</p>
                            <div class="flex gap-1">
                                <div id="ps1" class="h-1.5 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="ps2" class="h-1.5 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="ps3" class="h-1.5 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="ps4" class="h-1.5 flex-1 rounded bg-slate-200 transition-colors"></div>
                            </div>
                            <p id="ps-label" class="text-xs text-slate-400 mt-1"></p>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <footer class="text-center py-4 text-xs text-slate-400 border-t border-slate-200 bg-white">
        &copy; <?php echo date('Y'); ?> San Jose Del Monte Legislative Research System. All rights reserved.
    </footer>
</div>

<script>
function switchProfileTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-content-' + tab).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="fa-solid fa-eye text-sm"></i>' : '<i class="fa-solid fa-eye-slash text-sm"></i>';
}

// Password strength meter
document.getElementById('new_pass').addEventListener('input', function() {
    const val = this.value;
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-emerald-500'];
    const labels = ['Weak','Fair','Good','Strong'];
    for (let i = 1; i <= 4; i++) {
        const bar = document.getElementById('ps'+i);
        bar.className = 'h-1.5 flex-1 rounded transition-colors ' + (i <= score ? colors[score-1] : 'bg-slate-200');
    }
    document.getElementById('ps-label').textContent = score > 0 ? labels[score-1] : '';
});

<?php if ($success_msg || $error_msg): ?>
// Auto-activate password tab if password error
<?php if ($error_msg && isset($_POST['change_password'])): ?>
switchProfileTab('password');
<?php endif; ?>
<?php endif; ?>
</script>

</body>
</html>
