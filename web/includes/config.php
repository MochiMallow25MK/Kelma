<?php
// Shared setup: session, database connection, and small helper functions.
session_start();

define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'kelma');
define('PORT', 3307);
define('AGENT_URL', 'http://127.0.0.1:8001');
define('PASS_SCORE', 60);

if (file_exists(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';
if (!defined('AZURE_KEY')) define('AZURE_KEY', '');
if (!defined('AZURE_REGION')) define('AZURE_REGION', 'eastus');

$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, PORT);
if ($db->connect_error) die('Database connection failed: ' . $db->connect_error);
$db->set_charset('utf8mb4');

// Escape text before printing it in HTML (prevents XSS)
function e($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }

// Send the visitor to the login page unless they have the right role
function require_role($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header('Location: /kelma/web/login.php');
        exit;
    }
}

// Send a JSON response (used by the api/ files)
function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
