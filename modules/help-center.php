<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

$pageTitle  = "Help Center";
$username   = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> – SJDM Legislative Research</title>
    <meta name="description" content="Help Center and documentation for the San Jose Del Monte City Legislative Research System.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .help-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1d4ed8 100%);
            position: relative; overflow: hidden;
        }
        .help-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 80% 50%, rgba(99,102,241,.25) 0%, transparent 65%),
                        radial-gradient(ellipse at 20% 80%, rgba(37,99,235,.2) 0%, transparent 55%);
        }
        .help-hero-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        #helpSearch:focus { outline:none; box-shadow:0 0 0 3px rgba(99,102,241,.4),0 0 32px rgba(99,102,241,.15); }
        .module-card { transition: transform .2s, box-shadow .2s; cursor: pointer; }
        .module-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(30,58,138,.12); }
        .faq-answer { max-height:0; overflow:hidden; transition: max-height .35s cubic-bezier(0.4,0,0.2,1), padding .25s; }
        .faq-answer.open { max-height:600px; }
        .faq-icon { transition: transform .3s cubic-bezier(0.4,0,0.2,1); }
        .faq-item.open .faq-icon { transform: rotate(45deg); }
        .faq-item.open .faq-question { color: #1d4ed8; }
        .step-line::before {
            content: ''; position: absolute; left:19px; top:40px; bottom:-8px;
            width:2px; background: linear-gradient(to bottom, #bfdbfe, transparent);
        }
        .toc-link { transition: color .15s, padding-left .15s; }
        .toc-link:hover { color:#1d4ed8; padding-left:4px; }
        .toc-link.active { color:#1d4ed8; font-weight:600; padding-left:4px; }
        .tag-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content-wrapper ml-72 flex flex-col min-h-screen transition-all duration-300">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="flex-1">

<!-- HERO -->
<div class="help-hero px-6 md:px-12 py-14 relative">
    <div class="help-hero-dots"></div>
    <div class="relative z-10 max-w-3xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-blue-100 text-xs font-semibold mb-5 backdrop-blur-sm">
            <i class="fa-solid fa-circle-question"></i> SJDM Legislative Research System
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white leading-tight">Help Center &amp; Documentation</h1>
        <p class="mt-3 text-blue-200 text-base">Everything you need to know about using the Legislative Research System</p>
        <div class="mt-8 relative max-w-lg mx-auto">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 pointer-events-none"></i>
            <input type="text" id="helpSearch" placeholder="Search documentation, FAQs..."
                class="w-full pl-11 pr-4 py-3 rounded-xl bg-white text-slate-800 text-sm font-medium shadow-xl transition-all"
                oninput="filterHelp(this.value)">
            <span id="searchNoResult" class="hidden absolute -bottom-7 left-0 text-xs text-blue-200">No results found for your search.</span>
        </div>
    </div>
</div>

<div class="px-6 md:px-10 py-10 max-w-7xl mx-auto">

    <!-- STATS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center"><i class="fa-solid fa-layer-group text-blue-600 text-lg"></i></div>
            <div><p class="text-xl font-extrabold text-slate-800">7</p><p class="text-xs text-slate-500">Modules</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center"><i class="fa-solid fa-book-open-reader text-indigo-600 text-lg"></i></div>
            <div><p class="text-xl font-extrabold text-slate-800">20+</p><p class="text-xs text-slate-500">Guide Articles</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center"><i class="fa-solid fa-circle-question text-violet-600 text-lg"></i></div>
            <div><p class="text-xl font-extrabold text-slate-800">15</p><p class="text-xs text-slate-500">FAQ Answers</p></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center"><i class="fa-solid fa-headset text-emerald-600 text-lg"></i></div>
            <div><p class="text-xl font-extrabold text-slate-800">24/7</p><p class="text-xs text-slate-500">Self-Service</p></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">

        <!-- TOC -->
        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sticky top-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">On this page</p>
                <nav class="space-y-1 text-sm text-slate-600" id="tocNav">
                    <a href="#getting-started"   class="toc-link block py-1">🚀 Getting Started</a>
                    <a href="#modules"            class="toc-link block py-1">📦 Modules Overview</a>
                    <a href="#policy-research"    class="toc-link block py-1 pl-3 text-xs">Policy Research</a>
                    <a href="#data-collection"    class="toc-link block py-1 pl-3 text-xs">Data Collection</a>
                    <a href="#impact-assessment"  class="toc-link block py-1 pl-3 text-xs">Impact Assessment</a>
                    <a href="#benchmarking"       class="toc-link block py-1 pl-3 text-xs">Benchmarking Analysis</a>
                    <a href="#report-gen"         class="toc-link block py-1 pl-3 text-xs">Report Generation</a>
                    <a href="#data-viz"           class="toc-link block py-1 pl-3 text-xs">Data Visualization</a>
                    <a href="#account"            class="toc-link block py-1">👤 Account &amp; Settings</a>
                    <a href="#faq"                class="toc-link block py-1">❓ FAQ</a>
                    <a href="#contact"            class="toc-link block py-1">📞 Contact Support</a>
                </nav>
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Quick Links</p>
                    <a href="profile.php"      class="flex items-center gap-2 text-xs text-slate-600 hover:text-blue-700 py-1 transition"><i class="fa-solid fa-user w-4 text-center text-blue-500"></i> My Profile</a>
                    <a href="settings.php"     class="flex items-center gap-2 text-xs text-slate-600 hover:text-blue-700 py-1 transition"><i class="fa-solid fa-gear w-4 text-center text-blue-500"></i> Settings</a>
                    <a href="../dashboard.php" class="flex items-center gap-2 text-xs text-slate-600 hover:text-blue-700 py-1 transition"><i class="fa-solid fa-house w-4 text-center text-blue-500"></i> Dashboard</a>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="xl:col-span-3 space-y-10" id="helpContent">

            <!-- GETTING STARTED -->
            <section id="getting-started" class="searchable-section">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow"><i class="fa-solid fa-rocket text-white text-sm"></i></div>
                    <div><h2 class="text-xl font-bold text-slate-800">Getting Started</h2><p class="text-xs text-slate-500">Quick-start guide for new users</p></div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <div class="space-y-5">
                        <div class="flex gap-4 relative step-line">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0 border-2 border-blue-200 z-10">1</div>
                            <div class="flex-1 pb-2"><p class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fa-solid fa-right-to-bracket text-blue-600 text-xs"></i> Log In</p><p class="text-sm text-slate-600 mt-1 leading-relaxed">Go to <strong>login.php</strong> and enter your username and password provided by your administrator. First-time users should change their password immediately.</p></div>
                        </div>
                        <div class="flex gap-4 relative step-line">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0 border-2 border-indigo-200 z-10">2</div>
                            <div class="flex-1 pb-2"><p class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fa-solid fa-house text-indigo-600 text-xs"></i> Explore the Dashboard</p><p class="text-sm text-slate-600 mt-1 leading-relaxed">The <strong>Dashboard</strong> gives you a snapshot of recent activity, quick stats, and shortcuts to every module.</p></div>
                        </div>
                        <div class="flex gap-4 relative step-line">
                            <div class="w-10 h-10 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-sm shrink-0 border-2 border-violet-200 z-10">3</div>
                            <div class="flex-1 pb-2"><p class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fa-solid fa-sidebar text-violet-600 text-xs"></i> Navigate via the Sidebar</p><p class="text-sm text-slate-600 mt-1 leading-relaxed">The left sidebar lists all modules. Collapse it to icon-only mode using the <strong>toggle button (≡)</strong> in the navbar or sidebar header.</p></div>
                        </div>
                        <div class="flex gap-4 relative step-line">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0 border-2 border-emerald-200 z-10">4</div>
                            <div class="flex-1 pb-2"><p class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fa-solid fa-magnifying-glass text-emerald-600 text-xs"></i> Use the Global Search</p><p class="text-sm text-slate-600 mt-1 leading-relaxed">The search bar in the top navbar lets you search <strong>documents, reports, and policies</strong> instantly. Type at least 2 characters to see results.</p></div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm shrink-0 border-2 border-amber-200 z-10">5</div>
                            <div class="flex-1"><p class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="fa-solid fa-bell text-amber-600 text-xs"></i> Check Notifications</p><p class="text-sm text-slate-600 mt-1 leading-relaxed">The bell icon in the navbar shows real-time activity. Click it to see all or unread notifications and mark them as read.</p></div>
                        </div>
                    </div>
                    <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-900">
                        <i class="fa-solid fa-lightbulb text-amber-500 mt-0.5 shrink-0"></i>
                        <div><p class="font-semibold">Pro Tip</p><p class="text-xs mt-0.5 text-amber-800">Use compact sidebar mode to get more screen space when working with large tables or charts.</p></div>
                    </div>
                </div>
            </section>

            <!-- MODULES OVERVIEW -->
            <section id="modules" class="searchable-section">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow"><i class="fa-solid fa-layer-group text-white text-sm"></i></div>
                    <div><h2 class="text-xl font-bold text-slate-800">Modules Overview</h2><p class="text-xs text-slate-500">All available tools in the system</p></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <a href="policy-research.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-book-open text-blue-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Policy Research</p><span class="tag-badge bg-blue-100 text-blue-700">Core</span></div><p class="text-xs text-slate-500 leading-relaxed">Search, browse, and manage legislative policy documents with AI-powered summaries.</p><p class="text-xs font-semibold text-blue-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                    <a href="data-collection.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-database text-purple-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Data Collection</p><span class="tag-badge bg-purple-100 text-purple-700">Core</span></div><p class="text-xs text-slate-500 leading-relaxed">Upload, organize, and manage raw data sets. Supports CSV, Excel, and document files.</p><p class="text-xs font-semibold text-purple-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                    <a href="impact-assessment.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-chart-line text-emerald-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Impact Assessment</p><span class="tag-badge bg-emerald-100 text-emerald-700">Core</span></div><p class="text-xs text-slate-500 leading-relaxed">Evaluate the real-world impact of policies. Create assessments and track results over time.</p><p class="text-xs font-semibold text-emerald-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                    <a href="benchmarking-analysis.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-scale-balanced text-indigo-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Benchmarking Analysis</p><span class="tag-badge bg-indigo-100 text-indigo-700">Analysis</span></div><p class="text-xs text-slate-500 leading-relaxed">Compare local policies against regional or national benchmarks and identify gaps.</p><p class="text-xs font-semibold text-indigo-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                    <a href="report-generation.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-file-lines text-amber-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Report Generation</p><span class="tag-badge bg-amber-100 text-amber-700">Output</span></div><p class="text-xs text-slate-500 leading-relaxed">Generate formatted reports from your research data. Export to PDF or print-ready layouts.</p><p class="text-xs font-semibold text-amber-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                    <a href="data-visualization.php" class="module-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-4 no-underline">
                        <div class="w-11 h-11 rounded-xl bg-rose-50 flex items-center justify-center shrink-0"><i class="fa-solid fa-chart-pie text-rose-600 text-lg"></i></div>
                        <div><div class="flex items-center gap-2 mb-1"><p class="font-bold text-slate-800 text-sm">Data Visualization</p><span class="tag-badge bg-rose-100 text-rose-700">Output</span></div><p class="text-xs text-slate-500 leading-relaxed">Turn raw data into interactive charts and graphs. Bar, line, pie, and area charts.</p><p class="text-xs font-semibold text-rose-600 mt-2 flex items-center gap-1">Open module <i class="fa-solid fa-arrow-right text-[10px]"></i></p></div>
                    </a>
                </div>
            </section>

            <!-- MODULE GUIDES -->
            <?php
            $guides = [
                ['id'=>'policy-research',  'color'=>'blue',   'icon'=>'fa-book-open',      'title'=>'Policy Research',       'link'=>'policy-research.php',
                 'steps'=>['Use the search bar to find policies by keyword, category, or date.','Click <strong>Add Policy</strong> to upload a new document. Fill in title, category, and description.','Attach supporting documents (PDFs, Word files) using the file uploader inside the policy form.','Use the AI Summary button to auto-generate a brief overview of an uploaded document.','Click any policy row to open the detail view — edit, comment, or view the full document.']],
                ['id'=>'data-collection',  'color'=>'purple', 'icon'=>'fa-database',       'title'=>'Data Collection',       'link'=>'data-collection.php',
                 'steps'=>['Click <strong>Upload Data</strong> and select your file (CSV, XLSX, or PDF).','Each file is automatically tagged with a module, source, and upload date.','Use the filter dropdowns to browse by module, date range, or uploader.','Click a file row to preview its contents or download the original.','Delete outdated files using the trash icon — this action is permanent.']],
                ['id'=>'impact-assessment','color'=>'emerald','icon'=>'fa-chart-line',     'title'=>'Impact Assessment',     'link'=>'impact-assessment.php',
                 'steps'=>['Click <strong>Create Assessment</strong> and select the policy or legislation to evaluate.','Fill in the assessment criteria: social, economic, environmental, and legal impact fields.','Attach supporting data from the Data Collection module using the link data button.','Set the status (Draft, In Review, Finalized) and assign it to a reviewer.','View all assessments in the table. Click any row to open the full assessment report.']],
                ['id'=>'benchmarking',     'color'=>'indigo', 'icon'=>'fa-scale-balanced', 'title'=>'Benchmarking Analysis', 'link'=>'benchmarking-analysis.php',
                 'steps'=>['Select a benchmark category (e.g., infrastructure, health, education) from the dropdown.','Choose the local data set and the national or regional benchmark source to compare.','The system generates a side-by-side comparison table and gap analysis automatically.','Export results as PDF or include them in a report via the Report Generation module.','Use the chart toggle to switch between table view and bar/radar chart.']],
                ['id'=>'report-gen',       'color'=>'amber',  'icon'=>'fa-file-lines',     'title'=>'Report Generation',     'link'=>'report-generation.php',
                 'steps'=>['Click <strong>Generate Report</strong> and choose the type (Summary, Detailed, or Comparative).','Select data sources, date range, and modules to include.','Add a cover page title, author name, and executive summary using the form fields.','Preview the report in the browser before exporting. Click <strong>Export PDF</strong> to download.','Past reports are saved in the Reports Archive — search by title or date.']],
                ['id'=>'data-viz',         'color'=>'rose',   'icon'=>'fa-chart-pie',      'title'=>'Data Visualization',    'link'=>'data-visualization.php',
                 'steps'=>['Select a data set from the dropdown to begin building a chart.','Choose a chart type: Bar, Line, Pie, Doughnut, or Area.','Use the X-axis and Y-axis dropdowns to map your data columns to the chart.','Apply filters (date range, category) to focus on a specific data slice.','Click <strong>Save Chart</strong> to bookmark it, or <strong>Export Image</strong> to download a PNG.']],
            ];
            foreach ($guides as $g):
            ?>
            <section id="<?php echo $g['id']; ?>" class="searchable-section">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-<?php echo $g['color']; ?>-50 flex items-center justify-center">
                            <i class="fa-solid <?php echo $g['icon']; ?> text-<?php echo $g['color']; ?>-600 text-sm"></i>
                        </div>
                        <h3 class="font-bold text-slate-800"><?php echo $g['title']; ?> — How to Use</h3>
                        <a href="<?php echo $g['link']; ?>" class="ml-auto text-xs font-semibold text-<?php echo $g['color']; ?>-700 bg-<?php echo $g['color']; ?>-50 px-3 py-1.5 rounded-lg hover:bg-<?php echo $g['color']; ?>-100 transition flex items-center gap-1.5">
                            Open <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                    <div class="p-5">
                        <ol class="space-y-3">
                            <?php foreach ($g['steps'] as $si => $step): ?>
                            <li class="flex gap-3 text-sm text-slate-700 leading-relaxed">
                                <span class="w-6 h-6 rounded-full bg-<?php echo $g['color']; ?>-100 text-<?php echo $g['color']; ?>-700 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5"><?php echo $si+1; ?></span>
                                <span><?php echo $step; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </section>
            <?php endforeach; ?>

            <!-- ACCOUNT & SETTINGS -->
            <section id="account" class="searchable-section">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-slate-700 flex items-center justify-center shadow"><i class="fa-solid fa-user-gear text-white text-sm"></i></div>
                    <div><h2 class="text-xl font-bold text-slate-800">Account &amp; Settings</h2><p class="text-xs text-slate-500">Manage your profile, password, and preferences</p></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-2 mb-3"><div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center"><i class="fa-solid fa-user-pen text-blue-600 text-sm"></i></div><p class="font-bold text-slate-800 text-sm">Edit Your Profile</p></div>
                        <ol class="space-y-2"><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span><span>Go to <strong>Navbar → Your Name → My Profile</strong>.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span><span>Update your full name, email, and department.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span><span>Click <strong>Save Changes</strong>.</span></li></ol>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-2 mb-3"><div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center"><i class="fa-solid fa-lock text-red-500 text-sm"></i></div><p class="font-bold text-slate-800 text-sm">Change Password</p></div>
                        <ol class="space-y-2"><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-red-100 text-red-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span><span>Go to <strong>Settings → Change Password</strong>.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-red-100 text-red-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span><span>Enter your current password, then set a new one.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-red-100 text-red-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span><span>Use the strength meter to ensure it meets requirements.</span></li></ol>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-2 mb-3"><div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i class="fa-solid fa-bell text-amber-500 text-sm"></i></div><p class="font-bold text-slate-800 text-sm">Notification Preferences</p></div>
                        <ol class="space-y-2"><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span><span>Go to <strong>Settings → Notifications</strong>.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span><span>Toggle on/off each notification type.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span><span>Choose bell notification display frequency.</span></li></ol>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-2 mb-3"><div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center"><i class="fa-solid fa-palette text-violet-500 text-sm"></i></div><p class="font-bold text-slate-800 text-sm">Appearance</p></div>
                        <ol class="space-y-2"><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span><span>Go to <strong>Settings → Appearance</strong>.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span><span>Toggle compact sidebar, adjust font size.</span></li><li class="flex gap-2 text-xs text-slate-600"><span class="w-4 h-4 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span><span>Pick an accent color and layout density.</span></li></ol>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section id="faq" class="searchable-section">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center shadow"><i class="fa-solid fa-circle-question text-white text-sm"></i></div>
                    <div><h2 class="text-xl font-bold text-slate-800">Frequently Asked Questions</h2><p class="text-xs text-slate-500">Common questions and answers</p></div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100" id="faqList">
                    <?php
                    $faqs = [
                        ['cat'=>'Account','cc'=>'blue','q'=>'I forgot my password. How do I reset it?','a'=>'Contact your system administrator to reset your password. Once reset, change it immediately via <strong>Settings → Change Password</strong>.'],
                        ['cat'=>'Account','cc'=>'blue','q'=>'Can I change my username?','a'=>'No. Usernames are assigned by the administrator and cannot be changed. You can update your <strong>Full Name</strong>, <strong>Email</strong>, and <strong>Department</strong> in your profile.'],
                        ['cat'=>'Account','cc'=>'blue','q'=>'Why can\'t I access certain modules?','a'=>'Access depends on your <strong>assigned role</strong> (Admin, Researcher, or Viewer). Contact your system administrator to update your permissions.'],
                        ['cat'=>'Usage','cc'=>'emerald','q'=>'How do I upload a file or document?','a'=>'Go to the relevant module (e.g., <strong>Policy Research</strong> or <strong>Data Collection</strong>), then click <strong>Upload</strong> or <strong>Add</strong>. Accepted formats: PDF, DOCX, CSV, XLSX.'],
                        ['cat'=>'Usage','cc'=>'emerald','q'=>'What is the maximum file size I can upload?','a'=>'The system supports files up to <strong>50 MB</strong> per upload. For larger data sets, split the file into smaller parts before uploading.'],
                        ['cat'=>'Usage','cc'=>'emerald','q'=>'How do I search for a specific document or policy?','a'=>'Use the <strong>Global Search bar</strong> in the top navbar. Type at least 2 characters and results appear instantly. Use module-level filters for more refined results.'],
                        ['cat'=>'Usage','cc'=>'emerald','q'=>'Can I export data or reports?','a'=>'Yes. Reports can be exported as PDF from the <strong>Report Generation</strong> module. Charts can be exported as PNG from <strong>Data Visualization</strong>. Most data tables have a CSV download option.'],
                        ['cat'=>'Usage','cc'=>'emerald','q'=>'How do I link data to an impact assessment?','a'=>'Inside <strong>Impact Assessment</strong>, open an assessment and look for the <strong>Attach Data</strong> section. Select files from Data Collection to link them.'],
                        ['cat'=>'Notifications','cc'=>'amber','q'=>'Why am I getting too many bell notifications?','a'=>'Control this in <strong>Settings → Notifications</strong>. Set bell display to <strong>Important Only</strong> or <strong>Mute All</strong> to reduce alerts.'],
                        ['cat'=>'Notifications','cc'=>'amber','q'=>'How do I mark all notifications as read?','a'=>'Click the <strong>Bell icon</strong> in the top navbar, then click <strong>"Mark all as read"</strong> in the notification dropdown header.'],
                        ['cat'=>'Technical','cc'=>'red','q'=>'The page is loading slowly. What should I do?','a'=>'Try <strong>refreshing the page</strong> (Ctrl+R / Cmd+R). If the problem persists, clear your browser cache or try a different browser. Contact the administrator for persistent issues.'],
                        ['cat'=>'Technical','cc'=>'red','q'=>'I see an error when uploading a file. What\'s wrong?','a'=>'Common causes: file size over 50 MB, unsupported file format, or session timeout. Log out and back in, then retry with a supported file type.'],
                        ['cat'=>'Technical','cc'=>'red','q'=>'Why did I get logged out automatically?','a'=>'The system logs out inactive sessions after a timeout period for security. Log back in to continue — your data is saved automatically.'],
                        ['cat'=>'Admin','cc'=>'slate','q'=>'How do I add a new user to the system?','a'=>'Go to <strong>Administration → User Management</strong>, click <strong>Add User</strong>, fill in the details and assign a role, then save.'],
                        ['cat'=>'Admin','cc'=>'slate','q'=>'How do I deactivate a user account?','a'=>'In <strong>User Management</strong>, find the user, click <strong>Edit</strong>, change their status from <em>Active</em> to <em>Inactive</em>, and save.'],
                    ];
                    foreach ($faqs as $i => $faq):
                    ?>
                    <div class="faq-item" data-faq-q="<?php echo htmlspecialchars(strtolower($faq['q'])); ?>">
                        <button class="faq-question w-full flex items-start gap-3 px-6 py-4 text-left hover:bg-slate-50 transition-colors" onclick="toggleFaq(this)">
                            <span class="tag-badge bg-<?php echo $faq['cc']; ?>-100 text-<?php echo $faq['cc']; ?>-700 shrink-0 mt-0.5"><?php echo $faq['cat']; ?></span>
                            <span class="flex-1 text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($faq['q']); ?></span>
                            <i class="fa-solid fa-plus faq-icon text-slate-400 shrink-0 mt-0.5"></i>
                        </button>
                        <div class="faq-answer px-6"><div class="pb-4 pl-14 text-sm text-slate-600 leading-relaxed"><?php echo $faq['a']; ?></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- CONTACT -->
            <section id="contact" class="searchable-section">
                <div class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-800 rounded-2xl p-8 text-center text-white relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(ellipse at 60% 40%, #818cf8 0%, transparent 60%);"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center mx-auto mb-4 border border-white/20">
                            <i class="fa-solid fa-headset text-white text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-2">Still need help?</h2>
                        <p class="text-blue-200 text-sm max-w-md mx-auto mb-6">Contact your system administrator or the IT department for assistance with technical issues, account problems, or feature requests.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                            <div class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-sm">
                                <i class="fa-solid fa-building text-blue-300"></i>
                                <span class="font-medium">SJDM City Hall – IT Department</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-xl px-5 py-3 text-sm">
                                <i class="fa-solid fa-envelope text-blue-300"></i>
                                <span class="font-medium">it-support@sjdm.gov.ph</span>
                            </div>
                        </div>
                        <p class="text-xs text-blue-300 mt-5"><i class="fa-solid fa-clock mr-1"></i>Office hours: Mon–Fri, 8:00 AM – 5:00 PM</p>
                    </div>
                </div>
            </section>

        </div><!-- /main content -->
    </div><!-- /grid -->
</div><!-- /container -->
</main>

<footer class="text-center py-4 text-xs text-slate-400 border-t border-slate-200 bg-white">
    &copy; <?php echo date('Y'); ?> San Jose Del Monte Legislative Research System. All rights reserved.
</footer>
</div>

<script>
function toggleFaq(btn) {
    const item   = btn.closest('.faq-item');
    const answer = item.querySelector('.faq-answer');
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.faq-answer').classList.remove('open');
    });
    if (!isOpen) { item.classList.add('open'); answer.classList.add('open'); }
}

function filterHelp(query) {
    const q = query.toLowerCase().trim();
    const noRes = document.getElementById('searchNoResult');
    if (!q) {
        document.querySelectorAll('.searchable-section').forEach(s => s.style.display = '');
        document.querySelectorAll('.faq-item').forEach(f => f.style.display = '');
        noRes.classList.add('hidden'); return;
    }
    let anyVisible = false;
    document.querySelectorAll('.searchable-section:not(#faq)').forEach(section => {
        const show = section.innerText.toLowerCase().includes(q);
        section.style.display = show ? '' : 'none';
        if (show) anyVisible = true;
    });
    const faqSection = document.getElementById('faq');
    let faqVisible = false;
    document.querySelectorAll('.faq-item').forEach(item => {
        const show = item.innerText.toLowerCase().includes(q);
        item.style.display = show ? '' : 'none';
        if (show) faqVisible = true;
    });
    faqSection.style.display = faqVisible ? '' : 'none';
    if (faqVisible) anyVisible = true;
    noRes.classList.toggle('hidden', anyVisible);
}

const sections = document.querySelectorAll('section[id]');
const tocLinks = document.querySelectorAll('.toc-link');
window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(sec => { if (window.scrollY >= sec.offsetTop - 120) current = sec.id; });
    tocLinks.forEach(lnk => lnk.classList.toggle('active', lnk.getAttribute('href') === '#' + current));
}, { passive: true });
</script>
</body>
</html>
