<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Impact Assessments</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>
</head>
<body class='bg-gray-100 p-8'>
<div class='max-w-4xl mx-auto'>";

echo "<h1 class='text-3xl font-bold mb-6'>📊 Create Impact Assessments from Datasets</h1>";

// Get approved datasets that don't have assessments yet
$sql = "SELECT d.* FROM datasets d 
        LEFT JOIN impact_assessments ia ON d.dataset_id = ia.dataset_id 
        WHERE d.approval_status = 'Approved' 
        AND ia.assessment_id IS NULL";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
    echo "<p>Found <strong>" . $result->num_rows . "</strong> approved datasets without impact assessments.</p>";
    echo "<div class='mt-4'>";
    
    $created = 0;
    while($row = $result->fetch_assoc()) {
        $assessment_id = "IA-" . date('Ymd') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $policy_title = $conn->real_escape_string($row['dataset_name']);
        $department = $conn->real_escape_string($row['source_office'] ?? 'City Government');
        $category = $conn->real_escape_string($row['category'] ?? 'General');
        $dataset_id = $row['dataset_id'];
        
        $insert_sql = "INSERT INTO impact_assessments 
                       (assessment_id, dataset_id, policy_title, department, category, 
                        assessment_period, assessment_status, created_by, created_date,
                        implementation_rate, budget_utilization, impact_rating, assessment_summary, beneficiaries)
                       VALUES 
                       ('$assessment_id', '$dataset_id', '$policy_title', '$department', '$category',
                        DATE_FORMAT(NOW(), '%Y-%m'), 'Pending', '$username', NOW(),
                        0, 0, 'Pending', 'Assessment pending for $policy_title', 0)";
        
        if ($conn->query($insert_sql) === TRUE) {
            echo "<p class='text-green-600'>✅ Created: <strong>$assessment_id</strong> for <strong>" . $row['dataset_name'] . "</strong></p>";
            $created++;
        } else {
            echo "<p class='text-red-600'>❌ Error: " . $conn->error . "</p>";
        }
    }
    
    echo "<p class='mt-4 font-bold text-green-600'>✅ Created $created impact assessments.</p>";
    echo "</div></div>";
} else {
    echo "<div class='bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6'>";
    echo "<p class='text-yellow-700'>⚠️ No approved datasets found without impact assessments.</p>";
    echo "</div>";
}

// Show current assessments
echo "<div class='bg-white rounded-lg shadow p-6'>";
echo "<h2 class='text-xl font-bold mb-4'>Current Impact Assessments</h2>";

$ia_sql = "SELECT * FROM impact_assessments ORDER BY created_date DESC LIMIT 10";
$ia_result = $conn->query($ia_sql);

if ($ia_result && $ia_result->num_rows > 0) {
    echo "<table class='w-full border-collapse'>";
    echo "<tr class='bg-gray-100'>";
    echo "<th class='border p-2 text-left'>ID</th>";
    echo "<th class='border p-2 text-left'>Policy</th>";
    echo "<th class='border p-2 text-left'>Department</th>";
    echo "<th class='border p-2 text-left'>Status</th>";
    echo "<th class='border p-2 text-left'>Created</th>";
    echo "</tr>";
    while($row = $ia_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td class='border p-2 font-mono'>" . $row['assessment_id'] . "</td>";
        echo "<td class='border p-2'>" . $row['policy_title'] . "</td>";
        echo "<td class='border p-2'>" . $row['department'] . "</td>";
        echo "<td class='border p-2'>" . $row['assessment_status'] . "</td>";
        echo "<td class='border p-2'>" . date('M j, Y', strtotime($row['created_date'] ?? 'now')) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='mt-2'>Total: " . $conn->query("SELECT COUNT(*) as count FROM impact_assessments")->fetch_assoc()['count'] . " assessments</p>";
} else {
    echo "<p>No impact assessments found.</p>";
}
echo "</div>";

echo "<div class='flex gap-4 mt-6'>";
echo "<a href='impact-assessment.php' class='bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg'>";
echo "<i class='fa-solid fa-arrow-right mr-2'></i> Go to Impact Assessment";
echo "</a>";
echo "<a href='data-collection.php' class='bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg'>";
echo "<i class='fa-solid fa-arrow-left mr-2'></i> Go to Data Collection";
echo "</a>";
echo "</div>";

$conn->close();
echo "</div></body></html>";
?>