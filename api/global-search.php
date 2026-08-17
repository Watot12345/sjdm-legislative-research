<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total' => 0,
        'results' => []
    ]);
    exit();
}

require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

$esc = $conn->real_escape_string($query);
$results = [
    'policies' => [],
    'datasets' => [],
    'assessments' => [],
    'benchmarking' => [],
    'reports' => []
];

// Determine module path prefix based on referer or context
$isInModules = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/modules/') !== false);
$basePath = $isInModules ? '' : 'modules/';

// 1. Policy Documents (Limit 3)
$res_pol = $conn->query("SELECT id, title, category FROM policy_documents WHERE title LIKE '%$esc%' OR category LIKE '%$esc%' LIMIT 3");
if ($res_pol && $res_pol->num_rows > 0) {
    while ($row = $res_pol->fetch_assoc()) {
        $results['policies'][] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'subtitle' => $row['category'] ?: 'Policy Document',
            'icon' => 'fa-book-open',
            'color' => 'blue',
            'url' => $basePath . 'policy-research.php?search=' . urlencode($row['title'])
        ];
    }
}

// 2. Datasets (Limit 3)
$res_ds = $conn->query("SELECT id, dataset_id, dataset_name, source_office, approval_status FROM datasets WHERE dataset_name LIKE '%$esc%' OR dataset_id LIKE '%$esc%' OR source_office LIKE '%$esc%' LIMIT 3");
if ($res_ds && $res_ds->num_rows > 0) {
    while ($row = $res_ds->fetch_assoc()) {
        $results['datasets'][] = [
            'id' => $row['id'],
            'title' => $row['dataset_name'],
            'subtitle' => ($row['source_office'] ?: 'General') . ' (' . ($row['approval_status'] ?: 'Pending') . ')',
            'icon' => 'fa-database',
            'color' => 'green',
            'url' => $basePath . 'data-collection.php?search=' . urlencode($row['dataset_name'])
        ];
    }
}

// 3. Impact Assessments (Limit 3)
$res_ia = $conn->query("SELECT id, assessment_id, policy_title, department, impact_rating FROM impact_assessments WHERE policy_title LIKE '%$esc%' OR assessment_id LIKE '%$esc%' OR department LIKE '%$esc%' LIMIT 3");
if ($res_ia && $res_ia->num_rows > 0) {
    while ($row = $res_ia->fetch_assoc()) {
        $results['assessments'][] = [
            'id' => $row['id'],
            'title' => $row['policy_title'],
            'subtitle' => $row['assessment_id'] . ' &bull; Rating: ' . ($row['impact_rating'] ?: 'N/A'),
            'icon' => 'fa-chart-simple',
            'color' => 'purple',
            'url' => $basePath . 'impact-assessment.php?search=' . urlencode($row['policy_title'])
        ];
    }
}

// 4. Benchmarking (Limit 3)
$res_bm = $conn->query("SELECT id, benchmark_id, policy_title, department, status FROM benchmarking_submissions WHERE policy_title LIKE '%$esc%' OR benchmark_id LIKE '%$esc%' OR department LIKE '%$esc%' LIMIT 3");
if ($res_bm && $res_bm->num_rows > 0) {
    while ($row = $res_bm->fetch_assoc()) {
        $results['benchmarking'][] = [
            'id' => $row['id'],
            'title' => $row['policy_title'],
            'subtitle' => $row['benchmark_id'] . ' &bull; ' . ($row['status'] ?: 'Pending'),
            'icon' => 'fa-scale-balanced',
            'color' => 'indigo',
            'url' => $basePath . 'benchmarking-analysis.php?search=' . urlencode($row['policy_title'])
        ];
    }
}

// 5. Generated Reports (Limit 3)
$res_rep = $conn->query("SELECT id, benchmark_id, policy_title, status FROM benchmarking_submissions WHERE status = 'Report Generated' AND (policy_title LIKE '%$esc%' OR benchmark_id LIKE '%$esc%') LIMIT 3");
if ($res_rep && $res_rep->num_rows > 0) {
    while ($row = $res_rep->fetch_assoc()) {
        $results['reports'][] = [
            'id' => $row['id'],
            'title' => $row['policy_title'],
            'subtitle' => $row['benchmark_id'] . ' &bull; Ready to Download',
            'icon' => 'fa-file-lines',
            'color' => 'emerald',
            'url' => $basePath . 'report-generation.php?benchmark_id=' . urlencode($row['benchmark_id'])
        ];
    }
}

$total_count = count($results['policies']) + count($results['datasets']) + count($results['assessments']) + count($results['benchmarking']) + count($results['reports']);

echo json_encode([
    'success' => true,
    'query' => $query,
    'total' => $total_count,
    'results' => $results
]);
exit();
