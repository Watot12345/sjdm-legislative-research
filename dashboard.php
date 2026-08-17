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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE activity_logs SET is_read = 1 $notif_where_sql AND is_read = 0");
    echo json_encode(['success' => true]);
    exit();
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

// Dynamic Recent Activities & Notifications
$recent_activities_res = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 5");

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

<div class="bg-gradient-to-r from-blue-900 to-blue-600 rounded-xl text-white p-8 shadow-lg">

<h2 class="text-3xl font-bold">

Welcome Back,
<?php echo $username; ?>

</h2>

<p class="mt-3 text-blue-100">

Manage legislative research, analyze public policies,
evaluate impacts, compare ordinances, generate reports,
and monitor government policy intelligence.

</p>

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

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <a href="modules/policy-research.php"
        class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-blue-700">

            <div class="flex items-center justify-between">

                <div>

                    <h3 class="font-bold text-lg">

                        Policy Research

                    </h3>

                    <p class="text-slate-500 mt-2">

                        Search and analyze legislative documents.

                    </p>

                </div>

                <i class="fa-solid fa-book-open text-4xl text-blue-700"></i>

            </div>

        </a>

        <a href="modules/data-collection.php"
        class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-green-600">

            <div class="flex items-center justify-between">

                <div>

                    <h3 class="font-bold text-lg">

                        Data Collection

                    </h3>

                    <p class="text-slate-500 mt-2">

                        Upload and manage datasets.

                    </p>

                </div>

                <i class="fa-solid fa-database text-4xl text-green-600"></i>

            </div>

        </a>

        <a href="modules/report-generation.php"
        class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 border-l-4 border-red-600">

            <div class="flex items-center justify-between">

                <div>

                    <h3 class="font-bold text-lg">

                        Generate Reports

                    </h3>

                    <p class="text-slate-500 mt-2">

                        Export legislative reports.

                    </p>

                </div>

                <i class="fa-solid fa-file-pdf text-4xl text-red-600"></i>

            </div>

        </a>

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

        <button class="bg-blue-700 text-white px-5 py-2 rounded-lg hover:bg-blue-800">

            View All

        </button>

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

    <div class="bg-white rounded-xl shadow p-6">

        <h2 class="text-xl font-bold mb-5">

            Upcoming Deadlines

        </h2>

        <ul class="space-y-4">

            <li class="flex justify-between border-b pb-3">

                <span>

                    Submit Impact Assessment

                </span>

                <span class="text-red-600 font-semibold">

                    Jul 10

                </span>

            </li>

            <li class="flex justify-between border-b pb-3">

                <span>

                    Comparative Analysis Review

                </span>

                <span class="text-yellow-600 font-semibold">

                    Jul 12

                </span>

            </li>

            <li class="flex justify-between">

                <span>

                    Legislative Report Submission

                </span>

                <span class="text-green-600 font-semibold">

                    Jul 15

                </span>

            </li>

        </ul>

    </div>

</div>

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