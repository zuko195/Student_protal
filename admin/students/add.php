<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'Add Student';
$errors    = [];
$old       = [
    'full_name' => '', 'email' => '', 'phone' => '',
    'gender' => '', 'course' => '', 'dob' => '',
    'address' => '', 'status' => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $phone     = trim($_POST['phone']     ?? '');
    $gender    = $_POST['gender']         ?? '';
    $course    = trim($_POST['course']    ?? '');
    $dob       = $_POST['dob']            ?? '';
    $address   = trim($_POST['address']   ?? '');
    $status    = $_POST['status']         ?? '';

    $old = compact('full_name','email','phone','gender','course','dob','address','status');

    // Backend validation
    if ($full_name === '')  $errors['full_name'] = 'Full name is required.';
    if ($email === '')      $errors['email']     = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if ($password === '')   $errors['password']  = 'Password is required.';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($phone === '')      $errors['phone']     = 'Phone number is required.';
    elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) $errors['phone'] = 'Invalid phone number format.';
    if ($gender === '')     $errors['gender']    = 'Please select a gender.';
    if ($course === '')     $errors['course']    = 'Course is required.';
    if ($dob === '') {
        $errors['dob'] = 'Date of birth is required.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$d || $d->format('Y-m-d') !== $dob) $errors['dob'] = 'Invalid date of birth.';
        elseif ($d >= new DateTime())              $errors['dob'] = 'Date of birth cannot be in the future.';
    }
    if ($address === '')    $errors['address']   = 'Address is required.';
    if (!in_array($status, ['Active','Inactive'])) $errors['status'] = 'Status is required.';

    // Email duplicate check
    if (!isset($errors['email'])) {
        $chk = $conn->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors['email'] = 'Email already exists.';
        $chk->close();
    }

    // Image
    $imageErrors = validateImage($_FILES['profile_image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if (!empty($imageErrors)) $errors['profile_image'] = $imageErrors[0];

    if (empty($errors)) {
        $filename = uploadImage($_FILES['profile_image']);
        if (!$filename) {
            $errors['profile_image'] = 'Image upload failed. Please try again.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $conn->prepare(
                'INSERT INTO students (full_name,email,password,phone,gender,course,dob,profile_image,address,status,created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $stmt->bind_param('ssssssssss',
                $full_name, $email, $hashed, $phone, $gender,
                $course, $dob, $filename, $address, $status
            );
            if ($stmt->execute()) {
                setFlash('success', 'Student "' . $full_name . '" added successfully.');
                redirect(getBaseUrl() . 'admin/students/list.php');
            } else {
                $errors['db'] = 'Database error. Please try again.';
                deleteImage($filename);
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= getBaseUrl() ?>admin/students/list.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="sms-page-title"><i class="bi bi-person-plus"></i>Add Student</div>
            <p class="sms-page-subtitle mb-0">Fill in the details below to register a new student</p>
        </div>
    </div>
</div>

<?php if (isset($errors['db'])): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($errors['db']) ?></div>
<?php endif; ?>

<div class="sms-card sms-card--structured">

    <div class="sms-card-header">
        <div class="sms-card-title"><i class="bi bi-person-badge"></i> Student Information</div>
    </div>

    <div class="sms-card-body">
        <form method="POST" enctype="multipart/form-data" id="studentForm" novalidate>

            <!-- Section: Personal Details -->
            <div class="form-section-label">Personal Details</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="sms-form-label" for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           id="full_name" name="full_name" value="<?= e($old['full_name']) ?>"
                           placeholder="e.g. Rahul Sharma">
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label" for="email">Email Address <span class="required">*</span></label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           id="email" name="email" value="<?= e($old['email']) ?>"
                           placeholder="e.g. rahul@example.com">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label" for="password">Password <span class="required">*</span></label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           id="password" name="password" data-required="true"
                           placeholder="Minimum 8 characters">
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label" for="phone">Phone Number <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           id="phone" name="phone" value="<?= e($old['phone']) ?>"
                           placeholder="e.g. +91 9876543210">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="gender">Gender <span class="required">*</span></label>
                    <select class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>"
                            id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['gender'])): ?>
                        <div class="invalid-feedback"><?= e($errors['gender']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="dob">Date of Birth <span class="required">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>"
                           id="dob" name="dob" value="<?= e($old['dob']) ?>">
                    <?php if (isset($errors['dob'])): ?>
                        <div class="invalid-feedback"><?= e($errors['dob']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="status">Account Status <span class="required">*</span></label>
                    <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                            id="status" name="status">
                        <option value="">Select Status</option>
                        <?php foreach (['Active','Inactive'] as $s): ?>
                            <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                        <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="sms-form-label" for="address">Address <span class="required">*</span></label>
                    <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                              id="address" name="address" rows="2"
                              placeholder="Enter full address"><?= e($old['address']) ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <div class="invalid-feedback"><?= e($errors['address']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section: Academic Details -->
            <div class="form-section-label">Academic Details</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="sms-form-label" for="course">Course <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['course']) ? 'is-invalid' : '' ?>"
                           id="course" name="course" value="<?= e($old['course']) ?>"
                           placeholder="e.g. B.Tech Computer Science">
                    <?php if (isset($errors['course'])): ?>
                        <div class="invalid-feedback"><?= e($errors['course']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label" for="profile_image">Profile Photo <span class="required">*</span></label>
                    <input type="file" class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>"
                           id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png" data-required="true">
                    <div class="form-text text-muted">JPG, JPEG, PNG — max 5 MB</div>
                    <?php if (isset($errors['profile_image'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['profile_image']) ?></div>
                    <?php endif; ?>
                    <img id="imagePreview" src="" alt="Preview" class="mt-2">
                </div>
            </div>

            <!-- Form actions -->
            <div class="d-flex gap-2 pt-2 border-top mt-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Add Student
                </button>
                <a href="<?= getBaseUrl() ?>admin/students/list.php" class="btn btn-outline-secondary px-4">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
