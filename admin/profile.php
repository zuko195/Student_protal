<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$pageTitle = 'My Profile';

$adminId = (int)$_SESSION['user_id'];

// Load current data
$stmt = $conn->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    session_destroy();
    redirect(getBaseUrl() . 'login.php');
}

$errors  = [];
$success = '';
$old     = $admin;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['new_username'] ?? '');
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $old = array_merge($admin, compact('new_username', 'old_password', 'new_password', 'confirm_password'));

    // Validation
    if ($new_username === '') {
        $errors['new_username'] = 'Username is required.';
    } else {
        // Check if new username is already taken (and not same as current)
        if ($new_username !== $admin['username']) {
            $stmt = $conn->prepare('SELECT id FROM admins WHERE username = ? AND id != ?');
            $stmt->bind_param('si', $new_username, $adminId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['new_username'] = 'This username is already taken.';
            }
            $stmt->close();
        }
    }

    // Password change validation
    if (!empty($new_password)) {
        if ($old_password === '') {
            $errors['old_password'] = 'Current password is required to change password.';
        } else {
            // Verify old password
            $stmt = $conn->prepare('SELECT password FROM admins WHERE id = ?');
            $stmt->bind_param('i', $adminId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!password_verify($old_password, $result['password'])) {
                $errors['old_password'] = 'Current password is incorrect.';
            }
        }

        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        }
    }

    // Update if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE admins SET username = ? WHERE id = ?');
        $stmt->bind_param('si', $new_username, $adminId);

        if ($stmt->execute()) {
            // Update password if provided
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $stmt2->bind_param('si', $hashed, $adminId);
                $stmt2->execute();
                $stmt2->close();
            }

            // Update session
            $_SESSION['username'] = $new_username;

            // Reload admin data
            $stmt2 = $conn->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
            $stmt2->bind_param('i', $adminId);
            $stmt2->execute();
            $admin = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $old = $admin;

            $success = 'Profile updated successfully.';
        } else {
            $errors['db'] = 'Database error. Please try again.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="sms-page-header">
    <div>
        <div class="sms-page-title"><i class="bi bi-person-circle"></i>My Profile</div>
        <p class="sms-page-subtitle mb-0">Manage your admin account</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success py-2 small alert-dismissible fade show">
        <?= e($success) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($errors['db'])): ?>
    <div class="alert alert-danger py-2 small"><?= e($errors['db']) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Current info (read-only) -->
    <div class="col-md-4">
        <div class="sms-card text-center">
            <div class="sms-avatar-lg mb-3" style="width: 100px; height: 100px; margin: 0 auto; background: linear-gradient(135deg, var(--sms-primary) 0%, var(--sms-primary-lt) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="fw-bold"><?= e($admin['username']) ?></div>
            <div class="text-muted small mb-3">Administrator</div>

            <hr>
            <div class="text-start">
                <p class="small text-muted mb-0"><strong>Role:</strong> Admin</p>
            </div>
        </div>
    </div>

    <!-- Editable fields -->
    <div class="col-md-8">
        <div class="sms-card">
            <div class="sms-card-title"><i class="bi bi-pencil-square"></i> Update Profile</div>
            <form method="POST" id="profileForm" novalidate>

                <div class="mb-3">
                    <label class="sms-form-label" for="new_username">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['new_username']) ? 'is-invalid' : '' ?>"
                           id="new_username" name="new_username" value="<?= e($old['new_username'] ?? $admin['username']) ?>">
                    <?php if (isset($errors['new_username'])): ?>
                        <div class="invalid-feedback"><?= e($errors['new_username']) ?></div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">
                <div class="mb-3">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Leave password fields empty to keep current password</small>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="old_password">Current Password</label>
                    <input type="password" class="form-control <?= isset($errors['old_password']) ? 'is-invalid' : '' ?>"
                           id="old_password" name="old_password">
                    <?php if (isset($errors['old_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['old_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="new_password">New Password</label>
                    <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                           id="new_password" name="new_password">
                    <div class="form-text">min 8 characters</div>
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['new_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="sms-form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           id="confirm_password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
