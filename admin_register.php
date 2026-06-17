<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    redirect(getBaseUrl() . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
}

$errors = [];
$old    = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = compact('username');

    // ── Validation ───────────────────────────────────────────
    if ($username === '')
        $errors['username'] = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username))
        $errors['username'] = 'Username must be 3–30 characters (letters, numbers, underscore only).';

    // No full name, email, phone or department required for admin registration.

    if ($password === '')
        $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';

    if ($confirm === '')
        $errors['confirm_password'] = 'Please confirm your password.';
    elseif ($password !== '' && $confirm !== $password)
        $errors['confirm_password'] = 'Passwords do not match.';

    // ── Uniqueness checks ─────────────────────────────────────
    if (empty($errors['username'])) {
        $chk = $conn->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc())
            $errors['username'] = 'This username is already taken.';
        $chk->close();
    }
    // No email uniqueness check since email is not collected here.

    // ── Insert ────────────────────────────────────────────────
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'INSERT INTO admins (username, password) VALUES (?, ?)'
        );
        $stmt->bind_param('ss', $username, $hashed);
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Registration submitted! Your account is pending approval by a super admin.');
        redirect(getBaseUrl() . 'login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .req-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(237,232,224,.12);
            border: 1px solid rgba(237,232,224,.25);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .76rem;
            color: rgba(237,232,224,.75);
            margin-bottom: 6px;
        }
        .reg-panel-steps {
            margin-top: 28px;
            text-align: left;
        }
        .reg-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }
        .reg-step-num {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(237,232,224,.15);
            color: #EDE8E0;
            font-size: .78rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .reg-step-text {
            font-size: .83rem;
            color: rgba(237,232,224,.65);
            line-height: 1.5;
        }
        .reg-step-text strong { color: rgba(237,232,224,.9); }
        .pass-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e0d8d2;
            margin-top: 6px;
            overflow: hidden;
        }
        .pass-strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width .3s, background .3s;
            width: 0;
        }
        .pass-strength-label {
            font-size: .72rem;
            color: #888;
            margin-top: 3px;
        }
    </style>
</head>
<body class="sms-login-page">

<div class="sms-login-outer">

    <!-- ── Left decorative panel ── -->
    <div class="sms-login-panel d-none d-lg-flex">
        <div class="sms-login-panel-content">
            <div class="sms-login-panel-icon mb-4">
                <i class="bi bi-shield-lock-fill" style="font-size:2rem;color:#EDE8E0"></i>
            </div>
            <div class="sms-login-panel-title">Admin Registration</div>
            <div class="sms-login-panel-desc">
                Create your admin account to manage the Student Management System. New accounts require approval before access is granted.
            </div>

            <div class="reg-panel-steps">
                <div class="reg-step">
                    <div class="reg-step-num">1</div>
                    <div class="reg-step-text"><strong>Fill in your details</strong> — username and a secure password.</div>
                </div>
                <div class="reg-step">
                    <div class="reg-step-num">2</div>
                    <div class="reg-step-text"><strong>Submit for approval</strong> — a super admin will review your request.</div>
                </div>
                <div class="reg-step">
                    <div class="reg-step-num">3</div>
                    <div class="reg-step-text"><strong>Get access</strong> — once approved you can log in and manage students.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Form panel ── -->
    <div class="sms-login-form-side">
        <div class="sms-login-form-box" style="max-width:520px">

            <div class="sms-login-brand-mark">
                <div class="sms-login-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="sms-login-brand-name">Student Management System</div>
            </div>

            <div class="sms-login-form-header">
                <h2>Create Admin Account</h2>
                <p>Fill in the form below — your account will be reviewed before activation.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Please fix the errors below.
                </div>
            <?php endif; ?>

            <div class="sms-login-card-inner">
                <form method="POST" novalidate autocomplete="off">

                    <!-- Row: username -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="sms-form-label" for="username">
                                Username <span class="required">*</span>
                            </label>
                            <input type="text" id="username" name="username"
                                   class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['username']) ?>"
                                   placeholder="e.g. john_admin"
                                   maxlength="30">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                            <?php endif; ?>
                            <div class="form-text" style="font-size:.72rem">3–30 chars, letters/numbers/_</div>
                        </div>
                    </div>

                    <!-- Email removed from admin registration -->

                    <!-- Phone and Department removed from admin registration -->

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="sms-form-label" for="password">
                            Password <span class="required">*</span>
                        </label>
                        <input type="password" id="password" name="password"
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               placeholder="Minimum 8 characters"
                               autocomplete="new-password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                        <div class="pass-strength-bar"><div class="pass-strength-fill" id="strengthFill"></div></div>
                        <div class="pass-strength-label" id="strengthLabel"></div>
                    </div>

                    <!-- Confirm password -->
                    <div class="mb-4">
                        <label class="sms-form-label" for="confirm_password">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                               placeholder="Re-enter your password"
                               autocomplete="new-password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-person-plus me-1"></i>Submit Registration
                    </button>

                    <div class="sms-login-footer-note mt-3">
                        Already have an account? <a href="<?= getBaseUrl() ?>login.php">Sign in here</a>
                    </div>

                </form>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password strength meter
document.getElementById('password').addEventListener('input', function() {
    var val = this.value;
    var score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;

    var fill  = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');
    var levels = [
        [0,   '',        ''],
        [20,  '#DC2626', 'Very weak'],
        [40,  '#D97706', 'Weak'],
        [60,  '#EAB308', 'Fair'],
        [80,  '#22C55E', 'Strong'],
        [100, '#16A34A', 'Very strong']
    ];
    var l = levels[Math.min(score, 5)];
    fill.style.width     = l[0] + '%';
    fill.style.background = l[1];
    label.textContent    = l[2];
    label.style.color    = l[1];
});

// Confirm password live check
document.getElementById('confirm_password').addEventListener('input', function() {
    var pw  = document.getElementById('password').value;
    var cpw = this.value;
    if (cpw === '') { this.classList.remove('is-invalid','is-valid'); return; }
    if (pw === cpw) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});
</script>
</body>
</html>
