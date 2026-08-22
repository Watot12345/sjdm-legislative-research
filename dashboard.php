<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

require_once __DIR__ . '/config/config.php';
$conn = getDBConnection();

// Bell Notifications Data (Exclude login/logout authentication logs)
$notif_where_sql = "WHERE action NOT LIKE '%login%' AND action NOT LIKE '%logout%' AND module != 'Authentication'";

// Enforce Notification RBAC: Only Administrators see User Management and Security audit logs
if (function_exists('isAdmin') && !isAdmin()) {
    $notif_where_sql .= " AND module NOT IN ('User Management', 'Security', 'Administration')";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE activity_logs SET is_read = 1 $notif_where_sql AND is_read = 0");
    echo json_encode(['success' => true]);
    exit();
}

// ============================================
// DEADLINE MANAGEMENT CRUD HANDLERS
// ============================================
// Handle: Create Deadline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deadline'])) {
    if (!hasRole([ROLE_ADMIN, ROLE_RESEARCHER])) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to manage deadlines.'];
        header("Location: dashboard.php");
        exit();
    }
    $d_title = $conn->real_escape_string($_POST['title']);
    $d_category = $conn->real_escape_string($_POST['category'] ?? 'General');
    $d_due_date = $conn->real_escape_string($_POST['due_date']);
    $d_priority = $conn->real_escape_string($_POST['priority'] ?? 'medium');
    
    $conn->query("INSERT INTO upcoming_deadlines (title, category, due_date, priority, status, created_by) VALUES ('$d_title', '$d_category', '$d_due_date', '$d_priority', 'pending', '$username')");
    $conn->query("INSERT INTO activity_logs (user, action, module, timestamp) VALUES ('$username', 'Created deadline: $d_title', 'Dashboard', NOW())");
    header("Location: dashboard.php?deadline_status=created");
    exit();
}

// Handle: Update Deadline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deadline'])) {
    if (!hasRole([ROLE_ADMIN, ROLE_RESEARCHER])) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to manage deadlines.'];
        header("Location: dashboard.php");
        exit();
    }
    $d_id = intval($_POST['deadline_id']);
    $d_title = $conn->real_escape_string($_POST['title']);
    $d_category = $conn->real_escape_string($_POST['category'] ?? 'General');
    $d_due_date = $conn->real_escape_string($_POST['due_date']);
    $d_priority = $conn->real_escape_string($_POST['priority'] ?? 'medium');
    $d_status = $conn->real_escape_string($_POST['status'] ?? 'pending');
    
    $conn->query("UPDATE upcoming_deadlines SET title = '$d_title', category = '$d_category', due_date = '$d_due_date', priority = '$d_priority', status = '$d_status' WHERE id = $d_id");
    $conn->query("INSERT INTO activity_logs (user, action, module, timestamp) VALUES ('$username', 'Updated deadline: $d_title', 'Dashboard', NOW())");
    header("Location: dashboard.php?deadline_status=updated");
    exit();
}

// Handle: Toggle Status (Complete / Pending)
if (isset($_GET['toggle_deadline'])) {
    if (!hasRole([ROLE_ADMIN, ROLE_RESEARCHER])) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to manage deadlines.'];
        header("Location: dashboard.php");
        exit();
    }
    $d_id = intval($_GET['toggle_deadline']);
    $conn->query("UPDATE upcoming_deadlines SET status = IF(status='completed', 'pending', 'completed') WHERE id = $d_id");
    $conn->query("INSERT INTO activity_logs (user, action, module, timestamp) VALUES ('$username', 'Toggled deadline status ID: $d_id', 'Dashboard', NOW())");
    header("Location: dashboard.php?deadline_status=toggled");
    exit();
}

// Handle: Delete Deadline
if (isset($_GET['delete_deadline'])) {
    if (!canDeletePolicy()) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'Only administrators can delete deadlines.'];
        header("Location: dashboard.php");
        exit();
    }
    $d_id = intval($_GET['delete_deadline']);
    $conn->query("DELETE FROM upcoming_deadlines WHERE id = $d_id");
    $conn->query("INSERT INTO activity_logs (user, action, module, timestamp) VALUES ('$username', 'Deleted deadline ID: $d_id', 'Dashboard', NOW())");
    header("Location: dashboard.php?deadline_status=deleted");
    exit();
}

// Fetch Dynamic Deadlines
$deadlines_res = $conn->query("SELECT * FROM upcoming_deadlines ORDER BY status ASC, due_date ASC LIMIT 10");
$deadlines = [];
$pending_deadlines_count = 0;
if ($deadlines_res && $deadlines_res->num_rows > 0) {
    while ($d = $deadlines_res->fetch_assoc()) {
        $deadlines[] = $d;
        if ($d['status'] === 'pending') {
            $pending_deadlines_count++;
        }
    }
}

// Dynamic KPI Data Queries
$kpi_active_policies = (int)($conn->query("SELECT COUNT(*) as cnt FROM policy_documents")->fetch_assoc()['cnt'] ?? 0);
$kpi_data_sources = (int)($conn->query("SELECT COUNT(DISTINCT source_office) as cnt FROM datasets")->fetch_assoc()['cnt'] ?? 0);
$kpi_impact_reports = (int)($conn->query("SELECT COUNT(*) as cnt FROM impact_assessments")->fetch_assoc()['cnt'] ?? 0);
$kpi_generated_reports = (int)($conn->query("SELECT (SELECT COUNT(*) FROM benchmarking_submissions WHERE status = 'Report Generated') + (SELECT COUNT(*) FROM reports) as cnt")->fetch_assoc()['cnt'] ?? 0);

// Dynamic Policy Categories for Bar Chart
$policy_cats_res = $conn->query("SELECT category, COUNT(*) as count FROM policy_documents GROUP BY category");
$cat_labels = [];
$cat_counts = [];
if ($policy_cats_res && $policy_cats_res->num_rows > 0) {
    while($r = $policy_cats_res->fetch_assoc()) {
        if (!empty($r['category'])) {
            $cat_labels[] = $r['category'];
            $cat_counts[] = (int)$r['count'];
        }
    }
}

// Dynamic Impact Assessment Trends for Line Chart (6-Month Rolling Timeline)
$trend_labels = [];
$trend_counts = [];
for ($i = 5; $i >= 0; $i--) {
    $m_name = date('M Y', strtotime("-$i months"));
    $m_num = date('m', strtotime("-$i months"));
    $y_num = date('Y', strtotime("-$i months"));
    $cnt = (int)($conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE MONTH(created_date) = '$m_num' AND YEAR(created_date) = '$y_num'")->fetch_assoc()['count'] ?? 0);
    $trend_labels[] = $m_name;
    $trend_counts[] = $cnt;
}

// Dynamic Recent Legislative Activities (Excludes authentication and system logs)
$legislative_modules_filter = "WHERE module IN ('Policy Research', 'Data Collection', 'Impact Assessment', 'Benchmarking', 'Benchmarking Analysis', 'Report Generation')";
$recent_activities_res = $conn->query("SELECT * FROM activity_logs $legislative_modules_filter ORDER BY timestamp DESC LIMIT 5");

// Bell Notifications Data (Filtered to exclude auth logs)
$unread_notifs_count = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs $notif_where_sql AND is_read = 0")->fetch_assoc()['cnt'] ?? 0);
$all_notifs_res = $conn->query("SELECT * FROM activity_logs $notif_where_sql ORDER BY timestamp DESC LIMIT 10");
$all_notifs = [];
$unread_notifs = [];
if ($all_notifs_res && $all_notifs_res->num_rows > 0) {
    while($row = $all_notifs_res->fetch_assoc()) {
        $all_notifs[] = $row;
        if ($row['is_read'] == 0) {
            $unread_notifs[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | Legislative Research System</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
    font-family:'Inter',sans-serif;
    background:#F1F5F9;
}

.sidebar-scroll::-webkit-scrollbar{
    width:5px;
}

.sidebar-scroll::-webkit-scrollbar-thumb{
    background:#2563EB;
    border-radius:20px;
}

</style>

</head>

<body>

<div class="flex min-h-screen">

<!-- ========================= -->
<!-- SIDEBAR -->
<!-- ========================= -->
<?php include("includes/sidebar.php"); ?>

<!-- ========================= -->
<!-- MAIN CONTENT -->
<!-- ========================= -->

<div class="ml-72 flex-1">

<!-- NAVBAR -->
<?php $pageTitle = "Dashboard"; include("includes/navbar.php"); ?>

<!-- CONTENT -->

<main class="p-8">

<!-- ========================================= -->
<!-- 1. SEE FIRST: WELCOME & KPI ANALYTICS -->
<!-- ========================================= -->

<!-- WELCOME -->
<?php 
$current_role = getCurrentUserRole();
$role_titles = [
    ROLE_ADMIN => ['title' => 'System Administrator', 'subtitle' => 'Manage user permissions, monitor system audit logs, configure policies, and supervise municipal research operations.'],
    ROLE_RESEARCHER => ['title' => 'Legislative Researcher', 'subtitle' => 'Author legislative proposals, run Gemini AI legal citations, evaluate policy impact KPIs, and conduct comparative benchmarking.'],
    ROLE_DATA_ENCODER => ['title' => 'Data Encoder Portal', 'subtitle' => 'Collect, upload, and organize municipal datasets, baseline metrics, and departmental records for legislative evaluation.'],
    ROLE_VIEWER => ['title' => 'Executive Legislative Reviewer', 'subtitle' => 'Review published municipal ordinances, examine policy impact analytics, monitor benchmarking comparisons, and export reports.']
];
$r_info = $role_titles[$current_role] ?? ['title' => 'Legislative Portal', 'subtitle' => 'Manage and monitor City Government of San Jose Del Monte legislative records.'];
?>

<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-700 rounded-2xl text-white p-8 shadow-lg relative overflow-hidden">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="bg-white/15 backdrop-blur-md border border-white/20 text-blue-100 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">
                <?php echo htmlspecialchars($r_info['title']); ?>
            </span>
        </div>
        <h2 class="text-3xl font-bold">
            Welcome Back, <?php echo htmlspecialchars($username); ?>!
        </h2>
        <p class="mt-2 text-blue-100 max-w-2xl text-sm leading-relaxed">
            <?php echo htmlspecialchars($r_info['subtitle']); ?>
        </p>
    </div>
    <div class="absolute -right-10 -bottom-10 opacity-10 text-white text-9xl pointer-events-none">
        <i class="fa-solid fa-landmark"></i>
    </div>
</div>

<!-- ANALYTICS KPI CARDS -->

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mt-8">

<div class="bg-white rounded-xl shadow p-6">

<div class="flex justify-between">

<div>

<p class="text-slate-500">

Active Policy Papers

</p>

<h2 class="text-4xl font-bold mt-2">

<?php echo number_format($kpi_active_policies); ?>

</h2>

</div>

<div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">

<i class="fa-solid fa-book text-blue-700 text-2xl"></i>

</div>

</div>

<p class="text-green-600 mt-5">

Live Database Total

</p>

</div>

<div class="bg-white rounded-xl shadow p-6">

<div class="flex justify-between">

<div>

<p class="text-slate-500">

Data Sources

</p>

<h2 class="text-4xl font-bold mt-2">

<?php echo number_format($kpi_data_sources); ?>

</h2>

</div>

<div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">

<i class="fa-solid fa-database text-green-700 text-2xl"></i>

</div>

</div>

<p class="text-green-600 mt-5">

Connected Sources

</p>

</div>

<div class="bg-white rounded-xl shadow p-6">

<div class="flex justify-between">

<div>

<p class="text-slate-500">

Impact Reports

</p>

<h2 class="text-4xl font-bold mt-2">

<?php echo number_format($kpi_impact_reports); ?>

</h2>

</div>

<div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">

<i class="fa-solid fa-chart-line text-yellow-700 text-2xl"></i>

</div>

</div>

<p class="text-yellow-600 mt-5">

Assessments Recorded

</p>

</div>

<a href="modules/report-generation.php?status=Report+Generated" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition block border-b-2 border-transparent hover:border-purple-600 group">

<div class="flex justify-between">

<div>

<p class="text-slate-500 group-hover:text-purple-700 transition font-medium">

Generated Reports

</p>

<h2 class="text-4xl font-bold mt-2 text-slate-800">

<?php echo number_format($kpi_generated_reports); ?>

</h2>

</div>

<div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center group-hover:scale-110 transition">

<i class="fa-solid fa-file-lines text-purple-700 text-2xl"></i>

</div>

</div>

<p class="text-green-600 mt-5 flex items-center gap-1.5 font-medium">

<i class="fa-solid fa-circle-arrow-down"></i> Ready to Download

</p>

</a>

</div>


<!-- ========================================= -->
<!-- 2. SEE SECOND: CHARTS & VISUAL ANALYTICS -->
<!-- ========================================= -->

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mt-10">

    <div class="bg-white rounded-xl shadow p-6">

        <h2 class="font-bold text-xl mb-5 flex justify-between items-center">

            <span>Policy Research Statistics</span>

        </h2>

        <div class="h-72 flex items-center justify-center">

            <?php if (!empty($cat_labels)): ?>

                <canvas id="policyResearchChart"></canvas>

            <?php else: ?>

                <div class="text-center p-6 bg-slate-50 rounded-lg w-full">

                    <i class="fa-solid fa-chart-bar text-3xl text-slate-300 mb-2 block"></i>

                    <p class="text-slate-500 font-medium">No policy categories recorded in database yet.</p>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div class="bg-white rounded-xl shadow p-6">

        <h2 class="font-bold text-xl mb-5 flex justify-between items-center">

            <span>Impact Assessment Trends</span>

        </h2>

        <div class="h-72 flex items-center justify-center">

            <?php if (!empty($trend_labels)): ?>

                <canvas id="impactTrendsChart"></canvas>

            <?php else: ?>

                <div class="text-center p-6 bg-slate-50 rounded-lg w-full">

                    <i class="fa-solid fa-chart-line text-3xl text-slate-300 mb-2 block"></i>

                    <p class="text-slate-500 font-medium">No impact assessment trends recorded yet.</p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- ========================================= -->
<!-- 3. SEE THIRD: QUICK ACTIONS -->
<!-- ========================================= -->

<div class="mt-10">

    <div class="flex items-center justify-between mb-6">

        <h2 class="text-2xl font-bold text-slate-800">

            Quick Actions

        </h2>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        <?php if (isAdmin()): ?>
            <a href="admin/users.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-yellow-500 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-yellow-600 transition">User Management</h3>
                        <p class="text-slate-500 mt-2 text-xs">Provision accounts and manage role permissions.</p>
                    </div>
                    <i class="fa-solid fa-users-gear text-3xl text-yellow-500"></i>
                </div>
            </a>
            <a href="modules/policy-research.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-blue-700 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-blue-700 transition">Policy Research</h3>
                        <p class="text-slate-500 mt-2 text-xs">Search, analyze, and manage ordinances.</p>
                    </div>
                    <i class="fa-solid fa-book-open text-3xl text-blue-700"></i>
                </div>
            </a>
            <a href="modules/data-collection.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-green-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-green-600 transition">Data Collection</h3>
                        <p class="text-slate-500 mt-2 text-xs">Review incoming municipal datasets.</p>
                    </div>
                    <i class="fa-solid fa-database text-3xl text-green-600"></i>
                </div>
            </a>
            <a href="modules/report-generation.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-red-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-red-600 transition">Generate Reports</h3>
                        <p class="text-slate-500 mt-2 text-xs">Export official PDF and Word briefs.</p>
                    </div>
                    <i class="fa-solid fa-file-pdf text-3xl text-red-600"></i>
                </div>
            </a>

        <?php elseif (isResearcher()): ?>
            <a href="modules/add-policy.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-blue-700 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-blue-700 transition">Draft Policy</h3>
                        <p class="text-slate-500 mt-2 text-xs">Create a new legislative proposal.</p>
                    </div>
                    <i class="fa-solid fa-file-circle-plus text-3xl text-blue-700"></i>
                </div>
            </a>
            <a href="modules/policy-research.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-purple-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-purple-600 transition">AI Legal Citations</h3>
                        <p class="text-slate-500 mt-2 text-xs">Generate Gemini legal references.</p>
                    </div>
                    <i class="fa-solid fa-robot text-3xl text-purple-600"></i>
                </div>
            </a>
            <a href="modules/impact-assessment.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-indigo-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-indigo-600 transition">Impact KPIs</h3>
                        <p class="text-slate-500 mt-2 text-xs">Evaluate effectiveness and equity scores.</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-3xl text-indigo-600"></i>
                </div>
            </a>
            <a href="modules/benchmarking-analysis.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-amber-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-amber-600 transition">Benchmarking</h3>
                        <p class="text-slate-500 mt-2 text-xs">Score comparative policy matrices.</p>
                    </div>
                    <i class="fa-solid fa-scale-balanced text-3xl text-amber-600"></i>
                </div>
            </a>

        <?php elseif (isDataEncoder()): ?>
            <a href="modules/data-collection.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-green-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-green-600 transition">Upload Dataset</h3>
                        <p class="text-slate-500 mt-2 text-xs">Upload CSV, Excel, or PDF records.</p>
                    </div>
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-green-600"></i>
                </div>
            </a>
            <a href="modules/data-collection.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-blue-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-blue-600 transition">Dataset Library</h3>
                        <p class="text-slate-500 mt-2 text-xs">Organize and manage collection files.</p>
                    </div>
                    <i class="fa-solid fa-database text-3xl text-blue-600"></i>
                </div>
            </a>
            <a href="modules/policy-research.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-slate-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-slate-800 transition">Policy Repository</h3>
                        <p class="text-slate-500 mt-2 text-xs">Browse policy proposal references.</p>
                    </div>
                    <i class="fa-solid fa-book-open text-3xl text-slate-600"></i>
                </div>
            </a>
            <a href="modules/data-visualization.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-purple-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-purple-600 transition">Data Visualization</h3>
                        <p class="text-slate-500 mt-2 text-xs">View charts and collection statistics.</p>
                    </div>
                    <i class="fa-solid fa-chart-pie text-3xl text-purple-600"></i>
                </div>
            </a>

        <?php else: /* Viewer */ ?>
            <a href="modules/policy-research.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-blue-700 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-blue-700 transition">Policy Library</h3>
                        <p class="text-slate-500 mt-2 text-xs">Read ordinances and legal citations.</p>
                    </div>
                    <i class="fa-solid fa-book-open text-3xl text-blue-700"></i>
                </div>
            </a>
            <a href="modules/impact-assessment.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-indigo-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-indigo-600 transition">Impact Analytics</h3>
                        <p class="text-slate-500 mt-2 text-xs">Review municipal KPI evaluation scores.</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-3xl text-indigo-600"></i>
                </div>
            </a>
            <a href="modules/benchmarking-analysis.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-amber-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-amber-600 transition">Benchmarking</h3>
                        <p class="text-slate-500 mt-2 text-xs">Examine comparative ordinances.</p>
                    </div>
                    <i class="fa-solid fa-scale-balanced text-3xl text-amber-600"></i>
                </div>
            </a>
            <a href="modules/report-generation.php" class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-red-600 group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 group-hover:text-red-600 transition">Export Reports</h3>
                        <p class="text-slate-500 mt-2 text-xs">Download policy briefs and summaries.</p>
                    </div>
                    <i class="fa-solid fa-file-pdf text-3xl text-red-600"></i>
                </div>
            </a>
        <?php endif; ?>

    </div>

</div>


<!-- ========================================= -->
<!-- 4. SEE LAST: RECENT ACTIVITY TABLE & INFO -->
<!-- ========================================= -->

<!-- RECENT ACTIVITY TABLE -->

<div class="bg-white rounded-xl shadow mt-10">

    <div class="flex justify-between items-center px-6 py-5 border-b">

        <h2 class="text-xl font-bold">

            Recent Legislative Activities

        </h2>

        <?php if (function_exists('isAdmin') && isAdmin()): ?>
        <a href="admin/activity_logs.php" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2 shadow-sm">
            <span>View Full Audit Trail</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
        <?php endif; ?>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-slate-100">

                <tr>

                    <th class="text-left p-4">Reference No.</th>
                    <th class="text-left p-4">Policy Title</th>
                    <th class="text-left p-4">Module</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-left p-4">Last Updated</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($recent_activities_res && $recent_activities_res->num_rows > 0): ?>

                    <?php while($act = $recent_activities_res->fetch_assoc()): ?>

                        <tr class="border-b hover:bg-slate-50">

                            <td class="p-4 font-mono text-sm"><?php echo htmlspecialchars(!empty($act['document_id']) ? $act['document_id'] : ('LOG-' . sprintf('%04d', $act['id']))); ?></td>

                            <td class="p-4 font-medium text-slate-800"><?php echo htmlspecialchars($act['action']); ?></td>

                            <td class="p-4 text-slate-600"><?php echo htmlspecialchars($act['module']); ?></td>

                            <td class="p-4">

                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">

                                    Logged

                                </span>

                            </td>

                            <td class="p-4 text-sm text-slate-500"><?php echo date('M j, Y g:i A', strtotime($act['timestamp'])); ?></td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5" class="p-6 text-center text-slate-500">

                            No recent activities recorded in database yet.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- DASHBOARD INFORMATION (UPCOMING DEADLINES) -->
<div class="mt-10 mb-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
        
        <!-- Section Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        Upcoming Deadlines
                        <?php if ($pending_deadlines_count > 0): ?>
                            <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2.5 py-0.5 rounded-full">
                                <?php echo $pending_deadlines_count; ?> Pending
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Track legislative deliverables, assessment deadlines, and scheduled submissions.
                    </p>
                </div>
            </div>
            
            <!-- Add Deadline Button -->
            <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER])): ?>
            <button type="button" 
                    onclick="openAddDeadlineModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 shadow-xs hover:shadow transition cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Add Deadline</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Deadlines List -->
        <?php if (!empty($deadlines)): ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($deadlines as $dl): 
                    $today_ts = strtotime(date('Y-m-d'));
                    $due_ts = strtotime($dl['due_date']);
                    $days_diff = (int)round(($due_ts - $today_ts) / 86400);
                    $is_completed = ($dl['status'] === 'completed');

                    // Priority styles
                    $prio_color = 'bg-slate-100 text-slate-700';
                    if ($dl['priority'] === 'high') {
                        $prio_color = 'bg-rose-100 text-rose-700 border border-rose-200';
                    } elseif ($dl['priority'] === 'medium') {
                        $prio_color = 'bg-amber-100 text-amber-700 border border-amber-200';
                    } elseif ($dl['priority'] === 'low') {
                        $prio_color = 'bg-blue-100 text-blue-700 border border-blue-200';
                    }

                    // Due text & styling
                    if ($is_completed) {
                        $due_text = 'Completed';
                        $due_badge_class = 'text-emerald-600 bg-emerald-50 border border-emerald-200';
                    } elseif ($days_diff < 0) {
                        $due_text = 'Overdue (' . abs($days_diff) . 'd ago)';
                        $due_badge_class = 'text-red-700 bg-red-50 border border-red-200 font-bold';
                    } elseif ($days_diff === 0) {
                        $due_text = 'Due Today!';
                        $due_badge_class = 'text-rose-700 bg-rose-50 border border-rose-200 font-bold animate-pulse';
                    } elseif ($days_diff === 1) {
                        $due_text = 'Due Tomorrow';
                        $due_badge_class = 'text-amber-700 bg-amber-50 border border-amber-200 font-semibold';
                    } elseif ($days_diff <= 7) {
                        $due_text = 'In ' . $days_diff . ' days (' . date('M j', $due_ts) . ')';
                        $due_badge_class = 'text-amber-700 bg-amber-50 font-medium';
                    } else {
                        $due_text = date('M j, Y', $due_ts);
                        $due_badge_class = 'text-slate-600 bg-slate-100';
                    }
                ?>
                    <div class="py-3.5 flex items-center justify-between flex-wrap gap-3 hover:bg-slate-50/70 px-2 rounded-xl transition group <?php echo $is_completed ? 'opacity-60 bg-slate-50/40' : ''; ?>">
                        
                        <!-- Left: Status checkbox + Title & Category -->
                        <div class="flex items-center gap-3.5 min-w-0 flex-1">
                            <!-- Quick Toggle Completion -->
                            <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER])): ?>
                            <a href="?toggle_deadline=<?php echo $dl['id']; ?>" 
                               title="<?php echo $is_completed ? 'Mark as Pending' : 'Mark as Completed'; ?>" 
                               class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition cursor-pointer <?php echo $is_completed ? 'bg-emerald-600 text-white shadow-sm' : 'border-2 border-slate-300 hover:border-emerald-500 text-transparent hover:text-emerald-500'; ?>">
                                <i class="fa-solid fa-check text-xs"></i>
                            </a>
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 <?php echo $is_completed ? 'bg-emerald-600 text-white shadow-sm' : 'border-2 border-slate-300 text-transparent'; ?>">
                                <i class="fa-solid fa-check text-xs"></i>
                            </div>
                            <?php endif; ?>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-slate-800 <?php echo $is_completed ? 'line-through text-slate-400' : ''; ?>">
                                        <?php echo htmlspecialchars($dl['title']); ?>
                                    </span>
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-md <?php echo $prio_color; ?>">
                                        <?php echo ucfirst($dl['priority']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                    <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[11px] font-medium">
                                        <i class="fa-solid fa-tag text-[9px] mr-1 text-slate-400"></i><?php echo htmlspecialchars($dl['category']); ?>
                                    </span>
                                    <?php if (!empty($dl['created_by'])): ?>
                                        <span class="text-[11px] text-slate-400">&bull; By @<?php echo htmlspecialchars($dl['created_by']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Due Date Badge & Action Buttons -->
                        <div class="flex items-center gap-3">
                            <span class="text-xs px-3 py-1 rounded-lg <?php echo $due_badge_class; ?>">
                                <i class="fa-regular fa-clock mr-1"></i><?php echo $due_text; ?>
                            </span>

                            <!-- Actions -->
                            <div class="flex items-center gap-1">
                                <?php if (hasRole([ROLE_ADMIN, ROLE_RESEARCHER])): ?>
                                <!-- Edit / Update Button -->
                                <button type="button" 
                                        onclick="openUpdateDeadlineModal(<?php echo htmlspecialchars(json_encode($dl), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        title="Update Deadline"
                                        class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center transition cursor-pointer text-xs">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (canDeletePolicy()): ?>
                                <!-- Delete Button -->
                                <a href="?delete_deadline=<?php echo $dl['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this deadline?');"
                                   title="Delete Deadline"
                                   class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 flex items-center justify-center transition cursor-pointer text-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-slate-400">
                <i class="fa-regular fa-calendar-xmark text-4xl block mb-2 text-slate-300"></i>
                <p class="text-sm font-medium text-slate-600">No deadlines recorded yet.</p>
                <p class="text-xs text-slate-400 mt-1">Click "Add Deadline" above to track key dates and submissions.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ============================================ -->
<!-- ADD DEADLINE MODAL -->
<!-- ============================================ -->
<div id="addDeadlineModal" class="fixed inset-0 z-[999] hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 select-none">
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="addDeadlineModalCard">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-700 px-6 py-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-calendar-plus text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base leading-tight">Create New Deadline</h3>
                    <p class="text-xs text-blue-200 mt-0.5">Schedule a legislative milestone</p>
                </div>
            </div>
            <button type="button" onclick="closeAddDeadlineModal()" class="text-white/80 hover:text-white hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Form -->
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="add_deadline" value="1">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Deadline Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           required 
                           placeholder="e.g. Submit Impact Assessment for Health Ordinance" 
                           class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Category
                        </label>
                        <select name="category" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition bg-white">
                            <option value="Policy Research">Policy Research</option>
                            <option value="Data Collection">Data Collection</option>
                            <option value="Impact Assessment">Impact Assessment</option>
                            <option value="Benchmarking Analysis">Benchmarking Analysis</option>
                            <option value="Report Generation">Report Generation</option>
                            <option value="Council Presentation">Council Presentation</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="due_date" 
                               required 
                               value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" 
                               class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Priority Level
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center justify-center gap-2 p-2.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50">
                            <input type="radio" name="priority" value="low" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-semibold text-slate-700">Low</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-2.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-amber-600 has-[:checked]:bg-amber-50/50">
                            <input type="radio" name="priority" value="medium" checked class="text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-semibold text-amber-800">Medium</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-2.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-red-600 has-[:checked]:bg-red-50/50">
                            <input type="radio" name="priority" value="high" class="text-red-600 focus:ring-red-500">
                            <span class="text-xs font-semibold text-red-800">High</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" 
                        onclick="closeAddDeadlineModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition shadow-sm hover:shadow flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Create Deadline</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- UPDATE / EDIT DEADLINE MODAL -->
<!-- ============================================ -->
<div id="updateDeadlineModal" class="fixed inset-0 z-[999] hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 select-none">
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform scale-95 opacity-0 transition-all duration-200" id="updateDeadlineModalCard">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base leading-tight">Update Deadline</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Modify scheduled date or status</p>
                </div>
            </div>
            <button type="button" onclick="closeUpdateDeadlineModal()" class="text-white/80 hover:text-white hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Form -->
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="update_deadline" value="1">
            <input type="hidden" name="deadline_id" id="edit_deadline_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Deadline Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           id="edit_deadline_title" 
                           required 
                           class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Category
                        </label>
                        <select name="category" id="edit_deadline_category" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition bg-white">
                            <option value="Policy Research">Policy Research</option>
                            <option value="Data Collection">Data Collection</option>
                            <option value="Impact Assessment">Impact Assessment</option>
                            <option value="Benchmarking Analysis">Benchmarking Analysis</option>
                            <option value="Report Generation">Report Generation</option>
                            <option value="Council Presentation">Council Presentation</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="due_date" 
                               id="edit_deadline_due_date" 
                               required 
                               class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Priority Level
                        </label>
                        <select name="priority" id="edit_deadline_priority" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition bg-white">
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Status
                        </label>
                        <select name="status" id="edit_deadline_status" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none transition bg-white">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" 
                        onclick="closeUpdateDeadlineModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white text-sm font-semibold transition shadow-sm hover:shadow flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Deadline Modal Functions
function openAddDeadlineModal() {
    const modal = document.getElementById('addDeadlineModal');
    const card = document.getElementById('addDeadlineModalCard');
    if (modal && card) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }
}

function closeAddDeadlineModal() {
    const modal = document.getElementById('addDeadlineModal');
    const card = document.getElementById('addDeadlineModalCard');
    if (modal && card) {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }
}

function openUpdateDeadlineModal(data) {
    if (!data) return;
    document.getElementById('edit_deadline_id').value = data.id || '';
    document.getElementById('edit_deadline_title').value = data.title || '';
    document.getElementById('edit_deadline_category').value = data.category || 'General';
    document.getElementById('edit_deadline_due_date').value = data.due_date || '';
    document.getElementById('edit_deadline_priority').value = data.priority || 'medium';
    document.getElementById('edit_deadline_status').value = data.status || 'pending';

    const modal = document.getElementById('updateDeadlineModal');
    const card = document.getElementById('updateDeadlineModalCard');
    if (modal && card) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }
}

function closeUpdateDeadlineModal() {
    const modal = document.getElementById('updateDeadlineModal');
    const card = document.getElementById('updateDeadlineModalCard');
    if (modal && card) {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }
}

// Modal event listeners for clicking backdrop
document.addEventListener('click', function(e) {
    const addModal = document.getElementById('addDeadlineModal');
    const updateModal = document.getElementById('updateDeadlineModal');
    if (addModal && e.target === addModal) closeAddDeadlineModal();
    if (updateModal && e.target === updateModal) closeUpdateDeadlineModal();
});
</script>

</main>

</div>

</div>

<!-- NOTIFICATION & CHART JS SCRIPTS -->

<script>
function toggleNotificationDropdown() {
    const menu = document.getElementById('notificationMenu');
    menu.classList.toggle('hidden');
}

function seePreviousNotifs() {
    document.querySelectorAll('.notif-item-extra').forEach(el => {
        el.classList.remove('hidden');
    });
    const container = document.getElementById('seePreviousContainer');
    if (container) {
        container.style.display = 'none';
    }
}

document.addEventListener('click', function(event) {
    const container = document.getElementById('notificationDropdownContainer');
    const menu = document.getElementById('notificationMenu');
    if (container && !container.contains(event.target) && menu && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }
});

function switchNotifTab(tab) {
    const listAll = document.getElementById('notifListAll');
    const listUnread = document.getElementById('notifListUnread');
    const btnAll = document.getElementById('tabBtnAll');
    const btnUnread = document.getElementById('tabBtnUnread');

    if (tab === 'all') {
        listAll.classList.remove('hidden');
        listUnread.classList.add('hidden');
        btnAll.classList.add('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
        btnAll.classList.remove('border-transparent');
        btnUnread.classList.remove('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
        btnUnread.classList.add('border-transparent');
    } else {
        listAll.classList.add('hidden');
        listUnread.classList.remove('hidden');
        btnUnread.classList.add('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
        btnUnread.classList.remove('border-transparent');
        btnAll.classList.remove('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
        btnAll.classList.add('border-transparent');
    }
}

function markAllAsRead() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mark_all_read=1'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('unreadBadge');
            if (badge) badge.style.display = 'none';

            const subHeader = document.getElementById('unreadSubHeader');
            if (subHeader) subHeader.textContent = '0 unread updates';

            const countUnreadTab = document.getElementById('countUnreadTab');
            if (countUnreadTab) countUnreadTab.textContent = '0';

            const listUnread = document.getElementById('notifListUnread');
            if (listUnread) {
                listUnread.innerHTML = `<div class="p-8 text-center text-slate-400 text-xs">
                    <i class="fa-solid fa-check-double text-2xl mb-2 text-emerald-500 block"></i>
                    All caught up! No unread notifications.
                </div>`;
            }

            document.querySelectorAll('.unread-dot').forEach(el => {
                el.style.display = 'none';
            });
            document.querySelectorAll('.notif-item').forEach(el => {
                el.classList.remove('bg-blue-50/40');
            });
        }
    });
}
</script>

<?php if (!empty($cat_labels) || !empty($trend_labels)): ?>

<script>

document.addEventListener("DOMContentLoaded", function() {

    <?php if (!empty($cat_labels)): ?>

    const ctxBar = document.getElementById('policyResearchChart')?.getContext('2d');

    if (ctxBar) {

        new Chart(ctxBar, {

            type: 'bar',

            data: {

                labels: <?php echo json_encode($cat_labels); ?>,

                datasets: [{

                    label: 'Policies Recorded',

                    data: <?php echo json_encode($cat_counts); ?>,

                    backgroundColor: [
                        'rgba(37, 99, 235, 0.85)',
                        'rgba(22, 163, 74, 0.85)',
                        'rgba(124, 58, 237, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(6, 182, 212, 0.85)',
                        'rgba(239, 68, 68, 0.85)'
                    ],

                    borderColor: [
                        '#1d4ed8',
                        '#15803d',
                        '#6d28d9',
                        '#d97706',
                        '#0891b2',
                        '#dc2626'
                    ],

                    borderWidth: 1.5,

                    borderRadius: 6,

                    maxBarThickness: 48,

                    categoryPercentage: 0.6,

                    barPercentage: 0.75

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: { legend: { display: false } },

                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 4,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        }
                    }
                }

            }

        });

    }

    <?php endif; ?>


    <?php if (!empty($trend_labels)): ?>

    const ctxLine = document.getElementById('impactTrendsChart')?.getContext('2d');

    if (ctxLine) {

        new Chart(ctxLine, {

            type: 'line',

            data: {

                labels: <?php echo json_encode($trend_labels); ?>,

                datasets: [{

                    label: 'Impact Assessments Created',

                    data: <?php echo json_encode($trend_counts); ?>,

                    backgroundColor: 'rgba(16, 185, 129, 0.1)',

                    borderColor: 'rgba(16, 185, 129, 1)',

                    borderWidth: 3,

                    fill: true,

                    tension: 0.4,

                    pointRadius: 5

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: { legend: { display: false } },

                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }

            }

        });

    }

    <?php endif; ?>

});

</script>

<?php endif; ?>

</body>

</html>