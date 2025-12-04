<?php
// Start session
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cerdas_cermat';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Helper function to execute queries
function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}

// Helper function to fetch all results
function fetch_all($sql) {
    $result = query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Helper function to fetch single result
function fetch_single($sql) {
    $result = query($sql);
    return $result->fetch_assoc();
}

// Helper function to escape data
function escape($data) {
    global $conn;
    return $conn->real_escape_string($data);
}

// Helper function to get insert id
function get_insert_id() {
    global $conn;
    return $conn->insert_id;
}

// Helper function to start transaction
function begin_transaction() {
    global $conn;
    $conn->begin_transaction();
}

// Helper function to commit transaction
function commit() {
    global $conn;
    $conn->commit();
}

// Helper function to rollback transaction
function rollback() {
    global $conn;
    $conn->rollback();
}

// Authentication functions
function is_teacher() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
}

function require_teacher() {
    if (!is_teacher()) {
        header('Location: login.php');
        exit;
    }
}

function is_participant() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'participant';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>