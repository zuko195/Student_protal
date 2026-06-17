<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'phone' => '', 'gender' => '', 'course' => '', 'dob' => '', 'address' => '', 'security_question' => '', 'security_answer' => ''];

$securityQuestions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'In what city were you born?',
    'What is your favorite book?',
    'What is your favorite movie?',
    'What is the name of your best friend from childhood?',
    'What was your first car?',
    'What is your favorite sports team?',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $phone     = trim($_POST['phone'] ?? '');
    $gender    = $_POST['gender'] ?? '';
    $course    = trim($_POST['course'] ?? '');
    $dob       = $_POST['dob'] ?? '';
    $address   = trim($_POST['address'] ?? '');
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer   = trim($_POST['security_answer'] ?? '');

    $old['full_name'] = $full_name;
    $old['email']     = $email;
    $old['phone']     = $phone;
    $old['gender']    = $gender;
    $old['course']    = $course;
    $old['dob']       = $dob;
    $old['address']   = $address;
    $old['security_question'] = $security_question;
    $old['security_answer']   = $security_answer;

    if ($full_name === '') $errors['full_name'] = 'Full name is required.';
    if ($email === '') $errors['email'] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if ($password === '') $errors['password'] = 'Password is required.';
    if ($confirm === '') $errors['confirm_password'] = 'Please confirm your password.';
    if ($password !== '' && $confirm !== '' && $password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if ($phone !== '' && !preg_match('/^[0-9\-\+\s]+$/', $phone)) $errors['phone'] = 'Invalid phone number.';
    
    if ($security_question === '') $errors['security_question'] = 'Please select a security question.';
    if ($security_answer === '') $errors['security_answer'] = 'Security answer is required.';
    if (strlen($security_answer) < 2) $errors['security_answer'] = 'Security answer must be at least 2 characters.';

    // Handle optional profile image
    $profileImage = '';
    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imgErrors = validateImage($_FILES['profile_image']);
        if (!empty($imgErrors)) {
            $errors['profile_image'] = implode(' ', $imgErrors);
        } else {
            $uploaded = uploadImage($_FILES['profile_image']);
            if ($uploaded === false) {
                $errors['profile_image'] = 'Failed to save uploaded image.';
            } else {
                $profileImage = $uploaded;
            }
        }
    }

    if (empty($errors)) {
        // Check email uniqueness
        $stmt = $conn->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $answerHash = password_hash($security_answer, PASSWORD_DEFAULT);
            $ins  = $conn->prepare('INSERT INTO students (full_name, email, password, phone, gender, course, dob, profile_image, address, security_question, security_answer, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $status = 'Active';
            $ins->bind_param('ssssssssssss', $full_name, $email, $hash, $phone, $gender, $course, $dob, $profileImage, $address, $security_question, $answerHash, $status);
            if ($ins->execute()) {
                $ins->close();
                setFlash('success', 'Registration successful. You can now login.');
                redirect(getBaseUrl() . 'login.php');
            } else {
                $errors['general'] = 'Registration failed. Please try again later.';
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
    <title>Register — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="sms-body">
<div class="sms-login-wrap">
    <div class="sms-login-card">
        <div class="text-center mb-3">
            <div class="sms-login-logo"><i class="bi bi-mortarboard-fill"></i></div>
            <h4 class="fw-bold mb-0" style="color:var(--sms-primary)">Register</h4>
            <p class="text-muted small">Create a student account</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger py-2 small"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label class="sms-form-label" for="full_name">Full name</label>
                <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                       id="full_name" name="full_name" value="<?= e($old['full_name']) ?>">
                <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="sms-form-label" for="email">Email</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       id="email" name="email" value="<?= e($old['email']) ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="password">Password</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           id="password" name="password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="confirm_password">Confirm password</label>
                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           id="confirm_password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="phone">Phone</label>
                    <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($old['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="gender">Gender</label>
                    <select class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>" id="gender" name="gender">
                        <option value="">Select gender</option>
                        <option value="Male" <?= $old['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $old['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <?php if (isset($errors['gender'])): ?>
                        <div class="invalid-feedback"><?= e($errors['gender']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="course">Course</label>
                    <input type="text" class="form-control <?= isset($errors['course']) ? 'is-invalid' : '' ?>" id="course" name="course" value="<?= e($old['course']) ?>">
                    <?php if (isset($errors['course'])): ?>
                        <div class="invalid-feedback"><?= e($errors['course']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="sms-form-label" for="dob">Date of birth</label>
                    <input type="date" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>" id="dob" name="dob" value="<?= e($old['dob']) ?>">
                    <?php if (isset($errors['dob'])): ?>
                        <div class="invalid-feedback"><?= e($errors['dob']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="sms-form-label" for="security_question">Security Question <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['security_question']) ? 'is-invalid' : '' ?>" id="security_question" name="security_question">
                    <option value="">Select a security question</option>
                    <?php foreach ($securityQuestions as $question): ?>
                        <option value="<?= e($question) ?>" <?= $old['security_question'] === $question ? 'selected' : '' ?>>
                            <?= e($question) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['security_question'])): ?>
                    <div class="invalid-feedback"><?= e($errors['security_question']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="sms-form-label" for="security_answer">Security Answer <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['security_answer']) ? 'is-invalid' : '' ?>"
                       id="security_answer" name="security_answer" value="<?= e($old['security_answer']) ?>" placeholder="Answer (case-insensitive)">
                <?php if (isset($errors['security_answer'])): ?>
                    <div class="invalid-feedback"><?= e($errors['security_answer']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="sms-form-label" for="profile_image">Profile image (optional)</label>
                <input type="file" accept="image/*" class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>" id="profile_image" name="profile_image">
                <?php if (isset($errors['profile_image'])): ?>
                    <div class="invalid-feedback"><?= e($errors['profile_image']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="sms-form-label" for="address">Address</label>
                <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address"><?= e($old['address']) ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="invalid-feedback"><?= e($errors['address']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-person-plus me-1"></i>Register
            </button>

            <div class="text-center mt-3 small">
                <a href="login.php">Back to login</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
