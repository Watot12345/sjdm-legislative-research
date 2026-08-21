<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "Policy Research and Analysis";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// ============================================
// GEMINI AI CONFIGURATION - Direct API from .env
// ============================================
require_once __DIR__ . '/../includes/gemini_helper.php';

// ============================================
// FUNCTION: Get Legal Citations by Category
// ============================================
function getLegalCitationsByCategory($category) {
    $prompt = "You are a legal research assistant specializing in Philippine laws and local ordinances of San Jose Del Monte, Bulacan.

Based on the policy category: " . $category . "

Provide a concise list of exactly 3 legal citations and ordinances relevant to this category. Organize them as follows:

**RELEVANT LAWS AND ORDINANCES (3 citations):**
1. Republic Act No. / Ordinance No. [number/title] ([year])
   - Key provisions: [brief summary]
   - Relevance to " . $category . ": [brief explanation]

2. [Second citation]
   - Key provisions: [brief summary]
   - Relevance to " . $category . ": [brief explanation]

3. [Third citation]
   - Key provisions: [brief summary]
   - Relevance to " . $category . ": [brief explanation]

**Format:** Keep each citation to strictly 1 sentence per provision and 1 sentence per relevance. Do not write introductory or concluding paragraphs. Keep total response under 200 words.

Return only the 3 citations, no additional text.";

    return callGeminiAPI($prompt, 0.3, 400);
}

// ============================================
// FUNCTION: Get Legal Citations by Category (AJAX)
// ============================================
if (isset($_GET['get_citations_by_category'])) {
    $category = $_GET['get_citations_by_category'];
    $result = getLegalCitationsByCategory($category);
    
    if ($result !== null) {
        echo json_encode(['success' => true, 'citations' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch citations']);
    }
    exit();
}

// ============================================
// HANDLE: Manual Policy Submission
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_manual_policy'])) {
    if (!canEditPolicy()) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to create policies.'];
        header("Location: policy-research.php");
        exit();
    }
    $policy_title = $conn->real_escape_string($_POST['policy_title']);
    $policy_issues = $conn->real_escape_string($_POST['policy_issues']);
    $policy_objectives = $conn->real_escape_string($_POST['policy_objectives']);
    $policy_category = $conn->real_escape_string($_POST['policy_category']);
    $policy_description = $conn->real_escape_string($_POST['policy_description']);
    $researcher = $conn->real_escape_string($_POST['researcher']);
    $legal_citations = $conn->real_escape_string($_POST['legal_citations'] ?? '');
    
    // Generate unique document ID
    $document_id = 'POL-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Extract keywords using Gemini
    $keywords = extractKeywords($policy_title, $policy_description, $policy_category);
    $escaped_keywords = $conn->real_escape_string($keywords);
    
    // Insert into database
    $insert_sql = "INSERT INTO policy_documents 
                   (document_id, title, description, category, status, 
                    keywords, issues, objectives, researcher, legal_citations,
                    upload_date) 
                   VALUES 
                   ('$document_id', '$policy_title', '$policy_description', '$policy_category', 'Pending',
                    '$escaped_keywords', '$policy_issues', '$policy_objectives', '$researcher', '$legal_citations',
                    NOW())";
    
    if ($conn->query($insert_sql) === TRUE) {
        // Log activity
        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                   VALUES ('$username', 'Added manual policy: $policy_title', '$document_id', 'Policy Research', NOW())";
        $conn->query($log_sql);
        
        header("Location: policy-research.php?manual_success=1&doc_id=" . urlencode($document_id));
        exit();
    } else {
        $error_msg = "Error adding policy: " . $conn->error;
    }
}

// ============================================
// HANDLE: Update Existing Policy
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_policy'])) {
    if (!canEditPolicy()) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to edit policies.'];
        header("Location: policy-research.php");
        exit();
    }
    $doc_id = $conn->real_escape_string($_POST['doc_id']);
    $policy_title = $conn->real_escape_string($_POST['policy_title']);
    $policy_issues = $conn->real_escape_string($_POST['policy_issues']);
    $policy_objectives = $conn->real_escape_string($_POST['policy_objectives']);
    $policy_category = $conn->real_escape_string($_POST['policy_category']);
    $policy_description = $conn->real_escape_string($_POST['policy_description']);
    $researcher = $conn->real_escape_string($_POST['researcher']);
    $legal_citations = $conn->real_escape_string($_POST['legal_citations'] ?? '');
    
    $update_sql = "UPDATE policy_documents SET 
                   title = '$policy_title',
                   description = '$policy_description',
                   category = '$policy_category',
                   issues = '$policy_issues',
                   objectives = '$policy_objectives',
                   researcher = '$researcher',
                   legal_citations = '$legal_citations',
                   updated_at = NOW()
                   WHERE document_id = '$doc_id'";
    
    if ($conn->query($update_sql) === TRUE) {
        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                   VALUES ('$username', 'Updated policy: $policy_title', '$doc_id', 'Policy Research', NOW())";
        $conn->query($log_sql);
        
        header("Location: policy-research.php?update_success=1&doc_id=" . urlencode($doc_id));
        exit();
    } else {
        $error_msg = "Error updating policy: " . $conn->error;
    }
}

// ============================================
// FUNCTION: Format Legal Analysis HTML (Strips Markdown Asterisks & Memo Fluff)
// ============================================
function formatLegalAnalysisHtml($text) {
    if (empty($text)) return '';
    
    // Remove raw memo headers (TO:, FROM:, SUBJECT:, DATE:) if present from old entries
    $text = preg_replace('/^\s*(TO|FROM|SUBJECT|DATE):[^\n]*\n?/mi', '', $text);
    $text = str_replace('---', '', $text);
    
    // Convert Markdown headers (### Section) into clean styled text
    $text = preg_replace('/^#{1,4}\s*(.*)$/m', '<strong class="text-blue-900 block text-base mt-3 mb-1 font-bold">$1</strong>', $text);
    
    // Convert bold markdown **text** to <strong>text</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-blue-950 font-semibold">$1</strong>', $text);
    
    // Convert bullet asterisks * or - to clean bullets
    $text = preg_replace('/^\s*[\*\-]\s+/m', '&bull; ', $text);
    
    // Strip residual asterisks
    $text = str_replace('*', '', $text);
    
    return nl2br(trim($text));
}

// ============================================
// FUNCTION: Extract Concise Legal Analysis & Findings
// ============================================
function analyzeLegalCitations($policy_title, $policy_description, $policy_category, $policy_issues, $policy_objectives, $keywords = '') {
    $prompt = "You are an expert Philippine legal counsel serving the City Government of San Jose Del Monte, Bulacan.

Provide a concise, focused, and professional Legal Analysis Findings Report for the following policy:

POLICY DETAILS:
- Title: " . $policy_title . "
- Category: " . $policy_category . "
- Issues Addressed: " . $policy_issues . "
- Objectives: " . $policy_objectives . "

OUTPUT INSTRUCTIONS:
1. DO NOT include memo headers (no TO, FROM, SUBJECT, DATE headers).
2. DO NOT use raw markdown formatting symbols like asterisks (* or **), hashtags (#), or brackets [Insert...].
3. Provide a clear 3-part analysis directly tailored to this policy:
   I. APPLICABLE PHILIPPINE LAWS & LOCAL ORDINANCE BASELINE: List 2-3 specific national statutes (e.g., RA 9003, RA 7160, RA 9275) and explain their direct application to this SJDM policy.
   II. LEGAL FEASIBILITY & CONSTITUTIONAL POLICE POWER: Analyze how local police power under the Local Government Code authorizes the City of San Jose Del Monte to enact these regulations, addressing any enforcement gaps.
   III. LEGISLATIVE & ENFORCEMENT RECOMMENDATIONS: Provide 2 specific actionable recommendations for the Sangguniang Panlungsod and city offices (CENRO/CSWMB).
4. Keep the entire response concise, sharp, and highly specific (under 250 words).";

    return callGeminiAPI($prompt, 0.4, 450);
}

// ============================================
// FUNCTION: Extract Keywords from Policy
// ============================================
function extractKeywords($title, $description, $category) {
    // 1. Local PHP Rule-Based Keyword Extraction (0 API Tokens)
    $text = strtolower($title . ' ' . $category . ' ' . $description);
    $stopwords = ['the', 'a', 'an', 'and', 'or', 'but', 'for', 'of', 'with', 'without', 'ang', 'ng', 'sa', 'na', 'at', 'ay', 'ito', 'may', 'mga', 'isang', 'para', 'to', 'from', 'by', 'on', 'in', 'this', 'that', 'policy', 'document', 'ordinance'];
    $words = str_word_count($text, 1);
    $filtered = array_filter($words, function($w) use ($stopwords) {
        return !in_array($w, $stopwords) && strlen($w) > 3;
    });
    if (!empty($filtered)) {
        $counts = array_count_values($filtered);
        arsort($counts);
        $topWords = array_keys(array_slice($counts, 0, 8));
        return implode(', ', array_map('ucfirst', $topWords));
    }

    // 2. Fallback to API call if local extraction yields empty
    $prompt = "Extract 5-10 key legal and policy keywords from the following policy document. Return only the keywords separated by commas, nothing else.

Title: " . $title . "
Category: " . $category . "
Description: " . $description . "

Keywords:";

    $result = callGeminiAPI($prompt, 0.3, 100);
    return $result ? trim($result) : '';
}

// ============================================
// FUNCTION: Search Legal References
// ============================================
function searchLegalReferences($search_term) {
    $prompt = "You are a legal researcher specializing in Philippine laws and San Jose Del Monte, Bulacan city ordinances.

Search for legal references related to: " . $search_term . "

Provide comprehensive results organized as follows:

---

**PHILIPPINE NATIONAL LAWS:**

**Republic Acts (RA):**
- RA No. [number] - [title] ([year])
  - Key provisions: [relevant sections]
  - How it relates to '" . $search_term . "': [explanation]

**Presidential Decrees (PD):**
- PD No. [number] - [title] ([year])
  - Key provisions: [relevant sections]
  - How it relates to '" . $search_term . "': [explanation]

**Executive Orders (EO):**
- EO No. [number] - [title] ([year])
  - Key provisions: [relevant sections]
  - How it relates to '" . $search_term . "': [explanation]

**Supreme Court Decisions:**
- [Case Name], G.R. No. [number], [date]
  - Doctrine: [key legal principle]
  - Relevance: [how it applies to the search]

---

**SAN JOSE DEL MONTE, BULACAN LOCAL ORDINANCES:**

**City Ordinances:**
- Ordinance No. [XX-XXXX] - [title]
  - Date: [approval date]
  - Key provisions: [relevant sections]
  - How it relates to '" . $search_term . "': [explanation]

**City Resolutions:**
- Resolution No. [XX-XXXX] - [title]
  - Date: [approval date]
  - Key provisions: [relevant sections]
  - How it relates to '" . $search_term . "': [explanation]

---

**SUMMARY OF FINDINGS:**
- Total national laws found: [number]
- Total SJDM ordinances found: [number]
- Key themes identified: [list themes]

Format the response with clear sections and bullet points. Use only plain text.";

    return callGeminiAPI($prompt, 0.5, 4096);
}

// ============================================
// HANDLE: Generate Legal Citations for a Document (Full Analysis)
// ============================================
if (isset($_GET['analyze_doc_id'])) {
    if (!canRunAI()) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'You do not have permission to run AI analysis.'];
        header("Location: policy-research.php");
        exit();
    }
    $doc_id = $_GET['analyze_doc_id'];
    
    $doc_sql = "SELECT * FROM policy_documents WHERE document_id = '$doc_id'";
    $doc_result = $conn->query($doc_sql);
    
    if ($doc_result && $doc_result->num_rows > 0) {
        $doc = $doc_result->fetch_assoc();
        
        if ($doc['legal_analysis_status'] == 'Completed') {
            header("Location: policy-research.php?already_analyzed=1&doc_id=" . urlencode($doc_id));
            exit();
        }
        
        $keywords = extractKeywords($doc['title'], $doc['description'], $doc['category']);
        if ($keywords) {
            $escaped_keywords = $conn->real_escape_string($keywords);
            $update_keywords = "UPDATE policy_documents SET keywords = '$escaped_keywords' WHERE document_id = '$doc_id'";
            $conn->query($update_keywords);
        }
        
        $analysis = analyzeLegalCitations(
            $doc['title'],
            $doc['description'],
            $doc['category'],
            $doc['issues'] ?? '',
            $doc['objectives'] ?? '',
            $keywords
        );
        
        if ($analysis !== null) {
            $escaped_analysis = $conn->real_escape_string($analysis);
            $update_sql = "UPDATE policy_documents SET 
                           legal_analysis = '$escaped_analysis',
                           legal_analysis_status = 'Completed',
                           legal_analysis_date = NOW(),
                           analyzed_by = '$username'
                           WHERE document_id = '$doc_id'";
            
            if ($conn->query($update_sql) === TRUE) {
                $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                           VALUES ('$username', 'Generated Legal Citations and Similar Ordinances', '$doc_id', 'Policy Research', NOW())";
                $conn->query($log_sql);
                
                header("Location: policy-research.php?analysis_success=1&doc_id=" . urlencode($doc_id));
                exit();
            }
        } else {
            header("Location: policy-research.php?analysis_error=1");
            exit();
        }
    }
}

// ============================================
// HANDLE: Delete Policy
// ============================================
if (isset($_GET['delete_doc_id'])) {
    if (!canDeletePolicy()) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Access Denied', 'message' => 'Only administrators can delete policy documents.'];
        header("Location: policy-research.php");
        exit();
    }
    $doc_id = $conn->real_escape_string($_GET['delete_doc_id']);
    $delete_sql = "DELETE FROM policy_documents WHERE document_id = '$doc_id'";
    if ($conn->query($delete_sql) === TRUE) {
        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                   VALUES ('$username', 'Deleted policy document', '$doc_id', 'Policy Research', NOW())";
        $conn->query($log_sql);
        header("Location: policy-research.php?delete_success=1");
        exit();
    }
}

// ============================================
// GET: All Policy Documents (Repository Search & 10-Item Pagination)
// ============================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
if (!empty($search_query)) {
    $escaped_search = $conn->real_escape_string($search_query);
    $where_clause = " WHERE title LIKE '%$escaped_search%' OR document_id LIKE '%$escaped_search%' OR category LIKE '%$escaped_search%' OR researcher LIKE '%$escaped_search%' OR keywords LIKE '%$escaped_search%' OR description LIKE '%$escaped_search%' ";
}

// Count total filtered documents for pagination calculation
$count_sql = "SELECT COUNT(*) as count FROM policy_documents" . $where_clause;
$filtered_total_docs = $conn->query($count_sql)->fetch_assoc()['count'];
$total_pages = max(1, ceil($filtered_total_docs / $limit));

$documents_sql = "SELECT * FROM policy_documents" . $where_clause . " ORDER BY upload_date DESC LIMIT $limit OFFSET $offset";
$documents = $conn->query($documents_sql);

$total_docs = $conn->query("SELECT COUNT(*) as count FROM policy_documents")->fetch_assoc()['count'];
$analyzed_docs = $conn->query("SELECT COUNT(*) as count FROM policy_documents WHERE legal_analysis_status = 'Completed'")->fetch_assoc()['count'];
$pending_analysis = $conn->query("SELECT COUNT(*) as count FROM policy_documents WHERE legal_analysis_status = 'Pending' OR legal_analysis_status IS NULL")->fetch_assoc()['count'];

$gemini_status = "Unknown";
$test_response = callGeminiAPI("ping");

if ($test_response !== null) {
    $gemini_status = "Connected";
} else {
    $gemini_status = "Disconnected";
}

// Get selected document for editing/viewing
$selected_doc_id = isset($_GET['doc_id']) ? $_GET['doc_id'] : (isset($_SESSION['selected_doc_id']) ? $_SESSION['selected_doc_id'] : '');
$selected_doc = null;
$show_analysis = false;
$edit_mode = isset($_GET['edit']) ? true : false;

if (!empty($selected_doc_id)) {
    $doc_sql = "SELECT * FROM policy_documents WHERE document_id = '$selected_doc_id'";
    $doc_result = $conn->query($doc_sql);
    if ($doc_result && $doc_result->num_rows > 0) {
        $selected_doc = $doc_result->fetch_assoc();
        $show_analysis = true;
    }
}

if (isset($_SESSION['selected_doc_id'])) {
    unset($_SESSION['selected_doc_id']);
}

// Get categories for dropdown
$categories = ['Education', 'Health', 'Agriculture', 'Environment', 'Infrastructure', 'Social Welfare', 
               'Economic Development', 'Peace and Order', 'Human Rights', 'Labor and Employment', 
               'Local Governance', 'Urban Development', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Research and Analysis</title>
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
        
        .gemini-badge {
            background: linear-gradient(135deg, #4285f4, #34a853, #fbbc04, #ea4335);
            color: white;
        }
        
        .legal-badge {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
        }
        
        .bridge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .bridge-status.online {
            background: #dcfce7;
            color: #166534;
        }
        
        .bridge-status.offline {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #d1fae5; color: #065f46; }
        .status-badge.selected { background: #dbeafe; color: #1e40af; }
        
        .analysis-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e5e7eb;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .analysis-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .analysis-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .analysis-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .analysis-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .keyword-tag {
            display: inline-block;
            padding: 3px 10px;
            background: #ede9fe;
            color: #5b21b6;
            border-radius: 12px;
            font-size: 12px;
            margin: 2px 4px 2px 0;
        }
        
        .manual-form-section {
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 2px solid #e0e7ff;
        }
        
        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            display: block;
        }
        
        .form-label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        
        .form-control {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control.textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .char-count {
            font-size: 12px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
        
        .main-form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .form-header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            border-radius: 16px 16px 0 0;
            padding: 24px 32px;
        }
        
        .quick-action-btn {
            transition: all 0.3s ease;
            border: 2px dashed #e2e8f0;
        }
        
        .quick-action-btn:hover {
            border-color: #3b82f6;
            background: #f0f7ff;
        }
        
        .doc-card {
            transition: all 0.3s ease;
            border-left: 4px solid #3b82f6;
        }
        
        .doc-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .doc-card.analyzed {
            border-left-color: #16a34a;
        }
        
        .doc-card.pending {
            border-left-color: #f59e0b;
        }
        
        .section-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
        }
        
        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        
        .section-divider-text {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            padding: 0 8px;
        }
        
        /* Citations container styles */
        .citations-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 16px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        
        .citations-container.loading {
            display: block;
            text-align: center;
            padding: 40px 20px;
        }
        
        .citations-container.loading .spinner {
            border: 4px solid #e2e8f0;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .citations-container.visible {
            display: block;
        }
        
        .citation-item {
            padding: 12px 16px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s ease;
        }
        
        .citation-item:hover {
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .citation-item.national {
            border-left-color: #1e3a8a;
        }
        
        .citation-item.local {
            border-left-color: #16a34a;
        }
        
        .citation-number {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
        }
        
        .citation-title {
            font-weight: 600;
            color: #0f172a;
        }
        
        .citation-detail {
            font-size: 13px;
            color: #475569;
            margin-top: 4px;
        }
        
        .citation-section-header {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
            padding: 8px 0 12px 0;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
        }
        
        .citation-section-header i {
            margin-right: 8px;
        }
        
        .citations-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 6px;
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
        
        <main class="p-8">
            
            <!-- Toast Notifications -->
            <?php if (isset($_GET['analysis_success'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Legal citations and similar ordinances generated successfully!
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($_GET['manual_success'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Policy added successfully! You can now analyze it.
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($_GET['update_success'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Policy updated successfully!
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($_GET['delete_success'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Policy deleted successfully!
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($_GET['analysis_error'])): ?>
                <div class="toast toast-error" id="toast">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    Error generating legal citations. Please try again.
                </div>
                <script>setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 5000);</script>
            <?php endif; ?>
            
            <?php if (isset($_GET['already_analyzed'])): ?>
                <div class="toast toast-warning" id="toast">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    This document has already been analyzed.
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
                        <i class="fa-solid fa-gavel text-blue-700 mr-2"></i>
                        Policy Research and Analysis
                    </h2>
                    <p class="text-slate-500 mt-2">
                        Manually input policy details for AI-powered legal research and analysis.
                    </p>
                    <div class="mt-2 flex items-center gap-4 flex-wrap">
                        <span class="bridge-status <?php echo $gemini_status == 'Connected' ? 'online' : 'offline'; ?>">
                            <i class="fa-solid fa-circle mr-1" style="font-size: 8px;"></i>
                            Gemini API: <?php echo $gemini_status; ?>
                        </span>
                        <span class="legal-badge px-3 py-1 rounded-full text-sm">
                            <i class="fa-solid fa-scale-balanced mr-1"></i> Legal Research
                        </span>
                        <span class="text-sm text-purple-600">
                            <i class="fa-solid fa-robot mr-1"></i>
                            AI Engine: Active
                        </span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <?php if (canEditPolicy()): ?>
                    <a href="?create=1" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2.5 rounded-lg shadow btn-scale font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        Create New Policy
                    </a>
                    <?php endif; ?>
                    <a href="data-collection.php" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow btn-scale font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-arrow-right"></i>
                        Data Collection
                    </a>
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Total Policies</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_docs; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fa-solid fa-file-lines text-blue-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 mt-4">In Repository</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Legal Analysis</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $analyzed_docs; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fa-solid fa-scale-balanced text-green-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-green-600 mt-4">Citations Found</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Pending Analysis</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $pending_analysis; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-yellow-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-yellow-600 mt-4">Needs Review</p>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- ============================================ -->
            <!-- CREATE / EDIT POLICY MODAL POPUP -->
            <!-- ============================================ -->
            <?php if (canEditPolicy() && (isset($_GET['create']) || ($edit_mode && $selected_doc))): ?>
            <div id="policyCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 md:p-6 overflow-y-auto animate-fade-in no-print">
                <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto flex flex-col max-h-[90vh]">

                    <!-- MODAL HEADER -->
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-900 to-blue-700 text-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-yellow-300">
                                <i class="fa-solid fa-pen-to-square text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold leading-tight">
                                    <?php echo ($edit_mode && $selected_doc) ? 'Edit Policy Document' : 'Create New Policy Document'; ?>
                                </h2>
                                <p class="text-xs text-blue-100 mt-0.5">
                                    <?php echo ($edit_mode && $selected_doc) ? 'Update policy parameters and legal citations below.' : 'Enter policy parameters for AI-powered legal research and analysis.'; ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <?php if ($edit_mode && $selected_doc): ?>
                                <a href="policy-research.php?doc_id=<?php echo $selected_doc['document_id']; ?>" 
                                   class="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                                    <i class="fa-solid fa-eye"></i> View Analysis
                                </a>
                            <?php endif; ?>
                            <button onclick="closePolicyModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition focus:outline-none">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- MODAL BODY FORM -->
                    <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-800">
                        <form method="POST" action="" id="policyForm">
                            <?php if ($edit_mode && $selected_doc): ?>
                                <input type="hidden" name="update_policy" value="1">
                                <input type="hidden" name="doc_id" value="<?php echo $selected_doc['document_id']; ?>">
                            <?php else: ?>
                                <input type="hidden" name="submit_manual_policy" value="1">
                            <?php endif; ?>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div>
                                    <!-- Policy Title -->
                                    <div class="mb-4">
                                        <label class="form-label" for="policy_title">
                                            <i class="fa-solid fa-heading text-blue-600 mr-1"></i>
                                            Policy Title <span class="required">*</span>
                                        </label>
                                        <input type="text" name="policy_title" id="policy_title" 
                                               class="form-control" placeholder="Enter the full policy title" 
                                               required maxlength="255"
                                               value="<?php echo ($selected_doc && $edit_mode) ? htmlspecialchars($selected_doc['title']) : ''; ?>">
                                        <div class="char-count"><span id="titleCount"><?php echo ($selected_doc && $edit_mode) ? strlen($selected_doc['title']) : 0; ?></span>/255</div>
                                    </div>
                                    
                                    <!-- Policy Category -->
                                    <div class="mb-4">
                                        <label class="form-label" for="policy_category">
                                            <i class="fa-solid fa-tag text-blue-600 mr-1"></i>
                                            Policy Category <span class="required">*</span>
                                        </label>
                                        <select name="policy_category" id="policy_category" class="form-control" required>
                                            <option value="">Select Category</option>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>" 
                                                    <?php echo ($selected_doc && $edit_mode && $selected_doc['category'] == $cat) ? 'selected' : ''; ?>>
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Researcher -->
                                    <div class="mb-4">
                                        <label class="form-label" for="researcher">
                                            <i class="fa-solid fa-user text-blue-600 mr-1"></i>
                                            Researcher Name
                                        </label>
                                        <input type="text" name="researcher" id="researcher" 
                                               class="form-control" placeholder="Enter researcher name" 
                                               value="<?php echo ($selected_doc && $edit_mode) ? htmlspecialchars($selected_doc['researcher']) : htmlspecialchars($username); ?>">
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div>
                                    <!-- Policy Issues -->
                                    <div class="mb-4">
                                        <label class="form-label" for="policy_issues">
                                            <i class="fa-solid fa-circle-exclamation text-orange-600 mr-1"></i>
                                            Issues Addressed <span class="required">*</span>
                                        </label>
                                        <textarea name="policy_issues" id="policy_issues" 
                                                  class="form-control textarea" 
                                                  placeholder="List the key issues this policy addresses..."
                                                  required><?php echo ($selected_doc && $edit_mode) ? htmlspecialchars($selected_doc['issues']) : ''; ?></textarea>
                                        <div class="char-count"><span id="issuesCount"><?php echo ($selected_doc && $edit_mode) ? strlen($selected_doc['issues']) : 0; ?></span> characters</div>
                                    </div>
                                    
                                    <!-- Policy Objectives -->
                                    <div class="mb-4">
                                        <label class="form-label" for="policy_objectives">
                                            <i class="fa-solid fa-bullseye text-green-600 mr-1"></i>
                                            Policy Objectives <span class="required">*</span>
                                        </label>
                                        <textarea name="policy_objectives" id="policy_objectives" 
                                                  class="form-control textarea" 
                                                  placeholder="What are the main objectives of this policy?"
                                                  required><?php echo ($selected_doc && $edit_mode) ? htmlspecialchars($selected_doc['objectives']) : ''; ?></textarea>
                                        <div class="char-count"><span id="objectivesCount"><?php echo ($selected_doc && $edit_mode) ? strlen($selected_doc['objectives']) : 0; ?></span> characters</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Policy Description (Full width) -->
                            <div class="mb-4">
                                <label class="form-label" for="policy_description">
                                    <i class="fa-solid fa-align-left text-purple-600 mr-1"></i>
                                    Policy Description
                                </label>
                                <textarea name="policy_description" id="policy_description" 
                                          class="form-control textarea" 
                                          placeholder="Provide a detailed description of the policy, its scope, and intended outcomes..."
                                          style="min-height: 100px;"><?php echo ($selected_doc && $edit_mode) ? htmlspecialchars($selected_doc['description']) : ''; ?></textarea>
                                <div class="char-count"><span id="descriptionCount"><?php echo ($selected_doc && $edit_mode) ? strlen($selected_doc['description']) : 0; ?></span> characters</div>
                            </div>

                            <!-- LEGAL CITATIONS SECTION -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between">
                                    <label class="form-label" for="legal_citations">
                                        <i class="fa-solid fa-scale-balanced text-indigo-600 mr-1"></i>
                                        3 Legal Citations & Similar Ordinances
                                        <span class="text-xs font-normal text-slate-400 ml-2">
                                            (Auto-generated based on category)
                                        </span>
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="button" id="refreshCitations" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1">
                                            <i class="fa-solid fa-rotate"></i> Refresh
                                        </button>
                                        <button type="button" id="toggleCitations" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1">
                                            <i class="fa-solid fa-chevron-down"></i> Show/Hide
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="citationsContainer" class="citations-container">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-slate-600">
                                            <i class="fa-regular fa-file-lines mr-1"></i> 
                                            <span id="citationCount">0</span> citations found
                                        </span>
                                        <span class="text-xs text-slate-400">
                                            <i class="fa-regular fa-clock mr-1"></i> 
                                            <span id="citationTime">--</span>
                                        </span>
                                    </div>
                                    <div id="citationContent">
                                        <!-- Citations will be loaded here -->
                                    </div>
                                </div>
                                
                                <textarea name="legal_citations" id="legal_citations" style="display:none;"></textarea>
                            </div>
                            
                            <!-- MODAL FOOTER BUTTONS -->
                            <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t justify-between items-center">
                                <div class="flex gap-3">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg text-xs font-semibold transition flex items-center gap-2 shadow-sm">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        <?php echo ($edit_mode && $selected_doc) ? 'Update Policy' : 'Save Policy Document'; ?>
                                    </button>
                                    <button type="reset" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-2.5 rounded-lg text-xs font-semibold transition">
                                        Clear Form
                                    </button>
                                </div>

                                <div class="flex gap-2">
                                    <?php if ($edit_mode && $selected_doc && canDeletePolicy()): ?>
                                        <button type="button" 
                                                onclick="openConfirmModal('Delete Policy', 'Are you sure you want to delete this policy document? This action cannot be undone.', 'policy-research.php?delete_doc_id=<?php echo $selected_doc['document_id']; ?>', 'bg-red-600 hover:bg-red-700', 'bg-gradient-to-r from-red-900 to-red-700')"
                                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="closePolicyModal()" class="bg-slate-500 hover:bg-slate-600 text-white px-5 py-2.5 rounded-lg text-xs font-semibold transition">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- QUICK ACTIONS - AI Analysis -->
            <?php if ($selected_doc && $show_analysis): ?>
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl border border-purple-200 p-6 mb-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h4 class="font-bold text-slate-800">
                            <i class="fa-solid fa-robot text-purple-600 mr-2"></i>
                            AI-Powered Legal Analysis
                        </h4>
                        <p class="text-sm text-slate-600 mt-1">
                            <strong><?php echo htmlspecialchars($selected_doc['title']); ?></strong>
                            <?php if ($selected_doc['legal_analysis_status'] == 'Completed'): ?>
                                <span class="text-green-600 ml-2">
                                    <i class="fa-solid fa-check-circle"></i> Analysis Completed
                                </span>
                            <?php else: ?>
                                <span class="text-yellow-600 ml-2">
                                    <i class="fa-solid fa-clock"></i> Pending Analysis
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <?php if ($selected_doc['legal_analysis_status'] != 'Completed' && canRunAI()): ?>
                            <a href="?analyze_doc_id=<?php echo $selected_doc['document_id']; ?>" 
                               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg btn-scale flex items-center gap-2">
                                <i class="fa-solid fa-gavel"></i>
                                Generate Legal Citations
                            </a>
                        <?php endif; ?>
                        <?php if (canUploadData()): ?>
                        <a href="data-collection.php?doc_id=<?php echo $selected_doc['document_id']; ?>&title=<?php echo urlencode($selected_doc['title']); ?>&description=<?php echo urlencode($selected_doc['description']); ?>&category=<?php echo urlencode($selected_doc['category']); ?>" 
                           class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg btn-scale flex items-center gap-2">
                            <i class="fa-solid fa-arrow-right"></i>
                            Submit to Data Collection
                        </a>
                        <?php endif; ?>
                        <?php if (canEditPolicy()): ?>
                        <a href="policy-research.php?edit=1&doc_id=<?php echo $selected_doc['document_id']; ?>" 
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg btn-scale flex items-center gap-2">
                            <i class="fa-solid fa-pen"></i>
                            Edit Policy
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>



            <!-- ============================================ -->
            <!-- POLICY DOCUMENTS LIST (Secondary Section) -->
            <!-- ============================================ -->
            <div class="bg-white rounded-xl shadow mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between px-6 py-5 border-b gap-4">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <i class="fa-solid fa-folder-open text-blue-700"></i>
                            Policy Repository
                        </h2>
                        <p class="text-slate-500 text-sm mt-0.5">Search, filter, and manage saved policy documents</p>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Live Repository Search Bar -->
                        <form method="GET" action="policy-research.php" class="relative flex items-center">
                            <input type="text" name="search" id="repoSearchInput" placeholder="Search repository..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   class="w-64 border border-slate-300 rounded-lg pl-9 pr-8 py-1.5 text-xs focus:ring-2 focus:ring-blue-700 outline-none transition">
                            <i class="fa-solid fa-search absolute left-3 text-slate-400 text-xs"></i>
                            <?php if (!empty($search_query)): ?>
                                <a href="policy-research.php" class="absolute right-2.5 text-slate-400 hover:text-slate-600 text-xs" title="Clear search">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </form>

                        <a href="?create=1" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-xs font-semibold btn-scale flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-plus"></i> Create New Policy
                        </a>
                        <span class="bg-blue-100 text-blue-700 px-3 py-1.5 rounded-full text-xs font-semibold">
                            <i class="fa-solid fa-file-lines mr-1"></i> <?php echo $filtered_total_docs; ?> Policies
                        </span>
                    </div>
                </div>

                <div class="p-4">
                    <?php if ($documents && $documents->num_rows > 0): ?>
                        <div class="grid gap-3">
                            <?php while($doc = $documents->fetch_assoc()): 
                                $is_analyzed = ($doc['legal_analysis_status'] == 'Completed');
                                $is_selected = ($selected_doc && $selected_doc['document_id'] == $doc['document_id']);
                            ?>
                                <div class="doc-card <?php echo $is_analyzed ? 'analyzed' : 'pending'; ?> <?php echo $is_selected ? 'bg-blue-50 border border-blue-200' : 'bg-white border border-slate-200'; ?> rounded-lg p-4 hover:shadow-md transition-all">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full <?php echo $is_analyzed ? 'bg-green-100' : 'bg-yellow-100'; ?> flex items-center justify-center">
                                                <i class="fa-solid <?php echo $is_analyzed ? 'fa-check-circle text-green-600' : 'fa-clock text-yellow-600'; ?>"></i>
                                            </div>
                                            <div>
                                                <a href="?doc_id=<?php echo $doc['document_id']; ?>" class="font-semibold text-slate-800 hover:text-blue-700 transition">
                                                    <?php echo htmlspecialchars($doc['title']); ?>
                                                </a>
                                                <div class="flex items-center gap-3 text-sm text-slate-500">
                                                    <span><i class="fa-regular fa-folder-open mr-1"></i> <?php echo $doc['category']; ?></span>
                                                    <span><i class="fa-regular fa-calendar mr-1"></i> <?php echo date('M j, Y', strtotime($doc['upload_date'])); ?></span>
                                                    <?php if ($doc['keywords']): ?>
                                                        <span class="hidden sm:inline">
                                                            <?php 
                                                            $keywords = explode(',', $doc['keywords']);
                                                            $display = array_slice($keywords, 0, 3);
                                                            foreach($display as $keyword) {
                                                                echo '<span class="keyword-tag">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                            }
                                                            if (count($keywords) > 3) {
                                                                echo '<span class="text-xs text-slate-400">+'. (count($keywords) - 3) .' more</span>';
                                                            }
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="status-badge <?php echo $is_analyzed ? 'completed' : 'pending'; ?>">
                                                <?php echo $is_analyzed ? 'Analyzed' : 'Pending'; ?>
                                            </span>
                                            <a href="?doc_id=<?php echo $doc['document_id']; ?>" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                               title="View Legal Analysis">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <?php if (canEditPolicy()): ?>
                                            <a href="policy-research.php?edit=1&doc_id=<?php echo $doc['document_id']; ?>" 
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                               title="Edit Policy">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (!$is_analyzed && canRunAI()): ?>
                                                <a href="?analyze_doc_id=<?php echo $doc['document_id']; ?>" 
                                                   class="bg-purple-600 hover:bg-purple-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                                   title="Generate Legal Citations">
                                                    <i class="fa-solid fa-gavel"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (canUploadData()): ?>
                                            <a href="data-collection.php?doc_id=<?php echo $doc['document_id']; ?>&title=<?php echo urlencode($doc['title']); ?>&description=<?php echo urlencode($doc['description']); ?>&category=<?php echo urlencode($doc['category']); ?>" 
                                               class="bg-green-600 hover:bg-green-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm"
                                               title="Submit to Data Collection">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-slate-500">
                            <i class="fa-solid fa-inbox text-5xl block mb-4 text-slate-300"></i>
                            <p class="font-semibold text-slate-700">No policy documents found.</p>
                            <p class="text-xs mt-1 text-slate-500">
                                <?php echo !empty($search_query) ? 'Try searching for another keyword or clear the search filter.' : 'Click "+ Create New Policy" to add your first policy proposal.'; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PAGINATION CONTROLS (Max 10 per page) -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-600">
                    <div class="font-medium text-slate-600">
                        Showing <span class="font-semibold text-slate-800"><?php echo $filtered_total_docs > 0 ? ($offset + 1) : 0; ?></span> to 
                        <span class="font-semibold text-slate-800"><?php echo min($offset + $limit, $filtered_total_docs); ?></span> of 
                        <span class="font-semibold text-slate-800"><?php echo $filtered_total_docs; ?></span> entries
                        <?php if (!empty($search_query)): ?>
                            (filtered for "<span class="text-blue-700 font-medium"><?php echo htmlspecialchars($search_query); ?></span>")
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="policy-research.php?page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-medium transition shadow-sm flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <span class="px-3.5 py-1.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 font-medium cursor-not-allowed flex items-center gap-1 opacity-60">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="policy-research.php?page=<?php echo $p; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="w-8 h-8 rounded-lg flex items-center justify-center font-semibold text-xs transition shadow-sm <?php echo ($p == $page) ? 'bg-blue-800 text-white shadow-blue-900/20' : 'bg-white border border-slate-300 hover:bg-slate-100 text-slate-700'; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <a href="policy-research.php?page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-medium transition shadow-sm flex items-center gap-1">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3.5 py-1.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 font-medium cursor-not-allowed flex items-center gap-1 opacity-60">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- DETAILED VIEW WITH ANALYSIS (When a doc is selected) -->
            <!-- ============================================ -->
            <!-- ============================================ -->
            <!-- DETAILED VIEW & LEGAL ANALYSIS MODAL POPUP -->
            <!-- ============================================ -->
            <?php if ($selected_doc && $show_analysis && !$edit_mode): ?>
            <div id="policyViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 md:p-6 overflow-y-auto animate-fade-in">
                <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto flex flex-col max-h-[90vh]">

                    <!-- MODAL HEADER (SCREEN ONLY) -->
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-900 to-blue-700 text-white flex justify-between items-center shrink-0 no-print">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-yellow-300">
                                <i class="fa-solid fa-scale-balanced text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold leading-tight flex items-center gap-2">
                                    <?php echo htmlspecialchars($selected_doc['title']); ?>
                                </h2>
                                <p class="text-xs text-blue-100 mt-0.5 flex items-center gap-2">
                                    Reference No: <span class="font-mono text-yellow-200 font-semibold"><?php echo htmlspecialchars($selected_doc['document_id']); ?></span>
                                    &bull; Category: <span class="font-semibold text-white"><?php echo htmlspecialchars($selected_doc['category']); ?></span>
                                    &bull; Date: <span class="font-medium text-blue-100"><?php echo date('M j, Y', strtotime($selected_doc['upload_date'] ?? 'now')); ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 no-print">
                            <button onclick="closePolicyModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition focus:outline-none" title="Close Modal">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- MODAL SCROLLABLE BODY -->
                    <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-800">

                        <style>
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
  main > div:not(#policyViewModal):not(#printableLegalAnalysis),
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
  #policyViewModal,
  #policyViewModal > div,
  #printableLegalAnalysis,
  .modal-content,
  .modal-body,
  .doc-content,
  .legislative-report-body,
  [class*="max-h-"],
  [class*="overflow-y"],
  [class*="overflow-auto"] {
    position: static !important;
    inset: auto !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
    max-height: none !important;
    height: auto !important;
    overflow: visible !important;
    overflow-y: visible !important;
    overflow-x: visible !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    background: transparent !important;
    background-color: transparent !important;
    box-shadow: none !important;
    border: none !important;
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
  .modal-header .close, .modal-close, [data-dismiss="modal"],
  input[type="radio"], input[type="checkbox"],
  form .submit-row {
    display: none !important;
  }

  .card, .panel, .kpi-card, .metric-card, .legal-document-card, .report-box, .bg-slate-50, .bg-white, .bg-blue-50, [class*="bg-"] {
    box-shadow: none !important;
    border-radius: 0 !important;
    background: transparent !important;
    background-color: transparent !important;
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

                        <div id="printableLegalAnalysis" class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                            <!-- FORMAL HEADER FOR PRINT ONLY -->
                            <div class="print-header hidden print:block text-center border-b-2 border-slate-900 pb-3 mb-4">
                                <img src="../City.jpg" alt="City Seal" class="w-16 h-16 rounded-full object-cover border border-slate-400 mx-auto mb-2">
                                <h1 class="text-xl font-bold uppercase tracking-wide text-slate-900 font-serif">City Government of San Jose Del Monte</h1>
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 font-serif">Office of the Sangguniang Panlungsod &bull; Legislative Research</h2>
                                <p class="text-xs text-slate-600 font-serif mt-1">Official Legal Research & Policy Analysis Report</p>
                            </div>

                            <!-- FOREGROUND CONTENT LAYER -->
                            <div class="relative z-10 space-y-4">

                            <!-- Document Title & Reference Header (AT THE VERY TOP) -->
                            <div class="border-b pb-4 mb-4 text-center print:text-left">
                                <h2 class="text-xl font-bold text-slate-900 font-serif mb-1"><?php echo htmlspecialchars($selected_doc['title']); ?></h2>
                                <p class="text-xs text-slate-600 font-mono">
                                    Reference No: <span class="font-bold text-indigo-700"><?php echo htmlspecialchars($selected_doc['document_id']); ?></span>
                                    &bull; Category: <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($selected_doc['category']); ?></span>
                                    &bull; Date: <span class="font-medium text-slate-700"><?php echo date('M j, Y', strtotime($selected_doc['upload_date'] ?? 'now')); ?></span>
                                </p>
                            </div>

                            <!-- Document Information & Analysis Status Grid -->
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <h3 class="font-semibold text-slate-700 mb-2 text-xs uppercase tracking-wider">Document Information</h3>
                                    <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 text-xs space-y-1 font-serif print:bg-transparent">
                                        <p><strong>ID:</strong> <?php echo $selected_doc['document_id']; ?></p>
                                        <p><strong>Title:</strong> <?php echo htmlspecialchars($selected_doc['title']); ?></p>
                                        <p><strong>Category:</strong> <?php echo $selected_doc['category']; ?></p>
                                        <p><strong>Status:</strong> <?php echo $selected_doc['status']; ?></p>
                                        <p><strong>Uploaded:</strong> <?php echo date('F j, Y', strtotime($selected_doc['upload_date'])); ?></p>
                                        <?php if ($selected_doc['researcher']): ?>
                                            <p><strong>Researcher:</strong> <?php echo htmlspecialchars($selected_doc['researcher']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-700 mb-2 text-xs uppercase tracking-wider">Analysis Status</h3>
                                    <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 text-xs space-y-1 font-serif print:bg-transparent">
                                        <p><strong>Legal Analysis:</strong> 
                                            <span class="<?php echo $selected_doc['legal_analysis_status'] == 'Completed' ? 'text-green-600 font-semibold' : 'text-yellow-600 font-semibold'; ?>">
                                                <?php echo $selected_doc['legal_analysis_status'] ?? 'Pending'; ?>
                                            </span>
                                        </p>
                                        <?php if ($selected_doc['legal_analysis_date']): ?>
                                            <p><strong>Analyzed:</strong> <?php echo date('F j, Y h:i A', strtotime($selected_doc['legal_analysis_date'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($selected_doc['analyzed_by']): ?>
                                            <p><strong>Analyzed By:</strong> <?php echo htmlspecialchars($selected_doc['analyzed_by']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($selected_doc['keywords']): ?>
                                            <p class="mt-1"><strong>Keywords:</strong></p>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <?php 
                                                $keywords = explode(',', $selected_doc['keywords']);
                                                foreach($keywords as $keyword) {
                                                    echo '<span class="keyword-tag">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Issues Addressed & Policy Objectives Grid -->
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php if ($selected_doc['issues']): ?>
                                <div class="bg-orange-50/40 p-3.5 rounded-lg border border-orange-200 print:bg-transparent">
                                    <h3 class="font-semibold text-orange-900 mb-1 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-orange-600 no-print"></i>
                                        Issues Addressed
                                    </h3>
                                    <p class="text-slate-800 text-xs leading-relaxed font-serif"><?php echo nl2br(htmlspecialchars($selected_doc['issues'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($selected_doc['objectives']): ?>
                                <div class="bg-green-50/40 p-3.5 rounded-lg border border-green-200 print:bg-transparent">
                                    <h3 class="font-semibold text-green-900 mb-1 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fa-solid fa-bullseye text-green-600 no-print"></i>
                                        Policy Objectives
                                    </h3>
                                    <p class="text-slate-800 text-xs leading-relaxed font-serif"><?php echo nl2br(htmlspecialchars($selected_doc['objectives'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Saved Legal Citations -->
                            <?php if ($selected_doc['legal_citations']): ?>
                            <div class="legal-document-card bg-white p-4 rounded-lg border border-slate-300 shadow-sm print:border-slate-400 print:shadow-none">
                                <h3 class="text-sm font-bold text-slate-900 font-serif mb-2 pb-1 border-b border-slate-200 flex items-center gap-2">
                                    <i class="fa-solid fa-scale-balanced text-indigo-700 no-print"></i>
                                    Relevant Legal Citations & Ordinances
                                </h3>
                                <div class="text-xs leading-relaxed text-slate-800 font-serif">
                                    <?php echo nl2br(htmlspecialchars($selected_doc['legal_citations'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Full Legal Analysis Report -->
                            <?php if ($selected_doc['legal_analysis']): ?>
                            <div class="legal-document-card bg-white p-4 rounded-lg border border-slate-300 shadow-sm print:border-slate-400 print:shadow-none">
                                <h3 class="text-sm font-bold text-slate-900 font-serif mb-2 pb-1 border-b border-slate-200 flex items-center gap-2">
                                    <i class="fa-solid fa-gavel text-indigo-700 no-print"></i>
                                    Comprehensive Legal Analysis Findings
                                </h3>
                                <div class="text-xs leading-relaxed text-slate-800 font-serif">
                                    <?php echo formatLegalAnalysisHtml($selected_doc['legal_analysis']); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Description -->
                            <?php if ($selected_doc['description']): ?>
                            <div class="legal-document-card bg-white p-4 rounded-lg border border-slate-300 shadow-sm print:border-slate-400 print:shadow-none">
                                <h3 class="text-sm font-bold text-slate-900 font-serif mb-2 pb-1 border-b border-slate-200">
                                    Policy Description
                                </h3>
                                <p class="text-xs leading-relaxed text-slate-800 font-serif"><?php echo nl2br(htmlspecialchars($selected_doc['description'])); ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- FORMAL SIGNATURE / AUTHENTICATION BLOCK (PRINT ONLY) -->
                            <div class="print-signature-block hidden print:flex pt-4 mt-6 border-t border-slate-400">
                                <div class="signature-col text-center">
                                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-6">Prepared / Researched By:</p>
                                    <div class="signature-line border-t border-slate-900 pt-1">
                                        <p class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($selected_doc['researcher'] ?? $username); ?></p>
                                        <p class="text-[10px] text-slate-600">Legislative Research Division</p>
                                    </div>
                                </div>
                                <div class="signature-col text-center">
                                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-6">Verified & Approved By:</p>
                                    <div class="signature-line border-t border-slate-900 pt-1">
                                        <p class="text-xs font-bold text-slate-900">Head of Legislative Research & Evaluation</p>
                                        <p class="text-[10px] text-slate-600">City Government of San Jose Del Monte</p>
                                    </div>
                                </div>
                            </div>
                            </div><!-- End Foreground Content Layer -->
                        </div>
                    </div>

                    <!-- MODAL FOOTER ACTION BUTTONS -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center shrink-0 flex-wrap gap-3 no-print">
                        <div class="flex gap-2">
                            <?php if ($selected_doc['legal_analysis_status'] != 'Completed' && canRunAI()): ?>
                                <a href="?analyze_doc_id=<?php echo $selected_doc['document_id']; ?>" 
                                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-gavel"></i>
                                    Generate Full Legal Analysis
                                </a>
                            <?php endif; ?>
                            
                            <?php if (canUploadData()): ?>
                            <a href="data-collection.php?doc_id=<?php echo $selected_doc['document_id']; ?>&title=<?php echo urlencode($selected_doc['title']); ?>&description=<?php echo urlencode($selected_doc['description']); ?>&category=<?php echo urlencode($selected_doc['category']); ?>" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-arrow-right"></i>
                                Submit for Data Collection
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-2 items-center flex-wrap">
                            <div class="flex items-center gap-1.5 bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs shadow-sm">
                                <i class="fa-solid fa-file-lines text-blue-700"></i>
                                <label for="paperSizeSelect" class="text-slate-600 font-medium">Paper Size:</label>
                                <select id="paperSizeSelect" onchange="setPrintPaperSize(this.value)" class="bg-transparent font-bold text-slate-800 focus:outline-none cursor-pointer">
                                    <option value="A4" selected>A4 Standard</option>
                                    <option value="letter">Short (Letter 8.5" × 11")</option>
                                    <option value="legal">Long (Legal 8.5" × 14")</option>
                                    <option value="folio">Folio / Oficio (8.5" × 13")</option>
                                </select>
                            </div>
                            <button onclick="window.print()" class="bg-blue-800 hover:bg-blue-900 text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-print"></i>
                                Print Legal Analysis
                            </button>
                            <button onclick="closePolicyModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg text-xs font-semibold transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FOOTER -->
            <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
                <p class="mt-2">Policy Research Module with <strong>Google Gemini AI</strong>-Powered Legal Analysis</p>
            </footer>

        </main>
    </div>

    <script>
        // ============================================
        // DYNAMIC PRINT PAPER SIZE SELECTOR (A4, Short, Long, Folio)
        // ============================================
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

        // ============================================
        // LEGAL CITATIONS - AUTO-GENERATE ON CATEGORY CHANGE
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('policy_category');
            const citationsContainer = document.getElementById('citationsContainer');
            const citationContent = document.getElementById('citationContent');
            const citationCount = document.getElementById('citationCount');
            const citationTime = document.getElementById('citationTime');
            const toggleBtn = document.getElementById('toggleCitations');
            const refreshBtn = document.getElementById('refreshCitations');
            const legalCitationsTextarea = document.getElementById('legal_citations');
            
            let isVisible = false;
            let currentCategory = '';
            
            // Function to fetch citations
            function fetchCitations(category) {
                if (!category || category === '') {
                    citationsContainer.classList.remove('visible', 'loading');
                    citationContent.innerHTML = '<p class="text-slate-400 text-center py-8">Please select a category to view legal citations.</p>';
                    citationCount.textContent = '0';
                    return;
                }
                
                // Show loading state
                citationsContainer.classList.add('loading', 'visible');
                citationContent.innerHTML = `
                    <div class="spinner"></div>
                    <p class="text-slate-600">Generating 20 legal citations for "${category}"...</p>
                    <p class="text-sm text-slate-400 mt-2">This may take a few seconds</p>
                `;
                citationCount.textContent = '...';
                
                // Fetch from server
                fetch('?get_citations_by_category=' + encodeURIComponent(category))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Parse and display citations
                            displayCitations(data.citations);
                            // Store in hidden textarea for form submission
                            legalCitationsTextarea.value = data.citations;
                            currentCategory = category;
                        } else {
                            citationContent.innerHTML = `
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                                    ${data.error || 'Failed to fetch citations. Please try again.'}
                                </div>
                            `;
                            citationCount.textContent = '0';
                        }
                        citationsContainer.classList.remove('loading');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        citationContent.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                                <i class="fa-solid fa-exclamation-circle mr-2"></i>
                                Network error. Please try again.
                            </div>
                        `;
                        citationCount.textContent = '0';
                        citationsContainer.classList.remove('loading');
                    });
            }
            
            // Function to display citations
            function displayCitations(text) {
                // Check if citations container is visible
                if (!citationsContainer.classList.contains('visible')) {
                    citationsContainer.classList.add('visible');
                    isVisible = true;
                }
                
                // Count lines that look like citations (start with numbers)
                const lines = text.split('\n');
                let nationalCount = 0;
                let localCount = 0;
                let html = '';
                let currentSection = '';
                
                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    
                    // Detect section headers
                    if (line.includes('PHILIPPINE NATIONAL LAWS') || line.includes('NATIONAL LAWS')) {
                        html += `<div class="citation-section-header"><i class="fa-solid fa-landmark text-blue-700"></i> Philippine National Laws</div>`;
                        currentSection = 'national';
                        continue;
                    } else if (line.includes('SAN JOSE DEL MONTE') || line.includes('LOCAL ORDINANCES')) {
                        html += `<div class="citation-section-header"><i class="fa-solid fa-city text-green-700"></i> San Jose Del Monte, Bulacan Local Ordinances</div>`;
                        currentSection = 'local';
                        continue;
                    }
                    
                    // Check if line starts with a number (citation entry)
                    if (/^\d+\./.test(line)) {
                        const citationClass = currentSection === 'national' ? 'national' : 'local';
                        if (citationClass === 'national') nationalCount++;
                        else localCount++;
                        
                        // Split into parts
                        const parts = line.split(' - ');
                        const numberPart = parts[0] || line;
                        const titlePart = parts[1] || '';
                        
                        // Check if there's a key provisions line (next line or same line)
                        let detailLine = '';
                        if (i + 1 < lines.length && lines[i + 1].trim().includes('Key provisions:')) {
                            detailLine = lines[i + 1].trim();
                            // Skip the detail line in the next iteration
                            i++;
                        }
                        
                        html += `
                            <div class="citation-item ${citationClass}">
                                <div class="citation-number">${numberPart}</div>
                                <div class="citation-title">${titlePart}</div>
                                ${detailLine ? `<div class="citation-detail">${detailLine}</div>` : ''}
                            </div>
                        `;
                    } else if (line && !line.startsWith('---') && !line.startsWith('===') && line.length > 5) {
                        // Add as detail text
                        if (!html.includes(line)) {
                            // Check if it's a key provisions line
                            if (line.includes('Key provisions:') || line.includes('Relevance:')) {
                                html += `<div class="citation-detail text-xs text-slate-500 ml-6 mt-1">${line}</div>`;
                            }
                        }
                    }
                }
                
                // If no citations were found, show a message
                if (!html) {
                    html = `<p class="text-slate-400 text-center py-8">No citations found for this category. Try selecting a different category.</p>`;
                    nationalCount = 0;
                    localCount = 0;
                }
                
                citationContent.innerHTML = html;
                citationCount.textContent = (nationalCount + localCount);
                citationTime.textContent = new Date().toLocaleTimeString();
                
                // Show the citations container
                citationsContainer.classList.add('visible');
                isVisible = true;
                toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Hide';
            }
            
            // Toggle visibility
            toggleBtn.addEventListener('click', function() {
                if (isVisible) {
                    citationsContainer.classList.remove('visible');
                    isVisible = false;
                    toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> Show';
                } else {
                    citationsContainer.classList.add('visible');
                    isVisible = true;
                    toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Hide';
                    // If no citations loaded yet and category is selected, fetch
                    if (currentCategory || categorySelect.value) {
                        fetchCitations(categorySelect.value || currentCategory);
                    }
                }
            });
            
            // Refresh citations
            refreshBtn.addEventListener('click', function() {
                const category = categorySelect.value;
                if (category) {
                    fetchCitations(category);
                } else {
                    alert('Please select a category first.');
                }
            });
            
            // Auto-generate when category changes
            categorySelect.addEventListener('change', function() {
                const category = this.value;
                if (category) {
                    fetchCitations(category);
                    // Auto-show the container if it's hidden
                    if (!isVisible) {
                        citationsContainer.classList.add('visible');
                        isVisible = true;
                        toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Hide';
                    }
                } else {
                    citationsContainer.classList.remove('visible', 'loading');
                    citationContent.innerHTML = '<p class="text-slate-400 text-center py-8">Please select a category to view legal citations.</p>';
                    citationCount.textContent = '0';
                    citationTime.textContent = '--';
                    isVisible = false;
                    toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> Show';
                }
            });
            
            // If editing and category is pre-selected, auto-load citations
            <?php if ($edit_mode && $selected_doc && $selected_doc['category']): ?>
                setTimeout(function() {
                    const category = '<?php echo $selected_doc['category']; ?>';
                    if (category) {
                        // If there are existing citations, display them
                        <?php if ($selected_doc['legal_citations']): ?>
                            const existingCitations = <?php echo json_encode($selected_doc['legal_citations']); ?>;
                            if (existingCitations) {
                                displayCitations(existingCitations);
                                legalCitationsTextarea.value = existingCitations;
                                currentCategory = category;
                                citationsContainer.classList.add('visible');
                                isVisible = true;
                                toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Hide';
                            }
                        <?php else: ?>
                            fetchCitations(category);
                        <?php endif; ?>
                    }
                }, 500);
            <?php endif; ?>
            
            // Character counters
            const titleInput = document.getElementById('policy_title');
            const issuesInput = document.getElementById('policy_issues');
            const objectivesInput = document.getElementById('policy_objectives');
            const descriptionInput = document.getElementById('policy_description');
            
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    document.getElementById('titleCount').textContent = this.value.length;
                });
            }
            if (issuesInput) {
                issuesInput.addEventListener('input', function() {
                    document.getElementById('issuesCount').textContent = this.value.length;
                });
            }
            if (objectivesInput) {
                objectivesInput.addEventListener('input', function() {
                    document.getElementById('objectivesCount').textContent = this.value.length;
                });
            }
            if (descriptionInput) {
                descriptionInput.addEventListener('input', function() {
                    document.getElementById('descriptionCount').textContent = this.value.length;
                });
            }
        });

        function closePolicyModal() {
            window.location.href = 'policy-research.php';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('policyViewModal');
                if (modal) closePolicyModal();
            }
        });

        // Auto-search Repository on typing (500ms debounce)
        const searchInput = document.getElementById('repoSearchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }

        // Toast auto-dismiss
        setTimeout(() => {
            let toast = document.getElementById('toast');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        <?php if ($selected_doc && $show_analysis && !$edit_mode): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const section = document.getElementById('analysisSection');
            if (section) {
                setTimeout(() => {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 500);
            }
        });
        <?php endif; ?>
        
        <?php if (isset($_GET['manual_success']) && isset($_GET['doc_id'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                window.location.href = '?doc_id=<?php echo $_GET['doc_id']; ?>';
            }, 1500);
        });
        <?php endif; ?>
    </script>

    <!-- GLOBAL ACTION CONFIRMATION MODAL (Replaces browser alert/confirm "localhost says") -->
    <div id="actionConfirmModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 animate-fade-in no-print">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto flex flex-col">
            <!-- Modal Header -->
            <div id="confirmModalHeader" class="px-6 py-4 bg-blue-900 text-white flex justify-between items-center">
                <h3 id="confirmModalTitle" class="text-base font-bold flex items-center gap-2">
                    <i class="fa-solid fa-circle-question text-yellow-300"></i> Action Confirmation
                </h3>
                <button type="button" onclick="closeConfirmModal()" class="text-slate-300 hover:text-white text-lg transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="p-6">
                <p id="confirmModalMessage" class="text-sm text-slate-700 leading-relaxed font-medium">
                    Are you sure you want to proceed with this action?
                </p>
            </div>
            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 rounded-lg text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                    Cancel
                </button>
                <a id="confirmModalActionBtn" href="#" class="px-5 py-2 rounded-lg text-xs font-semibold text-white bg-blue-700 hover:bg-blue-800 transition flex items-center gap-1.5 shadow-sm">
                    Confirm
                </a>
            </div>
        </div>
    </div>

    <script>
        function openConfirmModal(title, message, actionUrl, btnClass = 'bg-blue-700 hover:bg-blue-800', headerBg = 'bg-blue-900') {
            document.getElementById('confirmModalTitle').innerHTML = '<i class="fa-solid fa-circle-question text-yellow-300"></i> ' + title;
            document.getElementById('confirmModalMessage').textContent = message;
            
            const actionBtn = document.getElementById('confirmModalActionBtn');
            actionBtn.href = actionUrl;
            actionBtn.className = 'px-5 py-2 rounded-lg text-xs font-semibold text-white transition flex items-center gap-1.5 shadow-sm ' + btnClass;
            
            document.getElementById('confirmModalHeader').className = 'px-6 py-4 text-white flex justify-between items-center ' + headerBg;
            
            document.getElementById('actionConfirmModal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('actionConfirmModal').classList.add('hidden');
        }
    </script>

</body>
</html>
<?php
$conn->close();
?>
