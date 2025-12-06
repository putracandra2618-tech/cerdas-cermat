<?php
/**
 * Configuration File for Cerdas Cermat System
 * Database connection, admin credentials, and helper functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// Database Configuration
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cerdas_cermat');

// =====================================================
// Admin Credentials (Static, No Hashing)
// =====================================================
define('ADMIN_USERNAME', 'panitia');
define('ADMIN_PASSWORD', 'texmaco25');

// =====================================================
// Scoring Configuration
// =====================================================
define('CORRECT_POINTS', 10);
define('WRONG_POINTS', 0);

// =====================================================
// Database Connection
// =====================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// =====================================================
// Database Helper Functions
// =====================================================

/**
 * Execute a query and return result
 */
function query($sql)
{
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Query failed: " . $conn->error . " | SQL: " . $sql);
        return false;
    }
    return $result;
}

/**
 * Fetch all results as associative array
 */
function fetch_all($sql)
{
    $result = query($sql);
    if (!$result)
        return [];

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

/**
 * Fetch single result as associative array
 */
function fetch_single($sql)
{
    $result = query($sql);
    if (!$result)
        return null;
    return $result->fetch_assoc();
}

/**
 * Escape data for SQL queries
 */
function escape($data)
{
    global $conn;
    return $conn->real_escape_string($data);
}

/**
 * Get last insert ID
 */
function get_insert_id()
{
    global $conn;
    return $conn->insert_id;
}

/**
 * Begin transaction
 */
function begin_transaction()
{
    global $conn;
    $conn->begin_transaction();
}

/**
 * Commit transaction
 */
function commit()
{
    global $conn;
    $conn->commit();
}

/**
 * Rollback transaction
 */
function rollback()
{
    global $conn;
    $conn->rollback();
}

// =====================================================
// Authentication Functions
// =====================================================

/**
 * Check if admin is logged in
 */
function is_admin()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin authentication
 */
function require_admin()
{
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Logout admin
 */
function logout_admin()
{
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

/**
 * Check if participant is logged in
 */
function is_participant()
{
    return isset($_SESSION['participant_team_id']) && isset($_SESSION['participant_room_id']);
}

/**
 * Get participant team ID
 */
function get_participant_team_id()
{
    return $_SESSION['participant_team_id'] ?? null;
}

/**
 * Get participant room ID
 */
function get_participant_room_id()
{
    return $_SESSION['participant_room_id'] ?? null;
}

// =====================================================
// Utility Functions
// =====================================================

/**
 * Generate random room code
 */
function generate_room_code()
{
    $prefix = 'TEX25';
    $suffix = sprintf('%02d', rand(1, 99));
    return $prefix . '-' . $suffix;
}

/**
 * Get current microtime as decimal
 */
function get_microtime()
{
    return microtime(true);
}

/**
 * Calculate time difference in seconds
 */
function time_diff($start_time, $end_time = null)
{
    if ($end_time === null) {
        $end_time = get_microtime();
    }
    return round($end_time - $start_time, 4);
}

/**
 * Redirect to URL
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * JSON response
 */
function json_response($data, $success = true)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

/**
 * JSON error response
 */
function json_error($message, $code = 400)
{
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

?>