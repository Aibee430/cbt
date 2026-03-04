<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_questions');

$message = null;
$error = null;
$bulk_report = null;
$error_rows = [];
// Bulk delete feedback message.
$bulk_delete_report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $question_type = $_POST['question_type'] ?? 'mcq';
        $marks = (int)($_POST['marks'] ?? 1);
        $correct_answer = trim($_POST['correct_answer'] ?? '');

        if ($subject_id && $question_text) {
            DB::insert('questions', [
                'subject_id' => $subject_id,
                'question_text' => $question_text,
                'question_type' => $question_type,
                'correct_answer' => ($question_type === 'fill') ? $correct_answer : null,
                'marks' => $marks
            ]);
            $question_id = DB::insertId();

            if ($question_type === 'mcq') {
                $options = [
                    trim($_POST['option_a'] ?? ''),
                    trim($_POST['option_b'] ?? ''),
                    trim($_POST['option_c'] ?? ''),
                    trim($_POST['option_d'] ?? '')
                ];
                $correct_index = (int)($_POST['correct_option'] ?? 1) - 1;

                foreach ($options as $idx => $option_text) {
                    if ($option_text === '') {
                        continue;
                    }
                    DB::insert('question_options', [
                        'question_id' => $question_id,
                        'option_text' => $option_text,
                        'is_correct' => ($idx === $correct_index) ? 1 : 0
                    ]);
                }
            }

            $message = 'Question added.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['question_id'] ?? 0);
        if ($id) {
            DB::delete('question_options', 'question_id=%i', $id);
            DB::delete('questions', 'id=%i', $id);
            $message = 'Question removed.';
        }
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['question_ids'] ?? [];
        $ids = array_filter(array_map('intval', $ids));
        if ($ids) {
            // Remove options first to satisfy foreign key constraints.
            DB::delete('question_options', 'question_id IN %li', $ids);
            DB::delete('questions', 'id IN %li', $ids);
            $bulk_delete_report = 'Bulk delete completed. Removed: ' . count($ids) . '.';
        } else {
            $error = 'Please select at least one question to delete.';
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

                $has_subject_column = isset($columns['subject_code']) || isset($columns['subject_id']);
                if (!isset($columns['question_type']) || !isset($columns['question_text']) || !$has_subject_column) {
                    $error = 'CSV is missing required columns. Use the provided template.';
                }

                if ($error) {
                    fclose($handle);
                } else {
                    $subject_map = DB::query('SELECT id, code FROM subjects');
                    $subject_by_code = [];
                    foreach ($subject_map as $row) {
                        $subject_by_code[strtolower($row['code'])] = (int)$row['id'];
                    }

                    $inserted = 0;
                    $skipped = 0;
                    $row_number = 1;
                    $row_errors = [];

                    $add_row_error = function ($line, $reason, $question_text = '') use (&$row_errors, &$error_rows) {
                        $question_preview = trim((string)$question_text);
                        if ($question_preview === '') {
                            $question_preview = '[missing question_text]';
                        } else {
                            $question_preview = mb_strimwidth($question_preview, 0, 90, '...');
                        }
                        $row_errors[] = "line {$line}: {$reason} (question: {$question_preview})";
                        $error_rows[] = [
                            'line' => (int)$line,
                            'question' => $question_preview,
                            'reason' => $reason
                        ];
                    };

                    $normalize_type = function ($value) {
                        $type = strtolower(trim((string)$value));
                        if (in_array($type, ['mcq', 'objective', 'obj', 'multiple_choice', 'multiple choice'], true)) {
                            return 'mcq';
                        }
                        if (in_array($type, ['fill', 'fill in the blank', 'fill_in_blank', 'fib'], true)) {
                            return 'fill';
                        }
                        if (in_array($type, ['essay'], true)) {
                            return 'essay';
                        }
                        return '';
                    };

                    $get = function ($key, $row) use ($columns) {
                        return isset($columns[$key]) ? ($row[$columns[$key]] ?? '') : '';
                    };

                    // Parse each CSV row and insert questions only when validation passes.
                    while (($row = fgetcsv($handle)) !== false) {
                        $row_number++;
                        if (count(array_filter($row, 'strlen')) === 0) {
                            continue;
                        }

                        $subject_id = 0;
                        $subject_id_raw = trim((string)$get('subject_id', $row));
                        $subject_code_raw = strtolower(trim((string)$get('subject_code', $row)));
                        if ($subject_id_raw !== '' && ctype_digit($subject_id_raw)) {
                            $subject_id = (int)$subject_id_raw;
                        } elseif ($subject_code_raw !== '') {
                            $subject_id = $subject_by_code[$subject_code_raw] ?? 0;
                        }

                        $question_type = $normalize_type($get('question_type', $row));
                        $question_text = trim((string)$get('question_text', $row));
                        $marks_raw = trim((string)$get('marks', $row));
                        $marks = ($marks_raw === '') ? 0 : (int)$marks_raw;
                        $correct_answer = trim((string)$get('correct_answer', $row));

                        if (!$subject_id) {
                            $skipped++;
                            $add_row_error($row_number, 'invalid or missing subject_id/subject_code', $question_text);
                            continue;
                        }
                        if ($question_text === '') {
                            $skipped++;
                            $add_row_error($row_number, 'question_text is required', $question_text);
                            continue;
                        }
                        if ($question_type === '') {
                            $skipped++;
                            $add_row_error($row_number, 'question_type must be mcq, fill, or essay', $question_text);
                            continue;
                        }
                        if ($marks_raw === '') {
                            $skipped++;
                            $add_row_error($row_number, 'marks is required', $question_text);
                            continue;
                        }
                        if ($marks <= 0) {
                            $skipped++;
                            $add_row_error($row_number, 'marks must be a positive integer', $question_text);
                            continue;
                        }

                        $options = [];
                        $correct_index = -1;
                        if ($question_type === 'fill' && $correct_answer === '') {
                            $skipped++;
                            $add_row_error($row_number, 'correct_answer is required for fill questions', $question_text);
                            continue;
                        }
                        if ($question_type === 'mcq') {
                            $options = [
                                trim((string)$get('option_a', $row)),
                                trim((string)$get('option_b', $row)),
                                trim((string)$get('option_c', $row)),
                                trim((string)$get('option_d', $row))
                            ];
                            $correct_raw = strtoupper(trim((string)$get('correct_option', $row)));
                            if ($correct_raw === '') {
                                $skipped++;
                                $add_row_error($row_number, 'correct_option is required for mcq questions', $question_text);
                                continue;
                            }
                            $correct_index = ctype_digit($correct_raw) ? ((int)$correct_raw - 1) : (ord($correct_raw) - ord('A'));
                            if ($correct_index < 0 || $correct_index > 3) {
                                $skipped++;
                                $add_row_error($row_number, 'correct_option must be A-D or 1-4', $question_text);
                                continue;
                            }

                            $filled_option_count = 0;
                            foreach ($options as $option_text) {
                                if ($option_text !== '') {
                                    $filled_option_count++;
                                }
                            }
                            if ($filled_option_count < 2) {
                                $skipped++;
                                $add_row_error($row_number, 'mcq questions require at least two non-empty options', $question_text);
                                continue;
                            }
                            if (($options[$correct_index] ?? '') === '') {
                                $skipped++;
                                $add_row_error($row_number, 'correct_option points to an empty option', $question_text);
                                continue;
                            }
                        }

                        try {
                            DB::insert('questions', [
                                'subject_id' => $subject_id,
                                'question_text' => $question_text,
                                'question_type' => $question_type,
                                'correct_answer' => ($question_type === 'fill') ? $correct_answer : null,
                                'marks' => $marks
                            ]);
                            $question_id = DB::insertId();

                            if ($question_type === 'mcq') {
                                foreach ($options as $idx => $option_text) {
                                    if ($option_text === '') {
                                        continue;
                                    }
                                    DB::insert('question_options', [
                                        'question_id' => $question_id,
                                        'option_text' => $option_text,
                                        'is_correct' => ($idx === $correct_index) ? 1 : 0
                                    ]);
                                }
                            }

                            $inserted++;
                        } catch (Throwable $e) {
                            $skipped++;
                            $add_row_error($row_number, 'database error while saving row', $question_text);
                        }
                    }

                    fclose($handle);
                    $bulk_report = "Bulk upload completed. Inserted: {$inserted}, Skipped: {$skipped}.";
                    if ($skipped > 0) {
                        $error = "Some rows where skipped: {$skipped}";
                    }
                }
            }
        } else {
            $error = 'Please choose a CSV file to upload.';
        }
    }
}

$subjects = DB::query('SELECT * FROM subjects ORDER BY name');
$questions = DB::query('SELECT questions.*, subjects.name AS subject_name FROM questions JOIN subjects ON subjects.id=questions.subject_id ORDER BY questions.created_at DESC');
?>
<h3 class="mb-1">Question Bank</h3>
<div class="card shadow-sm mt-2 mb-2">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Question Bulk Upload (CSV)</span>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addQuestionModal">Add Single Question</button>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Download the template, fill it, and upload here.
                    <a href="/cbt/docs/question_upload_template.csv">Download template</a>
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
<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($bulk_report): ?>
    <div class="alert alert-info" data-auto-dismiss><?php echo htmlspecialchars($bulk_report); ?></div>
<?php endif; ?>
<?php if ($bulk_delete_report): ?>
    <div class="alert alert-warning" data-auto-dismiss><?php echo htmlspecialchars($bulk_delete_report); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo nl2br(htmlspecialchars($error)); ?>
        <?php if ($error_rows): ?>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Line</th>
                            <th>Question</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($error_rows as $row): ?>
                            <tr>
                                <td><?php echo (int)$row['line']; ?></td>
                                <td><?php echo htmlspecialchars($row['question']); ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Question List</span>
                <div class="d-flex gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <label for="subjectFilter" class="form-label mb-0 small text-muted">Filter by subject</label>
                        <select id="subjectFilter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="">All subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['name']); ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" type="submit" form="bulkDeleteForm" id="bulkDeleteBtn" disabled>Delete Selected</button>
                </div>
            </div>
            <div class="card-body p-0">
                <form id="bulkDeleteForm" method="post">
                    <input type="hidden" name="action" value="bulk_delete">
                </form>
                <table class="table mb-0" id="questionsTable">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Question</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Marks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$questions): ?>
                            <tr><td colspan="6" class="text-center text-muted">No questions yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-check" name="question_ids[]" value="<?php echo (int)$question['id']; ?>" form="bulkDeleteForm">
                                </td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($question['question_text'], 0, 60, '...')); ?></td>
                                <td><?php echo htmlspecialchars($question['subject_name']); ?></td>
                                <td><span class="badge badge-soft"><?php echo htmlspecialchars(strtoupper($question['question_type'])); ?></span></td>
                                <td><?php echo (int)$question['marks']; ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this question?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="question_id" value="<?php echo (int)$question['id']; ?>">
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

<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Single Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addQuestionForm">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo (int)$subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="question_type" class="form-select" required>
                                <option value="mcq">Multiple Choice</option>
                                <option value="fill">Fill in the Blank</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Marks</label>
                            <input type="number" name="marks" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Question</label>
                            <textarea name="question_text" class="form-control" rows="6" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correct Answer (Fill in the blank)</label>
                            <input type="text" name="correct_answer" class="form-control mb-3">
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-2">MCQ Options</div>
                                <div class="mb-2">
                                    <input type="text" name="option_a" class="form-control" placeholder="Option A">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="option_b" class="form-control" placeholder="Option B">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="option_c" class="form-control" placeholder="Option C">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="option_d" class="form-control" placeholder="Option D">
                                </div>
                                <label class="form-label">Correct Option</label>
                                <select name="correct_option" class="form-select">
                                    <option value="1">A</option>
                                    <option value="2">B</option>
                                    <option value="3">C</option>
                                    <option value="4">D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="addQuestionForm">Save Question</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/cbt/assets/libs/datatables/css/dataTables.bootstrap5.min.css">
<script src="/cbt/assets/libs/jquery/jquery.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
<script src="/cbt/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>
// DataTable setup with search, paging, and sorting.
$(function () {
    const table = $('#questionsTable').DataTable({
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5] }
        ]
    });

    const bulkBtn = document.getElementById('bulkDeleteBtn');
    const selectAll = document.getElementById('selectAll');
    const subjectFilter = document.getElementById('subjectFilter');

    function syncBulkButton() {
        const anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
        bulkBtn.disabled = !anyChecked;
    }

    selectAll.addEventListener('change', function () {
        const rows = table.rows({ search: 'applied' }).nodes();
        $('input.row-check', rows).prop('checked', this.checked);
        syncBulkButton();
    });

    $('#questionsTable tbody').on('change', '.row-check', function () {
        const rows = table.rows({ search: 'applied' }).nodes();
        const allChecked = $('input.row-check', rows).length === $('input.row-check:checked', rows).length;
        selectAll.checked = allChecked;
        syncBulkButton();
    });

    subjectFilter.addEventListener('change', function () {
        const value = this.value.trim();
        if (!value) {
            table.column(2).search('').draw();
            return;
        }
        const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        table.column(2).search('^' + escaped + '$', true, false).draw();
    });

    syncBulkButton();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
