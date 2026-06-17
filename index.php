<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        redirect(getBaseUrl() . 'admin/dashboard.php');
    } elseif ($role === 'student') {
        redirect(getBaseUrl() . 'student/dashboard.php');
    }
}

redirect(getBaseUrl() . 'login.php');
