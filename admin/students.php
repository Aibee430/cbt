<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_students');

$message = null;
$error = null;
$bulk_report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $full_name = trim($_POST['full_name'] ?? '');
        $reg_no = trim($_POST['reg_no'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $class_id = (int)($_POST['class_id'] ?? 0);
        $password = $_POST['password'] ?? 'student123';

        if ($full_name && $reg_no && $email && $class_id) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            DB::insert('students', [
                'class_id' => $class_id,
                'full_name' => $full_name,
                'reg_no' => $reg_no,
                'email' => $email,
                'password_hash' => $hash,
                'status' => 'active'
            ]);
            $message = 'Student added.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['student_id'] ?? 0);
        if ($id) {
            DB::delete('students', 'id=%i', $id);
            $message = 'Student removed.';
        }
    }

    if ($action === 'reset_password') {
        $id = (int)($_POST['student_id'] ?? 0);
        $password = $_POST['password'] ?? 'student123';
        if ($id) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            DB::update('students', ['password_hash' => $hash], 'id=%i', $id);
            $message = 'Student password reset.';
        }
    }

    if ($action === 'bulk_upload') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $tmp = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($tmp, 'r');
            $header = $handle ? fgetcsv($handle) : null;

            if (!$header) {
                $error = 'CSV header is missing or invalid.';
            } else {
                $columns = [];
                foreach ($header as $index => $name) {
                    $columns[strtolower(trim($name))] = $index;
                }

                $has_class_column = isset($columns['class_id']) || isset($columns['class_name']);
                if (!isset($columns['full_name']) || !isset($columns['reg_no']) || !isset($columns['email']) || !$has_class_column) {
                    $error = 'CSV is missing required columns. Use the provided template.';
                }

                if ($error) {
                    fclose($handle);
                } else {
                    $class_map = DB::query('SELECT id, name FROM classes');
                    $class_by_name = [];
                    foreach ($class_map as $row) {
                        $class_by_name[strtolower($row['name'])] = (int)$row['id'];
                    }

                    $inserted = 0;
                    $skipped = 0;
                    $get = function ($key, $row) use ($columns) {
                        return isset($columns[$key]) ? ($row[$columns[$key]] ?? '') : '';
                    };

                    while (($row = fgetcsv($handle)) !== false) {
                        if (count(array_filter($row, 'strlen')) === 0) {
                            continue;
                        }

                        $full_name = trim($get('full_name', $row));
                        $reg_no = trim($get('reg_no', $row));
                        $email = trim($get('email', $row));
                        $password = trim($get('password', $row)) ?: 'student123';
                        $class_id = 0;

                        if (isset($columns['class_id']) && is_numeric($get('class_id', $row))) {
                            $class_id = (int)$get('class_id', $row);
                        } elseif (isset($columns['class_name'])) {
                            $class_name = strtolower(trim($get('class_name', $row)));
                            $class_id = $class_by_name[$class_name] ?? 0;
                        }

                        if (!$full_name || !$reg_no || !$email || !$class_id) {
                            $skipped++;
                            continue;
                        }

                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        DB::insert('students', [
                            'class_id' => $class_id,
                            'full_name' => $full_name,
                            'reg_no' => $reg_no,
                            'email' => $email,
                            'password_hash' => $hash,
                            'status' => 'active'
                        ]);
                        $inserted++;
                    }

                    fclose($handle);
                    $bulk_report = "Bulk upload completed. Inserted: {$inserted}, Skipped: {$skipped}.";
                }
            }
        } else {
            $error = 'Please choose a CSV file to upload.';
        }
    }
}

$classes = DB::query('SELECT * FROM classes ORDER BY name');
$students = DB::query('SELECT students.*, classes.name AS class_name FROM students JOIN classes ON classes.id=students.class_id ORDER BY students.created_at DESC');
?>
<h3 class="mb-3">Students</h3>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($bulk_report): ?>
    <div class="alert alert-info" data-auto-dismiss><?php echo htmlspecialchars($bulk_report); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Student Actions</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Student</button>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Bulk upload new students using the CSV template.
                    <a href="/codexCbt/docs/student_upload_template.csv">Download template</a>
                </p>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="bulk_upload">
                    <div class="col-auto">
                        <label class="form-label mb-0">CSV File</label>
                    </div>
                    <div class="col">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary" type="submit">Upload CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">Student List</div>
            <div class="card-body p-0">
                <table class="table mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Reg No</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$students): ?>
                            <tr><td colspan="5" class="text-center text-muted">No students yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($student['status']); ?></span></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
                                        <input type="hidden" name="password" value="student123">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Reset Password</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
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

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addStudentForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reg No</label>
                        <input type="text" name="reg_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
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
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" placeholder="student123">
                        <div class="form-text">Leave blank to use default password.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="addStudentForm">Save</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/codexCbt/assets/libs/datatables/css/dataTables.bootstrap5.min.css">
<script src="/codexCbt/assets/libs/jquery/jquery.min.js"></script>
<script src="/codexCbt/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
<script src="/codexCbt/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#studentsTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [4] }
        ]
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
