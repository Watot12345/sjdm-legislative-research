<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Enforce Strict Admin-Only Access via RBAC
requireRole(ROLE_ADMIN);

$pageTitle = "Activity & Audit Trail";
$username = $_SESSION['username'];
$conn = getDBConnection();

// ============================================
// STATISTICS CALCULATIONS
// ============================================
$total_logs = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs")->fetch_assoc()['cnt'] ?? 0);
$auth_logs = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE module IN ('Authentication', 'Security') OR action LIKE '%login%' OR action LIKE '%logout%' OR action LIKE '%2FA%'")->fetch_assoc()['cnt'] ?? 0);
$legislative_logs = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE module IN ('Policy Research', 'Data Collection', 'Impact Assessment', 'Benchmarking', 'Benchmarking Analysis', 'Report Generation')")->fetch_assoc()['cnt'] ?? 0);
$user_mgmt_logs = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE module = 'User Management'")->fetch_assoc()['cnt'] ?? 0);
$unique_users_count = (int)($conn->query("SELECT COUNT(DISTINCT user) as cnt FROM activity_logs WHERE user IS NOT NULL AND user != ''")->fetch_assoc()['cnt'] ?? 0);
$today_logs = (int)($conn->query("SELECT COUNT(*) as cnt FROM activity_logs WHERE DATE(timestamp) = CURDATE()")->fetch_assoc()['cnt'] ?? 0);

// Get distinct modules for filter dropdown
$modules_res = $conn->query("SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");
$module_list = [];
if ($modules_res) {
    while($m = $modules_res->fetch_assoc()) {
        $module_list[] = $m['module'];
    }
}

// Fetch all logs ordered by latest
$logs_res = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity & Audit Trail - Legislative Research System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-hover { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .ml-72 { margin-left: 0 !important; }
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen text-slate-800 antialiased">

    <?php include("../includes/sidebar.php"); ?>
    
    <div class="ml-72">
        <?php include("../includes/navbar.php"); ?>
        
        <main class="p-8">
            
            <!-- STATISTICS KPI GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                
                <!-- Total Logs -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Logs</p>
                            <h2 class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo number_format($total_logs); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-list text-xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 text-xs font-medium mt-3">All System Events</p>
                </div>

                <!-- Today's Activity -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Today's Logs</p>
                            <h2 class="text-3xl font-extrabold text-emerald-600 mt-2"><?php echo number_format($today_logs); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-day text-xl"></i>
                        </div>
                    </div>
                    <p class="text-emerald-600 text-xs font-medium mt-3">Recorded Today</p>
                </div>

                <!-- Legislative Logs -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Legislative</p>
                            <h2 class="text-3xl font-extrabold text-blue-700 mt-2"><?php echo number_format($legislative_logs); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <i class="fa-solid fa-landmark text-xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 text-xs font-medium mt-3">Policy Milestones</p>
                </div>

                <!-- Security & Auth -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Auth & 2FA</p>
                            <h2 class="text-3xl font-extrabold text-amber-600 mt-2"><?php echo number_format($auth_logs); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="fa-solid fa-key text-xl"></i>
                        </div>
                    </div>
                    <p class="text-amber-600 text-xs font-medium mt-3">Logins & Device Trust</p>
                </div>

                <!-- User Management -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">User Admin</p>
                            <h2 class="text-3xl font-extrabold text-indigo-600 mt-2"><?php echo number_format($user_mgmt_logs); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="fa-solid fa-user-shield text-xl"></i>
                        </div>
                    </div>
                    <p class="text-indigo-600 text-xs font-medium mt-3">Account CRUD Actions</p>
                </div>

                <!-- Active Users -->
                <div class="bg-white rounded-xl shadow p-5 card-hover border border-slate-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Active Actors</p>
                            <h2 class="text-3xl font-extrabold text-purple-600 mt-2"><?php echo number_format($unique_users_count); ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="fa-solid fa-users text-xl"></i>
                        </div>
                    </div>
                    <p class="text-purple-600 text-xs font-medium mt-3">Distinct Operators</p>
                </div>
            </div>

            <!-- MAIN LOGS CONTAINER -->
            <div class="bg-white rounded-xl shadow mb-8 border border-slate-200">
                
                <!-- HEADER & TOOLBAR -->
                <div class="p-6 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2.5">
                            <i class="fa-solid fa-shield-halved text-blue-700"></i>
                            <span>System Activity & Audit Trail</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Real-time tamper-evident chronological event log across all municipal modules.</p>
                    </div>

                    <!-- ACTION BUTTONS & SEARCH -->
                    <div class="flex flex-wrap items-center gap-3 no-print">
                        
                        <!-- Module Filter -->
                        <div class="relative">
                            <select id="moduleFilterSelect" onchange="filterLogs()" class="text-xs border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-600 cursor-pointer font-medium">
                                <option value="all">Filter by Module (All)</option>
                                <option value="Authentication">Authentication / 2FA</option>
                                <option value="User Management">User Management</option>
                                <option value="Policy Research">Policy Research</option>
                                <option value="Data Collection">Data Collection</option>
                                <option value="Impact Assessment">Impact Assessment</option>
                                <option value="Benchmarking">Benchmarking Analysis</option>
                                <option value="Report Generation">Report Generation</option>
                                <option value="Security">Security & Devices</option>
                                <option value="Dashboard">Dashboard Operations</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text" 
                                   id="auditSearchInput" 
                                   oninput="filterLogs()" 
                                   placeholder="Search action, user, doc ID..." 
                                   class="w-60 pl-9 pr-4 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        </div>

                        <button type="button" onclick="exportLogsCSV()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 border border-slate-300">
                            <i class="fa-solid fa-file-csv text-emerald-600 text-sm"></i>
                            <span>Export CSV</span>
                        </button>

                        <button type="button" onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-3.5 py-2 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-print text-xs"></i>
                            <span>Print Log</span>
                        </button>
                    </div>
                </div>

                <!-- CATEGORY FILTER TABS (2 DEDICATED MODULE GROUPS) -->
                <div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 px-6 pt-3 bg-slate-50/70 no-print">
                    <button type="button" 
                            id="tabBtnLegislative"
                            onclick="switchCategoryTab('legislative')" 
                            class="px-4 py-2.5 text-xs font-bold rounded-t-lg border-b-2 border-blue-600 text-blue-700 bg-white transition flex items-center gap-2 cursor-pointer shadow-xs">
                        <i class="fa-solid fa-landmark text-sm"></i>
                        <span>Legislative Module Activity</span>
                        <span class="bg-emerald-100 text-emerald-900 px-2 py-0.5 rounded-full text-[11px] font-bold"><?php echo $legislative_logs; ?></span>
                    </button>

                    <button type="button" 
                            id="tabBtnSystem"
                            onclick="switchCategoryTab('system')" 
                            class="px-4 py-2.5 text-xs font-semibold rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-shield-halved text-sm"></i>
                        <span>System & Security Logs</span>
                        <span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded-full text-[11px] font-bold"><?php echo $auth_logs + $user_mgmt_logs; ?></span>
                    </button>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="auditTable">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider font-semibold border-b">
                            <tr>
                                <th class="p-4 w-20">Log ID</th>
                                <th class="p-4">Module</th>
                                <th class="p-4">Action / Event Details</th>
                                <th class="p-4">User</th>
                                <th class="p-4">Reference No.</th>
                                <th class="p-4">Timestamp</th>
                                <th class="text-center p-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs" id="auditTableBody">
                            <?php if ($logs_res && $logs_res->num_rows > 0): ?>
                                <?php while($row = $logs_res->fetch_assoc()): ?>
                                    <?php 
                                        $mod = $row['module'] ?? 'System';
                                        $act = $row['action'] ?? '';
                                        $doc_id = $row['document_id'] ?? '';
                                        $is_sys = (in_array($mod, ['Authentication', 'User Management', 'Security', 'Administration', 'Dashboard'], true) || 
                                                   stripos($act, 'login') !== false || 
                                                   stripos($act, 'logout') !== false || 
                                                   stripos($act, '2FA') !== false || 
                                                   stripos($act, 'deadline') !== false);
                                        $cat = $is_sys ? 'system' : 'legislative';
                                        
                                        if ($mod === 'Policy Research') {
                                            $badge_class = 'bg-blue-100 text-blue-700 border border-blue-200';
                                            $icon = 'fa-scale-balanced';
                                        } elseif ($mod === 'Data Collection') {
                                            $badge_class = 'bg-cyan-100 text-cyan-700 border border-cyan-200';
                                            $icon = 'fa-database';
                                        } elseif ($mod === 'Impact Assessment') {
                                            $badge_class = 'bg-purple-100 text-purple-700 border border-purple-200';
                                            $icon = 'fa-chart-pie';
                                        } elseif ($mod === 'Benchmarking' || $mod === 'Benchmarking Analysis') {
                                            $badge_class = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                            $icon = 'fa-chart-column';
                                        } elseif ($mod === 'Report Generation') {
                                            $badge_class = 'bg-rose-100 text-rose-700 border border-rose-200';
                                            $icon = 'fa-file-lines';
                                        } elseif ($mod === 'Authentication') {
                                            $badge_class = 'bg-slate-100 text-slate-700 border border-slate-300';
                                            $icon = 'fa-key';
                                        } elseif ($mod === 'User Management') {
                                            $badge_class = 'bg-indigo-100 text-indigo-700 border border-indigo-200';
                                            $icon = 'fa-users';
                                        } elseif ($mod === 'Security') {
                                            $badge_class = 'bg-amber-100 text-amber-800 border border-amber-200';
                                            $icon = 'fa-shield-halved';
                                        } else {
                                            $badge_class = 'bg-slate-100 text-slate-700 border border-slate-200';
                                            $icon = 'fa-circle-info';
                                        }
                                    ?>
                                <tr class="hover:bg-slate-50 transition audit-row" data-category="<?php echo $cat; ?>" data-module="<?php echo htmlspecialchars($mod); ?>">
                                    <td class="p-4 font-mono font-bold text-slate-500">
                                        #<?php echo sprintf('%05d', $row['id']); ?>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 <?php echo $badge_class; ?>">
                                            <i class="fa-solid <?php echo $icon; ?> text-[10px]"></i>
                                            <?php echo htmlspecialchars($mod); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-medium text-slate-800">
                                        <?php echo htmlspecialchars($act); ?>
                                    </td>
                                    <td class="p-4 font-mono whitespace-nowrap">
                                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-semibold">
                                            <?php echo htmlspecialchars($row['user'] ?: 'System'); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-mono text-slate-600 whitespace-nowrap">
                                        <?php if (!empty($doc_id)): ?>
                                            <span class="text-blue-700 font-medium"><?php echo htmlspecialchars($doc_id); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-slate-500 whitespace-nowrap">
                                        <?php echo date('M j, Y h:i:s A', strtotime($row['timestamp'])); ?>
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <span class="bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                                            Recorded
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr id="noLogsRow">
                                    <td colspan="7" class="text-center p-12 text-slate-500 text-sm">
                                        <i class="fa-solid fa-inbox text-4xl block mb-4 text-slate-300"></i>
                                        No activity logs recorded yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr id="noMatchingLogsRow" class="hidden">
                                <td colspan="7" class="text-center p-12 text-slate-500 text-sm">
                                    <i class="fa-solid fa-magnifying-glass text-4xl block mb-4 text-slate-300"></i>
                                    No activity logs match your filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOOTER -->
                <div class="flex flex-wrap justify-between items-center px-6 py-4 border-t gap-3 no-print">
                    <p class="text-xs text-slate-500 font-medium">
                        Showing <strong class="text-slate-800" id="logStartEntry">0</strong> to <strong class="text-slate-800" id="logEndEntry">0</strong> of <strong class="text-slate-800" id="logTotalEntries">0</strong> audit entries
                    </p>

                    <div class="flex items-center gap-1.5 flex-wrap" id="logPaginationControls">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500 text-xs">
                <p>© <?php echo date('Y'); ?> Legislative Research, Policy Analysis, and Impact Evaluation System (SJDM)</p>
                <p class="mt-1">Administrative Audit Trail — Official Record Compliance</p>
            </footer>

        </main>
    </div>

    <!-- JAVASCRIPT: FILTERING, PAGINATION & CSV EXPORT -->
    <script>
        let currentLogPage = 1;
        let activeCategoryTab = 'legislative';
        const logsPerPage = 15;

        function switchCategoryTab(tab) {
            activeCategoryTab = tab;
            currentLogPage = 1;

            const tabLeg = document.getElementById('tabBtnLegislative');
            const tabSys = document.getElementById('tabBtnSystem');

            const activeClass = "px-4 py-2.5 text-xs font-bold rounded-t-lg border-b-2 border-blue-600 text-blue-700 bg-white transition flex items-center gap-2 cursor-pointer shadow-xs";
            const inactiveClass = "px-4 py-2.5 text-xs font-semibold rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition flex items-center gap-2 cursor-pointer";

            if (tabLeg) tabLeg.className = (tab === 'legislative') ? activeClass : inactiveClass;
            if (tabSys) tabSys.className = (tab === 'system') ? activeClass : inactiveClass;

            updateLogPagination();
        }

        function getFilteredLogRows() {
            const searchInput = document.getElementById('auditSearchInput');
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const moduleFilter = document.getElementById('moduleFilterSelect').value;
            const allRows = Array.from(document.querySelectorAll('#auditTableBody tr.audit-row'));

            return allRows.filter(row => {
                const rowCat = row.getAttribute('data-category');
                const matchesCategory = (rowCat === activeCategoryTab);

                const rowMod = (row.getAttribute('data-module') || '').toLowerCase();
                const matchesMod = (moduleFilter === 'all') || (rowMod === moduleFilter.toLowerCase()) || 
                                   (moduleFilter === 'Benchmarking' && rowMod.includes('benchmarking'));
                
                const matchesQuery = !query || row.textContent.toLowerCase().includes(query);
                return matchesCategory && matchesMod && matchesQuery;
            });
        }

        function filterLogs() {
            currentLogPage = 1;
            updateLogPagination();
        }

        function updateLogPagination() {
            const allRows = Array.from(document.querySelectorAll('#auditTableBody tr.audit-row'));
            const matchingRows = getFilteredLogRows();
            const totalMatching = matchingRows.length;
            const totalPages = Math.max(1, Math.ceil(totalMatching / logsPerPage));

            if (currentLogPage > totalPages) currentLogPage = totalPages;
            if (currentLogPage < 1) currentLogPage = 1;

            allRows.forEach(row => row.style.display = 'none');

            const noMatchingRow = document.getElementById('noMatchingLogsRow');
            if (totalMatching === 0 && allRows.length > 0) {
                if (noMatchingRow) noMatchingRow.classList.remove('hidden');
            } else {
                if (noMatchingRow) noMatchingRow.classList.add('hidden');
            }

            const startIndex = (currentLogPage - 1) * logsPerPage;
            const endIndex = Math.min(startIndex + logsPerPage, totalMatching);

            matchingRows.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });

            const startEl = document.getElementById('logStartEntry');
            const endEl = document.getElementById('logEndEntry');
            const totalEl = document.getElementById('logTotalEntries');
            if (startEl) startEl.textContent = totalMatching > 0 ? (startIndex + 1) : 0;
            if (endEl) endEl.textContent = endIndex;
            if (totalEl) totalEl.textContent = totalMatching;

            const controlsContainer = document.getElementById('logPaginationControls');
            if (!controlsContainer) return;

            let html = '';

            // Prev
            if (currentLogPage > 1) {
                html += `<button type="button" onclick="changeLogPage(${currentLogPage - 1})" class="border border-slate-300 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition flex items-center gap-1">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                </button>`;
            } else {
                html += `<button type="button" disabled class="border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed flex items-center gap-1 opacity-70">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                </button>`;
            }

            const maxVisibleBtns = 5;
            let startPage = Math.max(1, currentLogPage - 2);
            let endPage = Math.min(totalPages, startPage + maxVisibleBtns - 1);
            if (endPage - startPage < maxVisibleBtns - 1) {
                startPage = Math.max(1, endPage - maxVisibleBtns + 1);
            }

            for (let p = startPage; p <= endPage; p++) {
                if (p === currentLogPage) {
                    html += `<span class="bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm">${p}</span>`;
                } else {
                    html += `<button type="button" onclick="changeLogPage(${p})" class="border border-slate-300 text-slate-700 hover:bg-blue-50 hover:text-blue-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition">${p}</button>`;
                }
            }

            // Next
            if (currentLogPage < totalPages) {
                html += `<button type="button" onclick="changeLogPage(${currentLogPage + 1})" class="border border-slate-300 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition flex items-center gap-1">
                    Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>`;
            } else {
                html += `<button type="button" disabled class="border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed flex items-center gap-1 opacity-70">
                    Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>`;
            }

            controlsContainer.innerHTML = html;
        }

        function changeLogPage(page) {
            currentLogPage = page;
            updateLogPagination();
        }

        function exportLogsCSV() {
            const rows = getFilteredLogRows();
            if (rows.length === 0) {
                alert('No log entries to export.');
                return;
            }

            let csv = "Log ID,Module,Action Details,User,Reference ID,Timestamp,Status\n";
            rows.forEach(r => {
                const cols = Array.from(r.querySelectorAll('td')).map(td => {
                    let text = td.innerText.replace(/"/g, '""').trim();
                    return `"${text}"`;
                });
                csv += cols.join(",") + "\n";
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `activity_logs_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateLogPagination();
        });
    </script>

</body>
</html>
<?php $conn->close(); ?>
