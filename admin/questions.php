<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_questions');

$message = null;
$error = null;
$bulk_report = null;
$error_rows = [];
// Bulk delete feedback message.
$bulk_delete_report = null;
$uploaded_asset_target = null;
$reopen_question_modal = false;
$question_class_scope_enabled = question_class_scope_enabled();
$uploadable_asset_fields = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d'];
$form_values = [
    'subject_id' => '',
    'class_id' => '',
    'question_type' => 'mcq',
    'marks' => '1',
    'question_text' => '',
    'correct_answer' => '',
    'option_a' => '',
    'option_b' => '',
    'option_c' => '',
    'option_d' => '',
    'correct_option' => '1'
];

function question_upload_dir() {
    return dirname(__DIR__) . '/uploads/questions';
}

function question_upload_web_path($filename) {
    return '/cbt/uploads/questions/' . rawurlencode($filename);
}

function append_uploaded_asset_markup($existing, $markup) {
    $existing = trim((string)$existing);
    if ($existing === '') {
        return $markup;
    }

    $separator = "\n";
    if (strpos($existing, '<img') !== false || strpos($existing, '</') !== false) {
        $separator = "\n";
    } elseif (substr($existing, -1) !== ' ') {
        $separator = ' ';
    }

    return $existing . $separator . $markup;
}

function store_question_asset($file) {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Please choose an image file to upload.');
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $extensions = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Only PNG, JPG, GIF, WEBP, and SVG images are allowed.');
    }

    $directory = question_upload_dir();
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the question upload directory.');
    }

    $original_name = pathinfo((string)($file['name'] ?? 'question-image'), PATHINFO_FILENAME);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($original_name));
    $slug = trim((string)$slug, '-');
    if ($slug === '') {
        $slug = 'question-image';
    }

    $filename = $slug . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to save the uploaded image.');
    }

    return [
        'path' => $destination,
        'url' => question_upload_web_path($filename),
        'alt' => ucwords(str_replace('-', ' ', $slug))
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    foreach ($form_values as $key => $value) {
        if (isset($_POST[$key])) {
            $form_values[$key] = (string)$_POST[$key];
        }
    }

    if ($action === 'upload_asset') {
        $reopen_question_modal = true;
        try {
            $asset = store_question_asset($_FILES['question_asset'] ?? []);
            $uploaded_asset_target = $_POST['insert_target'] ?? 'question_text';
            if (!in_array($uploaded_asset_target, $uploadable_asset_fields, true)) {
                throw new RuntimeException('Invalid upload target.');
            }

            $uploaded_asset_markup = '<img src="' . htmlspecialchars($asset['url'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($asset['alt'], ENT_QUOTES, 'UTF-8') . '">';
            $form_values[$uploaded_asset_target] = append_uploaded_asset_markup($form_values[$uploaded_asset_target] ?? '', $uploaded_asset_markup);
            $message = 'Image uploaded into ' . ucwords(str_replace('_', ' ', $uploaded_asset_target)) . '.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    if ($action === 'add') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $class_id = (int)($_POST['class_id'] ?? 0);
        $question_text = sanitize_rich_content($_POST['question_text'] ?? '');
        $question_type = $_POST['question_type'] ?? 'mcq';
        $marks = (int)($_POST['marks'] ?? 1);
        $correct_answer = trim($_POST['correct_answer'] ?? '');

        $question_data = [
            'subject_id' => $subject_id,
            'question_text' => $question_text,
            'question_type' => $question_type,
            'correct_answer' => ($question_type === 'fill') ? $correct_answer : null,
            'marks' => $marks
        ];
        if ($question_class_scope_enabled) {
            $question_data['class_id'] = $class_id;
        }

        if ($subject_id && (!$question_class_scope_enabled || $class_id) && $question_text) {
            DB::insert('questions', $question_data);
            $question_id = DB::insertId();

            if ($question_type === 'mcq') {
                $options = [
                    sanitize_rich_content($_POST['option_a'] ?? ''),
                    sanitize_rich_content($_POST['option_b'] ?? ''),
                    sanitize_rich_content($_POST['option_c'] ?? ''),
                    sanitize_rich_content($_POST['option_d'] ?? '')
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
            $form_values = [
                'subject_id' => '',
                'class_id' => '',
                'question_type' => 'mcq',
                'marks' => '1',
                'question_text' => '',
                'correct_answer' => '',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_option' => '1'
            ];
        } else {
            $reopen_question_modal = true;
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
                $has_class_column = isset($columns['class_name']) || isset($columns['class_id']);
                if (!isset($columns['question_type']) || !isset($columns['question_text']) || !$has_subject_column || ($question_class_scope_enabled && !$has_class_column)) {
                    $error = 'CSV is missing required columns. Use the provided template.';
                }

                if ($error) {
                    fclose($handle);
                } else {
                    $subject_map = DB::query('SELECT id, code FROM subjects');
                    $class_map = $question_class_scope_enabled ? DB::query('SELECT id, name FROM classes') : [];
                    $subject_by_code = [];
                    foreach ($subject_map as $row) {
                        $subject_by_code[strtolower($row['code'])] = (int)$row['id'];
                    }
                    $class_by_name = [];
                    foreach ($class_map as $row) {
                        $class_by_name[strtolower($row['name'])] = (int)$row['id'];
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
                        $class_id = 0;
                        $class_id_raw = trim((string)$get('class_id', $row));
                        $class_name_raw = strtolower(trim((string)$get('class_name', $row)));
                        if ($class_id_raw !== '' && ctype_digit($class_id_raw)) {
                            $class_id = (int)$class_id_raw;
                        } elseif ($class_name_raw !== '') {
                            $class_id = $class_by_name[$class_name_raw] ?? 0;
                        }

                        if (!$subject_id) {
                            $skipped++;
                            $add_row_error($row_number, 'invalid or missing subject_id/subject_code', $question_text);
                            continue;
                        }
                        if ($question_class_scope_enabled && !$class_id) {
                            $skipped++;
                            $add_row_error($row_number, 'invalid or missing class_id/class_name', $question_text);
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
                                sanitize_rich_content($get('option_a', $row)),
                                sanitize_rich_content($get('option_b', $row)),
                                sanitize_rich_content($get('option_c', $row)),
                                sanitize_rich_content($get('option_d', $row))
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
                            $question_text = sanitize_rich_content($question_text);
                            $question_data = [
                                'subject_id' => $subject_id,
                                'question_text' => $question_text,
                                'question_type' => $question_type,
                                'correct_answer' => ($question_type === 'fill') ? $correct_answer : null,
                                'marks' => $marks
                            ];
                            if ($question_class_scope_enabled) {
                                $question_data['class_id'] = $class_id;
                            }

                            DB::insert('questions', $question_data);
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
$classes = DB::query('SELECT * FROM classes ORDER BY name');
$questions = $question_class_scope_enabled
    ? DB::query('
        SELECT questions.*, subjects.name AS subject_name, classes.name AS class_name
        FROM questions
        JOIN subjects ON subjects.id=questions.subject_id
        LEFT JOIN classes ON classes.id=questions.class_id
        ORDER BY questions.created_at DESC
    ')
    : DB::query('
        SELECT questions.*, subjects.name AS subject_name, NULL AS class_name
        FROM questions
        JOIN subjects ON subjects.id=questions.subject_id
        ORDER BY questions.created_at DESC
    ');
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
                <div class="small text-muted mb-3">
                    Rich content is supported in `question_text` and MCQ options. Use LaTeX delimiters like `\( x^2 + y^2 = z^2 \)`
                    for equations, and add diagrams with safe image tags such as
                    `<img src="/cbt/uploads/questions/free-body.png" alt="Free body diagram">`.
                    Questions are now class-specific. In CSV files, provide `class_id` or `class_name`, and wrap any cell containing commas or HTML in double quotes.
                </div>
                <?php if (!$question_class_scope_enabled): ?>
                    <div class="alert alert-warning py-2">
                        Class-linked questions are not active yet on this database. Run migration `003_add_question_class_scope.sql` to enable class-specific questions.
                    </div>
                <?php endif; ?>
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
                        <label for="subjectFilter" class="form-label mb-0 small text-muted">Subject</label>
                        <select id="subjectFilter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="">All subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['name']); ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($question_class_scope_enabled): ?>
                        <div class="d-flex align-items-center gap-2">
                            <label for="classFilter" class="form-label mb-0 small text-muted">Class</label>
                            <select id="classFilter" class="form-select form-select-sm" style="min-width: 180px;">
                                <option value="">All classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class['name']); ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
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
                            <?php if ($question_class_scope_enabled): ?>
                                <th>Class</th>
                            <?php endif; ?>
                            <th>Type</th>
                            <th>Marks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$questions): ?>
                            <tr><td colspan="<?php echo $question_class_scope_enabled ? '7' : '6'; ?>" class="text-center text-muted">No questions yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-check" name="question_ids[]" value="<?php echo (int)$question['id']; ?>" form="bulkDeleteForm">
                                </td>
                                <td><?php echo htmlspecialchars(rich_text_preview($question['question_text'], 60)); ?></td>
                                <td><?php echo htmlspecialchars($question['subject_name']); ?></td>
                                <?php if ($question_class_scope_enabled): ?>
                                    <td><?php echo htmlspecialchars($question['class_name'] ?? 'General'); ?></td>
                                <?php endif; ?>
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
    <div class="modal-dialog modal-xl cbt-question-modal modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Single Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" id="addQuestionAssetForm" class="d-none">
                    <input type="hidden" name="action" value="upload_asset">
                    <input type="hidden" name="insert_target" id="assetUploadTarget" value="<?php echo htmlspecialchars($uploaded_asset_target ?? 'question_text'); ?>">
                    <?php foreach (array_keys($form_values) as $key): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" id="assetMirror_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($form_values[$key]); ?>">
                    <?php endforeach; ?>
                    <input type="file" name="question_asset" id="assetUploadInput" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                </form>

                <div class="border rounded p-3 bg-light mb-4">
                    <div class="fw-semibold">Field Image Upload</div>
                    <div class="small text-muted mb-0">
                        Each rich field has its own upload button below. Uploaded images are added directly to that field so question and option images do not overwrite one another.
                    </div>
                </div>

                <form method="post" id="addQuestionForm">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo (int)$subject['id']; ?>" <?php echo ((string)(int)$subject['id'] === $form_values['subject_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($question_class_scope_enabled): ?>
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Select class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo (int)$class['id']; ?>" <?php echo ((string)(int)$class['id'] === $form_values['class_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="question_type" class="form-select" required>
                                <option value="mcq" <?php echo $form_values['question_type'] === 'mcq' ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="fill" <?php echo $form_values['question_type'] === 'fill' ? 'selected' : ''; ?>>Fill in the Blank</option>
                                <option value="essay" <?php echo $form_values['question_type'] === 'essay' ? 'selected' : ''; ?>>Essay</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Marks</label>
                            <input type="number" name="marks" class="form-control" value="<?php echo htmlspecialchars($form_values['marks']); ?>" min="1" required>
                        </div>
                        <div class="col-lg-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Question</label>
                                <button class="btn btn-sm btn-outline-primary field-image-upload-btn" type="button" data-target-field="question_text">Upload Question Image</button>
                            </div>
                            <textarea name="question_text" id="questionTextField" class="form-control rich-input" rows="6" required><?php echo htmlspecialchars($form_values['question_text']); ?></textarea>
                            <div class="form-text">
                                Supports safe HTML and MathJax LaTeX. Example:
                                `&lt;img src="/cbt/uploads/questions/circuit.png" alt="Circuit"&gt;` and `\( F = ma \)`.
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Correct Answer (Fill in the blank)</label>
                            <input type="text" name="correct_answer" class="form-control mb-3" value="<?php echo htmlspecialchars($form_values['correct_answer']); ?>">
                            <div class="border rounded p-3">
                                <div class="fw-semibold mb-2">MCQ Options</div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 small text-muted">Option A</label>
                                        <button class="btn btn-sm btn-outline-primary field-image-upload-btn" type="button" data-target-field="option_a">Upload Image</button>
                                    </div>
                                    <input type="text" name="option_a" id="optionAField" class="form-control rich-input" placeholder="Option A" value="<?php echo htmlspecialchars($form_values['option_a']); ?>">
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 small text-muted">Option B</label>
                                        <button class="btn btn-sm btn-outline-primary field-image-upload-btn" type="button" data-target-field="option_b">Upload Image</button>
                                    </div>
                                    <input type="text" name="option_b" id="optionBField" class="form-control rich-input" placeholder="Option B" value="<?php echo htmlspecialchars($form_values['option_b']); ?>">
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 small text-muted">Option C</label>
                                        <button class="btn btn-sm btn-outline-primary field-image-upload-btn" type="button" data-target-field="option_c">Upload Image</button>
                                    </div>
                                    <input type="text" name="option_c" id="optionCField" class="form-control rich-input" placeholder="Option C" value="<?php echo htmlspecialchars($form_values['option_c']); ?>">
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 small text-muted">Option D</label>
                                        <button class="btn btn-sm btn-outline-primary field-image-upload-btn" type="button" data-target-field="option_d">Upload Image</button>
                                    </div>
                                    <input type="text" name="option_d" id="optionDField" class="form-control rich-input" placeholder="Option D" value="<?php echo htmlspecialchars($form_values['option_d']); ?>">
                                </div>
                                <label class="form-label">Correct Option</label>
                                <select name="correct_option" class="form-select">
                                    <option value="1" <?php echo $form_values['correct_option'] === '1' ? 'selected' : ''; ?>>A</option>
                                    <option value="2" <?php echo $form_values['correct_option'] === '2' ? 'selected' : ''; ?>>B</option>
                                    <option value="3" <?php echo $form_values['correct_option'] === '3' ? 'selected' : ''; ?>>C</option>
                                    <option value="4" <?php echo $form_values['correct_option'] === '4' ? 'selected' : ''; ?>>D</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="fw-semibold">Live Preview</div>
                                        <div class="small text-muted">Preview equations and diagrams before saving.</div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="refreshPreviewBtn">Refresh Preview</button>
                                </div>
                                <div id="questionPreview" class="cbt-preview-surface">
                                    <div class="text-muted">Preview will appear here.</div>
                                </div>
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
    const classFilter = document.getElementById('classFilter');
    const preview = document.getElementById('questionPreview');
    const previewButton = document.getElementById('refreshPreviewBtn');
    const richFields = {
        question_text: document.getElementById('questionTextField'),
        option_a: document.getElementById('optionAField'),
        option_b: document.getElementById('optionBField'),
        option_c: document.getElementById('optionCField'),
        option_d: document.getElementById('optionDField')
    };
    const addQuestionForm = document.getElementById('addQuestionForm');
    const addQuestionAssetForm = document.getElementById('addQuestionAssetForm');
    const assetUploadTarget = document.getElementById('assetUploadTarget');
    const assetUploadInput = document.getElementById('assetUploadInput');
    const fieldUploadButtons = document.querySelectorAll('.field-image-upload-btn');

    function syncBulkButton() {
        const anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
        bulkBtn.disabled = !anyChecked;
    }

    function renderPreview() {
        if (!preview) {
            return;
        }

        const questionHtml = richFields.question_text && richFields.question_text.value.trim()
            ? richFields.question_text.value.trim()
            : '<span class="text-muted">Question content not entered yet.</span>';

        const optionNames = ['option_a', 'option_b', 'option_c', 'option_d'];
        let optionsHtml = '';
        optionNames.forEach(function (name, index) {
            const value = richFields[name] ? richFields[name].value.trim() : '';
            if (!value) {
                return;
            }
            const label = String.fromCharCode(65 + index);
            optionsHtml += '<li><strong>' + label + '.</strong> <span>' + value + '</span></li>';
        });

        preview.innerHTML =
            '<div class="cbt-rich-content">' + questionHtml + '</div>' +
            (optionsHtml ? '<hr><div class="small text-muted mb-2">MCQ Options</div><ol class="mb-0">' + optionsHtml + '</ol>' : '');

        if (window.MathJax && typeof window.MathJax.typesetPromise === 'function') {
            window.MathJax.typesetPromise([preview]).catch(function () {});
        }
    }

    function insertMarkupIntoField(field, markup) {
        if (!field || !markup) {
            return;
        }

        const start = field.selectionStart || 0;
        const end = field.selectionEnd || 0;
        const current = field.value || '';
        field.value = current.slice(0, start) + markup + current.slice(end);
        field.focus();
        const nextPos = start + markup.length;
        if (typeof field.setSelectionRange === 'function') {
            field.setSelectionRange(nextPos, nextPos);
        }
        renderPreview();
    }

    function syncAssetUploadMirrors() {
        if (!addQuestionAssetForm) {
            return;
        }

        const fieldsToMirror = ['subject_id', 'class_id', 'question_type', 'marks', 'question_text', 'correct_answer', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'];
        fieldsToMirror.forEach(function (name) {
            const source = addQuestionForm ? addQuestionForm.querySelector('[name="' + name + '"]') : null;
            const mirror = document.getElementById('assetMirror_' + name);
            if (!mirror) {
                return;
            }

            mirror.value = source ? source.value : '';
        });
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

    if (classFilter) {
        classFilter.addEventListener('change', function () {
            const value = this.value.trim();
            if (!value) {
                table.column(3).search('').draw();
                return;
            }
            const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            table.column(3).search('^' + escaped + '$', true, false).draw();
        });
    }

    Object.keys(richFields).forEach(function (key) {
        if (!richFields[key]) {
            return;
        }
        richFields[key].addEventListener('input', renderPreview);
    });

    if (previewButton) {
        previewButton.addEventListener('click', renderPreview);
    }

    fieldUploadButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!assetUploadInput || !assetUploadTarget) {
                return;
            }

            assetUploadTarget.value = button.getAttribute('data-target-field') || 'question_text';
            assetUploadInput.click();
        });
    });

    if (assetUploadInput) {
        assetUploadInput.addEventListener('change', function () {
            if (!assetUploadInput.files || assetUploadInput.files.length === 0 || !addQuestionAssetForm) {
                return;
            }

            syncAssetUploadMirrors();
            addQuestionAssetForm.submit();
        });
    }

    <?php if ($reopen_question_modal): ?>
    const reopenQuestionModalElement = document.getElementById('addQuestionModal');
    if (reopenQuestionModalElement && window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(reopenQuestionModalElement).show();
    }
    <?php endif; ?>

    renderPreview();
    syncBulkButton();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
