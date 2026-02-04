<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_admins');

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $password = $_POST['password'] ?? 'admin123';

        if ($name && $email) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            DB::insert('admin_users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'role' => $role
            ]);
            $message = 'Admin account created.';
        }
    }

    if ($action === 'update_role') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $role = $_POST['role'] ?? 'viewer';

        $super_admin_count = (int)DB::queryFirstField("SELECT COUNT(*) FROM admin_users WHERE role='super_admin'");
        $is_last_super = ($super_admin_count <= 1);
        $target = DB::queryFirstRow('SELECT * FROM admin_users WHERE id=%i', $admin_id);

        // Prevent removing the last super admin.
        if ($target && $target['role'] === 'super_admin' && $is_last_super && $role !== 'super_admin') {
            $error = 'You cannot remove the last super admin.';
        } else {
            DB::update('admin_users', ['role' => $role], 'id=%i', $admin_id);
            $message = 'Role updated.';
        }
    }

    if ($action === 'reset_password') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $password = $_POST['password'] ?? 'admin123';

        $hash = password_hash($password, PASSWORD_DEFAULT);
        DB::update('admin_users', ['password_hash' => $hash], 'id=%i', $admin_id);
        $message = 'Password reset.';
    }

    if ($action === 'delete') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);

        if ($admin_id === (int)$_SESSION['admin']['id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $super_admin_count = (int)DB::queryFirstField("SELECT COUNT(*) FROM admin_users WHERE role='super_admin'");
            $target = DB::queryFirstRow('SELECT * FROM admin_users WHERE id=%i', $admin_id);

            if ($target && $target['role'] === 'super_admin') {
                $error = 'Super Admin accounts cannot be deleted.';
            } else {
                DB::delete('admin_users', 'id=%i', $admin_id);
                $message = 'Admin removed.';
            }
        }
    }
}

$admins = DB::query('SELECT * FROM admin_users ORDER BY created_at DESC');
$roles = [
    'super_admin' => 'Super Admin',
    'exam_manager' => 'Exam Manager',
    'result_manager' => 'Result Manager',
    'viewer' => 'Viewer'
];
?>
<h3 class="mb-3">Admin Users</h3>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Admin List</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAdminModal">Add Admin</button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="adminsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$admins): ?>
                            <tr><td colspan="4" class="text-center text-muted">No admins yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>">
                                        <select name="role" class="form-select form-select-sm">
                                            <?php foreach ($roles as $key => $label): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $admin['role'] === $key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>">
                                        <input type="hidden" name="password" value="admin123">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Reset Password</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this admin?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" <?php echo $admin['role'] === 'super_admin' ? 'disabled' : ''; ?>>Delete</button>
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

<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addAdminForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <?php foreach ($roles as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" placeholder="admin123">
                        <div class="form-text">Leave blank to use default password.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="addAdminForm">Create Admin</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/cbt/assets/libs/datatables/css/dataTables.bootstrap5.min.css">
<script src="/cbt/assets/libs/jquery/jquery.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#adminsTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [2, 3] }
        ]
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
