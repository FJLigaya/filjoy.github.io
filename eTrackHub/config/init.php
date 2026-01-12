<?php   
define('ACCESS', true);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

// Start secure session
startSecureSession();

// Get database instance
$db = Database::getInstance()->getConnection();