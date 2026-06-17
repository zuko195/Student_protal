<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireStudent();

$pageTitle = 'My Notes';

// Filters
$filterPriority = $_GET['priority'] ?? '';
$filterFromDate = $_GET['from_date'] ?? '';
$filterToDate   = $_GET['to_date'] ?? '';
$filterSearch   = trim($_GET['q'] ?? '');

$notes = [];
$notes_error = '';
try {
    $sid = (int)$_SESSION['user_id'];

    $where  = ["(type = 'broadcast' OR (type = 'personal' AND student_id = ?))"];
    $params = [$sid];
    $types  = 'i';

    if (in_array($filterPriority, ['normal','important','urgent'], true)) {
        $where[] = 'priority = ?';
        $params[] = $filterPriority;
        $types .= 's';
    }
    if ($filterFromDate !== '') {
        $where[] = 'DATE(created_at) >= ?';
        $params[] = $filterFromDate;
        $types .= 's';
    }
    if ($filterToDate !== '') {
        $where[] = 'DATE(created_at) <= ?';
        $params[] = $filterToDate;
        $types .= 's';
    }
    if ($filterSearch !== '') {
        $where[] = '(title LIKE ? OR body LIKE ?)';
        $like = '%' . $filterSearch . '%';
        $params[] = $like; $params[] = $like;
        $types .= 'ss';
    }

    // Only show notes that haven't expired
    $where[] = '(expires_at IS NULL OR expires_at >= NOW())';

    $sql = "SELECT * FROM notes WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT 100";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $notes[] = $r;
    }
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $notes_error = 'Notes feature unavailable: the `notes` table is missing. Run the migration notes_migration.sql to create it.';
    $notes = [];
}

require_once __DIR__ . '/../../includes/student_header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="sms-page-header">
            <div>
                <div class="sms-page-title"><i class="bi bi-bell-fill"></i>Notes & Announcements</div>
                <p class="sms-page-subtitle">All notes visible to you.</p>
            </div>
            <a href="<?= getBaseUrl() ?>student/dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>

        <?php if (!empty($notes_error)): ?>
            <div class="alert alert-warning"><?= e($notes_error) ?></div>
        <?php endif; ?>

        <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-end" style="max-width:760px">
            <select name="priority" class="form-select form-select-sm" style="width:145px">
                <option value=""<?= $filterPriority === '' ? ' selected' : '' ?>>All priorities</option>
                <option value="normal"<?= $filterPriority === 'normal' ? ' selected' : '' ?>>Normal</option>
                <option value="important"<?= $filterPriority === 'important' ? ' selected' : '' ?>>Important</option>
                <option value="urgent"<?= $filterPriority === 'urgent' ? ' selected' : '' ?>>Urgent</option>
            </select>

            <input type="date" name="from_date" value="<?= e($filterFromDate) ?>" class="form-control form-control-sm" style="width:160px" placeholder="From date">
            <input type="date" name="to_date" value="<?= e($filterToDate) ?>" class="form-control form-control-sm" style="width:160px" placeholder="To date">

            <input type="text" name="q" value="<?= e($filterSearch) ?>" class="form-control form-control-sm" placeholder="Search title or body…">
            <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-filter"></i> Filter
            </button>
            <a href="<?= getBaseUrl() ?>student/notes/list.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x"></i> Clear
            </a>
        </form>

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
                                <i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($note['created_at'])) ?>
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

<?php require_once __DIR__ . '/../../includes/student_footer.php';
?>