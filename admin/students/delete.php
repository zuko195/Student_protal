<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(getBaseUrl() . 'admin/students/list.php');

// Load student to get image filename
$stmt = $conn->prepare('SELECT id, full_name, profile_image FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) redirect(getBaseUrl() . 'admin/students/list.php');

// Delete record
$del = $conn->prepare('DELETE FROM students WHERE id = ?');
$del->bind_param('i', $id);
if ($del->execute()) {
    deleteImage($student['profile_image']);
    setFlash('success', 'Student "' . $student['full_name'] . '" deleted successfully.');
} else {
    setFlash('success', 'Failed to delete student. Please try again.');
}
$del->close();

redirect(getBaseUrl() . 'admin/students/list.php');
