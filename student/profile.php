<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

$pageTitle = 'My Profile';

$userId = (int)$_SESSION['user_id'];

// Load current data
$stmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    redirect(getBaseUrl() . 'login.php');
}

$errors  = [];
$success = '';
$old     = $student;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $gender    = trim($_POST['gender'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $course    = trim($_POST['course'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    $old = array_merge($student, compact('full_name','email','phone','gender','dob','course','address'));

    // Validation
    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    }
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email address.';
    } else {
        // Ensure email is unique among other students
        $check = $conn->prepare('SELECT id FROM students WHERE email = ? AND id <> ? LIMIT 1');
        $check->bind_param('si', $email, $userId);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();
        if ($exists) {
            $errors['email'] = 'This email is already taken.';
        }
    }
    if ($phone === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format.';
    }
    if ($gender !== '' && !in_array($gender, ['Male','Female','Other'], true)) {
        $errors['gender'] = 'Invalid gender selection.';
    }
    if ($dob !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$d) $errors['dob'] = 'Invalid date of birth.';
    }
    if ($address === '') {
        $errors['address'] = 'Address is required.';
    }

    // Image (optional)
    $newFilename = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $imageErrors = validateImage($_FILES['profile_image']);
        if (!empty($imageErrors)) {
            $errors['profile_image'] = $imageErrors[0];
        } else {
            $newFilename = uploadImage($_FILES['profile_image']);
            if (!$newFilename) $errors['profile_image'] = 'Image upload failed. Please try again.';
        }
    }

    if (empty($errors)) {
        $finalImage = $newFilename ?? $student['profile_image'];

        $stmt = $conn->prepare('UPDATE students SET full_name=?, email=?, phone=?, gender=?, dob=?, address=?, course=?, profile_image=? WHERE id=?');
        $stmt->bind_param('ssssssssi', $full_name, $email, $phone, $gender, $dob, $address, $course, $finalImage, $userId);

        if ($stmt->execute()) {
            if ($newFilename && $student['profile_image'] !== $newFilename) {
                deleteImage($student['profile_image']);
            }
            // Refresh session name
            $_SESSION['full_name'] = $full_name;
            // Reload student data
            $stmt2 = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
            $stmt2->bind_param('i', $userId);
            $stmt2->execute();
            $student = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $old     = $student;
            $success = 'Profile updated successfully.';
        } else {
            $errors['db'] = 'Database error. Please try again.';
            if ($newFilename) deleteImage($newFilename);
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="sms-page-header">
    <div>
        <div class="sms-page-title"><i class="bi bi-person-circle"></i>My Profile</div>
        <p class="sms-page-subtitle mb-0">Manage your student account</p>
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
            <img src="<?= getBaseUrl() . UPLOAD_URL . e($student['profile_image']) ?>"
                 class="sms-avatar-lg mb-3"
                 id="currentImage"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&size=100&background=3D2B1F&color=fff'"
                 alt="">
            <div class="fw-bold"><?= e($student['full_name']) ?></div>
            <div class="text-muted small"><?= e($student['course']) ?></div>
            <div class="mt-2">
                <?php if ($student['status'] === 'Active'): ?>
                    <span class="badge-active">Active</span>
                <?php else: ?>
                    <span class="badge-inactive">Inactive</span>
                <?php endif; ?>
            </div>

            <hr>
            <div class="text-start">
                <p class="small text-muted mb-1"><strong>Email:</strong> <?= e($student['email']) ?></p>
                <p class="small text-muted mb-1"><strong>Course:</strong> <?= e($student['course']) ?></p>
                <p class="small text-muted mb-1"><strong>Gender:</strong> <?= e($student['gender']) ?></p>
                <p class="small text-muted mb-0"><strong>Joined:</strong> <?= e(date('d M Y', strtotime($student['created_at']))) ?></p>
            </div>
            
        </div>
    </div>

    <!-- Editable fields -->
    <div class="col-md-8">
        <div class="sms-card">
            <div class="sms-card-title"><i class="bi bi-pencil-square"></i> Update Profile</div>
            <form method="POST" enctype="multipart/form-data" id="profileForm" novalidate>

                <div class="mb-3">
                    <label class="sms-form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           id="full_name" name="full_name" value="<?= e($old['full_name']) ?>">
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="email">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           id="email" name="email" value="<?= e($old['email']) ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="phone">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           id="phone" name="phone" value="<?= e($old['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="sms-form-label" for="gender">Gender</label>
                            <select name="gender" id="gender" class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>">
                                <option value=""<?= $old['gender']==='' ? ' selected' : '' ?>>Prefer not to say</option>
                                <option value="Male"<?= $old['gender']==='Male' ? ' selected' : '' ?>>Male</option>
                                <option value="Female"<?= $old['gender']==='Female' ? ' selected' : '' ?>>Female</option>
                                <option value="Other"<?= $old['gender']==='Other' ? ' selected' : '' ?>>Other</option>
                            </select>
                            <?php if (isset($errors['gender'])): ?>
                                <div class="invalid-feedback"><?= e($errors['gender']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="sms-form-label" for="dob">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>" value="<?= e($old['dob']) ?>">
                            <?php if (isset($errors['dob'])): ?>
                                <div class="invalid-feedback"><?= e($errors['dob']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="course">Course</label>
                    <input type="text" class="form-control <?= isset($errors['course']) ? 'is-invalid' : '' ?>"
                           id="course" name="course" value="<?= e($old['course']) ?>">
                    <?php if (isset($errors['course'])): ?>
                        <div class="invalid-feedback"><?= e($errors['course']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="sms-form-label" for="address">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                              id="address" name="address" rows="3"><?= e($old['address']) ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <div class="invalid-feedback"><?= e($errors['address']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="sms-form-label" for="profile_image">
                        Profile Image <small class="text-muted fw-normal">(optional — replaces current)</small>
                    </label>
                    <input type="file" class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>"
                           id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png">
                    <div class="form-text">jpg, jpeg, png — max 5 MB</div>
                    <?php if (isset($errors['profile_image'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['profile_image']) ?></div>
                    <?php endif; ?>
                    <img id="imagePreview" src="" alt="Preview" class="mt-2">
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student_footer.php'; ?>
