<?php
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Student Portal' ?> — SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= getBaseUrl() ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="sms-body">

<nav class="navbar navbar-expand-lg sms-topbar px-4">
    <a class="navbar-brand fw-bold text-white" href="<?= getBaseUrl() ?>student/dashboard.php">
        <i class="bi bi-mortarboard-fill me-2"></i>Student Portal
    </a>
    <button class="navbar-toggler border-light" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="studentNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
            <li class="nav-item">
                <a class="sms-top-link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>"
                   href="<?= getBaseUrl() ?>student/dashboard.php">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="sms-top-link <?= $currentFile === 'profile.php' ? 'active' : '' ?>"
                   href="<?= getBaseUrl() ?>student/profile.php">
                    <i class="bi bi-person-circle me-1"></i>My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="sms-top-link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>"
                   href="<?= getBaseUrl() ?>student/dashboard.php#notes">
                    <i class="bi bi-bell me-1"></i>Notes
                </a>
            </li>
            <li class="nav-item ms-lg-2">
                <a class="btn btn-sm btn-outline-light" href="<?= getBaseUrl() ?>logout.php">
                    <i class="bi bi-box-arrow-left me-1"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="container py-4">
