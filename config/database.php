<?php
require_once __DIR__ . '/config.php';

$dbUrl = Environment::get('DATABASE_URL');
if (!empty($dbUrl)) {
    $parsed = parse_url($dbUrl);
    if ($parsed) {
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = (int)($parsed['port'] ?? 3306);
        $username = $parsed['user'] ?? 'root';
        $password = $parsed['pass'] ?? '';
        $dbname = ltrim($parsed['path'] ?? '', '/');
    }
}

if (empty($host)) {
    $host = Environment::get('DB_HOST', '127.0.0.1');
    $dbname = Environment::get('DB_NAME', Environment::get('DB_DATABASE', 'legislative_db'));
    $username = Environment::get('DB_USERNAME', 'root');
    $password = Environment::get('DB_PASSWORD', '');
    $port = (int)Environment::get('DB_PORT', 3306);
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    if (file_exists('/opt/lampp/var/mysql/mysql.sock')) {
        try {
            $pdo = new PDO(
                "mysql:dbname=$dbname;unix_socket=/opt/lampp/var/mysql/mysql.sock;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e2) {
            die("Database Connection Failed: " . $e2->getMessage());
        }
    } else {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

?>