<?php
require_once __DIR__ . '/header.php';
require_admin_permission('manage_exams');

$exam_id = (int)($_GET['exam_id'] ?? 0);
$exam = DB::queryFirstRow('SELECT exams.*, subjects.name AS subject_name FROM exams JOIN subjects ON subjects.id=exams.subject_id WHERE exams.id=%i', $exam_id);

if (!$exam) {
    redirect('/cbt/admin/exams.php');
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['question_ids'] ?? [];
    DB::delete('exam_questions', 'exam_id=%i', $exam_id);

    foreach ($selected as $qid) {
        DB::insert('exam_questions', [
            'exam_id' => $exam_id,
            'question_id' => (int)$qid
        ]);
    }

    $message = 'Exam questions updated.';
}

$questions = DB::query('SELECT * FROM questions WHERE subject_id=%i ORDER BY created_at DESC', $exam['subject_id']);
$existing = DB::queryFirstColumn('SELECT question_id FROM exam_questions WHERE exam_id=%i', $exam_id);
$existing_lookup = array_flip($existing);
?>
<h3 class="mb-3">Exam Questions</h3>

<div class="mb-3 text-muted">
    Exam: <strong><?php echo htmlspecialchars($exam['title']); ?></strong> | Subject: <?php echo htmlspecialchars($exam['subject_name']); ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="post">
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!$questions): ?>
                <p class="text-muted mb-0">No questions found for this subject.</p>
            <?php endif; ?>
            <?php foreach ($questions as $question): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="question_ids[]" value="<?php echo (int)$question['id']; ?>" <?php echo isset($existing_lookup[$question['id']]) ? 'checked' : ''; ?>>
                    <label class="form-check-label">
                        <?php echo htmlspecialchars(mb_strimwidth($question['question_text'], 0, 90, '...')); ?>
                        <span class="badge badge-soft ms-2"><?php echo strtoupper($question['question_type']); ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <span class="text-muted">Select questions for fixed exams.</span>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
