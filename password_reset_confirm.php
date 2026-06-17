<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$errors  = [];
$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$student = null;
$expired = false;
$invalid = false;

// ── Validate token on every load ─────────────────────────────
if ($token === '') {
    $invalid = true;
} else {
    $stmt = $conn->prepare(
        'SELECT id, full_name, email, reset_token, reset_expires
         FROM students
         WHERE reset_token = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        $invalid = true;
    } elseif (strtotime($student['reset_expires']) < time()) {
        $expired = true;
        // Clear the expired token
        $clr = $conn->prepare('UPDATE students SET reset_token = NULL, reset_expires = NULL WHERE id = ?');
        $clr->bind_param('i', $student['id']);
        $clr->execute();
        $clr->close();
    }
}

// ── Handle password reset POST ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student && !$expired && !$invalid) {
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password === '') {
        $errors['new_password'] = 'New password is required.';
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters.';
    }
    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm your new password.';
    } elseif ($new_password !== '' && $new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear token in one query
        $upd = $conn->prepare(
            'UPDATE students SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?'
        );
        $upd->bind_param('si', $hash, $student['id']);

        if ($upd->execute()) {
            $upd->close();
            setFlash('success', 'Password reset successful. You can now login with your new password.');
            redirect(getBaseUrl() . 'login.php');
        } else {
            $errors['db'] = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="sms-body">
<div class="sms-login-wrap">
    <div class="sms-login-card">
        <div class="text-center mb-3">
            <div class="sms-login-logo"><i class="bi bi-key-fill"></i></div>
            <h4 class="fw-bold mb-0" style="color:var(--sms-primary)">Set New Password</h4>
        </div>

        <?php if ($invalid): ?>
            <!-- ── Invalid token ── -->
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-x-circle me-2"></i>
                This reset link is invalid. It may have already been used.
            </div>
            <div class="text-center mt-3 small">
                <a href="password_reset.php">Request a new reset link</a>
            </div>

        <?php elseif ($expired): ?>
            <!-- ── Expired token ── -->
            <div class="alert alert-warning py-2 small">
                <i class="bi bi-clock me-2"></i>
                This reset link has expired (links are valid for 30 minutes).
            </div>
            <div class="text-center mt-3 small">
                <a href="password_reset.php">Request a new reset link</a>
            </div>

        <?php else: ?>
            <!-- ── Valid token — show form ── -->
            <p class="text-muted small text-center mb-3">
                Resetting password for <strong><?= e($student['email']) ?></strong>
            </p>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger py-2 small"><?= e($errors['db']) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="mb-3">
                    <label class="sms-form-label" for="new_password">New Password</label>
                    <input type="password"
                           class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                           id="new_password" name="new_password" autofocus>
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['new_password']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Minimum 8 characters</div>
                </div>

                <div class="mb-4">
                    <label class="sms-form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password"
                           class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           id="confirm_password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i>Reset Password
                </button>
            </form>

            <div class="text-center mt-3 small">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
