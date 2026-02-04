<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_subjects');

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if ($name && $code) {
            DB::insert('subjects', ['name' => $name, 'code' => $code]);
            $message = 'Subject added.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['subject_id'] ?? 0);
        if ($id) {
            DB::delete('subjects', 'id=%i', $id);
            $message = 'Subject removed.';
        }
    }
}

$subjects = DB::query('SELECT * FROM subjects ORDER BY name');
?>
<h3 class="mb-3">Subjects</h3>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">Add Subject</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Subject name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header">Existing Subjects</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$subjects): ?>
                            <tr><td colspan="3" class="text-center text-muted">No subjects yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this subject?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="subject_id" value="<?php echo (int)$subject['id']; ?>">
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
