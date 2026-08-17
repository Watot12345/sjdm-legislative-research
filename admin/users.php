<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "User Management";
$username = $_SESSION['username'];

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// ============================================
// HANDLE: Create New User
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $new_username = $conn->real_escape_string($_POST['new_username']);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $department = $conn->real_escape_string($_POST['department']);
    
    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username = '$new_username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $error_msg = "Username already exists. Please choose a different username.";
    } else {
        $insert_sql = "INSERT INTO users (username, password, full_name, email, role, department, created_at, created_by) 
                       VALUES ('$new_username', '$new_password', '$full_name', '$email', '$role', '$department', NOW(), '$username')";
        
        if ($conn->query($insert_sql) === TRUE) {
            $log_sql = "INSERT INTO activity_logs (user, action, module, timestamp) 
                        VALUES ('$username', 'Created new user: $new_username', 'User Management', NOW())";
            $conn->query($log_sql);
            
            header("Location: users.php?success=created");
            exit();
        } else {
            $error_msg = "Error creating user: " . $conn->error;
        }
    }
}

// ============================================
// HANDLE: Update User
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $department = $conn->real_escape_string($_POST['department']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE users SET 
                   full_name = '$full_name',
                   email = '$email',
                   role = '$role',
                   department = '$department',
                   status = '$status',
                   updated_at = NOW()
                   WHERE id = $user_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $log_sql = "INSERT INTO activity_logs (user, action, module, timestamp) 
                    VALUES ('$username', 'Updated user ID: $user_id', 'User Management', NOW())";
        $conn->query($log_sql);
        
        header("Location: users.php?success=updated");
        exit();
    } else {
        $error_msg = "Error updating user: " . $conn->error;
    }
}

// ============================================
// HANDLE: Reset Password
// ============================================
if (isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $update_sql = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $log_sql = "INSERT INTO activity_logs (user, action, module, timestamp) 
                    VALUES ('$username', 'Reset password for user ID: $user_id', 'User Management', NOW())";
        $conn->query($log_sql);
        
        header("Location: users.php?success=password_reset");
        exit();
    } else {
        $error_msg = "Error resetting password: " . $conn->error;
    }
}

// ============================================
// HANDLE: Delete User
// ============================================
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    
    // Don't allow deleting your own account
    $check_sql = "SELECT username FROM users WHERE id = $user_id";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        $user_data = $check_result->fetch_assoc();
        if ($user_data['username'] === $username) {
            $error_msg = "You cannot delete your own account.";
        } else {
            $delete_sql = "DELETE FROM users WHERE id = $user_id";
            if ($conn->query($delete_sql) === TRUE) {
                $log_sql = "INSERT INTO activity_logs (user, action, module, timestamp) VALUES ('$username', 'Deleted user: " . $user_data['username'] . "', 'User Management', NOW())";
                $conn->query($log_sql);
                header("Location: users.php?success=deleted");
                exit();
            }
        }
    }
}

// ============================================
// GET: All Users
// ============================================
$users_sql = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($users_sql);

$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$researcher_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'researcher'")->fetch_assoc()['count'];
$viewer_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'viewer'")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];

// Get user for editing
$edit_user = null;
if (isset($_GET['edit_user'])) {
    $user_id = intval($_GET['edit_user']);
    $edit_sql = "SELECT * FROM users WHERE id = $user_id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_user = $edit_result->fetch_assoc();
    }
}

// Get user for viewing
$view_user = null;
if (isset($_GET['view_user'])) {
    $user_id = intval($_GET['view_user']);
    $view_sql = "SELECT * FROM users WHERE id = $user_id";
    $view_result = $conn->query($view_sql);
    if ($view_result && $view_result->num_rows > 0) {
        $view_user = $view_result->fetch_assoc();
    }
}

$roles = ['admin', 'researcher', 'viewer'];
$departments = ['Legislative', 'Executive', 'Judicial', 'Finance', 'Planning', 'Health', 'Education', 'Agriculture', 'Environment', 'Infrastructure', 'Social Welfare', 'Other'];
$statuses = ['active', 'inactive', 'suspended'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
        }
        
        .btn-scale {
            transition: transform 0.2s ease-in-out;
        }
        
        .btn-scale:hover {
            transform: scale(1.05);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            z-index: 2000;
            animation: slideInRight 0.5s ease;
            max-width: 450px;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast-success { background: #16a34a; }
        .toast-error { background: #dc2626; }
        .toast-info { background: #2563eb; }
        .toast-warning { background: #f59e0b; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.active { background: #d1fae5; color: #065f46; }
        .status-badge.inactive { background: #fef3c7; color: #92400e; }
        .status-badge.suspended { background: #fee2e2; color: #991b1b; }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-badge.admin { background: #dbeafe; color: #1e40af; }
        .role-badge.researcher { background: #ede9fe; color: #5b21b6; }
        .role-badge.viewer { background: #f3e8ff; color: #6b21a5; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 600px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .form-control {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .user-card {
            transition: all 0.3s ease;
            border-left: 4px solid #3b82f6;
        }
        
        .user-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .user-card.admin { border-left-color: #1e40af; }
        .user-card.researcher { border-left-color: #5b21b6; }
        .user-card.viewer { border-left-color: #6b21a5; }
        
        .form-header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            border-radius: 12px 12px 0 0;
            padding: 20px 24px;
            margin: -30px -30px 20px -30px;
        }
    </style>
</head>

<body>

    <?php include("../includes/sidebar.php"); ?>
    
    <div class="ml-72">
        <?php include("../includes/navbar.php"); ?>
        
        <main class="p-8">
            
            <!-- Toast Notifications -->
            <?php if (isset($_GET['success'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    <?php 
                        switch($_GET['success']) {
                            case 'created': echo 'User created successfully!'; break;
                            case 'updated': echo 'User updated successfully!'; break;
                            case 'deleted': echo 'User deleted successfully!'; break;
                            case 'password_reset': echo 'Password reset successfully!'; break;
                            default: echo 'Operation completed successfully!';
                        }
                    ?>
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
                <div class="toast toast-error" id="toast">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    <?php echo $error_msg; ?>
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>

            <!-- PAGE HEADER -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800">
                        <i class="fa-solid fa-users-cog text-blue-700 mr-2"></i>
                        User Management
                    </h2>
                    <p class="text-slate-500 mt-2">
                        Manage system users, roles, and permissions.
                    </p>
                </div>
                <button onclick="openCreateModal()" class="bg-blue-800 hover:bg-blue-900 text-white px-6 py-3 rounded-lg shadow btn-scale">
                    <i class="fa-solid fa-user-plus mr-2"></i>
                    Create New User
                </button>
            </div>

            <!-- STATISTICS -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Total Users</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_users; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fa-solid fa-users text-blue-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 mt-4">All Users</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Admins</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $admin_count; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fa-solid fa-user-shield text-blue-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 mt-4">Administrators</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Researchers</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $researcher_count; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fa-solid fa-microscope text-purple-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-purple-600 mt-4">Researchers</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Viewers</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $viewer_count; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fa-solid fa-eye text-indigo-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-indigo-600 mt-4">View Only</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Active Users</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $active_users; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-green-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-green-600 mt-4">Currently Active</p>
                </div>
            </div>

            <!-- USER LIST -->
            <div class="bg-white rounded-xl shadow">
                <div class="flex items-center justify-between px-6 py-5 border-b">
                    <div>
                        <h2 class="text-2xl font-bold">Users</h2>
                        <p class="text-slate-500 mt-1">Manage user accounts and permissions</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" id="searchUsers" placeholder="Search users..." 
                               class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-700">
                        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm btn-scale">
                            <i class="fa-solid fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto p-4">
                    <?php if ($users && $users->num_rows > 0): ?>
                        <div class="grid gap-3">
                            <?php while($user = $users->fetch_assoc()): 
                                $is_current_user = ($user['username'] === $username);
                            ?>
                                <div class="user-card <?php echo $user['role']; ?> bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                                                <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold text-slate-800">
                                                        <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                                    </span>
                                                    <?php if ($is_current_user): ?>
                                                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center gap-3 text-sm text-slate-500">
                                                    <span><i class="fa-regular fa-user mr-1"></i> <?php echo htmlspecialchars($user['username']); ?></span>
                                                    <span><i class="fa-regular fa-envelope mr-1"></i> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                                                    <?php if ($user['department']): ?>
                                                        <span><i class="fa-regular fa-building mr-1"></i> <?php echo htmlspecialchars($user['department']); ?></span>
                                                    <?php endif; ?>
                                                    <span><i class="fa-regular fa-calendar mr-1"></i> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="role-badge <?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                            <span class="status-badge <?php echo $user['status'] ?? 'active'; ?>">
                                                <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                            </span>
                                            <a href="?view_user=<?php echo $user['id']; ?>" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm btn-scale">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="?edit_user=<?php echo $user['id']; ?>" 
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-sm btn-scale">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <?php if (!$is_current_user): ?>
                                                <button onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-sm btn-scale">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                                <a href="?delete_user=<?php echo $user['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this user?')"
                                                   class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm btn-scale">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-slate-500">
                            <i class="fa-solid fa-users-slash text-5xl block mb-4 text-slate-300"></i>
                            <p>No users found.</p>
                            <p class="text-sm mt-1">Click "Create New User" to add your first user.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FOOTER -->
            <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
                <p class="mt-2">User Management Module</p>
            </footer>

        </main>
    </div>

    <!-- ============================================ -->
    <!-- CREATE USER MODAL -->
    <!-- ============================================ -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="form-header">
                <h3 class="text-2xl font-bold text-white">
                    <i class="fa-solid fa-user-plus mr-3"></i>
                    Create New User
                </h3>
                <p class="text-blue-100 mt-1">Fill in the details to create a new user account</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="create_user" value="1">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="new_username" class="form-control" placeholder="Enter username" required>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address">
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter password" required minlength="6">
                        <p class="text-xs text-slate-400 mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach($roles as $role): ?>
                                <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Department</label>
                        <select name="department" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg btn-scale">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Create User
                    </button>
                    <button type="button" onclick="closeCreateModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2 rounded-lg btn-scale">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- EDIT USER MODAL -->
    <!-- ============================================ -->
    <?php if ($edit_user): ?>
    <div id="editUserModal" class="modal" style="display:block;">
        <div class="modal-content">
            <div class="form-header">
                <h3 class="text-2xl font-bold text-white">
                    <i class="fa-solid fa-user-edit mr-3"></i>
                    Edit User
                </h3>
                <p class="text-blue-100 mt-1">Update user information for <?php echo htmlspecialchars($edit_user['username']); ?></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($edit_user['username']); ?>" class="form-control bg-slate-100" disabled>
                        <p class="text-xs text-slate-400 mt-1">Username cannot be changed</p>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                        <select name="role" class="form-control" required>
                            <?php foreach($roles as $role): ?>
                                <option value="<?php echo $role; ?>" <?php echo ($edit_user['role'] == $role) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Department</label>
                        <select name="department" class="form-control">
                            <option value="">Select Department</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo ($edit_user['department'] == $dept) ? 'selected' : ''; ?>>
                                    <?php echo $dept; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach($statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo (($edit_user['status'] ?? 'active') == $status) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg btn-scale">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Update User
                    </button>
                    <a href="users.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2 rounded-lg btn-scale">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- VIEW USER MODAL -->
    <!-- ============================================ -->
    <?php if ($view_user): ?>
    <div id="viewUserModal" class="modal" style="display:block;">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-2xl">
                        <?php echo strtoupper(substr($view_user['full_name'] ?? $view_user['username'], 0, 2)); ?>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($view_user['full_name'] ?? $view_user['username']); ?></h3>
                        <p class="text-slate-500">@<?php echo htmlspecialchars($view_user['username']); ?></p>
                    </div>
                </div>
                <button onclick="window.location.href='users.php'" class="text-slate-500 hover:text-slate-700 text-2xl">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-slate-500">Role</p>
                    <p class="font-semibold"><span class="role-badge <?php echo $view_user['role']; ?>"><?php echo ucfirst($view_user['role']); ?></span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Status</p>
                    <p class="font-semibold"><span class="status-badge <?php echo $view_user['status'] ?? 'active'; ?>"><?php echo ucfirst($view_user['status'] ?? 'Active'); ?></span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($view_user['email'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Department</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($view_user['department'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Created At</p>
                    <p class="font-semibold"><?php echo date('F j, Y h:i A', strtotime($view_user['created_at'])); ?></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Created By</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($view_user['created_by'] ?? 'System'); ?></p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6 pt-4 border-t">
                <a href="?edit_user=<?php echo $view_user['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg btn-scale">
                    <i class="fa-solid fa-pen mr-2"></i>
                    Edit User
                </a>
                <?php if ($view_user['username'] !== $username): ?>
                    <button onclick="openResetPasswordModal(<?php echo $view_user['id']; ?>, '<?php echo htmlspecialchars($view_user['username']); ?>')" 
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg btn-scale">
                        <i class="fa-solid fa-key mr-2"></i>
                        Reset Password
                    </button>
                <?php endif; ?>
                <button onclick="window.location.href='users.php'" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2 rounded-lg btn-scale">
                    Close
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- RESET PASSWORD MODAL -->
    <!-- ============================================ -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="form-header">
                <h3 class="text-2xl font-bold text-white">
                    <i class="fa-solid fa-key mr-3"></i>
                    Reset Password
                </h3>
                <p class="text-blue-100 mt-1">Reset password for user: <span id="resetUsername" class="font-bold"></span></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="user_id" id="resetUserId">
                
                <div class="mt-4">
                    <label class="block font-semibold text-slate-700 mb-1">New Password <span class="text-red-500">*</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required minlength="6">
                    <p class="text-xs text-slate-400 mt-1">Minimum 6 characters</p>
                </div>
                
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg btn-scale">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Reset Password
                    </button>
                    <button type="button" onclick="closeResetPasswordModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2 rounded-lg btn-scale">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Create User Modal
        function openCreateModal() {
            document.getElementById('createUserModal').style.display = 'block';
        }
        
        function closeCreateModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }
        
        // Reset Password Modal
        function openResetPasswordModal(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('resetPasswordModal').style.display = 'block';
        }
        
        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('searchUsers')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');
            
            userCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const createModal = document.getElementById('createUserModal');
            const resetModal = document.getElementById('resetPasswordModal');
            
            if (event.target == createModal) {
                createModal.style.display = 'none';
            }
            if (event.target == resetModal) {
                resetModal.style.display = 'none';
            }
        }
        
        // Auto-dismiss toast
        setTimeout(() => {
            let toast = document.getElementById('toast');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
    </script>

</body>
</html>

<?php
$conn->close();
?>