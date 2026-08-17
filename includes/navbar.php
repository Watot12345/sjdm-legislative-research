<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Administrator';
$pageTitle = $pageTitle ?? "Dashboard";

// Dynamic Database Connection
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../config/config.php';
    $conn = getDBConnection();
}

// Notification filter (Exclude authentication login/logout logs)
$notif_where_sql = "WHERE action NOT LIKE '%login%' AND action NOT LIKE '%logout%' AND module != 'Authentication'";

// Handle AJAX Mark All Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE activity_logs SET is_read = 1 $notif_where_sql AND is_read = 0");
    echo json_encode(['success' => true]);
    exit();
}

// Bell Notifications Data
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

// Determine relative Logout URL
$logout_url = (strpos($_SERVER['PHP_SELF'], '/modules/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../logout.php' : 'logout.php';
?>

<header class="bg-white shadow-sm border-b border-slate-200 px-6 md:px-8 py-4 flex items-center justify-between relative z-30">

    <!-- Left: Sidebar Toggle & Page Title -->
    <div class="flex items-center gap-4">
        <button type="button" 
                onclick="toggleSidebarCompact()" 
                class="w-10 h-10 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-blue-700 flex items-center justify-center transition border border-slate-200 shadow-xs focus:outline-none cursor-pointer" 
                title="Toggle Compact / Expanded Sidebar">
            <i class="fa-solid fa-bars-staggered text-base"></i>
        </button>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 leading-tight">
                <?php echo htmlspecialchars($pageTitle); ?>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Dashboard /
                <span class="font-medium text-blue-700">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </span>
            </p>
        </div>
    </div>

    <!-- Right: Search, Notifications, User Dropdown -->
    <div class="flex items-center gap-5">

        <!-- Global Search Bar (Debounced, Indexed) -->
        <div class="relative hidden lg:block" id="globalSearchContainer">
            <input
                id="globalSearchInput"
                type="text"
                autocomplete="off"
                placeholder="Search documents, reports..."
                class="w-80 border border-slate-300 rounded-lg pl-11 pr-10 py-2 focus:ring-2 focus:ring-blue-600 outline-none transition-shadow"
                oninput="onGlobalSearchInput(this.value)"
                onkeydown="onGlobalSearchKeydown(event)"
                onfocus="if(this.value.length>=2) showGlobalDropdown()"
            >
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 pointer-events-none"></i>
            <button id="globalSearchClear" onclick="clearGlobalSearch()" class="hidden absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Search Results Dropdown -->
            <div id="globalSearchDropdown" class="hidden absolute left-0 right-0 top-12 bg-white rounded-xl shadow-2xl border border-slate-200 z-[999] overflow-hidden max-h-[420px] overflow-y-auto">
                <!-- Loading State -->
                <div id="gsd-loading" class="hidden p-5 text-center text-slate-500 text-sm">
                    <i class="fa-solid fa-circle-notch fa-spin mr-2 text-blue-500"></i> Searching...
                </div>
                <!-- No Results -->
                <div id="gsd-empty" class="hidden p-6 text-center">
                    <i class="fa-solid fa-search text-2xl text-slate-300 block mb-2"></i>
                    <p class="text-sm text-slate-500">No results found</p>
                    <p class="text-xs text-slate-400 mt-1">Try a different keyword</p>
                </div>
                <!-- Results Content -->
                <div id="gsd-content"></div>
                <!-- Footer hint -->
                <div id="gsd-footer" class="hidden px-4 py-2 bg-slate-50 border-t border-slate-100 text-xs text-slate-400 flex items-center justify-between">
                    <span><kbd class="bg-slate-200 rounded px-1 py-0.5 text-slate-500">&uarr;&darr;</kbd> Navigate &nbsp;<kbd class="bg-slate-200 rounded px-1 py-0.5 text-slate-500">Enter</kbd> Open</span>
                    <span id="gsd-footer-count"></span>
                </div>
            </div>
        </div>

        <!-- Global Bell Notification Dropdown Container -->
        <div class="relative" id="notificationDropdownContainer">
            <button id="bellButton" onclick="toggleNotificationDropdown()" class="relative w-11 h-11 rounded-full bg-slate-100 hover:bg-slate-200 transition flex items-center justify-center focus:outline-none">
                <i class="fa-solid fa-bell text-slate-600 text-lg"></i>
                <?php if ($unread_notifs_count > 0): ?>
                    <span id="unreadBadge" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full h-5 min-w-[20px] px-1 flex items-center justify-center border-2 border-white">
                        <?php echo $unread_notifs_count; ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- NOTIFICATION DROPDOWN MENU -->
            <div id="notificationMenu" class="hidden absolute right-0 mt-3 w-96 bg-white rounded-xl shadow-2xl border border-slate-200 z-50 overflow-hidden transform transition-all">
                <!-- HEADER (Blue Theme) -->
                <div class="p-4 bg-gradient-to-r from-blue-900 to-blue-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <i class="fa-solid fa-bell text-blue-200"></i> Notifications
                        </h3>
                        <p class="text-xs text-blue-100 mt-0.5" id="unreadSubHeader"><?php echo $unread_notifs_count; ?> unread updates</p>
                    </div>
                    <button onclick="markAllAsRead()" class="text-xs bg-blue-800 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg transition border border-blue-400/30 font-medium">
                        Mark all as read
                    </button>
                </div>

                <!-- TABS -->
                <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">
                    <button onclick="switchNotifTab('all')" id="tabBtnAll" class="flex-1 py-2.5 text-center border-b-2 border-blue-600 text-blue-600 font-bold bg-white transition">
                        All Messages (<span id="countAllTab"><?php echo count($all_notifs); ?></span>)
                    </button>
                    <button onclick="switchNotifTab('unread')" id="tabBtnUnread" class="flex-1 py-2.5 text-center border-b-2 border-transparent hover:text-slate-900 transition">
                        Unread (<span id="countUnreadTab"><?php echo count($unread_notifs); ?></span>)
                    </button>
                </div>

                <!-- NOTIFICATION LIST (Scrollable max 10) -->
                <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 text-left">
                    <!-- ALL TAB -->
                    <div id="notifListAll" class="block space-y-0">
                        <?php if (!empty($all_notifs)): ?>
                            <?php foreach ($all_notifs as $idx => $n): ?>
                                <div class="p-3.5 hover:bg-slate-50 transition flex gap-3 items-start notif-item <?php echo $n['is_read'] == 0 ? 'bg-blue-50/40' : ''; ?> <?php echo $idx >= 5 ? 'hidden notif-item-extra' : ''; ?>">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate"><?php echo htmlspecialchars($n['action']); ?></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Module: <?php echo htmlspecialchars($n['module']); ?> &bull; <?php echo htmlspecialchars($n['user']); ?></p>
                                        <p class="text-[10px] text-slate-400 mt-1"><?php echo date('M j, Y g:i A', strtotime($n['timestamp'])); ?></p>
                                    </div>
                                    <?php if ($n['is_read'] == 0): ?>
                                        <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0 mt-2 unread-dot"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-8 text-center text-slate-400 text-xs">
                                <i class="fa-regular fa-bell-slash text-2xl mb-2 text-slate-300 block"></i>
                                No notifications found.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- UNREAD TAB -->
                    <div id="notifListUnread" class="hidden space-y-0">
                        <?php if (!empty($unread_notifs)): ?>
                            <?php foreach ($unread_notifs as $idx => $n): ?>
                                <div class="p-3.5 bg-blue-50/40 hover:bg-slate-50 transition flex gap-3 items-start <?php echo $idx >= 5 ? 'hidden notif-item-extra' : ''; ?>">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate"><?php echo htmlspecialchars($n['action']); ?></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Module: <?php echo htmlspecialchars($n['module']); ?> &bull; <?php echo htmlspecialchars($n['user']); ?></p>
                                        <p class="text-[10px] text-slate-400 mt-1"><?php echo date('M j, Y g:i A', strtotime($n['timestamp'])); ?></p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0 mt-2"></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-8 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-check-double text-2xl mb-2 text-emerald-500 block"></i>
                                All caught up! No unread notifications.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SEE PREVIOUS BUTTON FOOTER -->
                <?php if (count($all_notifs) > 5): ?>
                    <div class="p-2.5 bg-slate-50 border-t border-slate-200 text-center" id="seePreviousContainer">
                        <button onclick="seePreviousNotifs()" class="text-xs font-semibold text-blue-700 hover:text-blue-900 transition flex items-center justify-center gap-1.5 w-full">
                            <span>See previous notifications</span>
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Global User Profile Dropdown -->
        <div class="relative group">
            <button class="flex items-center gap-3 focus:outline-none">
                <div class="w-11 h-11 rounded-full bg-blue-900 flex items-center justify-center text-white font-bold">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="hidden md:block text-left">
                    <p class="font-semibold text-slate-800 leading-tight">
                        <?php echo htmlspecialchars($username); ?>
                    </p>
                    <p class="text-xs text-slate-500">
                        System Administrator
                    </p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../modules/profile.php' : 'modules/profile.php'; ?>" class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-700 transition">
                    <i class="fa-solid fa-user text-blue-700"></i>
                    My Profile
                </a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../modules/settings.php' : 'modules/settings.php'; ?>" class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-700 transition">
                    <i class="fa-solid fa-gear text-blue-700"></i>
                    Settings
                </a>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../modules/help-center.php' : 'modules/help-center.php'; ?>" class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-700 transition">
                    <i class="fa-solid fa-circle-question text-blue-700"></i>
                    Help Center
                </a>
                <hr class="border-slate-100">
                <a href="<?php echo $logout_url; ?>"
                   class="flex items-center gap-3 px-5 py-3 text-sm text-red-600 hover:bg-red-50 font-medium transition">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>
        </div>

    </div>

</header>

<script>
function toggleNotificationDropdown() {
    const menu = document.getElementById('notificationMenu');
    if (menu) menu.classList.toggle('hidden');
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
        if (listAll) listAll.classList.remove('hidden');
        if (listUnread) listUnread.classList.add('hidden');
        if (btnAll) {
            btnAll.classList.add('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
            btnAll.classList.remove('border-transparent');
        }
        if (btnUnread) {
            btnUnread.classList.remove('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
            btnUnread.classList.add('border-transparent');
        }
    } else {
        if (listAll) listAll.classList.add('hidden');
        if (listUnread) listUnread.classList.remove('hidden');
        if (btnUnread) {
            btnUnread.classList.add('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
            btnUnread.classList.remove('border-transparent');
        }
        if (btnAll) {
            btnAll.classList.remove('border-blue-600', 'text-blue-600', 'font-bold', 'bg-white');
            btnAll.classList.add('border-transparent');
        }
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

/* ============================================================
   GLOBAL SEARCH – Debounced, Indexed, Grouped Dropdown
   ============================================================ */
(function() {
    let _searchTimer = null;
    let _currentQuery = '';
    let _selectedIndex = -1;
    let _resultItems = [];

    // Detect if we are inside /modules/ to build correct relative URLs
    const _isInModules = window.location.pathname.includes('/modules/');
    const _apiBase = _isInModules ? '../api/global-search.php' : 'api/global-search.php';

    const COLOR_MAP = {
        blue:    { bg: 'bg-blue-50',    icon: 'text-blue-600',    badge: 'bg-blue-100 text-blue-700' },
        green:   { bg: 'bg-green-50',   icon: 'text-green-600',   badge: 'bg-green-100 text-green-700' },
        purple:  { bg: 'bg-purple-50',  icon: 'text-purple-600',  badge: 'bg-purple-100 text-purple-700' },
        indigo:  { bg: 'bg-indigo-50',  icon: 'text-indigo-600',  badge: 'bg-indigo-100 text-indigo-700' },
        emerald: { bg: 'bg-emerald-50', icon: 'text-emerald-600', badge: 'bg-emerald-100 text-emerald-700' }
    };

    const GROUP_LABELS = {
        policies:    { label: 'Policy Documents',      icon: 'fa-book-open',       color: 'blue' },
        datasets:    { label: 'Datasets',              icon: 'fa-database',        color: 'green' },
        assessments: { label: 'Impact Assessments',    icon: 'fa-chart-simple',    color: 'purple' },
        benchmarking:{ label: 'Benchmarking',          icon: 'fa-scale-balanced',  color: 'indigo' },
        reports:     { label: 'Generated Reports',     icon: 'fa-file-lines',      color: 'emerald' }
    };

    window.onGlobalSearchInput = function(val) {
        _currentQuery = val.trim();
        const clearBtn = document.getElementById('globalSearchClear');
        if (clearBtn) clearBtn.classList.toggle('hidden', _currentQuery.length === 0);

        clearTimeout(_searchTimer);
        _selectedIndex = -1;

        if (_currentQuery.length < 2) {
            hideGlobalDropdown();
            return;
        }

        // Show loading immediately, debounce the actual query 350ms
        setGSDState('loading');
        showGlobalDropdown();

        _searchTimer = setTimeout(function() {
            doGlobalSearch(_currentQuery);
        }, 350);
    };

    window.onGlobalSearchKeydown = function(e) {
        const dd = document.getElementById('globalSearchDropdown');
        if (!dd || dd.classList.contains('hidden')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            _selectedIndex = Math.min(_selectedIndex + 1, _resultItems.length - 1);
            highlightItem(_selectedIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            _selectedIndex = Math.max(_selectedIndex - 1, 0);
            highlightItem(_selectedIndex);
        } else if (e.key === 'Enter' && _selectedIndex >= 0) {
            e.preventDefault();
            if (_resultItems[_selectedIndex]) {
                window.location.href = _resultItems[_selectedIndex];
            }
        } else if (e.key === 'Escape') {
            hideGlobalDropdown();
            document.getElementById('globalSearchInput').blur();
        }
    };

    window.showGlobalDropdown = function() {
        const dd = document.getElementById('globalSearchDropdown');
        if (dd) dd.classList.remove('hidden');
    };

    window.hideGlobalDropdown = function() {
        const dd = document.getElementById('globalSearchDropdown');
        if (dd) dd.classList.add('hidden');
        _selectedIndex = -1;
    };

    window.clearGlobalSearch = function() {
        const input = document.getElementById('globalSearchInput');
        if (input) { input.value = ''; input.focus(); }
        clearTimeout(_searchTimer);
        _currentQuery = '';
        hideGlobalDropdown();
        const clearBtn = document.getElementById('globalSearchClear');
        if (clearBtn) clearBtn.classList.add('hidden');
    };

    function setGSDState(state) {
        document.getElementById('gsd-loading').classList.toggle('hidden', state !== 'loading');
        document.getElementById('gsd-empty').classList.toggle('hidden', state !== 'empty');
        document.getElementById('gsd-content').classList.toggle('hidden', state === 'loading' || state === 'empty');
        const footer = document.getElementById('gsd-footer');
        if (footer) footer.classList.toggle('hidden', state !== 'results');
    }

    function doGlobalSearch(query) {
        const url = _apiBase + '?q=' + encodeURIComponent(query);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { setGSDState('empty'); return; }
                if (data.total === 0) { setGSDState('empty'); return; }
                renderResults(data.results, data.total, query);
            })
            .catch(() => setGSDState('empty'));
    }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return escHtml(text).replace(re, '<mark class="bg-yellow-100 text-yellow-900 rounded">$1</mark>');
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderResults(results, total, query) {
        const content = document.getElementById('gsd-content');
        const footer  = document.getElementById('gsd-footer-count');
        _resultItems = [];
        let html = '';

        Object.keys(GROUP_LABELS).forEach(groupKey => {
            const items = results[groupKey];
            if (!items || items.length === 0) return;

            const grp = GROUP_LABELS[groupKey];
            const clr = COLOR_MAP[grp.color] || COLOR_MAP.blue;

            html += `<div class="px-4 pt-3 pb-1">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid ${grp.icon} text-[9px]"></i>${grp.label}
                </p>
            </div>`;

            items.forEach(item => {
                const idx = _resultItems.length;
                _resultItems.push(item.url);
                html += `<a href="${escHtml(item.url)}" 
                    class="gsd-item flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition cursor-pointer"
                    data-idx="${idx}"
                    onmouseenter="highlightItem(${idx})"
                >
                    <div class="w-8 h-8 rounded-lg ${clr.bg} flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid ${item.icon} ${clr.icon} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">${highlight(item.title, query)}</p>
                        <p class="text-xs text-slate-500 truncate">${item.subtitle}</p>
                    </div>
                    <i class="fa-solid fa-arrow-right text-slate-300 text-xs flex-shrink-0"></i>
                </a>`;
            });
        });

        content.innerHTML = html;
        if (footer) footer.textContent = total + ' result' + (total !== 1 ? 's' : '');
        setGSDState('results');
    }

    function highlightItem(idx) {
        _selectedIndex = idx;
        document.querySelectorAll('.gsd-item').forEach((el, i) => {
            el.classList.toggle('bg-blue-50', i === idx);
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('globalSearchContainer');
        if (container && !container.contains(e.target)) {
            hideGlobalDropdown();
        }
    });
})();
</script>