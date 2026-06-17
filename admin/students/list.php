<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$pageTitle = 'All Students';

$search     = trim($_GET['search'] ?? '');
$searchType = $_GET['type'] ?? 'name';
$flash      = getFlash('success');

// Build query
if ($search !== '') {
    if ($searchType === 'email') {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare('SELECT * FROM students WHERE email LIKE ? ORDER BY created_at DESC');
        $stmt->bind_param('s', $like);
    } else {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare('SELECT * FROM students WHERE full_name LIKE ? ORDER BY created_at DESC');
        $stmt->bind_param('s', $like);
    }
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query('SELECT * FROM students ORDER BY created_at DESC');
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<!-- Page header -->
<div class="sms-page-header">
    <div>
        <div class="sms-page-title">
            <i class="bi bi-people"></i>All Students
        </div>
        <p class="sms-page-subtitle">
            <?php if ($search !== ''): ?>
                Showing results for "<strong><?= e($search) ?></strong>"
            <?php else: ?>
                Manage all registered students
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= getBaseUrl() ?>admin/students/add.php" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add Student
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 small">
        <i class="bi bi-check-circle me-1"></i><?= e($flash) ?>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="sms-card sms-card--structured">

    <!-- Search bar -->
    <div class="sms-search-bar">
        <form method="GET" id="searchForm">
            <div class="row g-2 align-items-center">
                <div class="col-sm-3 col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="name"  <?= $searchType === 'name'  ? 'selected' : '' ?>>By Name</option>
                        <option value="email" <?= $searchType === 'email' ? 'selected' : '' ?>>By Email</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-7">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search"
                               class="form-control border-start-0"
                               placeholder="Search students..."
                               value="<?= e($search) ?>"
                               style="box-shadow:none">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= getBaseUrl() ?>admin/students/list.php"
                           class="btn btn-outline-secondary btn-sm" title="Clear search">
                             <i class="bi bi-x"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table sms-table mb-0">
            <thead>
                <tr>
                    <th class="sms-row-num">#</th>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($students->num_rows === 0): ?>
                <tr>
                    <td colspan="7">
                        <div class="sms-empty">
                            <i class="bi bi-search"></i>
                            <?= $search ? 'No students match your search.' : 'No students found.' ?>
                        </div>
                    </td>
                </tr>
            <?php else:
                $i = 1;
                while ($row = $students->fetch_assoc()): ?>
                <tr>
                    <td class="sms-row-num"><?= $i++ ?></td>
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
                    <td><?= e($row['phone']) ?></td>
                    <td><?= e($row['course']) ?></td>
                    <td><?= e($row['gender']) ?></td>
                    <td>
                        <?php if ($row['status'] === 'Active'): ?>
                            <span class="badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="view.php?id=<?= $row['id'] ?>"
                               class="sms-btn-action sms-btn-view" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="sms-btn-action sms-btn-edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?id=<?= $row['id'] ?>"
                               class="sms-btn-action sms-btn-delete" title="Delete"
                               onclick="return confirm('Delete <?= e(addslashes($row['full_name'])) ?>? This cannot be undone.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($students->num_rows > 0): ?>
    <div class="sms-table-footer">
        <small class="text-muted">
            <?= $students->num_rows ?> student<?= $students->num_rows !== 1 ? 's' : '' ?> found
        </small>
        <a href="<?= getBaseUrl() ?>admin/students/add.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus me-1"></i>Add Another
        </a>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
