<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

$pageTitle = 'My Dashboard';

$stmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    redirect(getBaseUrl() . 'login.php');
}

// ── Fetch notes for this student (handle missing `notes` table gracefully)
$notes = [];
$notes_error = '';
$unreadCount = 0;
try {
    $sid = (int)$_SESSION['user_id'];

    $stmtN = $conn->prepare(
        "SELECT * FROM notes
         WHERE type = 'broadcast'
            OR (type = 'personal' AND student_id = ?)
         ORDER BY created_at DESC
         LIMIT 20"
    );
    $stmtN->bind_param('i', $sid);
    $stmtN->execute();
    $resN = $stmtN->get_result();
    while ($row = $resN->fetch_assoc()) {
        $notes[] = $row;
    }
    $stmtN->close();

    // Count unread personal notes (before marking read)
    $stmtU = $conn->prepare(
        "SELECT COUNT(*) FROM notes WHERE type='personal' AND student_id=? AND is_read=0"
    );
    $stmtU->bind_param('i', $sid);
    $stmtU->execute();
    $unreadCount = (int)$stmtU->get_result()->fetch_row()[0];
    $stmtU->close();

    // Mark personal notes as read
    $markRead = $conn->prepare("UPDATE notes SET is_read=1 WHERE type='personal' AND student_id=? AND is_read=0");
    $markRead->bind_param('i', $sid);
    $markRead->execute();
    $markRead->close();

} catch (mysqli_sql_exception $e) {
    // Likely the `notes` table doesn't exist. Fail gracefully and show admin hint.
    $notes_error = 'Notes feature unavailable: the `notes` table is missing. Run the migration notes_migration.sql to create it.';
    $notes = [];
    $unreadCount = 0;
}

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="row g-4">
    <!-- Welcome card -->
    <div class="col-12">
        <div class="sms-card d-flex align-items-center gap-4">
            <img src="<?= getBaseUrl() . UPLOAD_URL . e($student['profile_image']) ?>"
                 class="sms-avatar-lg"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($student['full_name']) ?>&size=100&background=3D2B1F&color=fff'"
                 alt="">
            <div>
                <div class="sms-page-title mb-1">Welcome back, <?= e($student['full_name']) ?>!</div>
                <div class="text-muted small mb-1">
                    <i class="bi bi-envelope me-1"></i><?= e($student['email']) ?>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-book me-1"></i><?= e($student['course']) ?>
                    &nbsp;·&nbsp;
                    <?php if ($student['status'] === 'Active'): ?>
                        <span class="badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge-inactive">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile summary -->
    <div class="col-md-6">
        <div class="sms-card h-100">
            <div class="sms-card-title"><i class="bi bi-person-badge"></i> Profile Summary</div>
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
                <div class="sms-detail-label">Date of Birth</div>
                <div class="sms-detail-val"><?= e(date('d M Y', strtotime($student['dob']))) ?></div>
            </div>
        </div>
    </div>

    <!-- Course details -->
    <div class="col-md-6">
        <div class="sms-card h-100">
            <div class="sms-card-title"><i class="bi bi-mortarboard"></i> Course Details</div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Course</div>
                <div class="sms-detail-val"><?= e($student['course']) ?></div>
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
                <div class="sms-detail-label">Address</div>
                <div class="sms-detail-val"><?= nl2br(e($student['address'])) ?></div>
            </div>
            <div class="sms-detail-row">
                <div class="sms-detail-label">Member Since</div>
                <div class="sms-detail-val"><?= e(date('d M Y', strtotime($student['created_at']))) ?></div>
            </div>
            <div class="mt-3">
                <a href="<?= getBaseUrl() ?>student/profile.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i>Update Profile
                </a>
            </div>
        </div>
    </div>

    <!-- ── Notes Section ─────────────────────────────────────── -->
    <div class="col-12">
        <div class="sms-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="sms-card-title mb-0">
                    <i class="bi bi-bell-fill"></i> Notes &amp; Announcements
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-1" style="font-size:.7rem;vertical-align:middle">
                            <?= $unreadCount ?> new
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($notes_error)): ?>
                <div class="alert alert-warning">
                    <?= e($notes_error) ?>
                </div>
            <?php endif; ?>

            <?php if (count($notes) === 0): ?>
                <div class="sms-empty py-4">
                    <i class="bi bi-bell-slash"></i>
                    No notes or announcements yet.
                </div>
            <?php else: ?>
                <div class="notes-list">
                <?php foreach ($notes as $note):
                    $isPersonal  = $note['type'] === 'personal';
                    $isUrgent    = $note['priority'] === 'urgent';
                    $isImportant = $note['priority'] === 'important';

                    $borderColor = $isUrgent ? '#DC2626' : ($isImportant ? '#D97706' : ($isPersonal ? '#2563EB' : '#3D2B1F'));
                    $bgColor     = $isUrgent ? '#fef2f2' : ($isImportant ? '#fffbeb' : ($isPersonal ? '#eff6ff' : '#faf7f4'));
                ?>
                    <div class="note-item" style="border-left:3px solid <?= $borderColor ?>;background:<?= $bgColor ?>">
                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if ($isPersonal): ?>
                                    <span class="note-badge" style="background:#dbeafe;color:#1d4ed8">
                                        <i class="bi bi-person-fill me-1"></i>Personal Note
                                    </span>
                                <?php else: ?>
                                    <span class="note-badge" style="background:#ede8e4;color:#3D2B1F">
                                        <i class="bi bi-megaphone-fill me-1"></i>Announcement
                                    </span>
                                <?php endif; ?>

                                <?php if ($isUrgent): ?>
                                    <span class="note-badge" style="background:#fee2e2;color:#DC2626">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent
                                    </span>
                                <?php elseif ($isImportant): ?>
                                    <span class="note-badge" style="background:#fef3c7;color:#D97706">
                                        <i class="bi bi-exclamation-circle me-1"></i>Important
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-muted" style="font-size:.75rem;white-space:nowrap">
                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($note['created_at'])) ?>
                            </span>
                        </div>
                        <div class="note-title"><?= e($note['title']) ?></div>
                        <div class="note-body"><?= nl2br(e($note['body'])) ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notes-list { display: flex; flex-direction: column; gap: .75rem; }
.note-item {
    padding: .9rem 1rem;
    border-radius: 8px;
}
.note-badge {
    display: inline-flex;
    align-items: center;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 600;
}
.note-title {
    font-weight: 700;
    font-size: .92rem;
    color: #1a1a1a;
    margin-top: .5rem;
    margin-bottom: .3rem;
}
.note-body {
    font-size: .86rem;
    color: #444;
    line-height: 1.55;
}
</style>

<?php require_once __DIR__ . '/../includes/student_footer.php'; ?>
