<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'Manage Notes';

// ── Delete action (safe against missing table) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['note_id'] ?? 0);
    if ($delId > 0) {
        try {
            $stmt = $conn->prepare('DELETE FROM notes WHERE id = ?');
            $stmt->bind_param('i', $delId);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Note deleted successfully.');
        } catch (mysqli_sql_exception $e) {
            setFlash('error', 'Notes feature unavailable: the `notes` table is missing.');
        }
    }
    redirect(getBaseUrl() . 'admin/notes/list.php');
}

// ── Filters ──────────────────────────────────────────────────
$filterType = $_GET['type'] ?? '';
$filterSearch = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($filterType === 'broadcast' || $filterType === 'personal') {
    $where[]  = 'n.type = ?';
    $params[] = $filterType;
    $types   .= 's';
}
if ($filterSearch !== '') {
    $where[]  = '(n.title LIKE ? OR n.body LIKE ? OR s.full_name LIKE ?)';
    $like = '%' . $filterSearch . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT n.*, s.full_name AS student_name
        FROM notes n
        LEFT JOIN students s ON s.id = n.student_id
        $whereSQL
        ORDER BY n.created_at DESC";

$notes = [];
$totalAll = $totalBroadcast = $totalPersonal = 0;
$notes_error = '';
try {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $notes[] = $r;
    }
    $stmt->close();

    // Counts for badges
    $totalAll       = (int)$conn->query("SELECT COUNT(*) FROM notes")->fetch_row()[0];
    $totalBroadcast = (int)$conn->query("SELECT COUNT(*) FROM notes WHERE type='broadcast'")->fetch_row()[0];
    $totalPersonal  = (int)$conn->query("SELECT COUNT(*) FROM notes WHERE type='personal'")->fetch_row()[0];

} catch (mysqli_sql_exception $e) {
    $notes_error = 'Notes feature unavailable: the `notes` table is missing. Run notes_migration.sql to create it.';
    $notes = [];
    $totalAll = $totalBroadcast = $totalPersonal = 0;
}

$flash = getFlash('success');

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div>
        <div class="sms-page-title"><i class="bi bi-bell-fill"></i>Notes & Announcements</div>
        <p class="sms-page-subtitle">Publish broadcast messages or private notes to individual students.</p>
    </div>
    <a href="<?= getBaseUrl() ?>admin/notes/compose.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Compose Note
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i><?= e($flash) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($notes_error)): ?>
    <div class="alert alert-warning"><?= e($notes_error) ?></div>
<?php endif; ?>

<!-- Stat pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="?type=" class="sms-filter-pill <?= $filterType === '' ? 'active' : '' ?>">
        All <span class="badge bg-secondary ms-1"><?= $totalAll ?></span>
    </a>
    <a href="?type=broadcast" class="sms-filter-pill <?= $filterType === 'broadcast' ? 'active' : '' ?>">
        <i class="bi bi-megaphone me-1"></i>Broadcast
        <span class="badge bg-secondary ms-1"><?= $totalBroadcast ?></span>
    </a>
    <a href="?type=personal" class="sms-filter-pill <?= $filterType === 'personal' ? 'active' : '' ?>">
        <i class="bi bi-person-lines-fill me-1"></i>Personal
        <span class="badge bg-secondary ms-1"><?= $totalPersonal ?></span>
    </a>
</div>

<!-- Search bar -->
<form method="get" class="mb-3 d-flex gap-2" style="max-width:460px">
    <input type="hidden" name="type" value="<?= e($filterType) ?>">
    <input type="text" name="q" value="<?= e($filterSearch) ?>"
           class="form-control form-control-sm" placeholder="Search title, body, student name…">
    <button class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-search"></i>
    </button>
    <?php if ($filterSearch): ?>
        <a href="?type=<?= e($filterType) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></a>
    <?php endif; ?>
</form>

<!-- Notes table -->
<div class="sms-card sms-card--structured">
    <div class="table-responsive">
        <table class="table sms-table mb-0">
            <thead>
                <tr>
                    <th style="width:110px">Type</th>
                    <th>Title</th>
                    <th>Recipient</th>
                    <th style="width:100px">Priority</th>
                    <th style="width:130px">Sent</th>
                    <th style="width:90px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($notes) === 0): ?>
                <tr>
                    <td colspan="6">
                        <div class="sms-empty">
                            <i class="bi bi-bell-slash"></i>
                            No notes found<?= ($filterSearch || $filterType) ? ' matching your filter' : ' yet' ?>.
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($notes as $n): ?>
                <tr>
                    <td>
                        <?php if ($n['type'] === 'broadcast'): ?>
                            <span class="badge" style="background:#3D2B1F;font-size:.73rem">
                                <i class="bi bi-megaphone me-1"></i>Broadcast
                            </span>
                        <?php else: ?>
                            <span class="badge bg-info text-dark" style="font-size:.73rem">
                                <i class="bi bi-person me-1"></i>Personal
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:.88rem"><?= e($n['title']) ?></div>
                        <div class="text-muted" style="font-size:.76rem"><?= e(mb_substr($n['body'], 0, 70)) ?><?= strlen($n['body']) > 70 ? '…' : '' ?></div>
                    </td>
                    <td style="font-size:.85rem">
                        <?= $n['type'] === 'broadcast' ? '<span class="text-muted">All Students</span>' : e($n['student_name'] ?? '—') ?>
                    </td>
                    <td>
                        <?php
                        $pMap = ['normal'=>['bg-success-subtle text-success-emphasis','Normal'],
                                 'important'=>['bg-warning-subtle text-warning-emphasis','Important'],
                                 'urgent'=>['bg-danger-subtle text-danger-emphasis','Urgent']];
                        [$pcls,$plabel] = $pMap[$n['priority']] ?? $pMap['normal'];
                        ?>
                        <span class="badge <?= $pcls ?>" style="font-size:.73rem"><?= $plabel ?></span>
                    </td>
                    <td style="font-size:.82rem;color:#666"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></td>
                    <td>
                        <a href="view.php?id=<?= $n['id'] ?>" class="sms-btn-action sms-btn-view" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('Delete this note permanently?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                            <button type="submit" class="sms-btn-action sms-btn-delete" title="Delete">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.sms-filter-pill {
    display: inline-flex;
    align-items: center;
    padding: .3rem .8rem;
    border-radius: 20px;
    border: 1.5px solid #d5c9c1;
    background: #fff;
    color: #3D2B1F;
    font-size: .82rem;
    text-decoration: none;
    transition: all .15s;
}
.sms-filter-pill:hover,
.sms-filter-pill.active {
    background: #3D2B1F;
    border-color: #3D2B1F;
    color: #fff;
}
</style>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
