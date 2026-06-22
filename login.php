<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Already logged in?
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    redirect(getBaseUrl() . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
}

$errors  = [];
$tab     = 'admin'; // default active tab
$oldData = ['username' => '', 'email' => ''];

// Ensure a default admin exists
try {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM admins");
    if ($res) {
        $row = $res->fetch_assoc();
        if ((int)$row['cnt'] === 0) {
            $defaultUser = 'admin';
            $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
            $ins = $conn->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
            $ins->bind_param('ss', $defaultUser, $defaultPass);
            $ins->execute();
            $ins->close();
        }
    }
} catch (Exception $e) { }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'admin';

    if ($tab === 'admin') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $oldData['username'] = $username;

        if ($username === '') $errors['username'] = 'Username is required.';
        if ($password === '') $errors['password'] = 'Password is required.';

        if (empty($errors)) {
            $stmt = $conn->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin  = $result->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['role']       = 'admin';
                $_SESSION['user_id']    = $admin['id'];
                $_SESSION['username']   = $admin['username'];
                $_SESSION['login_time'] = time();
                redirect(getBaseUrl() . 'admin/dashboard.php');
            } else {
                $errors['login'] = 'Invalid username or password.';
            }
        }
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password']   ?? '';
        $oldData['email'] = $email;

        if ($email === '')               $errors['email']    = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
        elseif (!isAllowedEmailDomain($email)) $errors['email'] = 'Only @gmail.com and @rayblaze.com email addresses are allowed.';
        if ($password === '')            $errors['password'] = 'Password is required.';

        if (empty($errors)) {
            $stmt = $conn->prepare('SELECT id, full_name, email, password, status FROM students WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result  = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();

            if ($student && password_verify($password, $student['password'])) {
                if ($student['status'] !== 'Active') {
                    $errors['login'] = 'Your account is inactive. Please contact the administrator.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['role']       = 'student';
                    $_SESSION['user_id']    = $student['id'];
                    $_SESSION['full_name']  = $student['full_name'];
                    $_SESSION['login_time'] = time();
                    redirect(getBaseUrl() . 'student/dashboard.php');
                }
            } else {
                $errors['login'] = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="sms-login-page">

<div class="sms-login-outer">

    <!-- ── Form panel (centered) ── -->
    <div class="sms-login-form-side">
        <div class="sms-login-form-box">

            <div class="sms-login-brand-mark">
                <div class="sms-login-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="sms-login-brand-name">Student Management System</div>
            </div>

            <div class="sms-login-form-header">
                <h2>Welcome back</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            <?php $flashSuccess = getFlash('success'); if (!empty($flashSuccess)): ?>
                <div class="alert alert-success py-2 small mb-3"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <div class="sms-login-card-inner">

                <!-- Tab switcher -->
                <div class="sms-login-tabs" id="loginTabs" role="tablist">
                    <button class="sms-login-tab <?= $tab === 'admin' ? 'active' : '' ?>" data-tab="admin" type="button">
                        <i class="bi bi-shield-lock"></i> Admin
                    </button>
                    <button class="sms-login-tab <?= $tab === 'student' ? 'active' : '' ?>" data-tab="student" type="button">
                        <i class="bi bi-person"></i> Student
                    </button>
                </div>

                <?php if (!empty($errors['login'])): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= e($errors['login']) ?></div>
                <?php endif; ?>

                <!-- Admin Login Form -->
                <div id="adminTab" <?= $tab !== 'admin' ? 'style="display:none"' : '' ?>>
                    <form method="POST" id="loginForm" novalidate>
                        <input type="hidden" name="tab" value="admin">
                        <div class="mb-3">
                            <label class="sms-form-label" for="username">
                                Username <span class="required">*</span>
                            </label>
                            <input type="text"
                                   class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                   id="username" name="username"
                                   value="<?= e($oldData['username']) ?>"
                                   placeholder="Enter your username"
                                   autocomplete="username">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-4">
                            <label class="sms-form-label" for="password">
                                Password <span class="required">*</span>
                            </label>
                            <input type="password"
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   id="password" name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In as Admin
                        </button>
                    </form>
                </div>

                <!-- Student Login Form -->
                <div id="studentTab" <?= $tab !== 'student' ? 'style="display:none"' : '' ?>>
                    <form method="POST" id="loginFormStudent" novalidate>
                        <input type="hidden" name="tab" value="student">
                        <div class="mb-3">
                            <label class="sms-form-label" for="email">
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email"
                                   class="form-control <?= ($tab === 'student' && isset($errors['email'])) ? 'is-invalid' : '' ?>"
                                   id="email" name="email"
                                   value="<?= e($oldData['email']) ?>"
                                   placeholder="Enter your email"
                                   autocomplete="email">
                            <?php if ($tab === 'student' && isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-4">
                            <label class="sms-form-label" for="passwordS">
                                Password <span class="required">*</span>
                            </label>
                            <input type="password"
                                   class="form-control <?= ($tab === 'student' && isset($errors['password'])) ? 'is-invalid' : '' ?>"
                                   id="passwordS" name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password">
                            <?php if ($tab === 'student' && isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In as Student
                        </button>
                    </form>

                    <div class="sms-login-footer-note mt-3">
                        Don't have an account? <a href="register.php">Register here</a><br>
                        <a href="password_reset.php">Forgot your password?</a>
                    </div>
                </div>

            </div><!-- /.sms-login-card-inner -->

        </div><!-- /.sms-login-form-box -->
    </div><!-- /.sms-login-form-side -->

</div><!-- /.sms-login-outer -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validation.js"></script>
<script>
// Tab switching
document.querySelectorAll('.sms-login-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.sms-login-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        var tab = this.dataset.tab;
        document.getElementById('adminTab').style.display   = tab === 'admin'   ? '' : 'none';
        document.getElementById('studentTab').style.display = tab === 'student' ? '' : 'none';
    });
});

// Student form validation
var sForm = document.getElementById('loginFormStudent');
if (sForm) {
    sForm.addEventListener('submit', function(e) {
        var valid = true;
        var email = document.getElementById('email');
        var pass  = document.getElementById('passwordS');
        [email, pass].forEach(function(f) {
            if (f) {
                f.classList.remove('is-invalid');
                var fb = f.parentElement.querySelector('.invalid-feedback');
                if (fb) fb.textContent = '';
            }
        });
        if (email && email.value.trim() === '') {
            email.classList.add('is-invalid');
            var fb = email.parentElement.querySelector('.invalid-feedback');
            if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; email.parentElement.appendChild(fb); }
            fb.textContent = 'Email is required.'; valid = false;
        } else if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.classList.add('is-invalid');
            var fb = email.parentElement.querySelector('.invalid-feedback');
            if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; email.parentElement.appendChild(fb); }
            fb.textContent = 'Invalid email format.'; valid = false;
        } else if (email && !['gmail.com','rayblaze.com'].includes(email.value.trim().split('@')[1]?.toLowerCase())) {
            email.classList.add('is-invalid');
            var fb = email.parentElement.querySelector('.invalid-feedback');
            if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; email.parentElement.appendChild(fb); }
            fb.textContent = 'Only @gmail.com and @rayblaze.com email addresses are allowed.'; valid = false;
        }
        if (pass && pass.value.trim() === '') {
            pass.classList.add('is-invalid');
            var fb = pass.parentElement.querySelector('.invalid-feedback');
            if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; pass.parentElement.appendChild(fb); }
            fb.textContent = 'Password is required.'; valid = false;
        }
        if (!valid) e.preventDefault();
    });
}
</script>
</body>
</html>
