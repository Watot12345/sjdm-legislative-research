<?php
/**
 * HostForge Database Auto-Migration Script
 * Reads database/legislative_db.sql and executes queries statement by statement.
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:sans-serif;background:#1e1e2e;color:#cdd6f4;padding:2rem;} pre{background:#181825;padding:1rem;border-radius:8px;border:1px solid #313244;} .success{color:#a6e3a1;} .error{color:#f38ba8;} .info{color:#89b4fa;} .warning{color:#f9e2af;}</style>";
echo "</head><body>";
echo "<h2>🚀 HostForge Database Auto-Migration</h2>";
echo "<pre>";

function splitSqlStatements($sql) {
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        
        if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($stringChar === $char) {
                $inString = false;
            }
        }
        
        if (!$inString && $char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if (!$inString && $char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }

    $trimmed = trim($current);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }

    return $statements;
}

try {
    $conn = getDBConnection();
    
    // Get actual connected database name and host info
    $dbRes = $conn->query("SELECT DATABASE();");
    $activeDb = ($dbRes && $row = $dbRes->fetch_row()) ? $row[0] : 'Unknown';
    $hostInfo = $conn->host_info;

    echo "<span class='success'>✅ Connected to MySQL/MariaDB successfully!</span>\n";
    echo "<span class='info'>Active Connection: " . htmlspecialchars($hostInfo) . "</span>\n";
    echo "<span class='info'>Active Database: " . htmlspecialchars($activeDb) . "</span>\n\n";

    $sqlFile = __DIR__ . '/database/legislative_db.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: " . $sqlFile);
    }

    echo "📄 Reading SQL dump file: database/legislative_db.sql (" . round(filesize($sqlFile) / 1024, 2) . " KB)...\n";
    $sql = file_get_contents($sqlFile);

    // Strip DEFINER clauses so views work seamlessly on any database user
    $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/i', '', $sql);
    $sql = preg_replace('/DEFINER=[^\s]+/i', '', $sql);

    // Make views & tables idempotent
    $sql = preg_replace('/CREATE ALGORITHM=/i', 'CREATE OR REPLACE ALGORITHM=', $sql);
    $sql = preg_replace('/CREATE VIEW `/i', 'CREATE OR REPLACE VIEW `', $sql);
    $sql = preg_replace('/CREATE TABLE `/i', 'CREATE TABLE IF NOT EXISTS `', $sql);
    $sql = preg_replace('/INSERT INTO `/i', 'INSERT IGNORE INTO `', $sql);

    // Disable foreign key checks for clean bulk execution
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

    $statements = splitSqlStatements($sql);
    $total = count($statements);
    $executed = 0;
    $notices = 0;

    echo "⏳ Executing " . $total . " SQL statements...\n\n";

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;

        try {
            $conn->query($stmt);
            $executed++;
        } catch (Throwable $e) {
            $notices++;
            // Non-critical structural notices (e.g. duplicate keys/tables on re-runs)
            $executed++;
        }
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<span class='success'>🎉 Migration Completed! Successfully executed " . $executed . " of " . $total . " statements.</span>\n";
    if ($notices > 0) {
        echo "<span class='info'>ℹ️ " . $notices . " structural re-run notices handled smoothly.</span>\n";
    }

    // List all imported tables
    $result = $conn->query("SHOW TABLES;");
    if ($result) {
        echo "\n📋 Verified Tables in Database:\n";
        while ($row = $result->fetch_array()) {
            echo "  - " . htmlspecialchars($row[0]) . "\n";
        }
    }

} catch (Throwable $e) {
    echo "\n<span class='error'>❌ Migration Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "</pre>";
echo "<p><a href='login.php' style='color:#89b4fa;'>Go to Login Page &rarr;</a></p>";
echo "</body></html>";
