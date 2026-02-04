<?php
require_once __DIR__ . '/header.php';

$exam_id = (int)($_GET['exam_id'] ?? 0);
$student_id = $_SESSION['student']['id'];
$class_id = $_SESSION['student']['class_id'];

$exam = DB::queryFirstRow('
    SELECT exams.*
    FROM exam_assignments
    JOIN exams ON exams.id=exam_assignments.exam_id
    WHERE exam_assignments.class_id=%i AND exams.id=%i
', $class_id, $exam_id);

if (!$exam) {
    flash('error', 'Exam not found or not assigned.');
    redirect('/cbt/student/dashboard.php');
}

$now = now_mysql();
if ($now < $exam['start_at'] || $now > $exam['end_at']) {
    flash('error', 'Exam is not available now.');
    redirect('/cbt/student/dashboard.php');
}

$attempt_count = (int)DB::queryFirstField('SELECT COUNT(*) FROM exam_attempts WHERE exam_id=%i AND student_id=%i', $exam_id, $student_id);
$allowed_attempts = $exam['allow_multiple_attempts'] ? $exam['max_attempts'] : 1;

// Resume any in-progress attempt instead of creating a new one.
$in_progress = DB::queryFirstRow('SELECT id FROM exam_attempts WHERE exam_id=%i AND student_id=%i AND status=%s', $exam_id, $student_id, 'in_progress');
if ($in_progress) {
    // Keep a quick session marker for active exam state.
    $_SESSION['active_exam_attempt_id'] = (int)$in_progress['id'];
    redirect('/cbt/student/exam.php?attempt_id=' . (int)$in_progress['id']);
}

if ($attempt_count >= $allowed_attempts) {
    flash('error', 'No attempts remaining for this exam.');
    redirect('/cbt/student/dashboard.php');
}

// Create a new attempt record for the student.
DB::insert('exam_attempts', [
    'exam_id' => $exam_id,
    'student_id' => $student_id,
    'attempt_number' => $attempt_count + 1,
    'started_at' => $now,
    'status' => 'in_progress'
]);
$attempt_id = DB::insertId();
// Track the active attempt for this session.
$_SESSION['active_exam_attempt_id'] = $attempt_id;

// Pull random questions or a fixed list depending on exam settings.
$question_ids = [];
if ((int)$exam['randomize'] === 1) {
    $question_ids = DB::queryFirstColumn('SELECT id FROM questions WHERE subject_id=%i ORDER BY RAND() LIMIT %i', $exam['subject_id'], $exam['question_count']);
} else {
    $question_ids = DB::queryFirstColumn('SELECT question_id FROM exam_questions WHERE exam_id=%i', $exam_id);
}

foreach ($question_ids as $qid) {
    DB::insert('exam_attempt_questions', [
        'attempt_id' => $attempt_id,
        'question_id' => (int)$qid
    ]);
}

redirect('/cbt/student/exam.php?attempt_id=' . $attempt_id);
