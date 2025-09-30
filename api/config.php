<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../xato.log');

// Database Configuration
class Database {
    private $host = 'localhost';
    private $dbname = 'stacknro_blog';
    private $username = 'stacknro_blog';
    private $password = 'admin-2025';
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            error_log("Database connection successful: " . date('Y-m-d H:i:s'));
        } catch(PDOException $e) {
            $errorMsg = "Database connection failed: " . $e->getMessage() . " at " . date('Y-m-d H:i:s');
            error_log($errorMsg);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ma\'lumotlar bazasiga ulanishda xato', 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Enhanced logging function
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $logMessage .= " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $logMessage .= " | User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    error_log($logMessage . "\n", 3, __DIR__ . '/../xato.log');
}

// Enhanced success logging
function logSuccess($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] SUCCESS: $message";
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($logMessage . "\n", 3, __DIR__ . '/../xato.log');
}

// Security Functions
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function validateCaptcha($userInput, $sessionCaptcha) {
    return strtolower(trim($userInput)) === strtolower(trim($sessionCaptcha));
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with(strtolower($email), '@gmail.com');
}

function createSlug($title) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    return $slug;
}

// Response helper
function jsonResponse($data, $status = 200) {
    // Log all responses
    logSuccess("API Response", ['status' => $status, 'data' => $data]);
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
