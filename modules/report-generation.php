<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "Report Generation Module";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// Get benchmark_id from URL
$benchmark_id = isset($_GET['benchmark_id']) ? $_GET['benchmark_id'] : '';
$submission_data = null;
$assessment_data = null;
$matrix_data = null;
$dataset_data = null;
$documents = [];

if (!empty($benchmark_id)) {
    $sql = "SELECT * FROM benchmarking_submissions WHERE benchmark_id = '$benchmark_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $submission_data = $result->fetch_assoc();
        
        if (!empty($submission_data['assessment_id'])) {
            $assess_sql = "SELECT * FROM impact_assessments WHERE assessment_id = '" . $submission_data['assessment_id'] . "'";
            $assess_result = $conn->query($assess_sql);
            if ($assess_result && $assess_result->num_rows > 0) {
                $assessment_data = $assess_result->fetch_assoc();
            }
        }
        
        $matrix_sql = "SELECT * FROM benchmarking_matrix WHERE benchmark_id = '$benchmark_id'";
        $matrix_result = $conn->query($matrix_sql);
        if ($matrix_result && $matrix_result->num_rows > 0) {
            $matrix_data = $matrix_result->fetch_assoc();
        }
        
        if (!empty($assessment_data['dataset_id'])) {
            $ds_sql = "SELECT * FROM datasets WHERE dataset_id = '" . $assessment_data['dataset_id'] . "'";
            $ds_result = $conn->query($ds_sql);
            if ($ds_result && $ds_result->num_rows > 0) {
                $dataset_data = $ds_result->fetch_assoc();
            }
            
            $doc_sql = "SELECT * FROM supporting_documents WHERE dataset_id = '" . $assessment_data['dataset_id'] . "' ORDER BY generated_date DESC";
            $doc_result = $conn->query($doc_sql);
            if ($doc_result && $doc_result->num_rows > 0) {
                while($doc = $doc_result->fetch_assoc()) {
                    $documents[] = $doc;
                }
            }
        }
    }
}

// Handle Confirm & Save report action
$save_success_msg = null;
$is_generated_saved = isset($_GET['generated']) && $_GET['generated'] == '1';
if ($is_generated_saved && !empty($benchmark_id)) {
    $conn->query("UPDATE benchmarking_submissions SET status = 'Report Generated' WHERE benchmark_id = '$benchmark_id'");
    
    // Sync into reports table
    $esc_b_id = $conn->real_escape_string($benchmark_id);
    $check_rep = $conn->query("SELECT id FROM reports WHERE report_id = '$esc_b_id'");
    if ($check_rep && $check_rep->num_rows == 0) {
        $r_title = $conn->real_escape_string($submission_data['policy_title'] ?? 'Policy Impact Assessment');
        $r_dept = $conn->real_escape_string($submission_data['department'] ?? 'Legislative Research');
        $r_user = $conn->real_escape_string($username);
        $conn->query("INSERT INTO reports (report_id, report_title, report_type, policy_category, output_format, report_status, created_by, created_at) VALUES ('$esc_b_id', '$r_title', 'Policy Impact Assessment', '$r_dept', 'PDF', 'Published', '$r_user', NOW())");
    }

    $save_success_msg = "Report (" . htmlspecialchars($submission_data['policy_title'] ?? $benchmark_id) . ") confirmed and saved successfully!";
}

// Handle AJAX PDF generation request
if (isset($_POST['generate_pdf']) && $_POST['generate_pdf'] == 'true') {
    header('Content-Type: application/json');
    
    // Build the report data
    $reportData = [
        'benchmark_id' => $submission_data['benchmark_id'] ?? 'N/A',
        'policy_title' => $submission_data['policy_title'] ?? 'N/A',
        'department' => $submission_data['department'] ?? 'N/A',
        'impact_percentage' => $submission_data['impact_percentage'] ?? 0,
        'impact_rating' => $submission_data['impact_rating'] ?? 'N/A',
        'status' => $submission_data['status'] ?? 'N/A',
        'submitted_by' => $submission_data['submitted_by'] ?? 'N/A',
        'submitted_date' => $submission_data['submitted_date'] ?? date('Y-m-d'),
        'document_count' => count($documents),
        'assessment' => $assessment_data,
        'matrix' => $matrix_data,
        'dataset' => $dataset_data,
        'documents' => $documents,
        'generated_by' => $username,
        'generated_date' => date('F j, Y'),
        'generated_datetime' => date('F j, Y h:i A')
    ];
    
    echo json_encode(['success' => true, 'data' => $reportData]);
    exit();
}

// Scalable Search & Server-Side Pagination
$search_term = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$current_page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = max(5, min(100, intval($_GET['limit'] ?? 10)));
$offset = ($current_page - 1) * $items_per_page;

$where_clauses = [];
if (!empty($search_term)) {
    $esc_search = $conn->real_escape_string($search_term);
    $where_clauses[] = "(benchmark_id LIKE '%$esc_search%' OR policy_title LIKE '%$esc_search%' OR department LIKE '%$esc_search%')";
}
if (!empty($status_filter) && $status_filter != 'All Status') {
    $esc_status = $conn->real_escape_string($status_filter);
    $where_clauses[] = "status = '$esc_status'";
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count for pagination calculation
$total_rows_result = $conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions" . $where_sql);
$total_rows = $total_rows_result ? intval($total_rows_result->fetch_assoc()['count']) : 0;
$total_pages = max(1, ceil($total_rows / $items_per_page));

// Get paginated submissions
$submissions_sql = "SELECT * FROM benchmarking_submissions" . $where_sql . " ORDER BY submitted_date DESC LIMIT $offset, $items_per_page";
$submissions_result = $conn->query($submissions_sql);

// Get global KPI counts
$total_submissions = $conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions")->fetch_assoc()['count'];
$completed_count = $conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions WHERE status = 'Completed'")->fetch_assoc()['count'];
$evaluated_count = $conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions WHERE status = 'Evaluated'")->fetch_assoc()['count'];
$pending_count = $conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions WHERE status = 'Pending Comparison'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generation Module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- jsPDF library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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
        .submission-row {
            transition: all 0.3s ease;
        }
        .submission-row:hover {
            background: #f1f5f9 !important;
        }
        .submission-row.high {
            background: #d1fae5 !important;
            border-left: 4px solid #16a34a;
        }
        .submission-row.moderate {
            background: #fef3c7 !important;
            border-left: 4px solid #f59e0b;
        }
        .submission-row.evaluated {
            background: #ede9fe !important;
            border-left: 4px solid #7c3aed;
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }

        .report-preview {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 30px 35px;
            background: white;
            max-width: 1100px;
            margin: 0 auto;
            text-align: justify;
        }
        
        .report-preview .report-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
            text-transform: uppercase;
        }
        
        .report-preview .section-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            margin-top: 16px;
            margin-bottom: 8px;
            color: #1a1a1a;
            text-align: left;
        }
        
        .report-preview .sub-section-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 4px;
            color: #1a1a1a;
            text-align: left;
        }
        
        .report-preview p {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            margin-bottom: 4px;
            line-height: 1.6;
            text-align: justify;
        }
        
        .report-preview table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            text-align: left;
        }
        
        .report-preview table th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            text-align: left;
            font-weight: bold;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
        }
        
        .report-preview table td {
            border: 1px solid #d1d5db;
            padding: 5px 10px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            text-align: left;
        }
        
        .report-preview table tr:nth-child(even) {
            background: #fafafa;
        }
        
        .report-preview .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10pt;
            font-weight: 600;
        }
        .badge-high { background: #d1fae5; color: #065f46; }
        .badge-moderate { background: #fef3c7; color: #92400e; }
        .badge-low { background: #fee2e2; color: #991b1b; }
        .badge-excellent { background: #d1fae5; color: #065f46; }
        .badge-good { background: #dbeafe; color: #1e40af; }
        .badge-fair { background: #fef3c7; color: #92400e; }
        .badge-poor { background: #fee2e2; color: #991b1b; }
        
        .report-preview .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 10pt;
            text-align: center;
            color: #6b7280;
            font-family: Arial, Helvetica, sans-serif;
        }
        
        .report-preview .header-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 11pt;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            padding: 8px;
            border-radius: 4px;
        }
        .report-preview .header-info p {
            text-align: center;
            margin-bottom: 2px;
        }
        .report-preview .header-info strong {
            font-weight: bold;
        }
        
        .legal-doc-block {
            margin-bottom: 12px;
            padding: 0;
            page-break-inside: avoid;
        }
        .legal-doc-block .doc-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 2px;
            text-align: left;
        }
        .legal-doc-block .doc-meta {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #6b7280;
            margin-bottom: 4px;
            text-align: left;
        }
        .legal-doc-block .doc-content {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            white-space: pre-wrap;
            padding: 0;
            overflow: visible;
            max-height: none;
            word-wrap: break-word;
            margin-top: 0;
            text-align: justify;
        }

        .pdf-loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .pdf-loading.active {
            display: flex;
        }
        .pdf-loading .spinner {
            background: white;
            padding: 35px 45px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 350px;
        }
        .pdf-loading .spinner i {
            font-size: 52px;
            color: #7c3aed;
            margin-bottom: 15px;
            animation: pulse 1s ease-in-out infinite;
        }
        .pdf-loading .spinner p {
            font-family: Arial, sans-serif;
            font-size: 15px;
            color: #1a1a1a;
            margin: 5px 0;
        }
        .pdf-loading .spinner .sub-text {
            font-size: 12px;
            color: #6b7280;
        }
        .pdf-loading .spinner .progress-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 18px;
            overflow: hidden;
        }
        .pdf-loading .spinner .progress-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #7c3aed, #4f46e5);
            border-radius: 4px;
            width: 0%;
            animation: progress 1.2s ease-in-out infinite;
        }
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 80%; }
            100% { width: 100%; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* ========================================================= */
        /* PRINT & WATERMARK MEDIA QUERIES (FULL REPEAT & WRAP)      */
        /* ========================================================= */
        .print-watermark, .print-header {
            display: none;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0.3in 0.35in !important;
            }

            /* HIDE ALL UI ELEMENTS (SIDEBAR, NAVBAR, TOASTS, BUTTONS, TABLES, ACTION BARS) */
            .no-print, nav, aside, .sidebar, .navbar, button, .btn,
            .ml-72 > header, .ml-72 > main > .toast,
            .ml-72 > main > .flex.justify-between.items-center,
            .ml-72 > main > .grid,
            .ml-72 > main > .bg-white.rounded-xl.shadow-md {
                display: none !important;
                visibility: hidden !important;
            }

            body, html {
                background: #ffffff !important;
                background-color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 11pt !important;
                line-height: 1.5 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .ml-72, main {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: transparent !important;
                background-color: transparent !important;
            }

            /* Universal Transparent Container Backgrounds so Watermark Wraps behind text on all pages */
            main *, .report-preview, .report-preview *, div, section, p, table, th, td, tr, tbody, thead, h1, h2, h3, h4, h5, h6, ul, ol, li, span, article {
                background: transparent !important;
                background-color: transparent !important;
            }

            /* REPEATING SEAL WATERMARK (EVERY PAGE) */
            .print-watermark {
                display: flex !important;
                visibility: visible !important;
                position: fixed !important;
                inset: 0 !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                align-items: center !important;
                justify-content: center !important;
                opacity: 0.12 !important;
                mix-blend-mode: multiply !important;
                z-index: 1 !important;
                pointer-events: none !important;
            }

            .print-watermark img {
                width: 4.8in !important;
                height: 4.8in !important;
                max-width: 80% !important;
                max-height: 80% !important;
                object-fit: contain !important;
                display: block !important;
                margin: auto !important;
            }

            .print-header {
                display: block !important;
                margin-bottom: 15pt !important;
                border-bottom: 2px solid #0f172a !important;
                padding-bottom: 8pt !important;
                text-align: center !important;
                width: 100% !important;
            }

            .print-header img {
                width: 45pt !important;
                height: 45pt !important;
                margin: 0 auto 3pt !important;
            }

            .print-header h1 { font-size: 14pt !important; margin: 0 !important; }
            .print-header h2 { font-size: 11pt !important; margin: 0 !important; }
            .print-header p { font-size: 8.5pt !important; margin: 0 !important; }

            .report-preview {
                display: block !important;
                visibility: visible !important;
                position: relative !important;
                z-index: 10 !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>

<body>

<!-- CENTERED WATERMARK LOGO FOR PRINT ONLY (REPEATED PER PAGE AT DOCUMENT ROOT) -->
<div class="print-watermark hidden print:flex pointer-events-none">
    <img src="../City.jpg" alt="Watermark City Logo" class="w-[500px] h-[500px] object-contain">
</div>

<!-- PDF Loading Overlay -->
<div class="pdf-loading" id="pdfLoading">
    <div class="spinner">
        <i class="fa-solid fa-file-pdf"></i>
        <p>Generating PDF Report...</p>
        <p class="sub-text">Please wait, your report is being prepared</p>
        <div class="progress-bar">
            <div class="fill"></div>
        </div>
    </div>
</div>

<?php include("../includes/sidebar.php"); ?>

<div class="ml-72">

<?php include("../includes/navbar.php"); ?>

<main class="p-8">

    <?php if (isset($_GET['generated'])): ?>
        <div class="toast toast-success" id="toast">
            <i class="fa-solid fa-check-circle mr-2"></i>
            Report generated successfully!
        </div>
        <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
    <?php endif; ?>



    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 no-print">
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between">
                <div>
                    <p class="text-slate-500">Total Submissions</p>
                    <h2 class="text-4xl font-bold mt-2"><?php echo $total_submissions; ?></h2>
                </div>
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-scale-balanced text-blue-700 text-2xl"></i>
                </div>
            </div>
            <p class="text-blue-600 mt-4">In Repository</p>
        </div>

        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between">
                <div>
                    <p class="text-slate-500">Completed</p>
                    <h2 class="text-4xl font-bold mt-2"><?php echo $completed_count; ?></h2>
                </div>
                <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fa-solid fa-check-circle text-green-700 text-2xl"></i>
                </div>
            </div>
            <p class="text-green-600 mt-4">Analyzed</p>
        </div>

        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between">
                <div>
                    <p class="text-slate-500">Evaluated</p>
                    <h2 class="text-4xl font-bold mt-2"><?php echo $evaluated_count; ?></h2>
                </div>
                <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fa-solid fa-star text-purple-700 text-2xl"></i>
                </div>
            </div>
            <p class="text-purple-600 mt-4">Matrix Completed</p>
        </div>

        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between">
                <div>
                    <p class="text-slate-500">Pending</p>
                    <h2 class="text-4xl font-bold mt-2"><?php echo $pending_count; ?></h2>
                </div>
                <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fa-solid fa-clock text-yellow-700 text-2xl"></i>
                </div>
            </div>
            <p class="text-yellow-600 mt-4">Awaiting Review</p>
        </div>
    </div>

    <!-- SCALABLE SEARCH & FILTER BAR -->
    <div class="bg-white rounded-xl shadow p-6 mb-8 no-print">
        <form method="GET" action="report-generation.php" class="grid lg:grid-cols-3 gap-4">
            <div class="relative lg:col-span-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400"></i>
                <input type="text" 
                       name="search"
                       value="<?php echo htmlspecialchars($search_term); ?>"
                       onkeyup="liveFilterTable(this.value)"
                       placeholder="Search by policy title, ID, department..." 
                       class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-600 focus:outline-none">
            </div>
            <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-600 focus:outline-none">
                <option value="All Status" <?php echo $status_filter == 'All Status' || empty($status_filter) ? 'selected' : ''; ?>>All Status</option>
                <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="Evaluated" <?php echo $status_filter == 'Evaluated' ? 'selected' : ''; ?>>Evaluated</option>
                <option value="Pending Comparison" <?php echo $status_filter == 'Pending Comparison' ? 'selected' : ''; ?>>Pending Comparison</option>
                <option value="Report Generated" <?php echo $status_filter == 'Report Generated' ? 'selected' : ''; ?>>Report Generated</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-purple-700 hover:bg-purple-800 text-white rounded-lg btn-scale font-semibold text-sm py-2.5 flex items-center justify-center gap-2 shadow-sm">
                    <i class="fa-solid fa-search"></i>
                    Search
                </button>
                <?php if (!empty($search_term) || (!empty($status_filter) && $status_filter != 'All Status')): ?>
                    <a href="report-generation.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3.5 py-2.5 rounded-lg text-sm font-semibold flex items-center justify-center transition" title="Clear Search">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- SUBMISSIONS TABLE -->
    <div class="bg-white rounded-xl shadow mb-8 no-print">
        <div class="flex items-center justify-between px-6 py-5 border-b">
            <div>
                <h2 class="text-2xl font-bold">
                    Select Policy for Report Generation
                </h2>
                <p class="text-slate-500 mt-1">
                    Choose a policy submission to generate a comprehensive report including all legal documents.
                </p>
            </div>
            <div>
                <a href="benchmarking.php" 
                   class="bg-blue-800 hover:bg-blue-900 text-white px-4 py-2 rounded-lg text-sm font-semibold btn-scale flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Back to Benchmarking
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="text-left p-4">Benchmark ID</th>
                        <th class="text-left p-4">Policy Title</th>
                        <th class="text-left p-4">Department</th>
                        <th class="text-left p-4">Impact %</th>
                        <th class="text-left p-4">Documents</th>
                        <th class="text-left p-4">Status</th>
                        <th class="text-center p-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions_result && $submissions_result->num_rows > 0): ?>
                        <?php while($row = $submissions_result->fetch_assoc()): 
                            $row_class = '';
                            if ($row['impact_rating'] == 'High') $row_class = 'high';
                            elseif ($row['impact_rating'] == 'Moderate') $row_class = 'moderate';
                            if ($row['status'] == 'Evaluated') $row_class .= ' evaluated';
                        ?>
                            <tr class="border-b hover:bg-slate-50 submission-row <?php echo $row_class; ?>">
                                <td class="p-4 font-mono font-bold text-purple-600"><?php echo htmlspecialchars($row['benchmark_id']); ?></td>
                                <td class="p-4 font-medium"><?php echo htmlspecialchars($row['policy_title']); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($row['department']); ?></td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-indigo-600"><?php echo $row['impact_percentage']; ?>%</span>
                                        <div class="w-16 bg-slate-200 rounded-full h-2">
                                            <div class="h-2 rounded-full" style="width:<?php echo $row['impact_percentage']; ?>%; background: linear-gradient(90deg, #f87171, #fbbf24, #34d399);"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm flex items-center gap-1">
                                        <i class="fa-solid fa-gavel mr-1"></i> <?php echo $row['document_count']; ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="status-badge px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $row['status'] == 'Report Generated' ? 'bg-emerald-100 text-emerald-800' : 
                                                  ($row['status'] == 'Completed' ? 'bg-blue-100 text-blue-700' : 
                                                  ($row['status'] == 'Evaluated' ? 'bg-purple-100 text-purple-700' : 'bg-yellow-100 text-yellow-700')); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center items-center gap-1.5">
                                        <a href="?benchmark_id=<?php echo urlencode($row['benchmark_id']); ?>" 
                                           class="bg-blue-600 hover:bg-blue-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                           title="Generate Comprehensive Report">
                                            <i class="fa-solid fa-file-pen"></i>
                                        </a>
                                        <a href="?benchmark_id=<?php echo urlencode($row['benchmark_id']); ?>&autodownload=1" 
                                           class="bg-purple-700 hover:bg-purple-800 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                           title="Download Report">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center p-8 text-slate-500">
                                <i class="fa-solid fa-inbox text-4xl block mb-4"></i>
                                No submissions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- INTERACTIVE PAGINATION FOOTER -->
        <div class="flex flex-wrap justify-between items-center px-6 py-4 border-t gap-3 no-print">
            <?php 
                $start_entry = $total_rows > 0 ? $offset + 1 : 0;
                $end_entry = min($offset + $items_per_page, $total_rows);
            ?>
            <p class="text-sm text-slate-500 font-medium">
                Showing <strong class="text-slate-800"><?php echo $start_entry; ?></strong> to <strong class="text-slate-800"><?php echo $end_entry; ?></strong> of <strong class="text-slate-800"><?php echo $total_rows; ?></strong> submissions
            </p>

            <div class="flex items-center gap-1.5 flex-wrap">
                <!-- PREVIOUS PAGE BUTTON -->
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>&limit=<?php echo $items_per_page; ?>" 
                       class="border border-slate-300 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition flex items-center gap-1">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                    </a>
                <?php else: ?>
                    <span class="border border-slate-200 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed flex items-center gap-1 opacity-70">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                    </span>
                <?php endif; ?>

                <!-- PAGE NUMBERS -->
                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                for ($p = $start_page; $p <= $end_page; $p++): 
                ?>
                    <?php if ($p == $current_page): ?>
                        <span class="bg-blue-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-sm">
                            <?php echo $p; ?>
                        </span>
                    <?php else: ?>
                        <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>&limit=<?php echo $items_per_page; ?>" 
                           class="border border-slate-300 text-slate-700 hover:bg-blue-50 hover:text-blue-700 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                            <?php echo $p; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- NEXT PAGE BUTTON -->
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_term); ?>&status=<?php echo urlencode($status_filter); ?>&limit=<?php echo $items_per_page; ?>" 
                       class="border border-slate-300 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition flex items-center gap-1">
                        Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                <?php else: ?>
                    <span class="border border-slate-200 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed flex items-center gap-1 opacity-70">
                        Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($save_success_msg): ?>
        <div class="toast toast-success mb-6 no-print" id="saveSuccessToast">
            <i class="fa-solid fa-circle-check mr-2 text-lg"></i>
            <?php echo $save_success_msg; ?>
        </div>
        <script>setTimeout(() => { const t = document.getElementById('saveSuccessToast'); if (t) t.style.display = 'none'; }, 6000);</script>
    <?php endif; ?>

    <!-- ========================================= -->
    <!-- REPORT PREVIEW -->
    <!-- ========================================= -->
    <?php if ($submission_data && !$is_generated_saved): ?>
    <div class="bg-white rounded-xl shadow mb-8">
        <div class="flex items-center justify-between px-6 py-5 border-b bg-gradient-to-r from-blue-50 to-purple-50 rounded-t-xl no-print">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fa-solid fa-file-lines text-purple-600"></i>
                    Report Preview
                </h2>
                <p class="text-slate-600 mt-1">
                    <?php echo htmlspecialchars($submission_data['policy_title']); ?>
                    <span class="text-sm text-slate-400 ml-2">(<?php echo $submission_data['benchmark_id']; ?>)</span>
                    <span class="text-sm text-purple-600 ml-2">
                        <i class="fa-solid fa-gavel mr-1"></i> <?php echo count($documents); ?> Legal Documents
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-semibold">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Official Report
                </span>
            </div>
        </div>

        <!-- ========================================= -->
        <!-- REPORT CONTENT -->
        <!-- ========================================= -->
        <div class="report-preview relative overflow-hidden" id="reportContent">
            
            <!-- FORMAL OFFICIAL HEADER FOR PRINT ONLY -->
            <div class="print-header hidden print:block text-center border-b-2 border-slate-900 pb-3 mb-4">
                <img src="../City.jpg" alt="City Seal" class="w-16 h-16 rounded-full object-cover border border-slate-400 mx-auto mb-2">
                <h1 class="text-xl font-bold uppercase tracking-wide text-slate-900 font-serif">City Government of San Jose Del Monte</h1>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 font-serif">Office of the Sangguniang Panlungsod &bull; Legislative Research</h2>
                <p class="text-xs text-slate-600 font-serif mt-1">Official Comprehensive Policy Impact Assessment Report</p>
            </div>

            <!-- Subtle Screen Watermark -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.04] no-print">
                <img src="../City.jpg" alt="City Seal" class="w-[450px] h-[450px] object-contain">
            </div>

            <!-- FOREGROUND CONTENT LAYER -->
            <div class="relative z-10 space-y-2">

            <!-- Report Title -->
            <h1 class="report-title">Policy Impact Assessment Report</h1>
            
            <!-- Header Info -->
            <div class="header-info">
                <p><strong>Report ID:</strong> <?php echo $submission_data['benchmark_id']; ?> &nbsp;|&nbsp;
                <strong>Date Generated:</strong> <?php echo date('F j, Y'); ?> &nbsp;|&nbsp;
                <strong>Prepared By:</strong> <?php echo htmlspecialchars($username); ?> &nbsp;|&nbsp;
                <strong>Department:</strong> <?php echo htmlspecialchars($submission_data['department']); ?> &nbsp;|&nbsp;
                <strong>Total Documents:</strong> <?php echo count($documents); ?> legal documents included</p>
            </div>

            <!-- Executive Summary -->
            <h2 class="section-title">Executive Summary</h2>
            <p>
                This report presents a comprehensive policy impact assessment for 
                <strong><?php echo htmlspecialchars($submission_data['policy_title']); ?></strong>. 
                The policy has been evaluated using a comprehensive benchmarking matrix across 10 criteria 
                covering legal alignment, constitutional compliance, implementation mechanisms, and sustainability.
                <?php if ($matrix_data): ?>
                The overall assessment yields a rating of <strong><?php echo $matrix_data['rating']; ?></strong> 
                with an average score of <strong><?php echo $matrix_data['average_score']; ?>/10</strong>.
                <?php else: ?>
                The benchmarking matrix evaluation is currently pending completion.
                <?php endif; ?>
                This report includes <strong><?php echo count($documents); ?></strong> legal documents 
                generated through AI-assisted analysis.
            </p>

            <!-- 1. Policy Overview -->
            <h2 class="section-title">1. Policy Overview</h2>
            <table>
                <tr><th style="width:25%;">Policy Title</th><td><?php echo htmlspecialchars($submission_data['policy_title']); ?></td></tr>
                <tr><th>Benchmark ID</th><td><?php echo htmlspecialchars($submission_data['benchmark_id']); ?></td></tr>
                <tr><th>Department</th><td><?php echo htmlspecialchars($submission_data['department']); ?></td></tr>
                <tr><th>Impact Percentage</th><td><?php echo $submission_data['impact_percentage']; ?>%</td></tr>
                <tr><th>Impact Rating</th><td><strong><span class="badge <?php echo $submission_data['impact_rating'] == 'High' ? 'badge-high' : ($submission_data['impact_rating'] == 'Moderate' ? 'badge-moderate' : 'badge-low'); ?>"><?php echo $submission_data['impact_rating']; ?></span></strong></td></tr>
                <tr><th>Status</th><td><?php echo htmlspecialchars($submission_data['status']); ?></td></tr>
                <tr><th>Submitted By</th><td><?php echo htmlspecialchars($submission_data['submitted_by']); ?></td></tr>
                <tr><th>Submitted Date</th><td><?php echo date('F j, Y', strtotime($submission_data['submitted_date'])); ?></td></tr>
                <tr><th>Documents Included</th><td><?php echo count($documents); ?> legal documents</td></tr>
            </table>

            <!-- 2. KPI Scores -->
            <h2 class="section-title">2. KPI Scores &amp; Performance Metrics</h2>
            <?php if ($assessment_data): ?>
            <table id="kpiTable">
                <tr><th>KPI</th><th>Score</th><th>Status</th></tr>
                <tr><td>Effectiveness</td><td><?php echo $assessment_data['kpi_effectiveness'] ?? 0; ?>%</td>
                    <td><span class="badge <?php echo ($assessment_data['kpi_effectiveness'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['kpi_effectiveness'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['kpi_effectiveness'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_effectiveness'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?></span></td></tr>
                <tr><td>Efficiency</td><td><?php echo $assessment_data['kpi_efficiency'] ?? 0; ?>%</td>
                    <td><span class="badge <?php echo ($assessment_data['kpi_efficiency'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['kpi_efficiency'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['kpi_efficiency'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_efficiency'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?></span></td></tr>
                <tr><td>Relevance</td><td><?php echo $assessment_data['kpi_relevance'] ?? 0; ?>%</td>
                    <td><span class="badge <?php echo ($assessment_data['kpi_relevance'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['kpi_relevance'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['kpi_relevance'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_relevance'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?></span></td></tr>
                <tr><td>Sustainability</td><td><?php echo $assessment_data['kpi_sustainability'] ?? 0; ?>%</td>
                    <td><span class="badge <?php echo ($assessment_data['kpi_sustainability'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['kpi_sustainability'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['kpi_sustainability'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_sustainability'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?></span></td></tr>
                <tr><td>Equity</td><td><?php echo $assessment_data['kpi_equity'] ?? 0; ?>%</td>
                    <td><span class="badge <?php echo ($assessment_data['kpi_equity'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['kpi_equity'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['kpi_equity'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_equity'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?></span></td></tr>
                <tr><td><strong>Implementation Rate</strong></td><td><strong><?php echo $assessment_data['implementation_rate'] ?? 0; ?>%</strong></td>
                    <td><span class="badge <?php echo ($assessment_data['implementation_rate'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['implementation_rate'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['implementation_rate'] ?? 0) >= 70 ? 'On Track' : (($assessment_data['implementation_rate'] ?? 0) >= 50 ? 'Moderate' : 'Needs Attention'); ?></span></td></tr>
                <tr><td><strong>Budget Utilization</strong></td><td><strong><?php echo $assessment_data['budget_utilization'] ?? 0; ?>%</strong></td>
                    <td><span class="badge <?php echo ($assessment_data['budget_utilization'] ?? 0) >= 70 ? 'badge-high' : (($assessment_data['budget_utilization'] ?? 0) >= 50 ? 'badge-moderate' : 'badge-low'); ?>"><?php echo ($assessment_data['budget_utilization'] ?? 0) >= 70 ? 'Efficient' : (($assessment_data['budget_utilization'] ?? 0) >= 50 ? 'Moderate' : 'Needs Review'); ?></span></td></tr>
                <tr><td><strong>Beneficiaries</strong></td><td><strong><?php echo number_format($assessment_data['beneficiaries'] ?? 0); ?></strong></td>
                    <td><span class="badge badge-high">Reached</span></td></tr>
            </table>
            <?php else: ?>
            <p>KPI data not available.</p>
            <?php endif; ?>

            <!-- 3. Benchmarking Matrix -->
            <h2 class="section-title">3. Benchmarking Matrix Evaluation</h2>
            <?php if ($matrix_data): ?>
            <table id="matrixTable">
                <tr><th>Criteria</th><th>Rating</th><th>Status</th></tr>
                <?php 
                $criteria_labels = [
                    1 => 'Alignment with Legal Framework',
                    2 => 'Compliance with Constitutional Provisions',
                    3 => 'Consistency with International Law',
                    4 => 'Protection of Rights and Liberties',
                    5 => 'Implementation Mechanism',
                    6 => 'Monitoring and Evaluation',
                    7 => 'Stakeholder Engagement',
                    8 => 'Resource Allocation',
                    9 => 'Risk Assessment',
                    10 => 'Sustainability'
                ];
                for ($i = 1; $i <= 10; $i++):
                    $value = $matrix_data['criteria' . $i] ?? 0;
                    $label = $criteria_labels[$i];
                ?>
                <tr><td><?php echo $i . '. ' . $label; ?></td><td><strong><?php echo $value; ?>/10</strong></td>
                    <td><span class="badge <?php echo $value >= 8 ? 'badge-excellent' : ($value >= 6 ? 'badge-good' : ($value >= 4 ? 'badge-fair' : 'badge-poor')); ?>"><?php echo $value >= 8 ? 'Excellent' : ($value >= 6 ? 'Good' : ($value >= 4 ? 'Fair' : 'Poor')); ?></span></td></tr>
                <?php endfor; ?>
                <tr style="font-weight:bold; background:#f3f4f6;"><td>Average Score</td><td><?php echo $matrix_data['average_score']; ?>/10</td>
                    <td><span class="badge <?php echo $matrix_data['rating'] == 'Excellent' ? 'badge-excellent' : ($matrix_data['rating'] == 'Good' ? 'badge-good' : ($matrix_data['rating'] == 'Fair' ? 'badge-fair' : 'badge-poor')); ?>"><?php echo $matrix_data['rating']; ?></span></td></tr>
            </table>
            <?php else: ?>
            <p>Matrix evaluation has not been completed for this policy. Please complete the benchmarking matrix evaluation first.</p>
            <?php endif; ?>

            <!-- 4. Recommendation -->
            <h2 class="section-title">4. Recommendation</h2>
            <?php if ($matrix_data): ?>
            <p><strong>Recommendation:</strong> <span class="badge <?php echo $matrix_data['recommendation'] == 'Strongly Recommend for Adoption' ? 'badge-excellent' : ($matrix_data['recommendation'] == 'Recommend with Amendments' ? 'badge-good' : ($matrix_data['recommendation'] == 'Needs Significant Revision' ? 'badge-fair' : 'badge-poor')); ?>"><?php echo htmlspecialchars($matrix_data['recommendation']); ?></span></p>
            <?php if (!empty($matrix_data['comments'])): ?>
            <p><strong>Comments:</strong> <?php echo nl2br(htmlspecialchars($matrix_data['comments'])); ?></p>
            <?php endif; ?>
            <?php else: ?>
            <p>Recommendation not available until matrix evaluation is completed.</p>
            <?php endif; ?>

            <!-- 5. Legal Documents -->
            <h2 class="section-title">5. Legal Documents</h2>
            <p>This section contains all <strong><?php echo count($documents); ?></strong> legal documents generated for this policy assessment through AI-assisted analysis.</p>

            <?php if (!empty($documents)): ?>
                <?php 
                $doc_counter = 1;
                foreach ($documents as $doc): 
                    $section_number = '5.' . $doc_counter;
                    $content = htmlspecialchars($doc['content']);
                    $formatted_content = nl2br($content);
                ?>
                <div class="legal-doc-block">
                    <div class="doc-title">
                        <?php echo $section_number . ' ' . htmlspecialchars($doc['title']); ?>
                    </div>
                    <div class="doc-meta">
                        <strong>Document ID:</strong> <?php echo htmlspecialchars($doc['document_id']); ?> &nbsp;|&nbsp;
                        <strong>Generated By:</strong> <?php echo htmlspecialchars($doc['generated_by']); ?> &nbsp;|&nbsp;
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($doc['generated_date'])); ?>
                    </div>
                    <div class="doc-content">
                        <?php echo $formatted_content; ?>
                    </div>
                </div>
                <?php 
                $doc_counter++;
                endforeach; 
                ?>
            <?php else: ?>
            <p style="color: #6b7280; font-style: italic;">No legal documents available for this policy.</p>
            <?php endif; ?>

            <!-- 6. Assessment Summary -->
            <h2 class="section-title">6. Assessment Summary</h2>
            <p><?php echo !empty($assessment_data['assessment_summary']) ? nl2br(htmlspecialchars($assessment_data['assessment_summary'])) : 'Assessment pending for ' . htmlspecialchars($submission_data['policy_title']); ?></p>

            <!-- 7. Dataset Information -->
            <h2 class="section-title">7. Dataset Information</h2>
            <?php if ($dataset_data): ?>
            <table id="datasetTable">
                <tr><th style="width:30%;">Dataset ID</th><td><?php echo htmlspecialchars($dataset_data['dataset_id']); ?></td></tr>
                <tr><th>Dataset Name</th><td><?php echo htmlspecialchars($dataset_data['dataset_name']); ?></td></tr>
                <tr><th>Category</th><td><?php echo htmlspecialchars($dataset_data['category']); ?></td></tr>
                <tr><th>Source Office</th><td><?php echo htmlspecialchars($dataset_data['source_office']); ?></td></tr>
                <tr><th>Approval Status</th><td><?php echo htmlspecialchars($dataset_data['approval_status']); ?></td></tr>
                <tr><th>Upload Date</th><td><?php echo date('F j, Y', strtotime($dataset_data['upload_date'])); ?></td></tr>
            </table>
            <?php else: ?>
            <p>Dataset information not available.</p>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer">
                <p>This report is generated automatically by the Legislative Research, Policy Analysis, and Impact Evaluation System.</p>
                <p style="margin-top:3px;">
                    Report ID: <?php echo $submission_data['benchmark_id']; ?> | 
                    Generated: <?php echo date('F j, Y h:i A'); ?> |
                    Documents: <?php echo count($documents); ?>
                </p>
                <p style="margin-top:3px; font-size:9pt; color:#999;">
                    &copy; <?php echo date('Y'); ?> Legislative Research, Policy Analysis, and Impact Evaluation System
                </p>
            </div>

            </div> <!-- End Foreground Content Layer -->

        </div>

        <!-- ========================================= -->
        <!-- BOTTOM ACTION FOOTER BAR (CONFIRM & SAVE / GENERATE PDF) -->
        <!-- ========================================= -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center flex-wrap gap-4 rounded-b-xl no-print">
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-600 font-medium">
                    <i class="fa-solid fa-circle-check text-green-600 mr-1.5"></i>
                    Report Preview for <strong><?php echo htmlspecialchars($submission_data['policy_title']); ?></strong>
                </span>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1.5 bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs shadow-sm">
                    <i class="fa-solid fa-file-lines text-blue-700"></i>
                    <label for="paperSizeSelect" class="text-slate-600 font-medium">Paper Size:</label>
                    <select id="paperSizeSelect" onchange="setPrintPaperSize(this.value)" class="bg-transparent font-bold text-slate-800 focus:outline-none cursor-pointer">
                        <option value="A4" selected>A4 Standard</option>
                        <option value="letter">Short (Letter 8.5" × 11")</option>
                        <option value="legal">Long (Legal 8.5" × 14")</option>
                        <option value="folio">Folio / Oficio (8.5" × 13")</option>
                    </select>
                </div>

                <button onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2.5 rounded-lg btn-scale font-semibold text-xs flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>

                <button onclick="generateFastPDF()" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg btn-scale font-semibold text-xs flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-file-pdf"></i> Generate PDF
                </button>

                <a href="?benchmark_id=<?php echo urlencode($benchmark_id); ?>&generated=1" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg btn-scale font-semibold text-xs flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-check"></i> Confirm & Save
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500 no-print">
        <p>&copy; <?php echo date('Y'); ?> Legislative Research, Policy Analysis, and Impact Evaluation System</p>
        <p class="mt-2">Report Generation Module</p>
    </footer>

</main>
</div>

<script>
    // Client-side real-time table filter
    function liveFilterTable(query) {
        const filter = query.toLowerCase();
        const rows = document.querySelectorAll('.submission-row, .dataset-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    // Dynamic Print Paper Size Selector (A4, Short, Long, Folio)
    function setPrintPaperSize(size) {
        let styleTag = document.getElementById('dynamicPaperSizeStyle');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'dynamicPaperSizeStyle';
            document.head.appendChild(styleTag);
        }
        let pageSizeCss = 'A4 portrait';
        if (size === 'letter') pageSizeCss = 'letter portrait';
        else if (size === 'legal') pageSizeCss = 'legal portrait';
        else if (size === 'folio') pageSizeCss = '8.5in 13in portrait';
        
        styleTag.innerHTML = '@media print { @page { size: ' + pageSizeCss + ' !important; margin: 0.3in 0.35in !important; } }';
    }

    // Fast PDF generation function - builds PDF directly from data
    function generateFastPDF() {
        const loading = document.getElementById('pdfLoading');
        loading.classList.add('active');

        // Get report data from the page
        const reportContent = document.getElementById('reportContent');
        
        // Use the built-in jsPDF with autoTable for proper pagination
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
        });

        const pageWidth = 210; // A4 width in mm
        const margin = 15;
        const contentWidth = pageWidth - (margin * 2);
        let y = margin;

        // Helper function to add a new page if needed
        function checkPage(minSpace = 20) {
            if (y > 280 - minSpace) {
                doc.addPage();
                y = margin;
            }
        }

        // Helper to add text with word wrap
        function addText(text, fontSize = 12, style = 'normal', indent = 0) {
            checkPage();
            doc.setFontSize(fontSize);
            doc.setFont('helvetica', style);
            const lines = doc.splitTextToSize(text, contentWidth - indent);
            doc.text(lines, margin + indent, y);
            y += (lines.length * (fontSize * 0.45)) + 2;
            return lines.length;
        }

        // Helper to add a section title
        function addSectionTitle(text) {
            checkPage(25);
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text(text, margin, y);
            y += 6;
            doc.setLineWidth(0.3);
            doc.line(margin, y, pageWidth - margin, y);
            y += 5;
        }

        // Helper to add a table
        function addTable(headers, rows) {
            checkPage(30);
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: y,
                margin: { left: margin, right: margin },
                styles: { fontSize: 9, cellPadding: 2 },
                headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                didDrawPage: function(data) {
                    y = data.cursor.y + 5;
                }
            });
            y = doc.lastAutoTable.finalY + 5;
        }

        // ---- BUILD REPORT ----

        // Title
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        const titleText = 'Policy Impact Assessment Report';
        doc.text(titleText, pageWidth / 2, y, { align: 'center' });
        y += 8;
        doc.setLineWidth(1);
        doc.line(margin, y, pageWidth - margin, y);
        y += 8;

        // Header Info
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        const headerLines = [
            'Report ID: <?php echo $submission_data['benchmark_id']; ?> | Date Generated: <?php echo date('F j, Y'); ?> | Prepared By: <?php echo htmlspecialchars($username); ?>',
            'Department: <?php echo htmlspecialchars($submission_data['department']); ?> | Total Documents: <?php echo count($documents); ?> legal documents included'
        ];
        headerLines.forEach(line => {
            const split = doc.splitTextToSize(line, contentWidth);
            doc.text(split, margin, y);
            y += 5;
        });
        y += 4;

        // 1. Policy Overview
        addSectionTitle('1. Policy Overview');
        const overviewRows = [
            ['Policy Title', '<?php echo htmlspecialchars($submission_data['policy_title']); ?>'],
            ['Benchmark ID', '<?php echo htmlspecialchars($submission_data['benchmark_id']); ?>'],
            ['Department', '<?php echo htmlspecialchars($submission_data['department']); ?>'],
            ['Impact Percentage', '<?php echo $submission_data['impact_percentage']; ?>%'],
            ['Impact Rating', '<?php echo $submission_data['impact_rating']; ?>'],
            ['Status', '<?php echo htmlspecialchars($submission_data['status']); ?>'],
            ['Submitted By', '<?php echo htmlspecialchars($submission_data['submitted_by']); ?>'],
            ['Submitted Date', '<?php echo date('F j, Y', strtotime($submission_data['submitted_date'])); ?>'],
            ['Documents Included', '<?php echo count($documents); ?> legal documents']
        ];
        addTable(['Field', 'Value'], overviewRows);

        // 2. KPI Scores
        addSectionTitle('2. KPI Scores & Performance Metrics');
        <?php if ($assessment_data): ?>
        const kpiRows = [
            ['Effectiveness', '<?php echo $assessment_data['kpi_effectiveness'] ?? 0; ?>%', '<?php echo ($assessment_data['kpi_effectiveness'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_effectiveness'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?>'],
            ['Efficiency', '<?php echo $assessment_data['kpi_efficiency'] ?? 0; ?>%', '<?php echo ($assessment_data['kpi_efficiency'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_efficiency'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?>'],
            ['Relevance', '<?php echo $assessment_data['kpi_relevance'] ?? 0; ?>%', '<?php echo ($assessment_data['kpi_relevance'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_relevance'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?>'],
            ['Sustainability', '<?php echo $assessment_data['kpi_sustainability'] ?? 0; ?>%', '<?php echo ($assessment_data['kpi_sustainability'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_sustainability'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?>'],
            ['Equity', '<?php echo $assessment_data['kpi_equity'] ?? 0; ?>%', '<?php echo ($assessment_data['kpi_equity'] ?? 0) >= 70 ? 'Good' : (($assessment_data['kpi_equity'] ?? 0) >= 50 ? 'Moderate' : 'Needs Improvement'); ?>'],
            ['Implementation Rate', '<?php echo $assessment_data['implementation_rate'] ?? 0; ?>%', '<?php echo ($assessment_data['implementation_rate'] ?? 0) >= 70 ? 'On Track' : (($assessment_data['implementation_rate'] ?? 0) >= 50 ? 'Moderate' : 'Needs Attention'); ?>'],
            ['Budget Utilization', '<?php echo $assessment_data['budget_utilization'] ?? 0; ?>%', '<?php echo ($assessment_data['budget_utilization'] ?? 0) >= 70 ? 'Efficient' : (($assessment_data['budget_utilization'] ?? 0) >= 50 ? 'Moderate' : 'Needs Review'); ?>'],
            ['Beneficiaries', '<?php echo number_format($assessment_data['beneficiaries'] ?? 0); ?>', 'Reached']
        ];
        addTable(['KPI', 'Score', 'Status'], kpiRows);
        <?php else: ?>
        addText('KPI data not available.', 11);
        <?php endif; ?>

        // 3. Benchmarking Matrix
        addSectionTitle('3. Benchmarking Matrix Evaluation');
        <?php if ($matrix_data): ?>
        const matrixRows = [
            <?php 
            $criteria_labels = [
                1 => 'Alignment with Legal Framework',
                2 => 'Compliance with Constitutional Provisions',
                3 => 'Consistency with International Law',
                4 => 'Protection of Rights and Liberties',
                5 => 'Implementation Mechanism',
                6 => 'Monitoring and Evaluation',
                7 => 'Stakeholder Engagement',
                8 => 'Resource Allocation',
                9 => 'Risk Assessment',
                10 => 'Sustainability'
            ];
            for ($i = 1; $i <= 10; $i++):
                $value = $matrix_data['criteria' . $i] ?? 0;
                $label = $criteria_labels[$i];
                $status = $value >= 8 ? 'Excellent' : ($value >= 6 ? 'Good' : ($value >= 4 ? 'Fair' : 'Poor'));
            ?>
            ['<?php echo $i . '. ' . $label; ?>', '<?php echo $value; ?>/10', '<?php echo $status; ?>'],
            <?php endfor; ?>
            ['Average Score', '<?php echo $matrix_data['average_score']; ?>/10', '<?php echo $matrix_data['rating']; ?>']
        ];
        addTable(['Criteria', 'Rating', 'Status'], matrixRows);
        <?php else: ?>
        addText('Matrix evaluation has not been completed for this policy.', 11);
        <?php endif; ?>

        // 4. Recommendation
        addSectionTitle('4. Recommendation');
        <?php if ($matrix_data): ?>
        addText('Recommendation: <?php echo htmlspecialchars($matrix_data['recommendation']); ?>', 11, 'bold');
        <?php if (!empty($matrix_data['comments'])): ?>
        addText('Comments: <?php echo htmlspecialchars(strip_tags($matrix_data['comments'])); ?>', 11);
        <?php endif; ?>
        <?php else: ?>
        addText('Recommendation not available until matrix evaluation is completed.', 11);
        <?php endif; ?>

        // 5. Legal Documents
        addSectionTitle('5. Legal Documents');
        addText('This section contains all <?php echo count($documents); ?> legal documents generated for this policy assessment through AI-assisted analysis.', 11);

        <?php if (!empty($documents)): 
            $doc_counter = 1;
            foreach ($documents as $doc): 
                $content = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $doc['content']));
                $title = htmlspecialchars($doc['title']);
                $doc_id = htmlspecialchars($doc['document_id']);
                $generated_by = htmlspecialchars($doc['generated_by']);
                $date = date('F j, Y', strtotime($doc['generated_date']));
        ?>
        // Document <?php echo $doc_counter; ?>
        checkPage(30);
        addText('<?php echo $doc_counter . '. ' . $title; ?>', 11, 'bold');
        addText('Document ID: <?php echo $doc_id; ?> | Generated By: <?php echo $generated_by; ?> | Date: <?php echo $date; ?>', 9, 'normal');
        
        // Split the content into lines and add
        <?php
        $content_lines = explode("\n", $content);
        foreach ($content_lines as $line):
            if (trim($line) == '') {
                $line = ' ';
            }
            $line = addslashes($line);
        ?>
        addText('<?php echo $line; ?>', 10);
        <?php endforeach; ?>
        y += 2;
        <?php 
            $doc_counter++;
            endforeach; 
        endif; 
        ?>

        // 6. Assessment Summary
        addSectionTitle('6. Assessment Summary');
        <?php $summary = !empty($assessment_data['assessment_summary']) ? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $assessment_data['assessment_summary'])) : 'Assessment pending for ' . htmlspecialchars($submission_data['policy_title']); ?>
        addText('<?php echo addslashes($summary); ?>', 11);

        // 7. Dataset Information
        addSectionTitle('7. Dataset Information');
        <?php if ($dataset_data): ?>
        const datasetRows = [
            ['Dataset ID', '<?php echo htmlspecialchars($dataset_data['dataset_id']); ?>'],
            ['Dataset Name', '<?php echo htmlspecialchars($dataset_data['dataset_name']); ?>'],
            ['Category', '<?php echo htmlspecialchars($dataset_data['category']); ?>'],
            ['Source Office', '<?php echo htmlspecialchars($dataset_data['source_office']); ?>'],
            ['Approval Status', '<?php echo htmlspecialchars($dataset_data['approval_status']); ?>'],
            ['Upload Date', '<?php echo date('F j, Y', strtotime($dataset_data['upload_date'])); ?>']
        ];
        addTable(['Field', 'Value'], datasetRows);
        <?php else: ?>
        addText('Dataset information not available.', 11);
        <?php endif; ?>

        // Footer
        checkPage(30);
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 100, 100);
        const footerLines = [
            'This report is generated automatically by the Legislative Research, Policy Analysis, and Impact Evaluation System.',
            'Report ID: <?php echo $submission_data['benchmark_id']; ?> | Generated: <?php echo date('F j, Y h:i A'); ?> | Documents: <?php echo count($documents); ?>',
            '© <?php echo date('Y'); ?> Legislative Research, Policy Analysis, and Impact Evaluation System'
        ];
        footerLines.forEach(line => {
            const split = doc.splitTextToSize(line, contentWidth);
            doc.text(split, pageWidth / 2, y, { align: 'center' });
            y += 5;
        });

        // Save the PDF
        const fileName = 'Policy_Impact_Assessment_Report_<?php echo $submission_data['benchmark_id']; ?>.pdf';
        doc.save(fileName);
        loading.classList.remove('active');
    }

    // Fallback: use html2pdf if needed (slower but works for complex layouts)
    function generatePDF() {
        const loading = document.getElementById('pdfLoading');
        loading.classList.add('active');

        const element = document.getElementById('reportContent');
        const opt = {
            margin: [10, 10, 10, 10],
            filename: 'Policy_Impact_Assessment_Report_<?php echo $submission_data['benchmark_id']; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2,
                useCORS: true,
                letterRendering: true,
                logging: false
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'portrait' 
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf().set(opt).from(element).save().then(function() {
            loading.classList.remove('active');
        }).catch(function(error) {
            console.error('PDF generation error:', error);
            loading.classList.remove('active');
            alert('Error generating PDF. Please try again.');
        });
    }

    <?php if (isset($_GET['generated'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const rc = document.getElementById('reportContent');
            if (rc) rc.scrollIntoView({ behavior: 'smooth' });
        });
    <?php endif; ?>

    <?php if (isset($_GET['autodownload']) && $_GET['autodownload'] == '1' && $submission_data): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                generateFastPDF();
            }, 600);
        });
    <?php endif; ?>

    document.querySelectorAll("button, a.btn-scale").forEach(function(el) {
        el.addEventListener("mouseenter", function() {
            this.classList.add("scale-105", "transition");
        });
        el.addEventListener("mouseleave", function() {
            this.classList.remove("scale-105");
        });
    });
</script>

</body>
</html>

<?php
$conn->close();
?>