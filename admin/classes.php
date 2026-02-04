<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_classes');

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            DB::insert('classes', ['name' => $name]);
            $message = 'Class added successfully.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['class_id'] ?? 0);
        if ($id) {
            DB::delete('classes', 'id=%i', $id);
            $message = 'Class removed.';
        }
    }
}

$classes = DB::query('SELECT * FROM classes ORDER BY name');
?>
<h3 class="mb-3">Classes</h3>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">Add Class</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Class name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header">Existing Classes</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$classes): ?>
                            <tr><td colspan="2" class="text-center text-muted">No classes yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this class?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
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
