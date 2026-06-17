<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) redirect(getBaseUrl() . 'admin/notes/list.php');

$stmt = $conn->prepare(
    'SELECT n.*, s.full_name AS student_name, s.email AS student_email
     FROM notes n
     LEFT JOIN students s ON s.id = n.student_id
     WHERE n.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$note) redirect(getBaseUrl() . 'admin/notes/list.php');

$pageTitle = 'View Note';
require_once __DIR__ . '/../../includes/admin_header.php';

$pMap = ['normal'    => ['bg-success-subtle text-success-emphasis','Normal'],
         'important' => ['bg-warning-subtle text-warning-emphasis','Important'],
         'urgent'    => ['bg-danger-subtle text-danger-emphasis','Urgent']];
[$pcls,$plabel] = $pMap[$note['priority']] ?? $pMap['normal'];
?>

<div class="sms-page-header">
    <div>
        <div class="sms-page-title"><i class="bi bi-bell-fill"></i>View Note</div>
    </div>
    <div class="d-flex gap-2">
        <a href="compose.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Note
        </a>
        <a href="list.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>All Notes
        </a>
    </div>
</div>

<div class="sms-card" style="max-width:720px">
    <!-- Meta row -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <?php if ($note['type'] === 'broadcast'): ?>
            <span class="badge" style="background:#3D2B1F;font-size:.76rem">
                <i class="bi bi-megaphone me-1"></i>Broadcast
            </span>
        <?php else: ?>
            <span class="badge bg-info text-dark" style="font-size:.76rem">
                <i class="bi bi-person me-1"></i>Personal
            </span>
        <?php endif; ?>
        <span class="badge <?= $pcls ?>" style="font-size:.76rem"><?= $plabel ?></span>
        <span class="text-muted small ms-auto">
            <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($note['created_at'])) ?>
        </span>
    </div>

    <!-- Title -->
    <h5 class="fw-bold mb-3" style="color:#3D2B1F"><?= e($note['title']) ?></h5>

    <!-- Recipient -->
    <div class="sms-detail-row mb-3">
        <div class="sms-detail-label">To</div>
        <div class="sms-detail-val">
            <?php if ($note['type'] === 'broadcast'): ?>
                <i class="bi bi-people me-1 text-muted"></i><strong>All Students</strong>
            <?php else: ?>
                <i class="bi bi-person me-1 text-muted"></i>
                <?= e($note['student_name'] ?? '—') ?>
                <?php if ($note['student_email']): ?>
                    <span class="text-muted small">(<?= e($note['student_email']) ?>)</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Body -->
    <div class="p-3 rounded" style="background:#faf7f4;border:1px solid #e8e0d8;font-size:.92rem;line-height:1.7;white-space:pre-wrap"><?= e($note['body']) ?></div>

    <!-- Delete -->
    <div class="mt-4 pt-3 border-top">
        <form method="post" action="list.php"
              onsubmit="return confirm('Delete this note permanently?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash3 me-1"></i>Delete Note
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
