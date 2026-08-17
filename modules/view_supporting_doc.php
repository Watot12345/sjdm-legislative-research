<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "View Supporting Document";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// Get document ID from URL
$doc_id = isset($_GET['doc_id']) ? $_GET['doc_id'] : '';

if (empty($doc_id)) {
    header("Location: data-collection.php");
    exit();
}

// Fetch document details
$sql = "SELECT * FROM supporting_documents WHERE document_id = '$doc_id'";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header("Location: data-collection.php");
    exit();
}

$document = $result->fetch_assoc();

// Get dataset information
$dataset_sql = "SELECT * FROM datasets WHERE dataset_id = '{$document['dataset_id']}'";
$dataset_result = $conn->query($dataset_sql);
$dataset = $dataset_result ? $dataset_result->fetch_assoc() : null;

// Log view activity
$log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
            VALUES ('$username', 'Viewed supporting document', '$doc_id', 'Data Collection', NOW())";
$conn->query($log_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Supporting Document - <?php echo htmlspecialchars($document['title']); ?></title>
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
        
        .gemini-badge {
            background: linear-gradient(135deg, #4285f4, #34a853, #fbbc04, #ea4335);
            color: white;
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
        
        .doc-content {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            background: #f8fafc;
            padding: 30px;
            border-radius: 8px;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
        }
        
        .doc-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .doc-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .doc-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .doc-content::-webkit-scrollbar-thumb:hover {
            background: #555;
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

    <!-- CENTERED WATERMARK LOGO FOR PRINT ONLY (REPEATED PER PAGE AT DOCUMENT ROOT) -->
    <div class="print-watermark hidden print:flex pointer-events-none">
        <img src="../City.jpg" alt="Watermark City Logo" class="w-[500px] h-[500px] object-contain">
    </div>

    <?php include("../includes/sidebar.php"); ?>
    
    <div class="ml-72">
        <?php include("../includes/navbar.php"); ?>
        
        <main class="p-8 relative">

            <!-- FORMAL PRINT HEADER WITH LOGO & AUTHENTICITY BADGE -->
            <div class="print-header hidden print:block border-b pb-4 mb-6 text-center border-slate-300">
                <img src="../City.jpg" alt="City Logo" class="w-20 h-20 rounded-full object-cover border-2 border-slate-300 shadow-sm mx-auto mb-2">
                <h1 class="text-xl font-bold uppercase tracking-wide text-slate-800">Republic of the Philippines</h1>
                <h2 class="text-lg font-semibold text-slate-700">City of San Jose Del Monte, Bulacan</h2>
                <p class="text-sm font-medium text-slate-600">Legislative Research, Policy Analysis & Impact Evaluation System</p>
                <div class="mt-2 inline-block px-3 py-1 bg-slate-100 border border-slate-300 rounded-full text-xs font-semibold text-slate-700">
                    <i class="fa-solid fa-shield-halved text-purple-600 mr-1"></i> Official AI Supporting Document &bull; Verified Authenticity
                </div>
            </div>

            <!-- PAGE HEADER -->
            <div class="flex justify-between items-center mb-8 fade-in no-print">
                <div>
                    <div class="flex items-center gap-3">
                        <a href="data-collection.php" class="text-blue-700 hover:text-blue-900">
                            <i class="fa-solid fa-arrow-left"></i> Back to Data Collection
                        </a>
                        <h2 class="text-3xl font-bold text-slate-800">Supporting Document</h2>
                    </div>
                    <p class="text-slate-500 mt-2">
                        Document ID: <span class="font-mono font-semibold"><?php echo $document['document_id']; ?></span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <button onclick="downloadDocument()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg shadow btn-scale">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download
                    </button>
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
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full gemini-badge flex items-center justify-center text-white text-xl">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($document['title']); ?></h3>
                            <span class="text-sm text-slate-500">Generated by <?php echo htmlspecialchars($document['generated_by']); ?></span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="detail-label">Document ID</label>
                            <p class="detail-value font-mono"><?php echo $document['document_id']; ?></p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Category</label>
                            <p class="detail-value">
                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm">
                                    <?php echo htmlspecialchars($document['category']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="detail-label">Generated By</label>
                                <p class="detail-value">
                                    <span class="gemini-badge px-3 py-1 rounded-full text-sm text-white">
                                        <i class="fa-solid fa-robot mr-1"></i> <?php echo htmlspecialchars($document['generated_by']); ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="detail-label">Generated Date</label>
                                <p class="detail-value">
                                    <i class="fa-solid fa-calendar text-slate-400 mr-2"></i>
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($document['generated_date'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($dataset): ?>
                            <div>
                                <label class="detail-label">Related Dataset</label>
                                <p class="detail-value">
                                    <a href="data-collection.php?view_dataset=<?php echo $dataset['dataset_id']; ?>" class="text-blue-700 hover:text-blue-900">
                                        <i class="fa-solid fa-database mr-2"></i>
                                        <?php echo htmlspecialchars($dataset['dataset_name']); ?>
                                        (<?php echo $dataset['dataset_id']; ?>)
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Metadata Sidebar -->
                <div class="bg-white rounded-xl shadow p-6 fade-in">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-info-circle text-blue-700"></i>
                        Document Info
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="p-3 bg-purple-50 rounded-lg border border-purple-200 no-print">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-gavel text-purple-600"></i>
                                <span class="font-semibold text-purple-800">Legal Document</span>
                            </div>
                            <p class="text-xs text-purple-600 mt-1">
                                This document was processed for legislative research and policy evaluation.
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Document Type</label>
                            <p class="detail-value">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                    <?php echo htmlspecialchars($document['category']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Document Size</label>
                            <p class="detail-value">
                                <?php 
                                $size = strlen($document['content']);
                                if ($size > 10000) {
                                    echo round($size / 1000, 1) . ' KB';
                                } else {
                                    echo $size . ' characters';
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="detail-label">Word Count</label>
                            <p class="detail-value">
                                <?php echo str_word_count($document['content']); ?> words
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DOCUMENT CONTENT -->
            <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-blue-700"></i>
                        Document Content
                    </h3>
                    <span class="text-sm text-slate-500">
                        <i class="fa-solid fa-eye mr-1"></i> Full Document
                    </span>
                </div>
                
                <div class="doc-content">
                    <?php echo htmlspecialchars($document['content']); ?>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="bg-white rounded-xl shadow p-6 mb-8 fade-in no-print">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-ellipsis-vertical text-blue-700"></i>
                    Actions
                </h3>
                
                <div class="flex flex-wrap gap-3 items-center">
                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2.5 text-sm">
                        <i class="fa-solid fa-file-lines text-blue-700"></i>
                        <label for="paperSizeSelect" class="text-slate-600 font-medium">Paper Size:</label>
                        <select id="paperSizeSelect" onchange="setPrintPaperSize(this.value)" class="bg-transparent font-bold text-slate-800 focus:outline-none cursor-pointer">
                            <option value="A4" selected>A4 Standard</option>
                            <option value="letter">Short (Letter 8.5" × 11")</option>
                            <option value="legal">Long (Legal 8.5" × 14")</option>
                            <option value="folio">Folio / Oficio (8.5" × 13")</option>
                        </select>
                    </div>

                    <button onclick="downloadDocument()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download Document
                    </button>
                    
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-print mr-2"></i>
                        Print Document
                    </button>
                    
                    <button onclick="copyDocument()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-copy mr-2"></i>
                        Copy to Clipboard
                    </button>
                    
                    <a href="data-collection.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg btn-scale">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Back to Data Collection
                    </a>
                </div>
            </div>

            <!-- FORMAL SIGNATURE / AUTHENTICATION BLOCK (PRINT ONLY) -->
            <div class="print-signature-block hidden print:flex">
                <div class="signature-col text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-8">Prepared By:</p>
                    <div class="signature-line border-t border-slate-900 pt-1">
                        <p class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($username ?? 'Policy Research Officer'); ?></p>
                        <p class="text-[10px] text-slate-600">Data Collection & Legal Processing Module</p>
                    </div>
                </div>
                <div class="signature-col text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-8">Verified & Approved By:</p>
                    <div class="signature-line border-t border-slate-900 pt-1">
                        <p class="text-xs font-bold text-slate-900">Head of Legislative Data Collection</p>
                        <p class="text-[10px] text-slate-600">City Government of San Jose Del Monte</p>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <footer class="mt-6 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
                <p class="mt-2 text-sm">Supporting Document Viewer - Data Collection Module</p>
            </footer>

        </main>
    </div>

    <script>
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

        function downloadDocument() {
            const content = document.querySelector('.doc-content').textContent;
            const title = document.querySelector('.text-xl.font-bold').textContent;
            const blob = new Blob([content], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = title + '.txt';
            link.click();
        }
        
        function copyDocument() {
            const content = document.querySelector('.doc-content').textContent;
            navigator.clipboard.writeText(content).then(() => {
                alert('Document copied to clipboard!');
            }).catch(() => {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = content;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Document copied to clipboard!');
            });
        }
    </script>

</body>
</html>
<?php
$conn->close();
?>