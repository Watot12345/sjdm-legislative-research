<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
require_once __DIR__ . '/../config/config.php';
$conn = getDBConnection();

if (isset($_GET['doc_id'])) {
    $doc_id = $conn->real_escape_string($_GET['doc_id']);
    
    $sql = "SELECT * FROM supporting_documents WHERE document_id = '$doc_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $doc = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'document_id' => $doc['document_id'],
            'title' => $doc['title'],
            'category' => $doc['category'],
            'content' => $doc['content'],
            'generated_by' => $doc['generated_by'],
            'generated_date' => $doc['generated_date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found. ID: ' . $doc_id]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No document ID provided']);
}

$conn->close();
?>