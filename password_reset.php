<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } elseif (!isAllowedEmailDomain($email)) {
        $errors['email'] = 'Only @gmail.com and @rayblaze.com email addresses are allowed.';
    } else {
        // Look up student
        $stmt = $conn->prepare('SELECT id, full_name, email FROM students WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always show success message to prevent email enumeration
        if ($student) {
            $token   = bin2hex(random_bytes(32)); // 64-char secure token
            $expires = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

            $upd = $conn->prepare('UPDATE students SET reset_token = ?, reset_expires = ? WHERE id = ?');
            $upd->bind_param('ssi', $token, $expires, $student['id']);
            $upd->execute();
            $upd->close();

            sendResetEmail($student['email'], $student['full_name'], $token);
        }

        $success = 'If an account with that email exists, a password reset link has been sent. Check your inbox.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="sms-body">
<div class="sms-login-wrap">
    <div class="sms-login-card">
        <div class="text-center mb-3">
            <div class="sms-login-logo"><i class="bi bi-envelope-fill"></i></div>
            <h4 class="fw-bold mb-0" style="color:var(--sms-primary)">Reset Password</h4>
            <p class="text-muted small">Enter your email to receive a reset link</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
            </div>
            <div class="text-center mt-3 small">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </div>
        <?php else: ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="sms-form-label" for="email">Email Address</label>
                <input type="email"
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="your@gmail.com or your@rayblaze.com"
                       autofocus>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                <?php endif; ?>
                <div class="form-text">Accepted: @gmail.com and @rayblaze.com</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-send me-1"></i>Send Reset Link
            </button>

            <div class="text-center mt-3 small">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
