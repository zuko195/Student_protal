<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'Edit Student';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(getBaseUrl() . 'admin/students/list.php');
}

// Load existing student
$stmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    redirect(getBaseUrl() . 'admin/students/list.php');
}

$errors = [];
$old    = $student; // Pre-fill with current data

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $gender    = $_POST['gender']         ?? '';
    $course    = trim($_POST['course']    ?? '');
    $dob       = $_POST['dob']            ?? '';
    $address   = trim($_POST['address']   ?? '');
    $status    = $_POST['status']         ?? '';

    $old = array_merge($student, compact('full_name','email','phone','gender','course','dob','address','status'));

    // Backend validation
    if ($full_name === '')  $errors['full_name'] = 'Full name is required.';
    if ($email === '')      $errors['email']     = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
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

    // Email duplicate (allow same student)
    if (!isset($errors['email'])) {
        $chk = $conn->prepare('SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1');
        $chk->bind_param('si', $email, $id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors['email'] = 'Email already exists.';
        $chk->close();
    }

    // Image (optional on edit)
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

        // Always update fields except password (password change handled separately elsewhere)
        $stmt = $conn->prepare(
            'UPDATE students SET full_name=?,email=?,phone=?,gender=?,course=?,dob=?,profile_image=?,address=?,status=? WHERE id=?'
        );
        $stmt->bind_param('sssssssssi', $full_name,$email,$phone,$gender,$course,$dob,$finalImage,$address,$status,$id);

        if ($stmt->execute()) {
            if ($newFilename && $student['profile_image'] !== $newFilename) {
                deleteImage($student['profile_image']);
            }
            setFlash('success', 'Student updated successfully.');
            redirect(getBaseUrl() . 'admin/students/list.php');
        } else {
            $errors['db'] = 'Database error. Please try again.';
            if ($newFilename) deleteImage($newFilename);
        }
        $stmt->close();
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
            <div class="sms-page-title"><i class="bi bi-pencil-square"></i>Edit Student</div>
            <p class="sms-page-subtitle mb-0">Update information for <strong><?= e($student['full_name']) ?></strong></p>
        </div>
    </div>
</div>

<?php if (isset($errors['db'])): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($errors['db']) ?></div>
<?php endif; ?>

<div class="sms-card sms-card--structured">
    <div class="sms-card-header">
        <div class="sms-card-title"><i class="bi bi-person-badge"></i> Edit Student Information</div>
    </div>
    <div class="sms-card-body">
        <form method="POST" enctype="multipart/form-data" id="studentForm" novalidate>

            <!-- Section: Personal Details -->
            <div class="form-section-label">Personal Details</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="sms-form-label" for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           id="full_name" name="full_name" value="<?= e($old['full_name']) ?>">
                    <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label" for="email">Email Address <span class="required">*</span></label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           id="email" name="email" value="<?= e($old['email']) ?>">
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <!-- Password changes are not handled on this form -->
                <div class="col-md-6">
                    <label class="sms-form-label" for="phone">Phone Number <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           id="phone" name="phone" value="<?= e($old['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="gender">Gender <span class="required">*</span></label>
                    <select class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>" id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['gender'])): ?><div class="invalid-feedback"><?= e($errors['gender']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="dob">Date of Birth <span class="required">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>"
                           id="dob" name="dob" value="<?= e($old['dob']) ?>">
                    <?php if (isset($errors['dob'])): ?><div class="invalid-feedback"><?= e($errors['dob']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="sms-form-label" for="status">Account Status <span class="required">*</span></label>
                    <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                        <option value="">Select Status</option>
                        <?php foreach (['Active','Inactive'] as $s): ?>
                            <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="sms-form-label" for="address">Address <span class="required">*</span></label>
                    <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                              id="address" name="address" rows="2"><?= e($old['address']) ?></textarea>
                    <?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?>
                </div>
            </div>

            <!-- Section: Academic Details -->
            <div class="form-section-label">Academic Details</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="sms-form-label" for="course">Course <span class="required">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['course']) ? 'is-invalid' : '' ?>"
                           id="course" name="course" value="<?= e($old['course']) ?>">
                    <?php if (isset($errors['course'])): ?><div class="invalid-feedback"><?= e($errors['course']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="sms-form-label">Current Profile Photo</label>
                    <div class="d-flex align-items-center gap-3 mt-1">
                        <img src="<?= getBaseUrl() . UPLOAD_URL . e($student['profile_image']) ?>"
                             class="sms-avatar" style="width:48px;height:48px;border-radius:8px"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&size=48&background=3D2B1F&color=fff'"
                             alt="">
                        <div class="flex-grow-1">
                            <label class="sms-form-label mb-1" for="profile_image">Upload New Photo</label>
                            <input type="file" class="form-control form-control-sm <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>"
                                   id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png">
                            <div class="form-text text-muted">JPG, JPEG, PNG — max 5 MB. Leave blank to keep current.</div>
                            <?php if (isset($errors['profile_image'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['profile_image']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <img id="imagePreview" src="" alt="Preview" class="mt-2">
                </div>
            </div>

            <!-- Form actions -->
            <div class="d-flex gap-2 pt-2 border-top">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Save Changes
                </button>
                <a href="<?= getBaseUrl() ?>admin/students/list.php" class="btn btn-outline-secondary px-4">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
