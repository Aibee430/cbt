<?php
require_once __DIR__ . '/header.php';
require_admin_permission('grade_results');

$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$attempt = DB::queryFirstRow('
    SELECT exam_attempts.*, exams.title, students.full_name
    FROM exam_attempts
    JOIN exams ON exams.id=exam_attempts.exam_id
    JOIN students ON students.id=exam_attempts.student_id
    WHERE exam_attempts.id=%i
', $attempt_id);

if (!$attempt) {
    redirect('/cbt/admin/results.php');
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $essay_marks = $_POST['essay_marks'] ?? [];

    foreach ($essay_marks as $answer_id => $marks) {
        $marks = max(0, (float)$marks);
        DB::update('exam_answers', [
            'marks_awarded' => $marks,
            'graded_by' => $_SESSION['admin']['id'],
            'graded_at' => now_mysql()
        ], 'id=%i', (int)$answer_id);
    }

    $message = 'Grades updated.';
}

$answers = DB::query('
    SELECT exam_answers.*, questions.question_text, questions.question_type, questions.marks, questions.correct_answer
    FROM exam_answers
    JOIN questions ON questions.id=exam_answers.question_id
    WHERE exam_answers.attempt_id=%i
', $attempt_id);

$total_marks = DB::queryFirstField('
    SELECT SUM(questions.marks)
    FROM exam_attempt_questions
    JOIN questions ON questions.id=exam_attempt_questions.question_id
    WHERE exam_attempt_questions.attempt_id=%i
', $attempt_id);

$score = 0;
$pending_essay = 0;

foreach ($answers as $answer) {
    if ($answer['question_type'] === 'essay') {
        if ($answer['marks_awarded'] === null) {
            $pending_essay++;
        }
        $score += (float)($answer['marks_awarded'] ?? 0);
    } else {
        $score += (float)($answer['marks_awarded'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $pending_essay === 0 ? 'graded' : 'submitted';
    DB::update('exam_attempts', [
        'score' => $score,
        'total_marks' => $total_marks ?? 0,
        'status' => $status
    ], 'id=%i', $attempt_id);
}
?>
<h3 class="mb-3">Attempt Review</h3>

<div class="mb-3">
    <span class="badge bg-dark"><?php echo htmlspecialchars($attempt['title']); ?></span>
    <span class="ms-2">Student: <?php echo htmlspecialchars($attempt['full_name']); ?></span>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" data-auto-dismiss><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="post">
    <?php foreach ($answers as $answer): ?>
        <div class="exam-question">
            <div class="fw-semibold mb-2">
                <?php echo htmlspecialchars($answer['question_text']); ?>
                <span class="badge badge-soft ms-2"><?php echo strtoupper($answer['question_type']); ?></span>
                <span class="badge bg-secondary ms-2">Marks: <?php echo (int)$answer['marks']; ?></span>
            </div>
            <?php if ($answer['question_type'] === 'mcq'): ?>
                <div class="text-muted">Selected Option ID: <?php echo htmlspecialchars($answer['selected_option_id']); ?></div>
                <div>Answer: <?php echo htmlspecialchars($answer['answer_text'] ?? '-'); ?></div>
                <div>Correct: <?php echo htmlspecialchars($answer['is_correct'] ? 'Yes' : 'No'); ?></div>
            <?php elseif ($answer['question_type'] === 'fill'): ?>
                <div>Answer: <?php echo htmlspecialchars($answer['answer_text'] ?? '-'); ?></div>
                <div class="text-muted">Expected: <?php echo htmlspecialchars($answer['correct_answer'] ?? '-'); ?></div>
            <?php else: ?>
                <div class="mb-3">
                    <div class="text-muted mb-1">Student Response</div>
                    <div class="border rounded p-2 bg-light"><?php echo nl2br(htmlspecialchars($answer['answer_text'] ?? '')); ?></div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Marks Awarded (max <?php echo (int)$answer['marks']; ?>)</label>
                    <input type="number" step="0.5" max="<?php echo (int)$answer['marks']; ?>" name="essay_marks[<?php echo (int)$answer['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($answer['marks_awarded']); ?>">
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button class="btn btn-primary" type="submit">Save Grades</button>
    <a class="btn btn-outline-secondary" href="/cbt/admin/results.php">Back</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
