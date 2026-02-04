<?php
require_once __DIR__ . '/header.php';
require_admin_permission('view_results');

$exam_id = (int)($_GET['exam_id'] ?? 0);
$exams = DB::query('SELECT id, title FROM exams ORDER BY title');
$release_report = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'bulk_release') {
        $ids = $_POST['attempt_ids'] ?? [];
        $ids = array_filter(array_map('intval', $ids));
        if ($ids) {
            $exam_ids = DB::queryFirstColumn('SELECT DISTINCT exam_id FROM exam_attempts WHERE id IN %li', $ids);
            if ($exam_ids) {
                // Release results by setting exams to immediate.
                DB::update('exams', [
                    'show_result' => 'immediate',
                    'result_release_at' => null
                ], 'id IN %li', $exam_ids);
            }
            $release_report = 'Results released for selected exams.';
        } else {
            $error = 'Please select at least one attempt.';
        }
    }
}

if ($exam_id) {
    $attempts = DB::query('
        SELECT exam_attempts.*, exams.title, students.full_name
        FROM exam_attempts
        JOIN exams ON exams.id=exam_attempts.exam_id
        JOIN students ON students.id=exam_attempts.student_id
        WHERE exam_attempts.exam_id=%i
        ORDER BY exam_attempts.started_at DESC
    ', $exam_id);
} else {
    $attempts = DB::query('
        SELECT exam_attempts.*, exams.title, students.full_name
        FROM exam_attempts
        JOIN exams ON exams.id=exam_attempts.exam_id
        JOIN students ON students.id=exam_attempts.student_id
        ORDER BY exam_attempts.started_at DESC
    ');
}
?>
<h3 class="mb-3">Results</h3>

<?php if ($release_report): ?>
    <div class="alert alert-info" data-auto-dismiss><?php echo htmlspecialchars($release_report); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Filter by Exam</label>
                <select name="exam_id" class="form-select">
                    <option value="0">All exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo (int)$exam['id']; ?>" <?php echo $exam_id === (int)$exam['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply Filter</button>
                <a class="btn btn-outline-secondary" href="/cbt/admin/export_results.php?format=csv&exam_id=<?php echo $exam_id; ?>">Export CSV</a>
                <a class="btn btn-outline-secondary" href="/cbt/admin/export_results.php?format=pdf&exam_id=<?php echo $exam_id; ?>">Export PDF</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Attempts</span>
        <button class="btn btn-sm btn-outline-primary" type="submit" form="bulkReleaseForm" id="bulkReleaseBtn" disabled>Release Selected Now</button>
    </div>
    <div class="card-body p-0">
        <form id="bulkReleaseForm" method="post" onsubmit="return confirm('Release results now for selected exams?');">
            <input type="hidden" name="action" value="bulk_release">
        </form>
        <table class="table mb-0" id="resultsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllResults"></th>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$attempts): ?>
                    <tr><td colspan="7" class="text-center text-muted">No attempts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($attempts as $attempt): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="row-check" name="attempt_ids[]" value="<?php echo (int)$attempt['id']; ?>" form="bulkReleaseForm">
                        </td>
                        <td><?php echo htmlspecialchars($attempt['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                        <td><?php echo number_format($attempt['score'], 2) . ' / ' . number_format($attempt['total_marks'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $attempt['status'] === 'graded' ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars($attempt['status']); ?>
                            </span>
                        </td>
                        <td><?php echo format_dt($attempt['submitted_at']); ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="/cbt/admin/grade.php?attempt_id=<?php echo (int)$attempt['id']; ?>">Grade/View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="/cbt/assets/libs/datatables/css/dataTables.bootstrap5.min.css">
<script src="/cbt/assets/libs/jquery/jquery.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const table = $('#resultsTable').DataTable({
        paging: false,
        order: [[5, 'desc']],
        columnDefs: [
            { orderable: false, targets: [0, 6] }
        ]
    });

    const bulkBtn = document.getElementById('bulkReleaseBtn');
    const selectAll = document.getElementById('selectAllResults');

    function syncBulkButton() {
        const anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
        bulkBtn.disabled = !anyChecked;
    }

    selectAll.addEventListener('change', function () {
        const rows = table.rows({ search: 'applied' }).nodes();
        $('input.row-check', rows).prop('checked', this.checked);
        syncBulkButton();
    });

    $('#resultsTable tbody').on('change', '.row-check', function () {
        const rows = table.rows({ search: 'applied' }).nodes();
        const allChecked = $('input.row-check', rows).length === $('input.row-check:checked', rows).length;
        selectAll.checked = allChecked;
        syncBulkButton();
    });

    syncBulkButton();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
