<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$pageTitle = 'Dashboard';

// Stats
$total    = $conn->query('SELECT COUNT(*) FROM students')->fetch_row()[0];
$active   = $conn->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetch_row()[0];
$inactive = $conn->query("SELECT COUNT(*) FROM students WHERE status='Inactive'")->fetch_row()[0];
$courses  = $conn->query("SELECT COUNT(DISTINCT course) FROM students WHERE course != '' AND course IS NOT NULL")->fetch_row()[0];

// Notes stats (handle missing `notes` table gracefully)
$notesTotal = $notesBroadcast = $notesPersonal = 0;
$recentNotes = [];
try {
    $notesTotal     = (int)$conn->query("SELECT COUNT(*) FROM notes")->fetch_row()[0];
    $notesBroadcast = (int)$conn->query("SELECT COUNT(*) FROM notes WHERE type='broadcast'")->fetch_row()[0];
    $notesPersonal  = (int)$conn->query("SELECT COUNT(*) FROM notes WHERE type='personal'")->fetch_row()[0];

    // Recent 4 notes
    $resNotes = $conn->query(
        "SELECT n.*, s.full_name AS student_name FROM notes n
         LEFT JOIN students s ON s.id = n.student_id
         ORDER BY n.created_at DESC LIMIT 4"
    );
    if ($resNotes) {
        while ($r = $resNotes->fetch_assoc()) {
            $recentNotes[] = $r;
        }
    }
} catch (mysqli_sql_exception $e) {
    // notes table likely missing — leave defaults and show empty widget
    $recentNotes = [];
}

// Latest 5 students
$latest = $conn->query('SELECT id, full_name, email, course, status, profile_image, created_at FROM students ORDER BY created_at DESC LIMIT 5');

// Recent 4 notes
// (recent notes already loaded above)

$flash = getFlash('success');

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div>
        <div class="sms-page-title">
            <i class="bi bi-speedometer2"></i>Dashboard
        </div>
        <p class="sms-page-subtitle">
            Welcome back, <strong><?= e($_SESSION['username']) ?></strong> — here's what's happening today.
        </p>
    </div>
    <a href="<?= getBaseUrl() ?>admin/students/add.php" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add Student
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i><?= e($flash) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stat cards (4 cards) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sms-stat-card total">
            <div class="sms-stat-icon total"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="sms-stat-val"><?= $total ?></div>
                <div class="sms-stat-label">Total Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sms-stat-card active">
            <div class="sms-stat-icon active"><i class="bi bi-person-check-fill"></i></div>
            <div>
                <div class="sms-stat-val"><?= $active ?></div>
                <div class="sms-stat-label">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sms-stat-card inactive">
            <div class="sms-stat-icon inactive"><i class="bi bi-person-x-fill"></i></div>
            <div>
                <div class="sms-stat-val"><?= $inactive ?></div>
                <div class="sms-stat-label">Inactive</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sms-stat-card courses">
            <div class="sms-stat-icon courses"><i class="bi bi-book-fill"></i></div>
            <div>
                <div class="sms-stat-val"><?= $courses ?></div>
                <div class="sms-stat-label">Courses</div>
            </div>
        </div>
    </div>
</div>

<!-- Notes stat cards -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold" style="color:#3D2B1F;font-size:.9rem">
                <i class="bi bi-bell me-1"></i>Notes Overview
            </div>
            <a href="<?= getBaseUrl() ?>admin/notes/list.php" class="btn btn-sm btn-outline-secondary btn-xs" style="font-size:.78rem">
                Manage Notes <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-4">
        <div class="sms-stat-card" style="background:linear-gradient(135deg,#3D2B1F,#5a3f2e)">
            <div class="sms-stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-bell-fill text-white"></i></div>
            <div>
                <div class="sms-stat-val text-white"><?= $notesTotal ?></div>
                <div class="sms-stat-label text-white" style="opacity:.85">Total Notes</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="sms-stat-card" style="background:linear-gradient(135deg,#4f46e5,#6366f1)">
            <div class="sms-stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-megaphone-fill text-white"></i></div>
            <div>
                <div class="sms-stat-val text-white"><?= $notesBroadcast ?></div>
                <div class="sms-stat-label text-white" style="opacity:.85">Broadcast</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="sms-stat-card" style="background:linear-gradient(135deg,#0891b2,#06b6d4)">
            <div class="sms-stat-icon" style="background:rgba(255,255,255,.15)"><i class="bi bi-person-fill text-white"></i></div>
            <div>
                <div class="sms-stat-val text-white"><?= $notesPersonal ?></div>
                <div class="sms-stat-label text-white" style="opacity:.85">Personal</div>
            </div>
        </div>
    </div>
</div>

<!-- Recently added students -->
<div class="sms-card sms-card--structured">
    <div class="sms-card-header">
        <div class="sms-card-title">
            <i class="bi bi-clock-history"></i> Recently Added Students
        </div>
        <a href="<?= getBaseUrl() ?>admin/students/list.php" class="btn btn-sm btn-outline-secondary">
            View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table sms-table mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($latest->num_rows === 0): ?>
                <tr>
                    <td colspan="5">
                        <div class="sms-empty">
                            <i class="bi bi-people"></i>
                            No students added yet.
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($row = $latest->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= getBaseUrl() . UPLOAD_URL . e($row['profile_image']) ?>"
                                 class="sms-avatar"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['full_name']) ?>&size=34&background=3D2B1F&color=fff'"
                                 alt="">
                            <div>
                                <div class="fw-semibold" style="font-size:0.88rem"><?= e($row['full_name']) ?></div>
                                <div class="text-muted" style="font-size:0.78rem"><?= e($row['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($row['course']) ?></td>
                    <td>
                        <?php if ($row['status'] === 'Active'): ?>
                            <span class="badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="<?= getBaseUrl() ?>admin/students/view.php?id=<?= $row['id'] ?>"
                           class="sms-btn-action sms-btn-view" title="View student">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Notes widget -->
<div class="sms-card sms-card--structured mt-4">
    <div class="sms-card-header">
        <div class="sms-card-title">
            <i class="bi bi-bell"></i> Recent Notes
        </div>
        <div class="d-flex gap-2">
            <a href="<?= getBaseUrl() ?>admin/notes/compose.php" class="btn btn-sm btn-primary" style="font-size:.78rem">
                <i class="bi bi-plus-lg me-1"></i>New Note
            </a>
            <a href="<?= getBaseUrl() ?>admin/notes/list.php" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <?php if (count($recentNotes) === 0): ?>
        <div class="sms-empty">
            <i class="bi bi-bell-slash"></i>No notes published yet.
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2 p-3">
        <?php foreach ($recentNotes as $rn):
            $isBc = $rn['type'] === 'broadcast';
            $pColor = ['normal'=>'#198754','important'=>'#D97706','urgent'=>'#DC2626'][$rn['priority']] ?? '#198754';
        ?>
            <div class="d-flex align-items-start gap-3 p-2 rounded"
                 style="border:1px solid #ede8e4;background:#faf7f4">
                <div style="width:34px;height:34px;border-radius:8px;
                            background:<?= $isBc ? '#3D2B1F' : '#2563EB' ?>;
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-size:.9rem;flex-shrink:0">
                    <i class="bi <?= $isBc ? 'bi-megaphone-fill' : 'bi-person-fill' ?>"></i>
                </div>
                <div class="flex-grow-1" style="min-width:0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold" style="font-size:.86rem;color:#1a1a1a"><?= e($rn['title']) ?></span>
                        <span class="badge" style="background:<?= $pColor ?>;font-size:.65rem"><?= ucfirst($rn['priority']) ?></span>
                    </div>
                    <div style="font-size:.76rem;color:#888;margin-top:.1rem">
                        <i class="bi <?= $isBc ? 'bi-people' : 'bi-person' ?> me-1"></i>
                        <?= $isBc ? 'All Students' : e($rn['student_name'] ?? '—') ?>
                        &nbsp;·&nbsp;
                        <?= date('d M Y', strtotime($rn['created_at'])) ?>
                    </div>
                </div>
                <a href="<?= getBaseUrl() ?>admin/notes/view.php?id=<?= $rn['id'] ?>"
                   class="sms-btn-action sms-btn-view" title="View" style="flex-shrink:0">
                    <i class="bi bi-eye"></i>
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
