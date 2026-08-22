<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$pageTitle = "Data Visualization Module";

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

// ============================================
// GET STATISTICS FROM DATABASE
// ============================================

// Impact Assessment Statistics
$total_assessments = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments")->fetch_assoc()['count'];
$high_impact = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE impact_rating = 'High'")->fetch_assoc()['count'];
$moderate_impact = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE impact_rating = 'Moderate'")->fetch_assoc()['count'];
$low_impact = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE impact_rating = 'Low'")->fetch_assoc()['count'];
$very_low_impact = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE impact_rating = 'Very Low'")->fetch_assoc()['count'];
$evaluated_count = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments WHERE kpi_evaluated = 'Yes'")->fetch_assoc()['count'];

// Benchmarking Statistics
$total_benchmarking = (int)$conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions")->fetch_assoc()['count'];
$completed_benchmarking = (int)$conn->query("SELECT COUNT(*) as count FROM benchmarking_submissions WHERE status IN ('Completed', 'Report Generated')")->fetch_assoc()['count'];

// Data Collection Statistics
$total_datasets = (int)$conn->query("SELECT COUNT(*) as count FROM datasets")->fetch_assoc()['count'];
$approved_datasets = (int)$conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status = 'Approved'")->fetch_assoc()['count'];
$pending_datasets = (int)$conn->query("SELECT COUNT(*) as count FROM datasets WHERE approval_status = 'Pending'")->fetch_assoc()['count'];

// Legal Documents Statistics
$total_documents = (int)$conn->query("SELECT COUNT(*) as count FROM supporting_documents")->fetch_assoc()['count'];

// Department-wise Impact
$dept_impact_sql = "SELECT department, COUNT(*) as count, AVG(impact_percentage) as avg_impact 
                    FROM impact_assessments 
                    WHERE department IS NOT NULL AND department != '' 
                    GROUP BY department";
$dept_impact_result = $conn->query($dept_impact_sql);
$departments = [];
$dept_counts = [];
$dept_avg_impact = [];
while ($row = $dept_impact_result->fetch_assoc()) {
    $departments[] = $row['department'];
    $dept_counts[] = $row['count'];
    $dept_avg_impact[] = round($row['avg_impact'], 1);
}

// Monthly trends
$monthly_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $month_num = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $count = (int)$conn->query("SELECT COUNT(*) as count FROM impact_assessments 
                           WHERE MONTH(created_date) = '$month_num' AND YEAR(created_date) = '$year'")->fetch_assoc()['count'];
    $monthly_trends[$month] = $count;
}

// Dynamic Impact Rating Distribution (For Doughnut Chart)
$impact_rating_sql = "SELECT impact_rating, COUNT(*) as count FROM impact_assessments WHERE impact_rating IS NOT NULL AND impact_rating != '' GROUP BY impact_rating";
$impact_rating_result = $conn->query($impact_rating_sql);
$impact_rating_labels = [];
$impact_rating_counts = [];
$impact_rating_colors = [];

$rating_palette = [
    'High' => '#16a34a',
    'Moderate' => '#eab308',
    'Low' => '#f97316',
    'Very Low' => '#dc2626',
    'Pending' => '#94a3b8'
];
$default_palette = ['#16a34a', '#eab308', '#f97316', '#2563eb', '#7c3aed', '#94a3b8'];

if ($impact_rating_result && $impact_rating_result->num_rows > 0) {
    $idx = 0;
    while ($row = $impact_rating_result->fetch_assoc()) {
        $r_name = $row['impact_rating'];
        $impact_rating_labels[] = $r_name . ' Impact';
        $impact_rating_counts[] = (int)$row['count'];
        $impact_rating_colors[] = $rating_palette[$r_name] ?? ($default_palette[$idx % count($default_palette)]);
        $idx++;
    }
} else {
    $impact_rating_labels = ['High Impact', 'Moderate Impact', 'Low Impact', 'Very Low Impact'];
    $impact_rating_counts = [0, 0, 0, 0];
    $impact_rating_colors = ['#16a34a', '#eab308', '#f97316', '#dc2626'];
}

// Dynamic Benchmarking Status Distribution (For Doughnut Chart)
$benchmarking_status_sql = "SELECT status, COUNT(*) as count FROM benchmarking_submissions WHERE status IS NOT NULL AND status != '' GROUP BY status";
$benchmarking_status_result = $conn->query($benchmarking_status_sql);
$benchmarking_status_labels = [];
$benchmarking_status_counts = [];
$benchmarking_status_colors = [];

$bench_status_palette = [
    'Completed' => '#16a34a',
    'Report Generated' => '#2563eb',
    'Evaluated' => '#7c3aed',
    'Pending Comparison' => '#eab308',
    'Pending' => '#f59e0b'
];
$default_bench_colors = ['#2563eb', '#16a34a', '#7c3aed', '#eab308', '#f97316', '#06b6d4'];

if ($benchmarking_status_result && $benchmarking_status_result->num_rows > 0) {
    $idx = 0;
    while ($row = $benchmarking_status_result->fetch_assoc()) {
        $st = $row['status'];
        $benchmarking_status_labels[] = $st;
        $benchmarking_status_counts[] = (int)$row['count'];
        $benchmarking_status_colors[] = $bench_status_palette[$st] ?? ($default_bench_colors[$idx % count($default_bench_colors)]);
        $idx++;
    }
} else {
    $benchmarking_status_labels = ['Pending', 'Completed', 'Evaluated'];
    $benchmarking_status_counts = [0, 0, 0];
    $benchmarking_status_colors = ['#eab308', '#16a34a', '#7c3aed'];
}

// Assessment status distribution
$assessment_status_sql = "SELECT assessment_status, COUNT(*) as count FROM impact_assessments GROUP BY assessment_status";
$assessment_status_result = $conn->query($assessment_status_sql);
$assessment_status_labels = [];
$assessment_status_counts = [];
while ($row = $assessment_status_result->fetch_assoc()) {
    $assessment_status_labels[] = $row['assessment_status'];
    $assessment_status_counts[] = (int)$row['count'];
}

// Module usage statistics
$module_stats = [
    'Data Collection' => $total_datasets,
    'Impact Assessment' => $total_assessments,
    'Benchmarking' => $total_benchmarking,
    'Legal Documents' => $total_documents
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Visualization Module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-container {
            position: relative;
            height: 320px;
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }
        .module-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .module-card:hover {
            border-color: #7c3aed;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.15);
        }
        .module-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .icon-blue { background: #dbeafe; color: #1e40af; }
        .icon-green { background: #d1fae5; color: #065f46; }
        .icon-purple { background: #ede9fe; color: #5b21b6; }
        .icon-orange { background: #fef3c7; color: #b45309; }
    </style>
</head>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="ml-72">

<?php include("../includes/navbar.php"); ?>

<main class="p-8">



    <!-- ========================================= -->
    <!-- MODULE OVERVIEW CARDS -->
    <!-- ========================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="module-card bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4">
                <div class="icon icon-blue">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm">Data Collection</p>
                    <h2 class="text-3xl font-bold mt-1"><?php echo $total_datasets; ?></h2>
                    <p class="text-green-600 text-sm mt-1">
                        <i class="fa-solid fa-check-circle mr-1"></i> <?php echo $approved_datasets; ?> Approved
                    </p>
                </div>
            </div>
        </div>

        <div class="module-card bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4">
                <div class="icon icon-green">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm">Impact Assessment</p>
                    <h2 class="text-3xl font-bold mt-1"><?php echo $total_assessments; ?></h2>
                    <p class="text-purple-600 text-sm mt-1">
                        <i class="fa-solid fa-check-circle mr-1"></i> <?php echo $evaluated_count; ?> Evaluated
                    </p>
                </div>
            </div>
        </div>

        <div class="module-card bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4">
                <div class="icon icon-purple">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm">Benchmarking</p>
                    <h2 class="text-3xl font-bold mt-1"><?php echo $total_benchmarking; ?></h2>
                    <p class="text-green-600 text-sm mt-1">
                        <i class="fa-solid fa-check-circle mr-1"></i> <?php echo $completed_benchmarking; ?> Completed
                    </p>
                </div>
            </div>
        </div>

        <div class="module-card bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4">
                <div class="icon icon-orange">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-sm">Legal Documents</p>
                    <h2 class="text-3xl font-bold mt-1"><?php echo $total_documents; ?></h2>
                    <p class="text-orange-600 text-sm mt-1">
                        <i class="fa-solid fa-robot mr-1"></i> AI Generated
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- FILTER SECTION -->
    <!-- ========================================= -->
    <div class="bg-white rounded-xl shadow p-6 mb-8 no-print">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <select class="border rounded-lg p-3 focus:ring-2 focus:ring-purple-600 focus:outline-none" id="moduleFilter" onchange="updateCharts()">
                <option value="all">All Modules</option>
                <option value="impact">Impact Assessment</option>
                <option value="benchmarking">Benchmarking</option>
                <option value="datacollection">Data Collection</option>
            </select>
            <select class="border rounded-lg p-3 focus:ring-2 focus:ring-purple-600 focus:outline-none">
                <option>All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="border rounded-lg p-3 focus:ring-2 focus:ring-purple-600 focus:outline-none">
                <option>All Impact Ratings</option>
                <option>High</option>
                <option>Moderate</option>
                <option>Low</option>
            </select>
            <input type="month" class="border rounded-lg p-3 focus:ring-2 focus:ring-purple-600 focus:outline-none">
            <button class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg btn-scale">
                <i class="fa-solid fa-filter mr-2"></i>
                Apply Filters
            </button>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- MAIN CHARTS - ROW 1 -->
    <!-- ========================================= -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">

        <!-- Impact Assessment Distribution -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Impact Assessment Distribution</h2>
                    <p class="text-sm text-slate-500">Policies by impact rating</p>
                </div>
                <span class="bg-purple-100 text-purple-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-chart-pie mr-1"></i> <?php echo $total_assessments; ?> Total
                </span>
            </div>
            <div class="chart-container">
                <canvas id="impactDistributionChart"></canvas>
            </div>
        </div>

        <!-- Department Performance -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Department Performance</h2>
                    <p class="text-sm text-slate-500">Average impact percentage by department</p>
                </div>
                <span class="bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-building mr-1"></i> <?php echo count($departments); ?> Departments
                </span>
            </div>
            <div class="chart-container">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- MAIN CHARTS - ROW 2 -->
    <!-- ========================================= -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">

        <!-- Monthly Policy Trends -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Monthly Policy Trends</h2>
                    <p class="text-sm text-slate-500">Assessments created per month</p>
                </div>
                <span class="bg-blue-100 text-blue-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-calendar mr-1"></i> Last 6 Months
                </span>
            </div>
            <div class="chart-container">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Benchmarking Status -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Benchmarking Status</h2>
                    <p class="text-sm text-slate-500">Submission status distribution</p>
                </div>
                <span class="bg-purple-100 text-purple-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-scale-balanced mr-1"></i> <?php echo $total_benchmarking; ?> Total
                </span>
            </div>
            <div class="chart-container">
                <canvas id="benchmarkingChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- MAIN CHARTS - ROW 3 -->
    <!-- ========================================= -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">

        <!-- Assessment Status -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Assessment Status</h2>
                    <p class="text-sm text-slate-500">Current status of all assessments</p>
                </div>
                <span class="bg-yellow-100 text-yellow-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-list mr-1"></i> <?php echo $total_assessments; ?> Total
                </span>
            </div>
            <div class="chart-container">
                <canvas id="assessmentStatusChart"></canvas>
            </div>
        </div>

        <!-- Module Usage -->
        <div class="bg-white rounded-xl shadow p-6 card-hover">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h2 class="text-xl font-bold">Module Usage Overview</h2>
                    <p class="text-sm text-slate-500">Activity across all modules</p>
                </div>
                <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full">
                    <i class="fa-solid fa-cubes mr-1"></i> <?php echo array_sum($module_stats); ?> Total
                </span>
            </div>
            <div class="chart-container">
                <canvas id="moduleUsageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- QUICK STATS CARDS (MOVED UP) -->
    <!-- ========================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 no-print">
        <div class="bg-white rounded-xl shadow p-5 text-center card-hover border border-slate-100">
            <i class="fa-solid fa-check-circle text-green-600 text-3xl mb-3"></i>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($approved_datasets); ?></h3>
            <p class="text-slate-500 mt-1 text-sm font-medium">Approved Datasets</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 text-center card-hover border border-slate-100">
            <i class="fa-solid fa-star text-purple-600 text-3xl mb-3"></i>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($high_impact); ?></h3>
            <p class="text-slate-500 mt-1 text-sm font-medium">High Impact Policies</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 text-center card-hover border border-slate-100">
            <i class="fa-solid fa-scale-balanced text-blue-600 text-3xl mb-3"></i>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($completed_benchmarking); ?></h3>
            <p class="text-slate-500 mt-1 text-sm font-medium">Benchmarking Completed</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 text-center card-hover border border-slate-100">
            <i class="fa-solid fa-robot text-orange-600 text-3xl mb-3"></i>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($total_documents); ?></h3>
            <p class="text-slate-500 mt-1 text-sm font-medium">AI-Generated Documents</p>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- FOOTER -->
    <!-- ========================================= -->
    <footer class="mt-10 border-t pt-6 pb-10 text-center text-slate-500">
        <p>© <?php echo date('Y'); ?> Legislative Research, Policy Analysis, and Impact Evaluation System</p>
        <p class="mt-2">Data Visualization Module - Real-time Analytics</p>
    </footer>

</main>
</div>

<script>
    // =========================================
    // CHART.JS INITIALIZATION
    // =========================================

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        }
    };

    // Colors
    const colors = {
        purple: ['#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd'],
        green: ['#16a34a', '#22c55e', '#4ade80', '#86efac'],
        blue: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd'],
        orange: ['#ea580c', '#f97316', '#fb923c', '#fdba74'],
        red: ['#dc2626', '#ef4444', '#f87171', '#fca5a5'],
        yellow: ['#ca8a04', '#eab308', '#facc15', '#fde047']
    };

    // ---------- 1. Impact Distribution (Doughnut) ----------
    new Chart(document.getElementById('impactDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($impact_rating_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($impact_rating_counts); ?>,
                backgroundColor: <?php echo json_encode($impact_rating_colors); ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            ...commonOptions,
            cutout: '65%',
            plugins: {
                ...commonOptions.plugins,
                legend: {
                    ...commonOptions.plugins.legend,
                    position: 'bottom'
                }
            }
        }
    });

    // ---------- 2. Department Performance (Bar) ----------
    new Chart(document.getElementById('departmentChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departments); ?>,
            datasets: [{
                label: 'Average Impact %',
                data: <?php echo json_encode($dept_avg_impact); ?>,
                backgroundColor: ['#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd', '#4f46e5', '#6366f1'],
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 48,
                categoryPercentage: 0.6,
                barPercentage: 0.75
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + '%'; }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // ---------- 3. Monthly Trends (Line) ----------
    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($monthly_trends)); ?>,
            datasets: [{
                label: 'Assessments Created',
                data: <?php echo json_encode(array_values($monthly_trends)); ?>,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#7c3aed',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 4,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    }
                }
            }
        }
    });

    // ---------- 4. Benchmarking Status (Doughnut) ----------
    new Chart(document.getElementById('benchmarkingChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($benchmarking_status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($benchmarking_status_counts); ?>,
                backgroundColor: <?php echo json_encode($benchmarking_status_colors); ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            ...commonOptions,
            cutout: '60%',
            plugins: {
                ...commonOptions.plugins,
                legend: {
                    ...commonOptions.plugins.legend,
                    position: 'bottom'
                }
            }
        }
    });

    // ---------- 5. Assessment Status (Bar) ----------
    new Chart(document.getElementById('assessmentStatusChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($assessment_status_labels); ?>,
            datasets: [{
                label: 'Assessments',
                data: <?php echo json_encode($assessment_status_counts); ?>,
                backgroundColor: ['#7c3aed', '#3b82f6', '#16a34a', '#eab308', '#f97316', '#dc2626'],
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 48,
                categoryPercentage: 0.6,
                barPercentage: 0.75
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 4,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    }
                }
            }
        }
    });

    // ---------- 6. Module Usage (Bar) ----------
    new Chart(document.getElementById('moduleUsageChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($module_stats)); ?>,
            datasets: [{
                label: 'Total Records',
                data: <?php echo json_encode(array_values($module_stats)); ?>,
                backgroundColor: ['#3b82f6', '#7c3aed', '#16a34a', '#f97316'],
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 48,
                categoryPercentage: 0.6,
                barPercentage: 0.75
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 4,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    }
                }
            }
        }
    });

    // ---------- Filter Function ----------
    function updateCharts() {
        const filter = document.getElementById('moduleFilter').value;
        // This can be extended to filter charts based on module selection
        console.log('Filter selected:', filter);
    }

    // ---------- Button Hover ----------
    document.querySelectorAll("button, .btn-scale").forEach(function(el) {
        el.addEventListener("mouseenter", function() {
            this.classList.add("scale-105", "transition");
        });
        el.addEventListener("mouseleave", function() {
            this.classList.remove("scale-105");
        });
    });

    // Auto-refresh every 60 seconds
    // setInterval(function() { location.reload(); }, 60000);
</script>

</body>
</html>

<?php
$conn->close();
?>