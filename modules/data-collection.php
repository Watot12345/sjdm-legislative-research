<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "Data Collection and Integration";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// ============================================
// GEMINI AI CONFIGURATION - Direct API from .env
// ============================================
require_once __DIR__ . '/../includes/gemini_helper.php';

// ============================================
// FUNCTION: Generate Unique Document ID
// ============================================
function generateUniqueDocId($conn, $prefix = "SD") {
    $unique = false;
    $doc_id = "";
    
    while (!$unique) {
        $timestamp = date('YmdHis');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $doc_id = $prefix . "-" . $timestamp . "-" . $random;
        
        $check_sql = "SELECT document_id FROM supporting_documents WHERE document_id = '$doc_id'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows == 0) {
            $unique = true;
        }
        usleep(1000);
    }
    
    return $doc_id;
}

// ============================================
// FUNCTION: Generate Supporting Documents with Gemini
// ============================================
function generateSupportingDocumentsWithGemini($dataset_id, $dataset_name, $category, $description, $conn) {
    // Get full dataset info
    $doc_sql = "SELECT * FROM datasets WHERE dataset_id = '$dataset_id'";
    $doc_result = $conn->query($doc_sql);
    if (!$doc_result || $doc_result->num_rows == 0) {
        return false;
    }
    $dataset = $doc_result->fetch_assoc();
    
    // Define the 3 core document categories
    $doc_categories = [
        'Fact-Checking & Legal Validation' => 'fact_check_validation',
        'Sectoral Data & Baseline Metrics' => 'sectoral_data_baseline',
        'Implementation & Enforcement Roadmap' => 'implementation_enforcement_roadmap'
    ];

    // Single-Pass Consolidated Prompt (3 Core Supporting Documents)
    $consolidatedPrompt = "You are an expert municipal data engineer and legal validator for San Jose Del Monte, Bulacan.
Based on the following dataset, generate a concise data validation & technical supporting report consisting of 3 sections:
1. Fact-Checking & Legal Validation (Verification of legal mandates, RA 9003/7160 compliance, data accuracy checks)
2. Sectoral Data & Baseline Metrics (Required municipal datasets, baseline metrics, collection sources)
3. Implementation & Enforcement Roadmap (Departmental responsibilities CENRO/CSWMB, enforcement timeline, evaluation criteria)

Dataset Name: " . $dataset_name . "
Category: " . $category . "
Description: " . $description . "
Source: " . ($dataset['source_office'] ?? 'LGU') . "

Return your response strictly as a JSON object with these exact 3 keys:
\"fact_check_validation\", \"sectoral_data_baseline\", \"implementation_enforcement_roadmap\".
Use plain text inside JSON values without raw markdown symbols.";

    $jsonContent = callGeminiAPI($consolidatedPrompt, 0.4, 1000);
    $parsedResults = [];

    if ($jsonContent !== null) {
        // Try to parse JSON from AI response
        $cleanJson = preg_replace('/^```json\s*|\s*```$/i', '', trim($jsonContent));
        $decoded = json_decode($cleanJson, true);
        if (is_array($decoded)) {
            $parsedResults = $decoded;
        }
    }

    $supporting_docs = [];

    foreach ($doc_categories as $doc_title => $json_key) {
        $doc_id = generateUniqueDocId($conn, "SD");
        
        if (isset($parsedResults[$json_key]) && !empty(trim($parsedResults[$json_key]))) {
            $content = trim($parsedResults[$json_key]);
            $generated_by = "Gemini AI";
        } else {
            // Fallback to template generator if section missing or API down
            $content = generateFallbackContent($doc_title, $dataset);
            $generated_by = "AI System (Fallback)";
        }
        
        // Escape content for database
        $escaped_content = $conn->real_escape_string($content);
        $escaped_title = $conn->real_escape_string($doc_title);
        $escaped_category = $conn->real_escape_string($doc_title);
        $escaped_generated_by = $conn->real_escape_string($generated_by);
        
        // Insert into database
        $insert_sql = "INSERT INTO supporting_documents (document_id, dataset_id, title, category, content, generated_date, generated_by) 
                       VALUES ('$doc_id', '$dataset_id', '$escaped_title', '$escaped_category', '$escaped_content', NOW(), '$escaped_generated_by')";
        
        if ($conn->query($insert_sql) === TRUE) {
            $supporting_docs[] = $doc_id;
        } else {
            error_log("Failed to insert document: " . $conn->error);
        }
    }
    
    // Update dataset status
    $update_sql = "UPDATE datasets SET 
                    ai_processed = 'Yes',
                    supporting_docs_generated = 'Yes',
                    ai_analysis_date = NOW(),
                    ready_for_impact_assessment = 'Yes'
                    WHERE dataset_id = '$dataset_id'";
    $conn->query($update_sql);
    
    return $supporting_docs;
}

// ============================================
// FUNCTION: Generate Fallback Content
// ============================================
function generateFallbackContent($docTitle, $dataset) {
    $name = $dataset['dataset_name'] ?? 'Dataset';
    $category = $dataset['category'] ?? 'General';
    $source = $dataset['source_office'] ?? 'City Government';
    $desc = $dataset['description'] ?? 'No description provided';
    $date = date('F j, Y');
    
    $fallbacks = [
        'Comparative Analysis' => "
COMPARATIVE ANALYSIS

DATASET: $name
CATEGORY: $category
DATE: $date

1. SIMILARITIES

The proposed policy aligns with existing laws and ordinances in the following ways:

- Consistent with the Local Government Code (RA 7160) provisions on local autonomy
- Aligns with San Jose Del Monte City Ordinances on $category
- Complies with national laws and constitutional provisions
- Supports the city's development goals and objectives

2. GAPS AND DIFFERENCES

Areas where the policy differs from existing laws:

- Missing provisions on enforcement mechanisms
- Potential conflicts with existing city ordinances on $category
- Overlaps with national government agency mandates
- Need for clearer definitions and procedures

HARMONIZATION REQUIREMENTS:
- Review of conflicting provisions
- Alignment with national legal framework
- Consistency with local government code
- Integration with existing city ordinances",

        'Harmonization Recommendations' => "
HARMONIZATION RECOMMENDATIONS

DATASET: $name
CATEGORY: $category
DATE: $date

1. SUGGESTED AMENDMENTS TO ALIGN WITH NATIONAL LAWS

a. Amend provisions to comply with Republic Act No. 7160 (Local Government Code)
b. Align with relevant Executive Orders and Administrative Orders
c. Ensure consistency with national development plans

2. RECOMMENDATIONS FOR LOCAL ORDINANCE ALIGNMENT

a. Revise existing city ordinances to incorporate new provisions
b. Develop implementing rules and regulations (IRR)
c. Create coordination mechanisms with city departments

3. PROPOSED REVISIONS FOR CONSISTENCY

a. Standardize terminology across all legal documents
b. Ensure consistent interpretation of key provisions
c. Establish review and update mechanisms",

        'Legal Framework Mapping' => "
LEGAL FRAMEWORK MAPPING

DATASET: $name
CATEGORY: $category
DATE: $date

1. HIERARCHY OF LAWS

a. Constitution of the Philippines
   - Article II: State Policies
   - Article X: Local Government
   - Article XII: National Economy and Patrimony

b. Republic Acts (National Laws)
   - RA 7160: Local Government Code of 1991
   - Relevant sectoral laws for $category

c. Executive Orders / Administrative Orders
   - EO on policy implementation
   - AO on administrative guidelines

d. Local Government Code (RA 7160) provisions
   - Powers and functions of local governments
   - Revenue generation and fiscal management

e. San Jose Del Monte City Ordinances
   - Existing ordinances on $category
   - City development plans

f. Implementing Rules and Regulations (IRR)
   - IRR of relevant national laws
   - IRR of city ordinances

2. APPLICABLE LEGAL PRINCIPLES

a. Constitutional Principles
   - Social justice and human rights
   - Local autonomy and decentralization
   - Participatory governance

b. Legal Doctrines
   - Doctrine of constitutional supremacy
   - Doctrine of separation of powers
   - Doctrine of local autonomy

c. Local Autonomy Considerations
   - City's power to legislate on local matters
   - Revenue generation and fiscal autonomy
   - Local development planning",

        'Implementation Recommendations' => "
IMPLEMENTATION RECOMMENDATIONS

DATASET: $name
CATEGORY: $category
DATE: $date

1. PROCEDURAL REQUIREMENTS

a. Step 1: Review and assessment of existing policies
b. Step 2: Stakeholder consultation and engagement
c. Step 3: Drafting of proposed policy/ordinance
d. Step 4: Legal review and compliance check
e. Step 5: Approval and adoption

2. APPROVAL PROCESS NEEDED

a. Ordinance: For policy requiring legislative action
   - First reading and referral to committee
   - Committee hearings and public consultations
   - Second and third readings
   - Approval by Sangguniang Panlungsod

b. Resolution: For policy directives and declarations
   - Introduction and referral
   - Committee deliberation
   - Adoption by Sangguniang Panlungsod

c. Executive Order: For administrative implementation
   - Preparation by concerned office
   - Review by City Legal Office
   - Approval and signing by City Mayor

3. REQUIRED CONSULTATIONS OR HEARINGS

a. Public hearings with affected communities
b. Consultations with relevant government agencies
c. Stakeholder meetings with civil society organizations
d. Coordination with national government agencies",

        'Monitoring and Evaluation' => "
MONITORING AND EVALUATION

DATASET: $name
CATEGORY: $category
DATE: $date

1. KEY LEGAL COMPLIANCE INDICATORS

a. Compliance with national laws and regulations
b. Adherence to constitutional provisions
c. Consistency with local ordinances
d. Implementation of legal requirements
e. Timely submission of reports and documentation

2. REPORTING REQUIREMENTS

a. Monthly progress reports
b. Quarterly compliance reports
c. Annual performance reports
d. Special reports on significant developments
e. Audit and financial reports

3. REVIEW MECHANISMS

a. Annual policy review and assessment
b. Mid-term evaluation and adjustments
c. Stakeholder feedback mechanisms
d. Performance audit and evaluation
e. Legislative oversight and review

4. PERFORMANCE METRICS

a. Implementation completion rate
b. Compliance rate with legal requirements
c. Stakeholder satisfaction levels
d. Impact and outcome indicators
e. Cost-effectiveness measures

5. EVALUATION TIMELINE

a. Quarterly: Progress monitoring
b. Semi-annual: Performance review
c. Annual: Comprehensive evaluation
d. Every 3 years: Impact assessment",

        'Recommendations' => "
RECOMMENDATIONS

DATASET: $name
CATEGORY: $category
DATE: $date

1. LEGAL RECOMMENDATIONS

a. Specific Legal Provisions to Include
   - Clear definitions and scope
   - Enforcement mechanisms
   - Penalties and sanctions
   - Appeal and review procedures

b. Suggested Amendments to Existing Ordinances
   - Update outdated provisions
   - Address identified gaps
   - Enhance enforcement mechanisms
   - Improve coordination provisions

c. Legal Basis for Policy Implementation
   - Constitutional authority
   - Statutory authorization
   - Local government powers
   - Administrative regulations

2. POLICY RECOMMENDATIONS

a. Short-term Actions (0-6 months)
   - Immediate implementation of critical provisions
   - Establishment of implementing structures
   - Initial stakeholder engagement

b. Medium-term Actions (6-12 months)
   - Full implementation of all provisions
   - Development of implementing rules
   - Capacity building and training

c. Long-term Actions (1-3 years)
   - Policy review and refinement
   - Impact assessment and evaluation
   - Policy mainstreaming and institutionalization

3. STAKEHOLDER RECOMMENDATIONS

a. Roles and Responsibilities
   - City Government: Lead implementation
   - National Agencies: Technical support
   - Civil Society: Monitoring and feedback
   - Private Sector: Partnership and resources

b. Coordination Mechanisms
   - Inter-agency coordination bodies
   - Regular consultation meetings
   - Information sharing systems
   - Joint monitoring and evaluation"
    ];
    
    return $fallbacks[$docTitle] ?? "$docTitle\n\nContent generated for: $name\nCategory: $category\nDate: $date";
}

// ============================================
// FUNCTION: Auto-create Impact Assessment
// ============================================
// ============================================
// FUNCTION: Create Impact Assessment
// ============================================
function createImpactAssessment($dataset_id, $conn, $username) {
    // Get dataset details
    $doc_sql = "SELECT * FROM datasets WHERE dataset_id = '$dataset_id'";
    $doc_result = $conn->query($doc_sql);
    if (!$doc_result || $doc_result->num_rows == 0) {
        return false;
    }
    $dataset = $doc_result->fetch_assoc();
    
    // Check if impact assessment already exists
    $check_sql = "SELECT 1 FROM impact_assessments WHERE dataset_id = '$dataset_id' LIMIT 1";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        return true; // Already exists
    }
    
    // Generate unique assessment ID
    $assessment_id = "IA-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Prepare initial assessment data based on dataset info
    $policy_title = $conn->real_escape_string($dataset['dataset_name']);
    $category = $conn->real_escape_string($dataset['category']);
    $source_office = $conn->real_escape_string($dataset['source_office']);
    $description = $conn->real_escape_string($dataset['description']);
    
    // Insert impact assessment record
    $insert_sql = "INSERT INTO impact_assessments 
                   (assessment_id, dataset_id, policy_title, category, department, 
                    assessment_period, overall_rating, assessment_status, created_by, created_date,
                    implementation_rate, budget_utilization, impact_rating, assessment_summary, beneficiaries)
                   VALUES 
                   ('$assessment_id', '$dataset_id', '$policy_title', '$category', '$source_office',
                    '2026-Q3', 'Moderate', 'Pending', '$username', NOW(),
                    0, 0, 'Pending', 'Assessment pending for $policy_title', 0)";
    
    if ($conn->query($insert_sql) === TRUE) {
        // Log the activity
        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                    VALUES ('$username', 'Auto-created Impact Assessment from Dataset', '$dataset_id', 'Impact Assessment', NOW())";
        $conn->query($log_sql);
        
        // Update dataset with impact assessment link
        $update_sql = "UPDATE datasets SET 
                       impact_assessment_id = '$assessment_id',
                       impact_assessment_created = 'Yes',
                       impact_assessment_date = NOW(),
                       ready_for_impact_assessment = 'Yes'
                       WHERE dataset_id = '$dataset_id'";
        $conn->query($update_sql);
        
        return $assessment_id;
    }
    
    return false;
}

// ============================================
// HANDLE: Dataset Approval with Gemini
// ============================================
if (isset($_GET['approve_id'])) {
    $approve_id = $_GET['approve_id'];
    
    $update_sql = "UPDATE datasets SET 
                    approval_status = 'Approved',
                    status = 'Approved',
                    approval_date = NOW(),
                    approved_by = '$username'
                    WHERE dataset_id = '$approve_id'";
    
    if ($conn->query($update_sql) === TRUE) {
        $doc_sql = "SELECT * FROM datasets WHERE dataset_id = '$approve_id'";
        $doc_result = $conn->query($doc_sql);
        if ($doc_result && $doc_result->num_rows > 0) {
            $dataset_data = $doc_result->fetch_assoc();
            
            // Generate supporting documents
            generateSupportingDocumentsWithGemini(
                $approve_id,
                $dataset_data['dataset_name'],
                $dataset_data['category'],
                $dataset_data['description'],
                $conn
            );
            
            // Auto-create Impact Assessment
            createImpactAssessment($approve_id, $conn, $username);
            
            $update_doc_sql = "UPDATE policy_documents SET 
                                data_collection_status = 'Approved',
                                impact_assessment_status = 'Ready for Assessment'
                                WHERE document_id = '{$dataset_data['doc_id']}'";
            $conn->query($update_doc_sql);
            
            $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                        VALUES ('$username', 'Approved Dataset with Gemini AI Analysis and Auto-created Impact Assessment', '$approve_id', 'Data Collection', NOW())";
            $conn->query($log_sql);
        }
        
        header("Location: data-collection.php?approved=1&filter_type=approved");
        exit();
    } else {
        $upload_error = "Error approving dataset: " . $conn->error;
    }
}

// ============================================
// HANDLE: Dataset Rejection
// ============================================
if (isset($_GET['reject_id'])) {
    $reject_id = $_GET['reject_id'];
    
    $update_sql = "UPDATE datasets SET 
                    approval_status = 'Rejected',
                    status = 'Rejected',
                    rejection_date = NOW(),
                    rejected_by = '$username'
                    WHERE dataset_id = '$reject_id'";
    
    if ($conn->query($update_sql) === TRUE) {
        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                    VALUES ('$username', 'Rejected Dataset', '$reject_id', 'Data Collection', NOW())";
        $conn->query($log_sql);
        
        header("Location: data-collection.php?rejected=1");
        exit();
    }
}

// ============================================
// HANDLE: Incoming Submission from Policy Module
// ============================================
if (isset($_GET['doc_id']) && !empty($_GET['doc_id'])
    && isset($_GET['title']) && !empty($_GET['title'])) {
    $submitted_doc_id = trim($_GET['doc_id']);
    $submitted_title = trim($_GET['title']);
    $submitted_desc = isset($_GET['description']) ? trim($_GET['description']) : '';
    $submitted_category = isset($_GET['category']) ? trim($_GET['category']) : '';

    $exists_sql = "SELECT 1 FROM datasets WHERE doc_id = '" . $conn->real_escape_string($submitted_doc_id) . "' LIMIT 1";
    $exists_result = $conn->query($exists_sql);

    if (!$exists_result || $exists_result->num_rows == 0) {
        $escaped_doc_id = $conn->real_escape_string($submitted_doc_id);
        $escaped_title = $conn->real_escape_string($submitted_title);
        $escaped_desc = $conn->real_escape_string($submitted_desc);
        $escaped_category = $conn->real_escape_string($submitted_category);
        $escaped_username = $conn->real_escape_string($username);

        $dataset_id = "DS-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $insert_sql = "INSERT INTO datasets
                       (dataset_id, dataset_name, source_office, dataset_category, category, description,
                        file_name, file_path, file_type, file_size, status, upload_date, uploaded_by,
                        approval_status, doc_id, ai_processed)
                       VALUES ('$dataset_id', '$escaped_title', 'Policy Research', '', '$escaped_category', '$escaped_desc',
                        '', '', '', 0, 'Pending', NOW(), '$escaped_username',
                        'Pending Approval', '$escaped_doc_id', 'No')";
        $conn->query($insert_sql);

        $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp)
                    VALUES ('$escaped_username', 'Submitted policy for data collection approval', '$escaped_doc_id', 'Data Collection', NOW())";
        $conn->query($log_sql);
    }

    header("Location: data-collection.php?submitted=1&filter_type=pending_approval");
    exit();
}

// ============================================
// HANDLE: Direct Dataset Upload
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_dataset'])) {
    $dataset_name = $_POST['dataset_name'];
    $source_office = $_POST['source_office'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    
    $upload_base = __DIR__ . "/../uploads/datasets/";
    if (!file_exists($upload_base)) {
        @mkdir($upload_base, 0777, true);
    }
    @chmod($upload_base, 0777);

    $file_name = time() . '_' . basename($_FILES["dataset_file"]["name"]);
    $relative_target_file = "uploads/datasets/" . $file_name;
    $abs_target_file = $upload_base . $file_name;
    $file_type = strtolower(pathinfo($relative_target_file, PATHINFO_EXTENSION));
    
    $dataset_id = "DS-" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    if (move_uploaded_file($_FILES["dataset_file"]["tmp_name"], $abs_target_file)) {
        $target_file = $relative_target_file;
        $escaped_dataset_name = $conn->real_escape_string($dataset_name);
        $escaped_source_office = $conn->real_escape_string($source_office);
        $escaped_category = $conn->real_escape_string($category);
        $escaped_description = $conn->real_escape_string($description);
        $escaped_file_name = $conn->real_escape_string($file_name);
        $escaped_file_path = $conn->real_escape_string($target_file);
        
        $file_size = isset($_FILES["dataset_file"]["size"]) ? intval($_FILES["dataset_file"]["size"]) : 0;
        $escaped_file_type = $conn->real_escape_string($file_type);

        $insert_sql = "INSERT INTO datasets (dataset_id, dataset_name, source_office, dataset_category, category, description, file_name, file_path, file_type, file_size, status, upload_date, uploaded_by, approval_status) 
                       VALUES ('$dataset_id', '$escaped_dataset_name', '$escaped_source_office', '$escaped_category', '$escaped_category', '$escaped_description', '$escaped_file_name', '$escaped_file_path', '$escaped_file_type', '$file_size', 'Approved', NOW(), '$username', 'Approved')";
        
        if ($conn->query($insert_sql) === TRUE) {
            generateSupportingDocumentsWithGemini(
                $dataset_id,
                $dataset_name,
                $category,
                $description,
                $conn
            );
            // Auto-create Impact Assessment for uploaded datasets
            createImpactAssessment($dataset_id, $conn, $username);
            
            $upload_success = "Dataset uploaded successfully with Gemini AI analysis and Impact Assessment created! Dataset ID: " . $dataset_id;
        } else {
            $upload_error = "Error: " . $conn->error;
        }
    } else {
        $upload_error = "Error uploading file.";
    }
}

// ============================================
// HANDLE: Delete Dataset
// ============================================
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $file_sql = "SELECT file_path FROM datasets WHERE dataset_id = '$delete_id'";
    $file_result = $conn->query($file_sql);
    if ($file_result && $file_result->num_rows > 0) {
        $file_row = $file_result->fetch_assoc();
        if (file_exists($file_row['file_path'])) {
            unlink($file_row['file_path']);
        }
    }
    
    $delete_sql = "DELETE FROM datasets WHERE dataset_id = '$delete_id'";
    if ($conn->query($delete_sql) === TRUE) {
        $delete_success = "Dataset deleted successfully!";
    }
}

// ============================================
// GET: Filter Parameters
// ============================================
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$search_office = isset($_GET['search_office']) ? $_GET['search_office'] : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';

// ============================================
// GET: View Dataset with Supporting Documents
// ============================================
$view_dataset_id = isset($_GET['view_dataset']) ? $_GET['view_dataset'] : '';
$view_dataset_data = null;
$view_supporting_docs = [];

if (!empty($view_dataset_id)) {
    $view_sql = "SELECT * FROM datasets WHERE dataset_id = '$view_dataset_id'";
    $view_result = $conn->query($view_sql);
    if ($view_result && $view_result->num_rows > 0) {
        $view_dataset_data = $view_result->fetch_assoc();
        
        $doc_sql = "SELECT * FROM supporting_documents WHERE dataset_id = '$view_dataset_id' ORDER BY generated_date DESC";
        $doc_result = $conn->query($doc_sql);
        if ($doc_result && $doc_result->num_rows > 0) {
            while($doc = $doc_result->fetch_assoc()) {
                $view_supporting_docs[] = $doc;
            }
        }
    }
}

// ============================================
// GET: Statistics with Impact Assessment Status
// ============================================
$total_datasets = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status != 'Rejected'")->fetch_assoc()['count'];
$total_approved = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status = 'Approved'")->fetch_assoc()['count'];
$total_pending_approval = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status = 'Pending Approval'")->fetch_assoc()['count'];
$total_rejected = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status = 'Rejected'")->fetch_assoc()['count'];
$total_ai_processed = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE ai_processed = 'Yes'")->fetch_assoc()['count'];
$total_supporting_docs = $conn->query("SELECT COUNT(*) as count FROM supporting_documents")->fetch_assoc()['count'];
$total_impact_ready = $conn->query("SELECT COUNT(*) as count FROM datasets WHERE ready_for_impact_assessment = 'Yes'")->fetch_assoc()['count'];

// ============================================
// GET: Pending Approval Datasets
// ============================================
$pending_sql = "SELECT * FROM datasets WHERE approval_status = 'Pending Approval' ORDER BY upload_date DESC";
$pending_datasets = $conn->query($pending_sql);

// Check if the Gemini API is reachable with the configured .env credentials
$gemini_status = "Unknown";
$gemini_color = "text-gray-600";
$test_response = callGeminiAPI("ping");

if ($test_response !== null) {
    $gemini_status = "Connected";
    $gemini_color = "text-green-600";
} else {
    $gemini_status = "Disconnected";
    $gemini_color = "text-red-600";
}

// ============================================
// BUILD: Main Query
// ============================================
$sql = "SELECT * FROM datasets WHERE 1=1";

if ($filter_type == 'pending_approval') {
    $sql .= " AND approval_status = 'Pending Approval'";
} elseif ($filter_type == 'approved') {
    $sql .= " AND approval_status = 'Approved'";
} elseif ($filter_type == 'rejected') {
    $sql .= " AND approval_status = 'Rejected'";
} elseif ($filter_type == 'all') {
    $sql .= " AND approval_status != 'Rejected'";
}

if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $sql .= " AND (dataset_name LIKE '%$search_query%' OR description LIKE '%$search_query%' OR dataset_id LIKE '%$search_query%')";
}
if (!empty($search_office) && $search_office != 'All Offices') {
    $search_office = $conn->real_escape_string($search_office);
    $sql .= " AND source_office = '$search_office'";
}
if (!empty($search_category) && $search_category != 'All Categories') {
    $search_category = $conn->real_escape_string($search_category);
    $sql .= " AND category = '$search_category'";
}
if (!empty($search_status) && $search_status != 'All Status') {
    $search_status = $conn->real_escape_string($search_status);
    $sql .= " AND status = '$search_status'";
}
$sql .= " ORDER BY upload_date DESC";

// Server-Side Scalable Pagination
$current_page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = max(5, min(100, intval($_GET['limit'] ?? 10)));
$offset = ($current_page - 1) * $items_per_page;

$count_query = preg_replace('/SELECT \* FROM datasets/', 'SELECT COUNT(*) as count FROM datasets', $sql);
$total_rows_res = $conn->query($count_query);
$total_rows = $total_rows_res ? intval($total_rows_res->fetch_assoc()['count']) : 0;
$total_pages = max(1, ceil($total_rows / $items_per_page));

$sql .= " LIMIT $offset, $items_per_page";
$datasets = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Collection and Integration</title>
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
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 700px;
            animation: slideIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
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
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast-success { background: #16a34a; }
        .toast-error { background: #dc2626; }
        .toast-info { background: #2563eb; }
        .toast-warning { background: #f59e0b; }
        
        .ai-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }
        
        .gemini-badge {
            background: linear-gradient(135deg, #4285f4, #34a853, #fbbc04, #ea4335);
            color: white;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-tab {
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #64748b;
            border-bottom: 3px solid transparent;
        }
        
        .filter-tab:hover {
            transform: translateY(-2px);
            color: #1e293b;
        }
        
        .filter-tab.active {
            border-bottom: 3px solid #1e3a8a;
            color: #1e3a8a;
            font-weight: 600;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .highlight-pending {
            border: 2px solid #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .doc-card {
            border-left: 4px solid #8b5cf6;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .doc-card:hover {
            background: #faf5ff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.2);
        }
        
        .doc-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .doc-icon.purple { background: #ede9fe; color: #7c3aed; }
        .doc-icon.blue { background: #dbeafe; color: #2563eb; }
        .doc-icon.green { background: #d1fae5; color: #059669; }
        .doc-icon.orange { background: #fef3c7; color: #d97706; }
        .doc-icon.pink { background: #fce7f3; color: #db2777; }
        .doc-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        
        .dataset-row {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dataset-row:hover {
            background: #f1f5f9 !important;
            transform: translateX(5px);
        }
        
        .dataset-row.selected {
            background: #ede9fe !important;
            border-left: 4px solid #7c3aed;
        }
        
        .view-dataset-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .view-dataset-btn:hover {
            transform: scale(1.1);
        }
        
        .supporting-docs-section {
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            transition: all 0.5s ease;
        }
        
        .supporting-docs-section.highlight {
            border-color: #7c3aed;
            box-shadow: 0 0 30px rgba(124, 58, 237, 0.15);
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .action-btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 6px;
        }
        
        .action-btn-sm i {
            font-size: 12px;
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
        
        .bridge-status.unknown {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .legal-doc-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .legal-doc-icon.gavel { background: #fef3c7; color: #b45309; }
        .legal-doc-icon.scale { background: #dbeafe; color: #1e40af; }
        .legal-doc-icon.book { background: #e0e7ff; color: #4f46e5; }
        .legal-doc-icon.chart { background: #d1fae5; color: #059669; }
        .legal-doc-icon.gear { background: #fce4ec; color: #c62828; }
        .legal-doc-icon.clipboard { background: #f3e5f5; color: #6a1b9a; }
        
        .impact-ready-badge {
            animation: pulse 2s infinite;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        }
        
        .proceed-btn {
            transition: all 0.3s ease;
        }
        
        .proceed-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);
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

  /* HIDE ALL BACKGROUND PAGE LAYOUT (SIDEBAR, NAVBAR, BREADCRUMBS, TOASTS, MODALS) */
  aside, nav, header, footer,
  .ml-72 > nav,
  .toast, #uploadModal, #confirmModal,
  button, .btn, .btn-scale,
  main > div.flex.justify-between.items-center.mb-8 > button,
  th:last-child, td:last-child, .no-print {
    display: none !important;
  }

  #viewDatasetModal,
  #viewDatasetModal > div,
  .modal-content,
  .modal-body {
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

  /* ---------- 2. FULL-WIDTH CLEAN DATA TABLES ---------- */
  table {
    width: 100% !important;
    max-width: 100% !important;
    border-collapse: collapse !important;
    margin-bottom: 12pt !important;
    page-break-inside: auto !important;
  }

  th, td {
    padding: 6pt 8pt !important;
    border: 1px solid #cbd5e1 !important;
    font-size: 9.5pt !important;
    color: #0f172a !important;
    background: transparent !important;
  }

  th {
    background-color: #f1f5f9 !important;
    font-weight: bold !important;
    text-transform: uppercase !important;
    font-size: 8.5pt !important;
  }

  tr {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  .card, .panel, .bg-white, .bg-slate-50, .shadow {
    box-shadow: none !important;
    border-radius: 0 !important;
    background: transparent !important;
  }

  /* ---------- 3. PREVENT MID-SENTENCE CUTS & BREAK AT PARAGRAPHS ---------- */
  p, li, blockquote, dt, dd, tr, td, th {
    orphans: 4 !important;
    widows: 4 !important;
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  /* ---------- 4. REPEATING SEAL WATERMARK (EVERY PAGE) ---------- */
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
            <?php if (isset($upload_success)): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    <?php echo $upload_success; ?>
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (isset($upload_error)): ?>
                <div class="toast toast-error" id="toast">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    <?php echo $upload_error; ?>
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (isset($delete_success)): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    <?php echo $delete_success; ?>
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (isset($_GET['submitted'])): ?>
                <div class="toast toast-warning" id="toast">
                    <i class="fa-solid fa-clock mr-2"></i>
                    Dataset submitted for approval. Gemini AI will generate legal analysis documents and auto-create Impact Assessment upon approval.
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (isset($_GET['approved'])): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Dataset approved! Gemini AI has generated legal framework documents and auto-created an Impact Assessment record.
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (isset($_GET['rejected'])): ?>
                <div class="toast toast-error" id="toast">
                    <i class="fa-solid fa-xmark-circle mr-2"></i>
                    Dataset rejected.
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>

            <!-- PAGE HEADER -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800">
                        Data Collection and Integration Module
                    </h2>
                    <p class="text-slate-500 mt-2">
                        Collect, validate, organize, and manage datasets with <strong>Gemini AI</strong>-powered legal framework analysis. <span class="text-purple-600 font-semibold">Approved datasets automatically proceed to Impact Assessment.</span>
                    </p>
                    <div class="mt-2 flex items-center gap-4 flex-wrap">
                        <span class="bridge-status <?php echo $gemini_status == 'Connected' ? 'online' : ($gemini_status == 'Disconnected' ? 'offline' : 'unknown'); ?>">
                            <i class="fa-solid fa-circle mr-1" style="font-size: 8px;"></i>
                            Gemini API: <?php echo $gemini_status; ?>
                        </span>
                        <span class="text-sm text-purple-600">
                            <i class="fa-solid fa-robot mr-1"></i>
                            AI Engine: Active
                        </span>
                        <span class="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full">
                            <i class="fa-solid fa-arrow-right mr-1"></i>
                            Auto-forwards to Impact Assessment
                        </span>
                    </div>
                </div>
                <button onclick="openUploadModal()" class="bg-blue-800 hover:bg-blue-900 text-white px-6 py-3 rounded-lg shadow btn-scale">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add New Dataset
                </button>
            </div>

            <!-- UPLOAD MODAL -->
            <div id="uploadModal" class="modal">
                <div class="modal-content">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold">Upload New Dataset</h2>
                        <button onclick="closeUploadModal()" class="text-slate-500 hover:text-slate-700 text-3xl">&times;</button>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_dataset" value="1">
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-semibold mb-2">Dataset Name *</label>
                                <input type="text" name="dataset_name" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-700" placeholder="Enter dataset name" required>
                            </div>
                            <div>
                                <label class="block font-semibold mb-2">Source Office *</label>
                                <select name="source_office" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-700" required>
                                    <option value="">Select Office</option>
                                    <option value="Planning Office">Planning Office</option>
                                    <option value="Mayor's Office">Mayor's Office</option>
                                    <option value="Health Office">Health Office</option>
                                    <option value="Agriculture Office">Agriculture Office</option>
                                    <option value="Engineering Office">Engineering Office</option>
                                    <option value="Finance Office">Finance Office</option>
                                    <option value="Environment Office">Environment Office</option>
                                    <option value="Education Office">Education Office</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold mb-2">Dataset Category *</label>
                                <select name="category" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-700" required>
                                    <option value="">Select Category</option>
                                    <option value="Population">Population</option>
                                    <option value="Health">Health</option>
                                    <option value="Environment">Environment</option>
                                    <option value="Economy">Economy</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Education">Education</option>
                                    <option value="Public Safety">Public Safety</option>
                                    <option value="Agriculture">Agriculture</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold mb-2">Upload File *</label>
                                <input type="file" name="dataset_file" class="w-full border rounded-lg p-3" accept=".csv,.xlsx,.xls,.pdf,.doc,.docx" required>
                                <p class="text-xs text-slate-400 mt-1">CSV, Excel, PDF, DOC, DOCX accepted</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block font-semibold mb-2">Description</label>
                            <textarea name="description" rows="4" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-700" placeholder="Describe the dataset..."></textarea>
                        </div>
                        
                        <div class="mt-4 p-3 bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg">
                            <p class="text-sm text-purple-700">
                                <i class="fa-solid fa-robot mr-2"></i>
                                <strong>Gemini AI-Powered Legal Analysis:</strong> Upon upload, Gemini AI will analyze the dataset and generate legal framework documents. <span class="font-semibold">An Impact Assessment will be auto-created for approved datasets.</span>
                            </p>
                            <?php if ($gemini_status == 'Disconnected'): ?>
                                <p class="text-sm text-red-600 mt-2">
                                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                                    <strong>Warning:</strong> Gemini API is not reachable. Documents will be generated using fallback content.
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6 flex gap-3">
                            <button type="submit" class="bg-blue-800 hover:bg-blue-900 text-white px-8 py-3 rounded-lg btn-scale">
                                <i class="fa-solid fa-upload mr-2"></i>
                                Upload Dataset
                            </button>
                            <button type="button" onclick="closeUploadModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-8 py-3 rounded-lg btn-scale">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Total Datasets</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_datasets; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fa-solid fa-database text-blue-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 mt-4">In Repository</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover <?php echo $filter_type == 'approved' ? 'border-2 border-green-500' : ''; ?>">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Approved</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_approved; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-green-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-green-600 mt-4">Ready for Use</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover relative <?php echo $filter_type == 'pending_approval' ? 'border-2 border-yellow-500' : ''; ?>">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Pending Approval</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_pending_approval; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-yellow-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-yellow-600 mt-4">Awaiting Review</p>
                    <?php if ($total_pending_approval > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center pulse">
                            <?php echo $total_pending_approval; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">AI Processed</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_ai_processed; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full ai-badge flex items-center justify-center">
                            <i class="fa-solid fa-robot text-white text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-purple-600 mt-4">Analysis Complete</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Legal Docs</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_supporting_docs; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="fa-solid fa-gavel text-indigo-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-indigo-600 mt-4">Legal Supporting Frameworks</p>
                </div>

                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500">Rejected</p>
                            <h2 class="text-4xl font-bold mt-2"><?php echo $total_rejected; ?></h2>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-xmark text-red-700 text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-red-600 mt-4">Needs Revision</p>
                </div>
            </div>

            <!-- FILTER TABS -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="flex border-b px-4 overflow-x-auto">
                    <a href="?filter_type=all" class="filter-tab px-4 py-3 <?php echo $filter_type == 'all' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-list mr-2"></i> All Datasets
                        <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full ml-1"><?php echo $total_datasets; ?></span>
                    </a>
                    <a href="?filter_type=pending_approval" class="filter-tab px-4 py-3 <?php echo $filter_type == 'pending_approval' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock mr-2"></i> Pending Approval
                        <?php if ($total_pending_approval > 0): ?>
                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-1"><?php echo $total_pending_approval; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?filter_type=approved" class="filter-tab px-4 py-3 <?php echo $filter_type == 'approved' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-check-circle mr-2"></i> Approved
                        <?php if ($total_approved > 0): ?>
                            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full ml-1"><?php echo $total_approved; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?filter_type=rejected" class="filter-tab px-4 py-3 <?php echo $filter_type == 'rejected' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-xmark mr-2"></i> Rejected
                        <?php if ($total_rejected > 0): ?>
                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-1"><?php echo $total_rejected; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- SEARCH & FILTER -->
            <div class="bg-white rounded-xl shadow p-6 mb-8">
                <form method="GET" action="">
                    <input type="hidden" name="filter_type" value="<?php echo $filter_type; ?>">
                    <div class="grid lg:grid-cols-5 gap-4">
                        <input type="text" name="search_query" placeholder="Search datasets..." value="<?php echo htmlspecialchars($search_query); ?>" class="border rounded-lg p-3 focus:ring-2 focus:ring-blue-700">
                        <select name="search_office" class="border rounded-lg p-3 focus:ring-2 focus:ring-blue-700">
                            <option value="All Offices">All Offices</option>
                            <option value="Planning Office" <?php echo $search_office == 'Planning Office' ? 'selected' : ''; ?>>Planning Office</option>
                            <option value="Health Office" <?php echo $search_office == 'Health Office' ? 'selected' : ''; ?>>Health Office</option>
                            <option value="Engineering Office" <?php echo $search_office == 'Engineering Office' ? 'selected' : ''; ?>>Engineering Office</option>
                            <option value="Finance Office" <?php echo $search_office == 'Finance Office' ? 'selected' : ''; ?>>Finance Office</option>
                            <option value="Environment Office" <?php echo $search_office == 'Environment Office' ? 'selected' : ''; ?>>Environment Office</option>
                            <option value="Education Office" <?php echo $search_office == 'Education Office' ? 'selected' : ''; ?>>Education Office</option>
                        </select>
                        <select name="search_category" class="border rounded-lg p-3 focus:ring-2 focus:ring-blue-700">
                            <option value="All Categories">All Categories</option>
                            <option value="Population" <?php echo $search_category == 'Population' ? 'selected' : ''; ?>>Population</option>
                            <option value="Health" <?php echo $search_category == 'Health' ? 'selected' : ''; ?>>Health</option>
                            <option value="Environment" <?php echo $search_category == 'Environment' ? 'selected' : ''; ?>>Environment</option>
                            <option value="Economy" <?php echo $search_category == 'Economy' ? 'selected' : ''; ?>>Economy</option>
                            <option value="Infrastructure" <?php echo $search_category == 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                            <option value="Education" <?php echo $search_category == 'Education' ? 'selected' : ''; ?>>Education</option>
                        </select>
                        <select name="search_status" class="border rounded-lg p-3 focus:ring-2 focus:ring-blue-700">
                            <option value="All Status">All Status</option>
                            <option value="Approved" <?php echo $search_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Pending" <?php echo $search_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $search_status == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Rejected" <?php echo $search_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="bg-blue-800 hover:bg-blue-900 text-white rounded-lg btn-scale">
                            <i class="fa-solid fa-search mr-2"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- PENDING APPROVAL SECTION -->
            <?php if ($filter_type == 'pending_approval' || $filter_type == 'all'): ?>
            <?php 
            $pending_sql = "SELECT * FROM datasets WHERE approval_status = 'Pending Approval' ORDER BY upload_date DESC";
            $pending_result = $conn->query($pending_sql);
            if ($pending_result && $pending_result->num_rows > 0): 
            ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl shadow mb-8 p-4 fade-in <?php echo isset($_GET['pending']) ? 'highlight-pending' : ''; ?>">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-yellow-700">
                        <i class="fa-solid fa-clock mr-2"></i>
                        Pending Approval (<?php echo $pending_result->num_rows; ?>)
                    </h2>
                    <span class="text-sm text-yellow-600">These datasets need your review and approval</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-yellow-100">
                            <tr>
                                <th class="text-left p-4">Dataset ID</th>
                                <th class="text-left p-4">Dataset Name</th>
                                <th class="text-left p-4">Source</th>
                                <th class="text-left p-4">Category</th>
                                <th class="text-left p-4">Submitted By</th>
                                <th class="text-left p-4">Date Submitted</th>
                                <th class="text-center p-4 no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-yellow-50 dataset-row" onclick="viewDatasetDocs('<?php echo $row['dataset_id']; ?>')">
                                    <td class="p-4 font-mono"><?php echo $row['dataset_id']; ?></td>
                                    <td class="p-4 font-medium"><?php echo htmlspecialchars($row['dataset_name']); ?></td>
                                    <td class="p-4"><?php echo $row['source_office']; ?></td>
                                    <td class="p-4">
                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs"><?php echo $row['category']; ?></span>
                                    </td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                                    <td class="p-4"><?php echo date('M j, Y h:i A', strtotime($row['upload_date'])); ?></td>
                                    <td class="p-4 no-print">
                                        <div class="flex justify-center gap-1.5" onclick="event.stopPropagation();">
                                            <button type="button" 
                                                    title="Approve Dataset & Generate Technical Framework"
                                                    onclick="openConfirmModal('Approve Dataset', 'Approve this dataset? Gemini AI will generate 3 technical supporting documents and auto-create an Impact Assessment record.', '?approve_id=<?php echo $row['dataset_id']; ?>', 'bg-green-600 hover:bg-green-700', 'bg-gradient-to-r from-green-900 to-green-700')"
                                                    class="bg-green-600 hover:bg-green-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    title="Reject Dataset"
                                                    onclick="openConfirmModal('Reject Dataset', 'Are you sure you want to reject this dataset?', '?reject_id=<?php echo $row['dataset_id']; ?>', 'bg-red-600 hover:bg-red-700', 'bg-gradient-to-r from-red-900 to-red-700')"
                                                    class="bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <button type="button" 
                                                    title="View Supporting Documents"
                                                    onclick="viewDatasetDocs('<?php echo $row['dataset_id']; ?>')" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 p-3 bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg">
                    <p class="text-sm text-purple-700">
                        <i class="fa-solid fa-robot mr-2"></i>
                        <strong>Gemini AI + Auto-Impact Assessment:</strong> Upon approval, Gemini AI generates 3 technical supporting documents (Fact-Checking, Data Baseline, & Implementation Roadmap) and <strong class="text-indigo-700">auto-creates an Impact Assessment record</strong> for the next phase.
                    </p>
                    <?php if ($gemini_status == 'Disconnected'): ?>
                        <p class="text-sm text-red-600 mt-2">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            <strong>Note:</strong> Gemini API is not reachable. Fallback content will be generated instead. Impact Assessment will still be auto-created.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- DATASET REPOSITORY -->
            <div class="bg-white rounded-xl shadow mb-8 fade-in">
                <div class="flex items-center justify-between px-6 py-5 border-b">
                    <div>
                        <h2 class="text-2xl font-bold">
                            <?php 
                                if ($filter_type == 'pending_approval') echo '⏳ Pending Approval List';
                                elseif ($filter_type == 'approved') echo '✅ Approved Datasets with Legal Analysis & Impact Assessment';
                                elseif ($filter_type == 'rejected') echo '❌ Rejected Datasets';
                                else echo 'Dataset Repository';
                            ?>
                        </h2>
                        <p class="text-slate-500 mt-1">
                            <?php 
                                if ($filter_type == 'pending_approval') echo 'Datasets waiting for your review and approval.';
                                elseif ($filter_type == 'approved') echo 'Approved datasets with Gemini AI-generated legal framework documents. <span class="text-purple-600 font-semibold">Impact Assessment auto-created.</span>';
                                elseif ($filter_type == 'rejected') echo 'Datasets that need revision.';
                                else echo 'Click any dataset row to view its Gemini AI-generated legal framework documents.';
                            ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <span class="gemini-badge px-3 py-1 rounded-full text-sm">
                            <i class="fa-solid fa-robot mr-1"></i> Gemini AI
                        </span>
                        <span class="bridge-status <?php echo $gemini_status == 'Connected' ? 'online' : ($gemini_status == 'Disconnected' ? 'offline' : 'unknown'); ?> text-sm px-3 py-1 rounded-full">
                            <i class="fa-solid fa-circle mr-1" style="font-size: 8px;"></i>
                            <?php echo $gemini_status; ?>
                        </span>
                        <button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg btn-scale">
                            <i class="fa-solid fa-file-export mr-2"></i>
                            Export
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-left p-4">Dataset ID</th>
                                <th class="text-left p-4">Dataset Name</th>
                                <th class="text-left p-4">Source</th>
                                <th class="text-left p-4">Category</th>
                                <th class="text-left p-4">Status</th>
                                <th class="text-left p-4">Legal Analysis</th>
                                <th class="text-left p-4">Impact Assessment</th>
                                <th class="text-left p-4">Date Uploaded</th>
                                <th class="text-center p-4 no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($datasets && $datasets->num_rows > 0): ?>
                                <?php while($row = $datasets->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-slate-50 dataset-row <?php echo $row['approval_status'] == 'Approved' ? 'bg-green-50' : ''; ?> <?php echo ($view_dataset_id == $row['dataset_id']) ? 'selected' : ''; ?>" onclick="viewDatasetDocs('<?php echo $row['dataset_id']; ?>')">
                                        <td class="p-4 font-mono"><?php echo $row['dataset_id']; ?></td>
                                        <td class="p-4 font-medium"><?php echo htmlspecialchars($row['dataset_name']); ?></td>
                                        <td class="p-4"><?php echo $row['source_office']; ?></td>
                                        <td class="p-4">
                                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs"><?php echo $row['category']; ?></span>
                                        </td>
                                        <td class="p-4">
                                            <span class="status-badge px-3 py-1 rounded-full text-sm 
                                                <?php echo $row['status'] == 'Approved' ? 'bg-green-100 text-green-700' : 
                                                          ($row['status'] == 'Processing' ? 'bg-blue-100 text-blue-700' :
                                                          ($row['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-700' : 
                                                          ($row['status'] == 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'))); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['ai_processed'] == 'Yes'): ?>
                                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm">
                                                    <i class="fa-solid fa-check mr-1"></i> Complete
                                                </span>
                                                <?php if ($row['supporting_docs_generated'] == 'Yes'): ?>
                                                    <span class="block text-xs text-indigo-600 mt-1">
                                                        <i class="fa-solid fa-gavel mr-1"></i> Legal Docs
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm">
                                                    <i class="fa-solid fa-clock mr-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['ready_for_impact_assessment'] == 'Yes'): ?>
                                                <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm flex items-center gap-1">
                                                    <i class="fa-solid fa-check-circle mr-1"></i> Ready
                                                    <span class="text-xs bg-indigo-200 px-1 rounded ml-1">
                                                        <i class="fa-solid fa-arrow-right"></i>
                                                    </span>
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm">
                                                    <i class="fa-solid fa-clock mr-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4"><?php echo date('M j, Y', strtotime($row['upload_date'])); ?></td>
                                        <td class="p-4 no-print">
                                            <div class="flex justify-center gap-1.5 flex-wrap" onclick="event.stopPropagation();">
                                                <button onclick="viewDatasetDocs('<?php echo $row['dataset_id']; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm" title="View Technical Supporting Documents">
                                                    <i class="fa-solid fa-gavel"></i>
                                                </button>
                                                <?php if ($row['ready_for_impact_assessment'] == 'Yes'): ?>
                                                    <button type="button"
                                                            title="Proceed to Policy Impact Assessment"
                                                            onclick="openConfirmModal('Proceed to Impact Assessment', 'Do you want to proceed to the Policy Impact Assessment phase for this dataset?', 'impact-assessment.php?dataset_id=<?php echo $row['dataset_id']; ?>', 'bg-purple-600 hover:bg-purple-700', 'bg-gradient-to-r from-purple-900 to-purple-700')"
                                                            class="bg-purple-600 hover:bg-purple-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm">
                                                        <i class="fa-solid fa-chart-line"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($row['ai_processed'] == 'Yes' && $row['supporting_docs_generated'] == 'Yes'): ?>
                                                    <a href="view_supporting_doc.php?doc_id=<?php echo $row['dataset_id']; ?>" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm" title="View All Legal Documents Folder">
                                                        <i class="fa-solid fa-folder-open"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($row['approval_status'] != 'Approved'): ?>
                                                    <button onclick="deleteDataset('<?php echo $row['dataset_id']; ?>')" class="bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm" title="Delete Dataset">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button"
                                                        title="Submit Dataset"
                                                        onclick="openConfirmModal('Submit Dataset', 'Are you sure you want to submit this dataset for review and approval?', '?submit_id=<?php echo $row['dataset_id']; ?>', 'bg-green-600 hover:bg-green-700', 'bg-gradient-to-r from-green-900 to-green-700')"
                                                        class="bg-green-600 hover:bg-green-700 text-white w-8 h-8 rounded-lg flex items-center justify-center text-xs btn-scale shadow-sm">
                                                    <i class="fa-solid fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center p-8 text-slate-500">
                                        <i class="fa-solid fa-inbox text-4xl block mb-4"></i>
                                        <?php 
                                            if ($filter_type == 'pending_approval') {
                                                echo 'No datasets pending approval.';
                                            } elseif ($filter_type == 'approved') {
                                                echo 'No approved datasets yet. Go to <a href="?filter_type=pending_approval" class="text-blue-600 hover:underline">Pending Approval</a> to approve datasets.';
                                            } elseif ($filter_type == 'rejected') {
                                                echo 'No rejected datasets.';
                                            } else {
                                                echo 'No datasets found. Upload your first dataset.';
                                            }
                                        ?>
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
                        Showing <strong class="text-slate-800"><?php echo $start_entry; ?></strong> to <strong class="text-slate-800"><?php echo $end_entry; ?></strong> of <strong class="text-slate-800"><?php echo $total_rows; ?></strong> datasets
                    </p>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- PREVIOUS PAGE BUTTON -->
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>&filter_type=<?php echo urlencode($filter_type); ?>&search_query=<?php echo urlencode($search_query); ?>&search_office=<?php echo urlencode($search_office); ?>&search_category=<?php echo urlencode($search_category); ?>&search_status=<?php echo urlencode($search_status); ?>&limit=<?php echo $items_per_page; ?>" 
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
                                <span class="bg-blue-800 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-sm">
                                    <?php echo $p; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?php echo $p; ?>&filter_type=<?php echo urlencode($filter_type); ?>&search_query=<?php echo urlencode($search_query); ?>&search_office=<?php echo urlencode($search_office); ?>&search_category=<?php echo urlencode($search_category); ?>&search_status=<?php echo urlencode($search_status); ?>&limit=<?php echo $items_per_page; ?>" 
                                   class="border border-slate-300 text-slate-700 hover:bg-blue-50 hover:text-blue-700 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                                    <?php echo $p; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- NEXT PAGE BUTTON -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>&filter_type=<?php echo urlencode($filter_type); ?>&search_query=<?php echo urlencode($search_query); ?>&search_office=<?php echo urlencode($search_office); ?>&search_category=<?php echo urlencode($search_category); ?>&search_status=<?php echo urlencode($search_status); ?>&limit=<?php echo $items_per_page; ?>" 
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

            <!-- VIEW DATASET SUPPORTING DOCUMENTS MODAL OVERLAY -->
            <?php if ($view_dataset_data && !empty($view_supporting_docs)): ?>
            <div id="viewDatasetModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
                <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto max-h-[90vh] flex flex-col">
                    <!-- Modal Header (Screen Only) -->
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 text-white flex justify-between items-center shrink-0 no-print">
                        <div>
                            <h2 class="text-base font-bold flex items-center gap-2">
                                <i class="fa-solid fa-gavel text-yellow-300"></i>
                                Technical Supporting Documents & Dataset Details
                            </h2>
                            <p class="text-xs text-blue-200 mt-0.5">
                                Dataset: <strong><?php echo htmlspecialchars($view_dataset_data['dataset_name']); ?></strong> (<?php echo $view_dataset_data['dataset_id']; ?>)
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($view_dataset_data['ready_for_impact_assessment'] == 'Yes'): ?>
                                <button type="button"
                                        onclick="openConfirmModal('Proceed to Impact Assessment', 'Do you want to proceed to the Policy Impact Assessment phase for this dataset?', 'impact-assessment.php?dataset_id=<?php echo $view_dataset_data['dataset_id']; ?>', 'bg-purple-600 hover:bg-purple-700', 'bg-gradient-to-r from-purple-900 to-purple-700')"
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-chart-line"></i> Proceed to Impact Assessment
                                </button>
                            <?php endif; ?>
                            <button type="button" onclick="closeViewSection()" class="text-blue-200 hover:text-white text-xl transition">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs text-slate-600">
                            <div>
                                <span class="font-semibold text-slate-800"><i class="fa-solid fa-tag text-blue-600 mr-1"></i> Category:</span> <?php echo $view_dataset_data['category']; ?> | 
                                <span class="font-semibold text-slate-800 ml-2"><i class="fa-solid fa-building text-blue-600 mr-1"></i> Source Office:</span> <?php echo $view_dataset_data['source_office']; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="gemini-badge px-3 py-1 rounded-full text-xs">
                                    <i class="fa-solid fa-robot mr-1"></i> Gemini AI
                                </span>
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    <i class="fa-solid fa-check mr-1"></i> <?php echo count($view_supporting_docs); ?> Documents Generated
                                </span>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php 
                            $doc_icons = [
                                'fa-solid fa-scale-balanced',
                                'fa-solid fa-handshake',
                                'fa-solid fa-sitemap',
                                'fa-solid fa-gears',
                                'fa-solid fa-chart-line',
                                'fa-solid fa-list-check'
                            ];
                            $icon_colors = ['gavel', 'scale', 'book', 'gear', 'chart', 'clipboard'];
                            $i = 0;
                            foreach($view_supporting_docs as $doc): 
                                $color = $icon_colors[$i % count($icon_colors)];
                                $icon = $doc_icons[$i % count($doc_icons)];
                                $i++;
                            ?>
                                <div class="border rounded-lg p-4 doc-card hover:shadow-md transition bg-white">
                                    <div class="flex items-start gap-3">
                                        <div class="legal-doc-icon <?php echo $color; ?>">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-sm truncate"><?php echo htmlspecialchars($doc['title']); ?></h3>
                                            <p class="text-xs text-slate-500 mt-1 font-mono"><?php echo $doc['document_id']; ?></p>
                                            <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                                    <i class="fa-solid fa-robot mr-1"></i> <?php echo htmlspecialchars($doc['generated_by']); ?>
                                                </span>
                                                <span class="text-xs text-slate-400">
                                                    <?php echo date('M j, Y', strtotime($doc['generated_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-2 line-clamp-2 ml-12">
                                        <?php 
                                        $preview = substr($doc['content'], 0, 120);
                                        echo htmlspecialchars($preview) . '...';
                                        ?>
                                    </p>
                                    <div class="mt-3 flex items-center gap-2 ml-12 no-print">
                                        <a href="view_supporting_doc.php?doc_id=<?php echo $doc['document_id']; ?>" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded-md transition flex items-center gap-1.5 font-medium">
                                            <i class="fa-solid fa-eye"></i> View Document
                                        </a>
                                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); downloadDoc('<?php echo $doc['document_id']; ?>')" class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-md transition flex items-center gap-1.5 font-medium">
                                            <i class="fa-solid fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Modal Footer (Screen Only) -->
                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0 no-print">
                        <button type="button" onclick="closeViewSection()" class="px-5 py-2 rounded-lg text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
            <?php elseif ($view_dataset_data && empty($view_supporting_docs)): ?>
            <div id="viewDatasetModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in no-print">
                <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto flex flex-col">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-900 to-indigo-900 text-white flex justify-between items-center">
                        <h2 class="text-base font-bold flex items-center gap-2">
                            <i class="fa-solid fa-file-lines text-yellow-300"></i> Dataset Details
                        </h2>
                        <button type="button" onclick="closeViewSection()" class="text-blue-200 hover:text-white text-xl transition">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="p-6 text-center">
                        <i class="fa-solid fa-robot text-5xl text-slate-300 mb-3 block"></i>
                        <h3 class="font-bold text-slate-800 text-base"><?php echo htmlspecialchars($view_dataset_data['dataset_name']); ?></h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5"><?php echo $view_dataset_data['dataset_id']; ?></p>
                        <p class="text-slate-500 text-xs mt-3">This dataset hasn't been analyzed by Gemini AI yet.</p>
                    </div>
                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button type="button" onclick="closeViewSection()" class="px-5 py-2 rounded-lg text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FOOTER -->
            <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
                <p class="mt-2">Data Collection and Integration Module with <strong>Google Gemini AI</strong>-Powered Legal Framework Analysis & Auto-Impact Assessment</p>
            </footer>

        </main>
    </div>

    <script>
        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            let uploadModal = document.getElementById('uploadModal');
            if (event.target == uploadModal) {
                uploadModal.style.display = 'none';
            }
        }

        // View dataset supporting documents
        function viewDatasetDocs(datasetId) {
            if (datasetId) {
                window.location.href = `?view_dataset=${datasetId}&filter_type=<?php echo $filter_type; ?>&search_query=<?php echo urlencode($search_query); ?>&search_office=<?php echo urlencode($search_office); ?>&search_category=<?php echo urlencode($search_category); ?>&search_status=<?php echo urlencode($search_status); ?>#viewDatasetSection`;
            }
        }

        // Close view section
        function closeViewSection() {
            window.location.href = `?filter_type=<?php echo $filter_type; ?>&search_query=<?php echo urlencode($search_query); ?>&search_office=<?php echo urlencode($search_office); ?>&search_category=<?php echo urlencode($search_category); ?>&search_status=<?php echo urlencode($search_status); ?>`;
        }

        function downloadDoc(docId) {
            window.open('view_supporting_doc.php?doc_id=' + docId + '&download=1', '_blank');
        }
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

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeConfirmModal();
                closeViewSection();
            }
        });

        function deleteDataset(datasetId) {
            openConfirmModal(
                'Delete Dataset',
                'Are you sure you want to delete this dataset? This action cannot be undone.',
                `?delete_id=${datasetId}`,
                'bg-red-600 hover:bg-red-700',
                'bg-gradient-to-r from-red-900 to-red-700'
            );
        }

        // Button hover effects
        document.querySelectorAll("button").forEach(function(button){
            button.addEventListener("mouseenter", function(){
                this.classList.add("scale-105","transition");
            });
            button.addEventListener("mouseleave", function(){
                this.classList.remove("scale-105");
            });
        });

        // Auto-dismiss toast notifications
        setTimeout(() => {
            let toast = document.getElementById('toast');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
    </script>

</body>
</html>

<?php
$conn->close();
?>