<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_assignments');

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $class_id = (int)($_POST['class_id'] ?? 0);
        if ($exam_id && $class_id) {
            DB::insertIgnore('exam_assignments', [
                'exam_id' => $exam_id,
                'class_id' => $class_id
            ]);
            $message = 'Exam assigned.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['assignment_id'] ?? 0);
        if ($id) {
            DB::delete('exam_assignments', 'id=%i', $id);
            $message = 'Assignment removed.';
        }
    }
}

$exams = DB::query('SELECT * FROM exams ORDER BY created_at DESC');
$classes = DB::query('SELECT * FROM classes ORDER BY name');
$assignments = DB::query('SELECT exam_assignments.id, exams.title, classes.name AS class_name FROM exam_assignments JOIN exams ON exams.id=exam_assignments.exam_id JOIN classes ON classes.id=exam_assignments.class_id ORDER BY exams.title');
?>
<h3 class="mb-3">Exam Assignments</h3>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">Assign Exam to Class</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Exam</label>
                        <select name="exam_id" class="form-select" required>
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?php echo (int)$exam['id']; ?>"><?php echo htmlspecialchars($exam['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo (int)$class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Assign</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header">Assignments</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Class</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$assignments): ?>
                            <tr><td colspan="3" class="text-center text-muted">No assignments yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this assignment?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
