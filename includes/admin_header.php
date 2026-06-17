<?php
// Determine active page for nav highlighting
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> — SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= getBaseUrl() ?>assets/css/style.css" rel="stylesheet">
</head>

<body class="sms-body">

    <!-- Mobile top bar -->
    <nav class="navbar navbar-dark sms-topbar d-lg-none px-3">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <i class="bi bi-mortarboard-fill"></i>SMS
        </a>
        <button class="btn btn-outline-light btn-sm" id="sidebarToggle" aria-label="Toggle menu">
            <i class="bi bi-list fs-5"></i>
        </button>
    </nav>

    <div class="d-flex" id="wrapper">

        <!-- ── Sidebar ── -->
        <div class="sms-sidebar" id="sidebar">

            <div class="sms-sidebar-brand">
                <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <span>SMS Admin</span>
            </div>

            <div class="px-2 mt-2">
                <div class="sms-nav-section">Main Menu</div>

                <a class="sms-nav-link <?= ($currentFile === 'dashboard.php' && $currentDir === 'admin') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>

                <div class="sms-nav-section mt-2">Students</div>

                <a class="sms-nav-link <?= ($currentFile === 'list.php') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/students/list.php">
                    <i class="bi bi-people"></i>All Students
                </a>

                <a class="sms-nav-link <?= ($currentFile === 'add.php') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/students/add.php">
                    <i class="bi bi-person-plus"></i>Add Student
                </a>

                <div class="sms-nav-section mt-2">Notes</div>

                <a class="sms-nav-link <?= ($currentDir === 'notes') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/notes/list.php">
                    <i class="bi bi-bell"></i>All Notes
                </a>

                <a class="sms-nav-link <?= ($currentFile === 'compose.php') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/notes/compose.php">
                    <i class="bi bi-pencil-square"></i>Compose Note
                </a>

                <div class="sms-nav-section mt-2">Account</div>

                <a class="sms-nav-link <?= ($currentFile === 'profile.php' && $currentDir === 'admin') ? 'active' : '' ?>"
                    href="<?= getBaseUrl() ?>admin/profile.php">
                    <i class="bi bi-person-circle"></i>My Profile
                </a>
            </div>

            <div class="sms-sidebar-footer">
                <div class="sms-sidebar-user">
                    <div class="sms-sidebar-user-avatar">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="sms-sidebar-user-name"><?= e($_SESSION['username'] ?? 'Admin') ?></div>
                </div>
                <a href="<?= getBaseUrl() ?>logout.php" class="sms-nav-link sms-nav-link--danger">
                    <i class="bi bi-box-arrow-left"></i>Logout
                </a>
            </div>

        </div>
        <!-- ── Page content ── -->
        <div class="sms-content" id="pageContent">