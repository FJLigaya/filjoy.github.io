<?php
require_once '../config/init.php';

if (isLoggedIn()) {
    jsonResponse(true, "Session active", [
        'role' => $_SESSION['role'],
        'name' => $_SESSION['name']
    ]);
} else {
    jsonResponse(false, "No active session");
}