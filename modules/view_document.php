<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "View Document";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// Get document ID from URL
$doc_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($doc_id)) {
    header("Location: policy-research.php");
    exit();
}

// Fetch document details
$sql = "SELECT * FROM policy_documents WHERE document_id = '$doc_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: policy-research.php");
    exit();
}

$document = $result->fetch_assoc();

// Fetch keywords for this document
$keywords_sql = "SELECT * FROM policy_keywords WHERE document_id = '$doc_id'";
$keywords_result = $conn->query($keywords_sql);
$keywords = [];
while ($kw = $keywords_result->fetch_assoc()) {
    $keywords[] = $kw['keyword'];
}

// If no keywords in keyword table, use the stored keywords field
if (empty($keywords) && !empty($document['keywords'])) {
    $keywords = explode(", ", $document['keywords']);
}

// Fetch AI analysis results if available
$ai_analysis = [];
if (!empty($document['nlp_keywords'])) {
    $ai_analysis['nlp_keywords'] = explode(", ", $document['nlp_keywords']);
}
if (!empty($document['similar_ordinance'])) {
    $ai_analysis['similar_ordinance'] = $document['similar_ordinance'];
}
if (!empty($document['legal_citation'])) {
    $ai_analysis['legal_citation'] = $document['legal_citation'];
}
if (!empty($document['ai_analysis_result'])) {
    $ai_analysis['full_result'] = $document['ai_analysis_result'];
}

// Get related documents (same category)
$related_sql = "SELECT * FROM policy_documents 
                WHERE category = '{$document['category']}' 
                AND document_id != '$doc_id' 
                LIMIT 3";
$related_docs = $conn->query($related_sql);

// Log view activity
$log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
            VALUES ('$username', 'Viewed document', '$doc_id', 'Policy Research', NOW())";
$conn->query($log_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - <?php echo htmlspecialchars($document['title']); ?></title>
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
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .ai-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }
        
        .detail-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .detail-value {
            color: #1e293b;
            font-size: 1rem;
            padding: 8px 0;
        }
        
        .metadata-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }
        
        .keyword-tag {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin: 2px 4px 2px 0;
        }
        
        .ai-tag {
            background: #ede9fe;
            color: #5b21b6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin: 2px 4px 2px 0;
        }
        
        .print-watermark, .print-header, .print-signature-block {
            display: none !important;
        }

@media print {

  /* ---------- 1. PAGE SETUP & MAXIMUM PRINTABLE AREA ---------- */
  @page {
    size: A4 portrait;
    margin: 0.3in 0.35in;
  }

  html, body {
    background: #ffffff !important;
    color: #0f172a !important;
    font-size: 11pt !important;
    line-height: 1.45 !important;
    font-family: 'Times New Roman', Georgia, serif !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  /* HIDE ALL BACKGROUND PAGE LAYOUT (SIDEBAR, NAVBAR, BREADCRUMBS, TABLES) */
  aside, nav, header, footer,
  .ml-72 > nav,
  main > h1, main > p, main > div.flex {
    display: none !important;
  }

  .ml-72, main {
    margin-left: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
  }

  h1 { font-size: 17pt !important; margin-bottom: 4pt !important; }
  h2 { font-size: 14pt !important; margin-bottom: 4pt !important; }
  h3 { font-size: 12pt !important; margin-bottom: 3pt !important; }
  h4, h5 { font-size: 11pt !important; margin-bottom: 2pt !important; }
  table, th, td { font-size: 10.5pt !important; }

  /* ---------- 2. UNCONSTRAIN CONTAINERS & FULL PAGE WIDTH ---------- */
  .doc-content,
  .legislative-report-body,
  [class*="max-h-"],
  [class*="overflow-y"],
  [class*="overflow-auto"] {
    max-height: none !important;
    height: auto !important;
    overflow: visible !important;
    overflow-y: visible !important;
    overflow-x: visible !important;
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Compact Edge-to-Edge Grid Layout */
  .grid {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    width: 100% !important;
    gap: 8pt !important;
    margin-bottom: 8pt !important;
  }
  .grid > div {
    flex: 1 1 48% !important;
    max-width: 48% !important;
    box-sizing: border-box !important;
  }

  .mb-6, .mb-8, .my-6 {
    margin-bottom: 8pt !important;
  }

  .p-4, .p-6, .p-5 {
    padding: 6pt 8pt !important;
  }

  /* ---------- 3. HIDE UI CHROME ---------- */
  .no-print,
  button, .btn,
  input[type="radio"], input[type="checkbox"],
  form .submit-row {
    display: none !important;
  }

  .card, .panel, .kpi-card, .metric-card, .legal-document-card, .report-box, .bg-slate-50 {
    box-shadow: none !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 0 !important;
    background: transparent !important;
  }

  /* ---------- 4. COLOR HANDLING ---------- */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .badge, .status-badge, .impact-badge, .rating-badge, .recommendation-badge {
    border: 1px solid currentColor !important;
    padding: 1pt 4pt !important;
  }

  /* ---------- 5. PREVENT MID-SENTENCE CUTS & BREAK AT PARAGRAPHS ---------- */
  p, li, blockquote, dt, dd, .legal-document-card, .report-box, tr, td, th {
    orphans: 4 !important;
    widows: 4 !important;
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  table, .comparison-matrix, .kpi-scores, .performance-metrics,
  .print-signature-block, .assessment-summary, .matrix-summary, .criteria-card, .doc-content {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  h1, h2, h3, h4, h5, h6 {
    page-break-after: avoid !important;
    break-after: avoid-page !important;
  }

  p, div, td, span {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
  }

  /* ---------- 6. COMPACT FORMAL LETTERHEAD (Page 1 Top) ---------- */
  .print-header {
    display: block !important;
    text-align: center;
    padding-bottom: 6pt !important;
    margin-bottom: 10pt !important;
    border-bottom: 2px solid #0f172a;
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

  /* ---------- 7. REPEATING SEAL WATERMARK (EVERY PAGE) ---------- */
  .print-watermark {
    display: flex !important;
    visibility: visible !important;
    position: fixed !important;
    top: 22% !important;
    left: 18% !important;
    transform: none !important;
    width: 4.8in !important;
    height: auto !important;
    opacity: 0.08 !important;
    mix-blend-mode: multiply !important;
    z-index: -1 !important;
    pointer-events: none !important;
    align-items: center !important;
    justify-content: center !important;
  }

  /* ---------- 8. SIGNATURE BLOCK ---------- */
  .print-signature-block {
    display: flex !important;
    justify-content: space-between;
    margin-top: 18pt !important;
    padding-top: 8pt !important;
    border-top: 1px solid #94a3b8;
    font-size: 9pt !important;
    width: 100% !important;
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  .print-signature-block .signature-col {
    width: 45%;
    text-align: center;
  }

  .print-signature-block .signature-line {
    border-top: 1px solid #0f172a;
    margin-top: 20pt !important;
    padding-top: 2pt !important;
  }
}
    </style>
</head>

<body>

    <?php include("../includes/sidebar.php"); ?>
    
    <div class="ml-72">
        <?php include("../includes/navbar.php"); ?>
        
        <main class="p-8 relative">
            
            <!-- CENTERED WATERMARK LOGO FOR PRINT ONLY (REPEATED PER PAGE) -->
            <div class="print-watermark hidden print:flex pointer-events-none">
                <img src="../City.jpg" alt="Watermark City Logo" class="w-[500px] h-[500px] object-contain">
            </div>

            <!-- FORMAL PRINT HEADER WITH LOGO & AUTHENTICITY BADGE -->
            <div class="print-header hidden print:block border-b pb-4 mb-6 text-center border-slate-300">
                <img src="../City.jpg" alt="City Logo" class="w-20 h-20 rounded-full object-cover border-2 border-slate-300 shadow-sm mx-auto mb-2">
                <h1 class="text-xl font-bold uppercase tracking-wide text-slate-800">Republic of the Philippines</h1>
                <h2 class="text-lg font-semibold text-slate-700">City of San Jose Del Monte, Bulacan</h2>
                <p class="text-sm font-medium text-slate-600">Legislative Research, Policy Analysis & Impact Evaluation System</p>
                <div class="mt-2 inline-block px-3 py-1 bg-slate-100 border border-slate-300 rounded-full text-xs font-semibold text-slate-700">
                    <i class="fa-solid fa-shield-halved text-blue-600 mr-1"></i> Official Policy Research Document &bull; Verified Authenticity
                </div>
            </div>

            <!-- PAGE HEADER -->
            <div class="flex justify-between items-center mb-8 fade-in no-print">
                <div>
                    <div class="flex items-center gap-3">
                        <a href="policy-research.php" class="text-blue-700 hover:text-blue-900">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                        <h2 class="text-3xl font-bold text-slate-800">View Document</h2>
                    </div>
                    <p class="text-slate-500 mt-2">Document ID: <span class="font-mono font-semibold"><?php echo $document['document_id']; ?></span></p>
                </div>
                <div class="flex gap-3">
                    <a href="edit_document.php?id=<?php echo $document['document_id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg shadow btn-scale">
                        <i class="fa-solid fa-pen mr-2"></i>
                        Edit
                    </a>
                    <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg shadow btn-scale">
                        <i class="fa-solid fa-print mr-2"></i>
                        Print
                    </button>
                </div>
            </div>

            <!-- DOCUMENT DETAILS -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
                <!-- Main Document Info -->
                <div class="xl:col-span-2 bg-white rounded-xl shadow p-6 fade-in">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-blue-700"></i>
                        Document Information
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="detail-label">Document ID</label>
                            <p class="detail-value font-mono"><?php echo $document['document_id']; ?></p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Policy Title</label>
                            <p class="detail-value text-xl font-semibold"><?php echo htmlspecialchars($document['title']); ?></p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="detail-label">Category</label>
                                <p class="detail-value">
                                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                        <?php echo $document['category']; ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="detail-label">Status</label>
                                <p class="detail-value">
                                    <span class="status-badge px-3 py-1 rounded-full text-sm 
                                        <?php echo $document['status'] == 'Approved' ? 'bg-green-100 text-green-700' : 
                                                  ($document['status'] == 'Under Review' ? 'bg-blue-100 text-blue-700' :
                                                  ($document['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-700' : 
                                                  ($document['status'] == 'Draft' ? 'bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-700'))); ?>">
                                        <?php echo $document['status']; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="detail-label">Description</label>
                            <p class="detail-value text-slate-600 leading-relaxed">
                                <?php echo htmlspecialchars($document['description'] ?? 'No description provided.'); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Keywords</label>
                            <div class="mt-2">
                                <?php if (!empty($keywords)): ?>
                                    <?php foreach ($keywords as $kw): ?>
                                        <span class="keyword-tag">#<?php echo htmlspecialchars(trim($kw)); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-slate-400">No keywords added</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Metadata Sidebar -->
                <div class="bg-white rounded-xl shadow p-6 fade-in">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-info-circle text-blue-700"></i>
                        Metadata
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="detail-label">Researcher</label>
                            <p class="detail-value">
                                <i class="fa-solid fa-user text-slate-400 mr-2"></i>
                                <?php echo htmlspecialchars($document['researcher']); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Upload Date</label>
                            <p class="detail-value">
                                <i class="fa-solid fa-calendar text-slate-400 mr-2"></i>
                                <?php echo date('F j, Y \a\t g:i A', strtotime($document['upload_date'])); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">File</label>
                            <p class="detail-value">
                                <i class="fa-solid fa-file-pdf text-red-500 mr-2"></i>
                                <a href="<?php echo $document['file_path']; ?>" target="_blank" class="text-blue-700 hover:text-blue-900">
                                    <?php echo htmlspecialchars($document['file_name']); ?>
                                </a>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">AI Processing Status</label>
                            <p class="detail-value">
                                <?php if ($document['ai_processed'] == 'Yes'): ?>
                                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm">
                                        <i class="fa-solid fa-check mr-1"></i> Processed
                                    </span>
                                    <?php if (!empty($document['ai_processed_date'])): ?>
                                        <span class="text-xs text-slate-400 block mt-1">
                                            Processed: <?php echo date('F j, Y g:i A', strtotime($document['ai_processed_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm">
                                        <i class="fa-solid fa-clock mr-1"></i> Not Processed
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Selected for Analysis</label>
                            <p class="detail-value">
                                <?php if ($document['selected_for_analysis'] == 'Yes'): ?>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                                        <i class="fa-solid fa-check-circle mr-1"></i> Selected
                                    </span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm">
                                        <i class="fa-solid fa-circle mr-1"></i> Not Selected
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($document['analysis_notes'])): ?>
                            <div>
                                <label class="detail-label">Analysis Notes</label>
                                <p class="detail-value text-slate-600 text-sm italic">
                                    "<?php echo htmlspecialchars($document['analysis_notes']); ?>"
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

           <!-- AI ANALYSIS RESULTS -->
<?php if (!empty($ai_analysis)): ?>
    <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full ai-badge flex items-center justify-center">
                <i class="fa-solid fa-robot text-white"></i>
            </div>
            <h3 class="text-xl font-bold">AI Analysis Results</h3>
            <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs">NLP Processed</span>
            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                <i class="fa-solid fa-map-pin mr-1"></i> San Jose Del Monte, Bulacan
            </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (!empty($ai_analysis['nlp_keywords'])): ?>
                <div class="metadata-box">
                    <label class="detail-label">Extracted Keywords</label>
                    <div class="mt-2">
                        <?php foreach ($ai_analysis['nlp_keywords'] as $kw): ?>
                            <span class="ai-tag"><?php echo htmlspecialchars(trim($kw)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($ai_analysis['similar_ordinance'])): ?>
                <div class="metadata-box">
                    <label class="detail-label">
                        <i class="fa-solid fa-map-pin text-green-600 mr-1"></i>
                        Similar Ordinance - San Jose Del Monte
                    </label>
                    <p class="detail-value font-semibold text-blue-700"><?php echo htmlspecialchars($ai_analysis['similar_ordinance']); ?></p>
                    <p class="text-xs text-slate-400 mt-1">City of San Jose Del Monte, Bulacan</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($ai_analysis['legal_citation'])): ?>
                <div class="metadata-box">
                    <label class="detail-label">
                        <i class="fa-solid fa-scale-balanced text-blue-600 mr-1"></i>
                        Legal Citations (Philippine Laws)
                    </label>
                    <p class="detail-value"><?php echo htmlspecialchars($ai_analysis['legal_citation']); ?></p>
                    <p class="text-xs text-slate-400 mt-1">Applicable to San Jose Del Monte, Bulacan</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($ai_analysis['full_result'])): ?>
                <div class="metadata-box md:col-span-2">
                    <label class="detail-label">
                        <i class="fa-solid fa-file-lines text-purple-600 mr-1"></i>
                        Full Analysis Report - SJDM Context
                    </label>
                    <div class="detail-value text-slate-600 whitespace-pre-line bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <?php echo htmlspecialchars($ai_analysis['full_result']); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- SJDM Context Information -->
        <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-city text-green-700"></i>
                <span class="font-semibold text-green-800">San Jose Del Monte, Bulacan Context:</span>
            </div>
            <p class="text-sm text-green-700 mt-1">
                This analysis considers the local government context of San Jose Del Monte, including its 
                city ordinances, local development plans, and compliance with national laws. 
                The city is one of the fastest-growing urban centers in Bulacan province.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                <i class="fa-solid fa-robot text-gray-500"></i>
            </div>
            <h3 class="text-xl font-bold">AI Analysis</h3>
            <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs">Not Processed</span>
        </div>
        <p class="text-slate-500">This document hasn't been processed by the AI engine yet. 
            <a href="policy-research.php" class="text-blue-700 hover:text-blue-900">Go back</a> to run AI analysis for San Jose Del Monte, Bulacan.
        </p>
    </div>
<?php endif; ?>

            <!-- RELATED DOCUMENTS -->
            <?php if ($related_docs->num_rows > 0): ?>
                <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-link text-blue-700"></i>
                        Related Documents
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php while($related = $related_docs->fetch_assoc()): ?>
                            <div class="border rounded-xl p-4 hover:shadow-md transition card-hover">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-sm"><?php echo htmlspecialchars($related['title']); ?></h4>
                                        <p class="text-xs text-slate-500 font-mono mt-1"><?php echo $related['document_id']; ?></p>
                                    </div>
                                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs">
                                        <?php echo $related['status']; ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 mt-2"><?php echo $related['category']; ?></p>
                                <a href="view_document.php?id=<?php echo $related['document_id']; ?>" class="text-blue-700 hover:text-blue-900 text-sm mt-2 inline-block">
                                    View <i class="fa-solid fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ACTION BUTTONS -->
            <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-ellipsis-vertical text-blue-700"></i>
                    Actions
                </h3>
                
                <div class="flex flex-wrap gap-3">
                    <a href="edit_document.php?id=<?php echo $document['document_id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-pen mr-2"></i>
                        Edit Document
                    </a>
                    
                    <a href="<?php echo $document['file_path']; ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download File
                    </a>
                    
                    <?php if ($document['ai_processed'] != 'Yes'): ?>
                        <form method="POST" action="policy-research.php" class="inline">
                            <input type="hidden" name="doc_id" value="<?php echo $document['document_id']; ?>">
                            <button type="submit" name="run_ai_analysis" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg btn-scale">
                                <i class="fa-solid fa-robot mr-2"></i>
                                Run AI Analysis
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($document['selected_for_analysis'] != 'Yes'): ?>
                        <button onclick="openSelectModal('<?php echo $document['document_id']; ?>', '<?php echo htmlspecialchars($document['title']); ?>')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg btn-scale">
                            <i class="fa-solid fa-check-double mr-2"></i>
                            Select for Analysis
                        </button>
                    <?php endif; ?>
                    
                    <button onclick="deleteDocument('<?php echo $document['document_id']; ?>')" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>

            <!-- FOOTER -->
            <!-- FORMAL SIGNATURE / AUTHENTICATION BLOCK (PRINT ONLY) -->
            <div class="print-signature-block hidden print:flex">
                <div class="signature-col text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-8">Prepared / Archived By:</p>
                    <div class="signature-line border-t border-slate-900 pt-1">
                        <p class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($username ?? 'Policy Research Officer'); ?></p>
                        <p class="text-[10px] text-slate-600">Legislative Research & Policy Division</p>
                    </div>
                </div>
                <div class="signature-col text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-8">Verified & Approved By:</p>
                    <div class="signature-line border-t border-slate-900 pt-1">
                        <p class="text-xs font-bold text-slate-900">Head of Legislative Research & Evaluation</p>
                        <p class="text-[10px] text-slate-600">City Government of San Jose Del Monte</p>
                    </div>
                </div>
            </div>

            <footer class="mt-6 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
            </footer>

        </main>
    </div>

    <!-- SELECT MODAL (for selecting document for analysis) -->
<div id="selectModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Select for Analysis</h2>
            <button onclick="closeSelectModal()" class="text-slate-500 hover:text-slate-700 text-3xl">&times;</button>
        </div>
        
        <form method="POST" action="policy-research.php">
            <input type="hidden" name="selected_doc_id" id="selected_doc_id">
            <input type="hidden" name="selected_doc_title" id="selected_doc_title">
            <input type="hidden" name="select_for_analysis" value="1">
            
            <div class="mb-4">
                <label class="font-semibold block mb-2">Document</label>
                <p class="text-slate-700 p-3 bg-slate-50 rounded-lg" id="selected_doc_title_display">-</p>
            </div>
            
            <div class="mb-4">
                <label class="font-semibold block mb-2">Analysis Notes</label>
                <textarea name="analysis_notes" rows="4" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-700" placeholder="Enter notes for the impact assessment team..."></textarea>
            </div>
            
            <div class="bg-blue-50 p-3 rounded-lg mb-4">
                <p class="text-sm text-blue-700">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    This document will be transferred to the Data Collection and Integration module for further processing.
                </p>
            </div>
            
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg btn-scale">
                    <i class="fa-solid fa-arrow-right mr-2"></i>
                    Proceed to Data Collection
                </button>
                <button type="button" onclick="closeSelectModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-8 py-3 rounded-lg btn-scale">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>

    <script>
        function openSelectModal(docId, docTitle) {
            document.getElementById('selected_doc_id').value = docId;
            document.getElementById('selected_doc_title').textContent = docTitle;
            document.getElementById('selectModal').style.display = 'block';
        }
        
        function closeSelectModal() {
            document.getElementById('selectModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            let modal = document.getElementById('selectModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                window.location.href = `policy-research.php?delete_id=${docId}`;
            }
        }
    </script>

</body>
</html>
<?php
$conn->close();
?>