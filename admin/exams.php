<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_exams');

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $instructions = trim($_POST['instructions'] ?? '');
        $start_at = str_replace('T', ' ', $_POST['start_at'] ?? '');
        $end_at = str_replace('T', ' ', $_POST['end_at'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $question_count = (int)($_POST['question_count'] ?? 0);
        $randomize = (int)($_POST['randomize'] ?? 1);
        $allow_multiple = (int)($_POST['allow_multiple_attempts'] ?? 0);
        $max_attempts = (int)($_POST['max_attempts'] ?? 1);
        $show_result = $_POST['show_result'] ?? 'after_release';
        $result_release_at = $_POST['result_release_at'] ? str_replace('T', ' ', $_POST['result_release_at']) : null;

        if ($title && $subject_id && $start_at && $end_at && $duration && $question_count) {
            DB::insert('exams', [
                'title' => $title,
                'subject_id' => $subject_id,
                'instructions' => $instructions,
                'start_at' => $start_at,
                'end_at' => $end_at,
                'duration_minutes' => $duration,
                'question_count' => $question_count,
                'randomize' => $randomize,
                'allow_multiple_attempts' => $allow_multiple,
                'max_attempts' => $max_attempts,
                'show_result' => $show_result,
                'result_release_at' => $result_release_at
            ]);
            $message = 'Exam created.';
        }
    }

    if ($action === 'update') {
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $instructions = trim($_POST['instructions'] ?? '');
        $start_at = str_replace('T', ' ', $_POST['start_at'] ?? '');
        $end_at = str_replace('T', ' ', $_POST['end_at'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $question_count = (int)($_POST['question_count'] ?? 0);
        $randomize = (int)($_POST['randomize'] ?? 1);
        $allow_multiple = (int)($_POST['allow_multiple_attempts'] ?? 0);
        $max_attempts = (int)($_POST['max_attempts'] ?? 1);
        $show_result = $_POST['show_result'] ?? 'after_release';
        $result_release_at = $_POST['result_release_at'] ? str_replace('T', ' ', $_POST['result_release_at']) : null;

        $existing = $exam_id ? DB::queryFirstRow('SELECT * FROM exams WHERE id=%i', $exam_id) : null;
        if ($existing) {
            $now = time();
            $is_open = $now >= strtotime($existing['start_at']) && $now <= strtotime($existing['end_at']);
            if ($is_open) {
                $error = 'Ongoing exams cannot be edited.';
            } elseif ($title && $subject_id && $start_at && $end_at && $duration && $question_count) {
                DB::update('exams', [
                    'title' => $title,
                    'subject_id' => $subject_id,
                    'instructions' => $instructions,
                    'start_at' => $start_at,
                    'end_at' => $end_at,
                    'duration_minutes' => $duration,
                    'question_count' => $question_count,
                    'randomize' => $randomize,
                    'allow_multiple_attempts' => $allow_multiple,
                    'max_attempts' => $max_attempts,
                    'show_result' => $show_result,
                    'result_release_at' => $result_release_at
                ], 'id=%i', $exam_id);
                $message = 'Exam updated.';
            }
        } else {
            $error = 'Exam not found.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['exam_id'] ?? 0);
        if ($id) {
            DB::delete('exam_questions', 'exam_id=%i', $id);
            DB::delete('exam_assignments', 'exam_id=%i', $id);
            DB::delete('exams', 'id=%i', $id);
            $message = 'Exam removed.';
        }
    }
}

$subjects = DB::query('SELECT * FROM subjects ORDER BY name');
$exams = DB::query('SELECT exams.*, subjects.name AS subject_name FROM exams JOIN subjects ON subjects.id=exams.subject_id ORDER BY exams.created_at DESC');
$now_ts = time();
?>
<h3 class="mb-3">Exams</h3>

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
                <span>Exam List</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createExamModal">Create Exam</button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="examsTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Mode</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$exams): ?>
                            <tr><td colspan="6" class="text-center text-muted">No exams yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($exams as $exam): ?>
                            <?php
                                $start_ts = strtotime($exam['start_at']);
                                $end_ts = strtotime($exam['end_at']);
                                $is_open = $now_ts >= $start_ts && $now_ts <= $end_ts;
                                $status_label = $is_open ? 'Ongoing' : ($now_ts < $start_ts ? 'Upcoming' : 'Closed');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                <td><?php echo format_dt($exam['start_at']); ?></td>
                                <td><?php echo format_dt($exam['end_at']); ?></td>
                                <td><?php echo $exam['randomize'] ? 'Random' : 'Fixed'; ?></td>
                                <td class="text-end">
                                    <span class="badge bg-<?php echo $is_open ? 'warning' : 'secondary'; ?> me-2"><?php echo $status_label; ?></span>
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editExamModal"
                                        data-id="<?php echo (int)$exam['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($exam['title'], ENT_QUOTES); ?>"
                                        data-subject="<?php echo (int)$exam['subject_id']; ?>"
                                        data-instructions="<?php echo htmlspecialchars($exam['instructions'] ?? '', ENT_QUOTES); ?>"
                                        data-start="<?php echo date('Y-m-d\TH:i', $start_ts); ?>"
                                        data-end="<?php echo date('Y-m-d\TH:i', $end_ts); ?>"
                                        data-duration="<?php echo (int)$exam['duration_minutes']; ?>"
                                        data-count="<?php echo (int)$exam['question_count']; ?>"
                                        data-randomize="<?php echo (int)$exam['randomize']; ?>"
                                        data-multi="<?php echo (int)$exam['allow_multiple_attempts']; ?>"
                                        data-max="<?php echo (int)$exam['max_attempts']; ?>"
                                        data-show="<?php echo htmlspecialchars($exam['show_result']); ?>"
                                        data-release="<?php echo $exam['result_release_at'] ? date('Y-m-d\TH:i', strtotime($exam['result_release_at'])) : ''; ?>"
                                        <?php echo $is_open ? 'disabled' : ''; ?>
                                    >Edit</button>
                                    <a class="btn btn-sm btn-outline-primary <?php echo $is_open ? 'disabled' : ''; ?>" <?php echo $is_open ? 'tabindex="-1" aria-disabled="true"' : ''; ?> href="/cbt/admin/exam_questions.php?exam_id=<?php echo (int)$exam['id']; ?>">Questions</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this exam and all related assignments?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="exam_id" value="<?php echo (int)$exam['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" <?php echo $is_open ? 'disabled' : ''; ?>>Delete</button>
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

<link rel="stylesheet" href="/cbt/assets/libs/datatables/css/dataTables.bootstrap5.min.css">
<script src="/cbt/assets/libs/jquery/jquery.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#examsTable').DataTable({
        pageLength: 10,
        order: [[2, 'desc']],
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
});
</script>

<div class="modal fade" id="createExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="createExamForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo (int)$subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start</label>
                            <input type="datetime-local" name="start_at" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End</label>
                            <input type="datetime-local" name="end_at" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (mins)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="30" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Question Count</label>
                            <input type="number" name="question_count" class="form-control" value="10" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Randomize Questions</label>
                        <select name="randomize" class="form-select">
                            <option value="1">Yes</option>
                            <option value="0">No (fixed set)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Multiple Attempts</label>
                            <select name="allow_multiple_attempts" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Attempts</label>
                            <input type="number" name="max_attempts" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Show Result</label>
                        <select name="show_result" class="form-select">
                            <option value="immediate">Immediately after submit</option>
                            <option value="after_release">After release</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Result Release Date (optional)</label>
                        <input type="datetime-local" name="result_release_at" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="createExamForm">Create Exam</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="editExamForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="exam_id" id="edit_exam_id">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" id="edit_subject_id" class="form-select" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo (int)$subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" id="edit_instructions" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start</label>
                            <input type="datetime-local" name="start_at" id="edit_start_at" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End</label>
                            <input type="datetime-local" name="end_at" id="edit_end_at" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (mins)</label>
                            <input type="number" name="duration_minutes" id="edit_duration" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Question Count</label>
                            <input type="number" name="question_count" id="edit_question_count" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Randomize Questions</label>
                        <select name="randomize" id="edit_randomize" class="form-select">
                            <option value="1">Yes</option>
                            <option value="0">No (fixed set)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Multiple Attempts</label>
                            <select name="allow_multiple_attempts" id="edit_allow_multiple" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Attempts</label>
                            <input type="number" name="max_attempts" id="edit_max_attempts" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Show Result</label>
                        <select name="show_result" id="edit_show_result" class="form-select">
                            <option value="immediate">Immediately after submit</option>
                            <option value="after_release">After release</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Result Release Date (optional)</label>
                        <input type="datetime-local" name="result_release_at" id="edit_result_release" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="editExamForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('editExamModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button) return;
    document.getElementById('edit_exam_id').value = button.getAttribute('data-id');
    document.getElementById('edit_title').value = button.getAttribute('data-title');
    document.getElementById('edit_subject_id').value = button.getAttribute('data-subject');
    document.getElementById('edit_instructions').value = button.getAttribute('data-instructions');
    document.getElementById('edit_start_at').value = button.getAttribute('data-start');
    document.getElementById('edit_end_at').value = button.getAttribute('data-end');
    document.getElementById('edit_duration').value = button.getAttribute('data-duration');
    document.getElementById('edit_question_count').value = button.getAttribute('data-count');
    document.getElementById('edit_randomize').value = button.getAttribute('data-randomize');
    document.getElementById('edit_allow_multiple').value = button.getAttribute('data-multi');
    document.getElementById('edit_max_attempts').value = button.getAttribute('data-max');
    document.getElementById('edit_show_result').value = button.getAttribute('data-show');
    document.getElementById('edit_result_release').value = button.getAttribute('data-release');
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
