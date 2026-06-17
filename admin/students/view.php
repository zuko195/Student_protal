<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'View Student';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(getBaseUrl() . 'admin/students/list.php');

$stmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) redirect(getBaseUrl() . 'admin/students/list.php');

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= getBaseUrl() ?>admin/students/list.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="sms-page-title"><i class="bi bi-person-lines-fill"></i>Student Details</div>
            <p class="sms-page-subtitle mb-0">Viewing profile for <strong><?= e($student['full_name']) ?></strong></p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="delete.php?id=<?= $student['id'] ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Delete this student? This cannot be undone.')">
            <i class="bi bi-trash me-1"></i>Delete
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Profile card (left) -->
    <div class="col-lg-3 col-md-4">
        <div class="sms-card text-center">
            <img src="<?= getBaseUrl() . UPLOAD_URL . e($student['profile_image']) ?>"
                 class="sms-avatar-lg mb-3"
                 style="display:block;margin:0 auto"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&size=100&background=3D2B1F&color=fff'"
                 alt="">
            <div class="fw-bold mb-1"><?= e($student['full_name']) ?></div>
            <div class="text-muted small mb-2"><?= e($student['course']) ?></div>
            <?php if ($student['status'] === 'Active'): ?>
                <span class="badge-active">Active</span>
            <?php else: ?>
                <span class="badge-inactive">Inactive</span>
            <?php endif; ?>
            <hr class="my-3">
            <div class="text-start small text-muted">
                <div class="mb-1"><i class="bi bi-envelope me-2"></i><?= e($student['email']) ?></div>
                <div><i class="bi bi-telephone me-2"></i><?= e($student['phone']) ?></div>
            </div>
        </div>
    </div>

    <!-- Details card (right) -->
    <div class="col-lg-9 col-md-8">
        <div class="sms-card">
            <div class="sms-card-title"><i class="bi bi-info-circle"></i> Student Information</div>

            <div class="sms-detail-row">
                <div class="sms-detail-label">Full Name</div>
                <div class="sms-detail-val"><?= e($student['full_name']) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Email</div>
                <div class="sms-detail-val"><?= e($student['email']) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Phone</div>
                <div class="sms-detail-val"><?= e($student['phone']) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Gender</div>
                <div class="sms-detail-val"><?= e($student['gender']) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Course</div>
                <div class="sms-detail-val"><?= e($student['course']) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Date of Birth</div>
                <div class="sms-detail-val"><?= e(date('d M Y', strtotime($student['dob']))) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Address</div>
                <div class="sms-detail-val"><?= nl2br(e($student['address'])) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Status</div>
                <div class="sms-detail-val">
                    <?php if ($student['status'] === 'Active'): ?>
                        <span class="badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge-inactive">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Registered On</div>
                <div class="sms-detail-val"><?= e(date('d M Y, h:i A', strtotime($student['created_at']))) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
