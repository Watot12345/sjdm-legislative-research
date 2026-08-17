<?php
// ============================================
// ENVIRONMENT VARIABLES LOADER
// ============================================

class Environment {
    private static $variables = [];
    
    public static function load($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at: " . $filePath);
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
    
    public static function all() {
        return self::$variables;
    }
}

// Load the .env file
try {
    Environment::load(__DIR__ . '/.env');
} catch (Exception $e) {
    die("Error loading configuration: " . $e->getMessage());
}

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
    $host = Environment::get('DB_HOST', '127.0.0.1');
    $username = Environment::get('DB_USERNAME', 'root');
    $password = Environment::get('DB_PASSWORD', '');
    $database = Environment::get('DB_NAME', 'legislative_db');
    
    // In LAMPP / Linux environments, 127.0.0.1 forces TCP connection over port 3306
    if ($host === 'localhost') {
        $host = '127.0.0.1';
    }
    
    $conn = @new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        // Fallback connection attempt using LAMPP default socket
        $conn = new mysqli('localhost', $username, $password, $database, null, '/opt/lampp/var/mysql/mysql.sock');
    }
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// ============================================
// GEMINI API CONFIGURATION
// ============================================
function getGeminiConfig() {
    return [
        'api_key' => Environment::get('GEMINI_API_KEY', ''),
        'model' => Environment::get('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'max_tokens' => Environment::get('GEMINI_MAX_TOKENS', 2048),
        'temperature' => Environment::get('GEMINI_TEMPERATURE', 0.7)
    ];
}