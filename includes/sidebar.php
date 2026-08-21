<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Administrator';
$currentPage = basename($_SERVER['PHP_SELF']);
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/modules/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$base_path = $is_subfolder ? '../' : '';
$city_logo = $base_path . 'City.jpg';
$logout_url = $base_path . 'logout.php';
?>

<!-- SIDEBAR COMPACT CSS & STYLES -->
<style>
/* Smooth Transition on Sidebar and Main Content */
aside#appSidebar {
    transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 40;
}
.ml-72, .main-content-wrapper {
    transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* COMPACT MODE STYLES */
body.sidebar-compact aside#appSidebar {
    width: 4.5rem !important; /* 72px */
}

body.sidebar-compact .ml-72,
body.sidebar-compact .main-content-wrapper {
    margin-left: 4.5rem !important; /* 72px */
}

/* Hide texts & elements in compact mode */
body.sidebar-compact .sidebar-text,
body.sidebar-compact .sidebar-heading,
body.sidebar-compact .sidebar-user-details,
body.sidebar-compact .sidebar-badge {
    display: none !important;
}

/* Center elements in compact mode */
body.sidebar-compact .sidebar-header {
    padding: 1rem 0.5rem !important;
    justify-content: center !important;
}

body.sidebar-compact .sidebar-logo-box {
    margin: 0 auto !important;
}

body.sidebar-compact .sidebar-nav-item {
    justify-content: center !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0.5rem !important;
    margin-right: 0.5rem !important;
    gap: 0 !important;
}

body.sidebar-compact .sidebar-nav-item i {
    font-size: 1.15rem !important;
    margin: 0 !important;
}

body.sidebar-compact .sidebar-footer {
    padding: 0.75rem 0.5rem !important;
}

body.sidebar-compact .sidebar-user-row {
    justify-content: center !important;
}

body.sidebar-compact .sidebar-logout-btn {
    padding-left: 0 !important;
    padding-right: 0 !important;
    justify-content: center !important;
}

body.sidebar-compact .sidebar-logout-text {
    display: none !important;
}

/* Floating Tooltips on Hover during Compact Mode */
body.sidebar-compact .sidebar-nav-item {
    position: relative;
}

body.sidebar-compact .sidebar-nav-item:hover::after {
    content: attr(data-title);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 0.75rem;
    background-color: #0f172a;
    color: #ffffff;
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    z-index: 60;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    pointer-events: none;
}
</style>

<!-- IMMEDIATE CLIENT-SIDE COMPACT CHECK TO AVOID FLICKER -->
<script>
if (localStorage.getItem('sidebar_compact') === 'true') {
    document.documentElement.classList.add('sidebar-compact-loading');
    document.body ? document.body.classList.add('sidebar-compact') : document.addEventListener('DOMContentLoaded', () => document.body.classList.add('sidebar-compact'));
}
</script>

<aside id="appSidebar" class="fixed left-0 top-0 w-72 h-screen bg-gradient-to-b from-blue-900 via-blue-800 to-blue-950 text-white shadow-2xl flex flex-col select-none">

    <!-- Header / Logo -->
    <div class="sidebar-header p-5 border-b border-blue-700/50 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="sidebar-logo-box w-12 h-12 rounded-full bg-white flex items-center justify-center overflow-hidden border-2 border-white/80 shadow-md shrink-0">
                <img src="<?php echo $city_logo; ?>" alt="San Jose Del Monte City Logo" class="w-full h-full object-cover">
            </div>
            <div class="sidebar-text min-w-0">
                <h1 class="font-bold text-base leading-tight truncate tracking-wide">
                    Legislative Research
                </h1>
                <p class="text-xs text-blue-200 font-medium truncate">
                    San Jose Del Monte
                </p>
            </div>
        </div>

        <!-- Sidebar Collapse / Expand Toggle Button -->
        <button type="button" 
                onclick="toggleSidebarCompact()" 
                id="sidebarToggleBtn"
                class="w-8 h-8 rounded-lg bg-blue-800/80 hover:bg-blue-700 text-blue-200 hover:text-white flex items-center justify-center transition shrink-0 cursor-pointer border border-blue-600/30"
                title="Collapse / Expand Sidebar">
            <i id="sidebarToggleIcon" class="fa-solid fa-angles-left text-xs"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4 space-y-1 custom-scrollbar">

        <p class="sidebar-heading px-6 mb-2 text-[11px] font-bold uppercase tracking-wider text-blue-300/80">
            Main Menu
        </p>

        <!-- Dashboard -->
        <a href="<?php echo $base_path; ?>dashboard.php"
           data-title="Dashboard"
           title="Dashboard"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'dashboard.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-house w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Dashboard</span>
        </a>

        <!-- Policy Research (Admin, Researcher, Viewer, Encoder) -->
        <a href="<?php echo $base_path; ?>modules/policy-research.php"
           data-title="Policy Research"
           title="Policy Research"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'policy-research.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-book-open w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Policy Research</span>
        </a>

        <!-- Data Collection (Admin, Researcher, Data Encoder) -->
        <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_DATA_ENCODER])): ?>
        <a href="<?php echo $base_path; ?>modules/data-collection.php"
           data-title="Data Collection"
           title="Data Collection"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'data-collection.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-database w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Data Collection</span>
        </a>
        <?php endif; ?>

        <!-- Impact Assessment (Admin, Researcher, Viewer) -->
        <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_VIEWER])): ?>
        <a href="<?php echo $base_path; ?>modules/impact-assessment.php"
           data-title="Impact Assessment"
           title="Impact Assessment"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'impact-assessment.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-chart-line w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Impact Assessment</span>
        </a>
        <?php endif; ?>

        <!-- Benchmarking (Admin, Researcher, Viewer) -->
        <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_VIEWER])): ?>
        <a href="<?php echo $base_path; ?>modules/benchmarking-analysis.php"
           data-title="Benchmarking & Comparative Analysis"
           title="Benchmarking & Comparative Analysis"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'benchmarking-analysis.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-scale-balanced w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Benchmarking Analysis</span>
        </a>
        <?php endif; ?>

        <!-- Report Generation (Admin, Researcher, Viewer) -->
        <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER, ROLE_VIEWER])): ?>
        <a href="<?php echo $base_path; ?>modules/report-generation.php"
           data-title="Report Generation"
           title="Report Generation"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'report-generation.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-file-lines w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Report Generation</span>
        </a>
        <?php endif; ?>

        <!-- Data Visualization (All Roles) -->
        <a href="<?php echo $base_path; ?>modules/data-visualization.php"
           data-title="Data Visualization"
           title="Data Visualization"
           class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'data-visualization.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
            <i class="fa-solid fa-chart-pie w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text text-sm">Data Visualization</span>
        </a>

        <?php if (function_exists('isAdmin') ? isAdmin() : (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
        <div class="pt-2">
            <p class="sidebar-heading px-6 mb-2 text-[11px] font-bold uppercase tracking-wider text-blue-300/80">
                Administration
            </p>

            <!-- User Management -->
            <a href="<?php echo $base_path; ?>admin/users.php"
               data-title="User Management"
               title="User Management"
               class="sidebar-nav-item flex items-center gap-3.5 px-4 py-2.5 mx-3 rounded-xl transition-all duration-150 group <?php echo $currentPage == 'users.php' ? 'bg-blue-700 text-white font-semibold shadow-md border border-blue-500/30' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'; ?>">
                <i class="fa-solid fa-users-gear w-6 text-center text-base shrink-0 group-hover:scale-110 transition-transform"></i>
                <span class="sidebar-text text-sm flex-1">User Management</span>
                <span class="sidebar-badge bg-yellow-500 text-blue-950 font-bold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">Admin</span>
            </a>
        </div>
        <?php endif; ?>

    </nav>
    
    <!-- User Profile & Logout Section -->
    <div class="sidebar-footer border-t border-blue-700/50 p-4 bg-blue-950/40">

        <div class="sidebar-user-row flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-800/90 border border-blue-600/40 text-blue-100 flex items-center justify-center shrink-0 shadow-inner">
                <i class="fa-solid fa-user text-sm"></i>
            </div>
            <div class="sidebar-user-details min-w-0 flex-1">
                <h4 class="font-semibold text-xs leading-tight truncate">
                    <?php echo htmlspecialchars($username); ?>
                </h4>
                <p class="text-[11px] text-blue-300 truncate">
                    <?php echo htmlspecialchars(function_exists('getRoleLabel') ? getRoleLabel($_SESSION['role'] ?? 'viewer') : ucfirst($_SESSION['role'] ?? 'viewer')); ?>
                </p>
            </div>
        </div>

        <!-- Logout Button -->
        <button type="button"
           onclick="openLogoutModal(event)"
           data-title="Logout"
           title="Logout"
           class="sidebar-logout-btn w-full mt-3 flex items-center justify-center gap-2 bg-red-600/90 hover:bg-red-600 text-white rounded-xl py-2 px-3 text-xs font-semibold transition-all shadow-sm hover:shadow cursor-pointer">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="sidebar-logout-text">Logout</span>
        </button>

    </div>

</aside>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutConfirmModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 select-none">
    <div class="relative bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="logoutModalCard">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                    <i class="fa-solid fa-right-from-bracket text-lg text-white"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base leading-tight text-white">Confirm Logout</h3>
                    <p class="text-xs text-red-100 mt-0.5">End your current session</p>
                </div>
            </div>
            <button type="button" onclick="closeLogoutModal()" class="text-white/80 hover:text-white hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Body Content -->
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0 text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p class="text-slate-800 text-sm font-medium">
                        Are you sure you want to log out of the platform?
                    </p>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Any unsaved progress may be lost. You will need to log in again to access the Legislative Research system.
                    </p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" 
                        onclick="closeLogoutModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition focus:outline-none cursor-pointer">
                    Cancel
                </button>
                <a id="logoutModalConfirmBtn" 
                   href="<?php echo $logout_url; ?>" 
                   class="px-5 py-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 text-white text-sm font-semibold hover:from-red-700 hover:to-rose-700 transition shadow-md hover:shadow-lg flex items-center gap-2 focus:outline-none cursor-pointer">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebarCompact() {
    const isCompact = document.body.classList.toggle('sidebar-compact');
    localStorage.setItem('sidebar_compact', isCompact ? 'true' : 'false');
    
    // Update Toggle Icon
    const toggleIcon = document.getElementById('sidebarToggleIcon');
    if (toggleIcon) {
        if (isCompact) {
            toggleIcon.className = 'fa-solid fa-angles-right text-xs';
        } else {
            toggleIcon.className = 'fa-solid fa-angles-left text-xs';
        }
    }
}

function openLogoutModal(event, targetUrl) {
    if (event) event.preventDefault();
    const modal = document.getElementById('logoutConfirmModal');
    const card = document.getElementById('logoutModalCard');
    const confirmBtn = document.getElementById('logoutModalConfirmBtn');
    
    if (targetUrl && confirmBtn) {
        confirmBtn.href = targetUrl;
    }
    
    if (modal && card) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutConfirmModal');
    const card = document.getElementById('logoutModalCard');
    if (modal && card) {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }
}

// Close modal on escape key or backdrop click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLogoutModal();
    }
});

document.addEventListener('click', function(e) {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal && e.target === modal) {
        closeLogoutModal();
    }
});

// Ensure icon state matches saved preference
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('sidebar_compact') === 'true') {
        document.body.classList.add('sidebar-compact');
        const toggleIcon = document.getElementById('sidebarToggleIcon');
        if (toggleIcon) {
            toggleIcon.className = 'fa-solid fa-angles-right text-xs';
        }
    }
});
</script>