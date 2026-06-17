<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'Compose Note';

// Load all students for recipient dropdown
$students = $conn->query('SELECT id, full_name, course FROM students ORDER BY full_name ASC');

$errors  = [];
$form    = ['type'=>'broadcast','student_id'=>'','title'=>'','body'=>'','priority'=>'normal','expires_at'=>''];

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['type']       = in_array($_POST['type'] ?? '', ['broadcast','personal']) ? $_POST['type'] : 'broadcast';
    $form['student_id'] = (int)($_POST['student_id'] ?? 0);
    $form['title']      = trim($_POST['title'] ?? '');
    $form['body']       = trim($_POST['body'] ?? '');
    $form['priority']   = in_array($_POST['priority'] ?? '', ['normal','important','urgent']) ? $_POST['priority'] : 'normal';
    $form['expires_at'] = trim($_POST['expires_at'] ?? '');

    if ($form['title'] === '') $errors[] = 'Title is required.';
    if ($form['body']  === '') $errors[] = 'Message body is required.';
    if ($form['type'] === 'personal' && $form['student_id'] < 1)
        $errors[] = 'Please select a recipient student.';

    $expiresAt = null;
    if ($form['expires_at'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $form['expires_at']);
        if (!$dt) {
            $errors[] = 'Expiry date is invalid.';
        } else {
            $expiresAt = $dt->format('Y-m-d 23:59:59');
        }
    }

    if (!$errors) {
        $sid = $form['type'] === 'personal' ? $form['student_id'] : null;
        $stmt = $conn->prepare(
            'INSERT INTO notes (admin_id, type, student_id, title, body, priority, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $adminId = (int)$_SESSION['user_id'];
        $stmt->bind_param('isissss',
            $adminId,
            $form['type'],
            $sid,
            $form['title'],
            $form['body'],
            $form['priority'],
            $expiresAt
        );
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Note published successfully.');
        redirect(getBaseUrl() . 'admin/notes/list.php');
    }
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div>
        <div class="sms-page-title"><i class="bi bi-pencil-square"></i>Compose Note</div>
        <p class="sms-page-subtitle">Publish an announcement to all students or send a private note to one student.</p>
    </div>
    <a href="<?= getBaseUrl() ?>admin/notes/list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Notes
    </a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= implode('<br>', array_map('e', $errors)) ?>
    </div>
<?php endif; ?>

<div class="sms-card" style="max-width:760px">
    <form method="post" id="composeForm">

        <!-- Note type selector -->
        <div class="mb-4">
            <label class="form-label fw-semibold mb-2">Note Type</label>
            <div class="d-flex gap-3">
                <label class="note-type-card <?= $form['type']==='broadcast' ? 'selected' : '' ?>">
                    <input type="radio" name="type" value="broadcast"
                           <?= $form['type']==='broadcast' ? 'checked' : '' ?>
                           class="d-none" id="typeBroadcast">
                    <div class="note-type-icon" style="background:#3D2B1F">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">Broadcast</div>
                        <div class="text-muted" style="font-size:.78rem">Visible to all students</div>
                    </div>
                </label>

                <label class="note-type-card <?= $form['type']==='personal' ? 'selected' : '' ?>">
                    <input type="radio" name="type" value="personal"
                           <?= $form['type']==='personal' ? 'checked' : '' ?>
                           class="d-none" id="typePersonal">
                    <div class="note-type-icon" style="background:#2563EB">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.9rem">Personal</div>
                        <div class="text-muted" style="font-size:.78rem">Private — one student only</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Recipient (shown only for personal) -->
        <div class="mb-3" id="recipientRow" style="<?= $form['type']==='broadcast' ? 'display:none' : '' ?>">
            <label for="student_id" class="form-label fw-semibold">
                Recipient Student <span class="text-danger">*</span>
            </label>
            <select name="student_id" id="student_id" class="form-select form-select-sm">
                <option value="">— Choose a student —</option>
                <?php
                $students->data_seek(0);
                while ($s = $students->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= $form['student_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['full_name']) ?>
                        <?= $s['course'] ? '(' . e($s['course']) . ')' : '' ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label fw-semibold">
                Title <span class="text-danger">*</span>
            </label>
            <input type="text" name="title" id="title" class="form-control form-control-sm"
                   value="<?= e($form['title']) ?>" placeholder="e.g. Semester exam schedule update"
                   maxlength="255">
        </div>

        <!-- Body -->
        <div class="mb-3">
            <label for="body" class="form-label fw-semibold">
                Message <span class="text-danger">*</span>
            </label>
            <textarea name="body" id="body" rows="6"
                      class="form-control form-control-sm"
                      placeholder="Write your note here…"><?= e($form['body']) ?></textarea>
        </div>

        <!-- Priority -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Priority</label>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach (['normal'=>['bi-circle','Normal','#198754'],
                                'important'=>['bi-exclamation-circle','Important','#D97706'],
                                'urgent'=>['bi-exclamation-triangle-fill','Urgent','#DC2626']] as $val=>[$icon,$label,$color]): ?>
                    <label class="priority-pill" style="--p-color:<?= $color ?>">
                        <input type="radio" name="priority" value="<?= $val ?>"
                               <?= $form['priority']===$val ? 'checked' : '' ?>
                               class="d-none">
                        <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label fw-semibold mb-0">Expiry</label>
                <small class="text-muted">Optional: set the date after which this note is no longer active.</small>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="expires_at" class="form-label">Expire Date</label>
                    <input type="date" name="expires_at" id="expires_at"
                           class="form-control form-control-sm"
                           value="<?= e($form['expires_at']) ?>">
                </div>
            </div>
            <div class="form-text">Leave blank to keep this note active indefinitely.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-send me-1"></i>Publish Note
            </button>
            <a href="<?= getBaseUrl() ?>admin/notes/list.php" class="btn btn-sm btn-outline-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<style>
.note-type-card {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .85rem 1.1rem;
    border: 2px solid #e0d8d2;
    border-radius: 10px;
    cursor: pointer;
    flex: 1;
    max-width: 220px;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.note-type-card.selected,
.note-type-card:has(input:checked) {
    border-color: #3D2B1F;
    box-shadow: 0 0 0 3px rgba(61,43,31,.1);
}
.note-type-icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.priority-pill {
    display: inline-flex;
    align-items: center;
    padding: .3rem .85rem;
    border-radius: 20px;
    border: 2px solid #e0d8d2;
    cursor: pointer;
    font-size: .82rem;
    color: #444;
    transition: all .15s;
}
.priority-pill:has(input:checked) {
    border-color: var(--p-color);
    color: var(--p-color);
    background: color-mix(in srgb, var(--p-color) 8%, #fff);
    font-weight: 600;
}
</style>

<script>
document.querySelectorAll('input[name="type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('recipientRow').style.display =
            this.value === 'personal' ? '' : 'none';

        document.querySelectorAll('.note-type-card').forEach(c => c.classList.remove('selected'));
        this.closest('.note-type-card').classList.add('selected');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
