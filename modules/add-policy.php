<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Enforce RBAC (Admin and Researcher only)
requireRole([ROLE_ADMIN, ROLE_RESEARCHER]);

$username = $_SESSION['username'];
$pageTitle = "Add New Policy";
$conn = getDBConnection();

// ============================================
// GENERATE UNIQUE DOCUMENT ID
// ============================================
function generateDocumentId($conn) {
    $prefix = "POL";
    $year = date('Y');
    $unique = false;
    $doc_id = "";
    
    while (!$unique) {
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $doc_id = $prefix . "-" . $year . "-" . $random;
        
        $check_sql = "SELECT document_id FROM policy_documents WHERE document_id = '$doc_id'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows == 0) {
            $unique = true;
        }
        usleep(1000);
    }
    
    return $doc_id;
}

// ============================================
// HANDLE: Add New Policy
// ============================================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_policy'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $researcher = $_POST['researcher'];
    $status = $_POST['status'];
    
    // Validate inputs
    if (empty($title) || empty($category)) {
        $error_message = "Title and Category are required fields.";
    } else {
        // Generate document ID
        $document_id = generateDocumentId($conn);
        
        // Handle file upload
        $file_name = '';
        $file_path = '';
        
        if (isset($_FILES['policy_file']) && $_FILES['policy_file']['error'] == 0) {
            $target_dir = "uploads/policies/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['policy_file']['name']);
            $file_path = $target_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['policy_file']['tmp_name'], $file_path)) {
                $error_message = "Error uploading file.";
            }
        }
        
        // Insert into database
        if (empty($error_message)) {
            $escaped_title = $conn->real_escape_string($title);
            $escaped_category = $conn->real_escape_string($category);
            $escaped_description = $conn->real_escape_string($description);
            $escaped_researcher = $conn->real_escape_string($researcher);
            $escaped_file_name = $conn->real_escape_string($file_name);
            $escaped_file_path = $conn->real_escape_string($file_path);
            
            $insert_sql = "INSERT INTO policy_documents (
                document_id, 
                title, 
                category, 
                description, 
                researcher, 
                file_name, 
                file_path, 
                status, 
                upload_date, 
                legal_analysis_status
            ) VALUES (
                '$document_id',
                '$escaped_title',
                '$escaped_category',
                '$escaped_description',
                '$escaped_researcher',
                '$escaped_file_name',
                '$escaped_file_path',
                '$status',
                NOW(),
                'Pending'
            )";
            
            if ($conn->query($insert_sql) === TRUE) {
                // Log activity
                $log_sql = "INSERT INTO activity_logs (user, action, document_id, module, timestamp) 
                           VALUES ('$username', 'Added new policy document', '$document_id', 'Policy Research', NOW())";
                $conn->query($log_sql);
                
                $success_message = "Policy document added successfully! Document ID: " . $document_id;
                
                // Clear form
                $_POST = array();
            } else {
                $error_message = "Error: " . $conn->error;
            }
        }
    }
}

// ============================================
// GET: Categories for dropdown
// ============================================
$categories = [
    'Agriculture',
    'Education',
    'Environment',
    'Health',
    'Infrastructure',
    'Public Safety',
    'Social Welfare',
    'Economic Development',
    'Urban Planning',
    'Transportation',
    'Tourism',
    'Culture and Arts',
    'Youth and Sports',
    'Senior Citizens',
    'Persons with Disabilities',
    'Indigenous Peoples',
    'Gender and Development',
    'Peace and Order',
    'Disaster Risk Reduction',
    'Climate Change'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Policy</title>
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
        
        .form-card {
            transition: all 0.3s ease;
        }
        
        .form-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .input-focus {
            transition: all 0.3s ease;
        }
        
        .input-focus:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
    </style>
</head>

<body>

    <?php include("../includes/sidebar.php"); ?>
    
    <div class="ml-72">
        <?php include("../includes/navbar.php"); ?>
        
        <main class="p-8">
            
            <!-- Toast Notifications -->
            <?php if (!empty($success_message)): ?>
                <div class="toast toast-success" id="toast">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    <?php echo $success_message; ?>
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('toast').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="toast toast-error" id="toast">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    <?php echo $error_message; ?>
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
                        Add New Policy Document
                    </h2>
                    <p class="text-slate-500 mt-2">
                        Create a new policy document for research and analysis. 
                        <strong>Gemini AI</strong> will analyze it for legal citations and similar ordinances.
                    </p>
                </div>
                <a href="policy-research.php" class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-lg shadow btn-scale">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to Policies
                </a>
            </div>

            <!-- ADD POLICY FORM -->
            <div class="bg-white rounded-xl shadow p-8 form-card fade-in">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="add_policy" value="1">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Title -->
                        <div class="md:col-span-2">
                            <label class="block font-semibold mb-2 text-slate-700">
                                Policy Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                   class="w-full border border-slate-300 rounded-lg p-3 input-focus" 
                                   placeholder="Enter the policy title" required>
                        </div>
                        
                        <!-- Category -->
                        <div>
                            <label class="block font-semibold mb-2 text-slate-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category" class="w-full border border-slate-300 rounded-lg p-3 input-focus" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" 
                                        <?php echo (isset($_POST['category']) && $_POST['category'] == $cat) ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label class="block font-semibold mb-2 text-slate-700">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" class="w-full border border-slate-300 rounded-lg p-3 input-focus" required>
                                <option value="Draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            </select>
                        </div>
                        
                        <!-- Researcher -->
                        <div>
                            <label class="block font-semibold mb-2 text-slate-700">
                                Researcher
                            </label>
                            <input type="text" name="researcher" 
                                   value="<?php echo isset($_POST['researcher']) ? htmlspecialchars($_POST['researcher']) : $username; ?>"
                                   class="w-full border border-slate-300 rounded-lg p-3 input-focus" 
                                   placeholder="Enter researcher name">
                        </div>
                        
                        <!-- File Upload -->
                        <div>
                            <label class="block font-semibold mb-2 text-slate-700">
                                Upload Document
                            </label>
                            <input type="file" name="policy_file" 
                                   class="w-full border border-slate-300 rounded-lg p-3 input-focus"
                                   accept=".pdf,.doc,.docx,.txt">
                            <p class="text-xs text-slate-400 mt-1">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                PDF, DOC, DOCX, TXT files accepted (Max: 10MB)
                            </p>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mt-6">
                        <label class="block font-semibold mb-2 text-slate-700">
                            Policy Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" rows="6" 
                                  class="w-full border border-slate-300 rounded-lg p-3 input-focus" 
                                  placeholder="Provide a detailed description of the policy..." required><?php 
                            echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                        ?></textarea>
                        <p class="text-xs text-slate-400 mt-1">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            A detailed description helps Gemini AI find accurate legal citations and similar ordinances.
                        </p>
                    </div>
                    
                    <!-- Gemini AI Info -->
                    <div class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-robot text-purple-600 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-purple-800">Gemini AI Analysis</h4>
                                <p class="text-sm text-purple-700">
                                    After adding this policy, you can use the <strong>"Analyze"</strong> button in the 
                                    Policy Research module to generate:
                                </p>
                                <ul class="text-sm text-purple-700 mt-1 list-disc list-inside">
                                    <li>Philippine legal citations (RA, PD, EO, AO)</li>
                                    <li>Similar ordinances from San Jose Del Monte, Bulacan</li>
                                    <li>Comparative analysis and legal framework mapping</li>
                                    <li>Executive legal summary for legislative review</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="bg-blue-800 hover:bg-blue-900 text-white px-8 py-3 rounded-lg btn-scale">
                            <i class="fa-solid fa-save mr-2"></i>
                            Save Policy
                        </button>
                        <button type="reset" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-8 py-3 rounded-lg btn-scale">
                            <i class="fa-solid fa-rotate mr-2"></i>
                            Reset
                        </button>
                        <a href="policy-research.php" class="bg-slate-600 hover:bg-slate-700 text-white px-8 py-3 rounded-lg btn-scale">
                            <i class="fa-solid fa-times mr-2"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- HELPFUL TIPS -->
            <div class="mt-8 grid md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-gavel text-blue-700 text-xl"></i>
                    </div>
                    <h4 class="font-semibold text-slate-800">Legal Citations</h4>
                    <p class="text-sm text-slate-500 mt-2">
                        Gemini AI will search for relevant Philippine laws including Republic Acts, 
                        Presidential Decrees, Executive Orders, and Administrative Orders.
                    </p>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-city text-green-700 text-xl"></i>
                    </div>
                    <h4 class="font-semibold text-slate-800">SJDM Ordinances</h4>
                    <p class="text-sm text-slate-500 mt-2">
                        Find similar ordinances and resolutions from San Jose Del Monte, Bulacan 
                        that relate to your policy topic.
                    </p>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-file-pen text-purple-700 text-xl"></i>
                    </div>
                    <h4 class="font-semibold text-slate-800">Legal Summary</h4>
                    <p class="text-sm text-slate-500 mt-2">
                        Generate a concise executive summary of the legal analysis, 
                        perfect for presentation to the Sangguniang Panlungsod.
                    </p>
                </div>
            </div>

            <!-- FOOTER -->
            <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500">
                <p>© 2026 Legislative Research, Policy Analysis, and Impact Evaluation System</p>
                <p class="mt-2">Add Policy Documents for <strong>Gemini AI</strong>-Powered Legal Analysis</p>
            </footer>

        </main>
    </div>

    <script>
        // Auto-dismiss toast notifications
        setTimeout(() => {
            let toast = document.getElementById('toast');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const category = document.querySelector('select[name="category"]').value;
            const description = document.querySelector('textarea[name="description"]').value.trim();
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a policy title.');
                document.querySelector('input[name="title"]').focus();
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                document.querySelector('select[name="category"]').focus();
                return false;
            }
            
            if (!description) {
                e.preventDefault();
                alert('Please enter a policy description.');
                document.querySelector('textarea[name="description"]').focus();
                return false;
            }
            
            return true;
        });
    </script>

</body>
</html>

<?php
$conn->close();
?>