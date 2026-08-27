<?php
// ============================================
// ENVIRONMENT VARIABLES LOADER
// ============================================

class Environment {
    private static $variables = [];
    
    public static function load($filePath) {
        if (!file_exists($filePath)) {
            // .env file is optional in containerized/cloud environments
            return;
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variable
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                if (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    public static function get($key, $default = null) {
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    public static function getBool($key, $default = true) {
        $value = self::get($key, null);
        if ($value === null) {
            return (bool)$default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $lower = strtolower(trim((string)$value));
        return in_array($lower, ['true', '1', 'on', 'yes'], true);
    }
    
    public static function all() {
        return self::$variables;
    }
}

// Load the .env file if present
Environment::load(__DIR__ . '/.env');

// Set timezone
$timezone = Environment::get('TIMEZONE', 'Asia/Manila');
date_default_timezone_set($timezone);

// Error reporting for development
if (Environment::get('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ============================================
// DATABASE CONNECTION FUNCTION
// ============================================
function getDBConnection() {
    $dbUrl = Environment::get('DATABASE_URL');
    if (!empty($dbUrl)) {
        $parsed = parse_url($dbUrl);
        if ($parsed) {
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = (int)($parsed['port'] ?? 3306);
            $username = $parsed['user'] ?? 'root';
            $password = $parsed['pass'] ?? '';
            $database = ltrim($parsed['path'] ?? '', '/');
        }
    }

    if (empty($host)) {
        $host = Environment::get('DB_HOST', '127.0.0.1');
        $username = Environment::get('DB_USERNAME', 'root');
        $password = Environment::get('DB_PASSWORD', '');
        $database = Environment::get('DB_NAME', Environment::get('DB_DATABASE', 'legislative_db'));
        $port = (int)Environment::get('DB_PORT', 3306);
    }
    
    // In LAMPP / Linux environments, 127.0.0.1 forces TCP connection over port 3306
    if ($host === 'localhost' && !file_exists('/opt/lampp/var/mysql/mysql.sock')) {
        $host = '127.0.0.1';
    }
    
    $conn = null;
    try {
        $conn = @new mysqli($host, $username, $password, $database, $port);
    } catch (Throwable $e) {
        $conn = null;
    }
    
    if ((!$conn || $conn->connect_error) && file_exists('/opt/lampp/var/mysql/mysql.sock')) {
        try {
            $conn = @new mysqli('localhost', $username, $password, $database, null, '/opt/lampp/var/mysql/mysql.sock');
        } catch (Throwable $e) {}
    }

    if (!$conn || $conn->connect_error) {
        // Fallback connection attempt for local LAMPP environment
        try {
            $conn = @new mysqli('127.0.0.1', 'root', '', 'legislative_db', 3306);
        } catch (Throwable $e) {}
    }
    
    if (!$conn || $conn->connect_error) {
        die("Database Connection failed: " . ($conn ? $conn->connect_error : "Could not connect to database host"));
    }
    
    return $conn;
}

// ============================================
// GEMINI API CONFIGURATION
// ============================================
function getGeminiConfig() {
    $primaryKey = Environment::get('GEMINI_API_KEY', '');
    $fallbackKeys = Environment::get('GEMINI_FALLBACK_API_KEYS', '');
    $backupKey = Environment::get('GEMINI_BACKUP_API_KEY', '');
    
    $rawKeys = [];
    if (!empty($primaryKey)) {
        $rawKeys = array_merge($rawKeys, explode(',', $primaryKey));
    }
    if (!empty($fallbackKeys)) {
        $rawKeys = array_merge($rawKeys, explode(',', $fallbackKeys));
    }
    if (!empty($backupKey)) {
        $rawKeys = array_merge($rawKeys, explode(',', $backupKey));
    }

    $keys = array_values(array_unique(array_filter(array_map('trim', $rawKeys))));

    return [
        'api_key' => $keys[0] ?? '',
        'api_keys' => $keys,
        'model' => Environment::get('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'max_tokens' => Environment::get('GEMINI_MAX_TOKENS', 2048),
        'temperature' => Environment::get('GEMINI_TEMPERATURE', 0.7)
    ];
}

// ============================================
// AUTH, 2FA & TOAST NOTIFICATION HELPERS
// ============================================
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/toast.php';

// Enforce 12-hour session lifetime limit on all authenticated requests
enforceSessionTimeout((int)Environment::get('AUTH_SESSION_LIFETIME_SECONDS', 43200));