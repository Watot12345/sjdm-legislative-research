<?php
/**
 * HostForge Database Auto-Migration Script
 * Runs the database/legislative_db.sql dump against the connected MySQL/MariaDB database.
 */

require_once __DIR__ . '/config/config.php';

// Security check: restrict execution if needed or allow initial migration
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:sans-serif;background:#1e1e2e;color:#cdd6f4;padding:2rem;} pre{background:#181825;padding:1rem;border-radius:8px;border:1px solid #313244;}</style>";
echo "</head><body>";
echo "<h2>🚀 HostForge Database Auto-Migration</h2>";
echo "<pre>";

try {
    $conn = getDBConnection();
    echo "✅ Connected to MySQL/MariaDB successfully!\n";

    $sqlFile = __DIR__ . '/database/legislative_db.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: " . $sqlFile);
    }

    echo "📄 Reading SQL dump file: database/legislative_db.sql...\n";
    $sql = file_get_contents($sqlFile);

    // Disable foreign key checks for clean import
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");

    echo "⏳ Executing SQL import queries...\n";
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->errno) {
        throw new Exception("SQL Error: " . $conn->error);
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

    echo "✅ Database schema and seed data imported successfully!\n\n";

    // Verify imported tables
    $result = $conn->query("SHOW TABLES;");
    echo "📋 Tables in database:\n";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
    }

} catch (Throwable $e) {
    echo "❌ Migration Failed: " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "</pre>";
echo "<p><a href='login.php' style='color:#89b4fa;'>Go to Login Page &rarr;</a></p>";
echo "</body></html>";
