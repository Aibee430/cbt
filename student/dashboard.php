<?php
require_once __DIR__ . '/header.php';

$student_id = $_SESSION['student']['id'];
$class_id = $_SESSION['student']['class_id'];
$now = now_mysql();

$exams = DB::query('
    SELECT exams.*, subjects.name AS subject_name
    FROM exam_assignments
    JOIN exams ON exams.id=exam_assignments.exam_id
    JOIN subjects ON subjects.id=exams.subject_id
    WHERE exam_assignments.class_id=%i
    ORDER BY exams.start_at DESC
', $class_id);

$attempts = DB::query('SELECT exam_id, COUNT(*) AS total_attempts FROM exam_attempts WHERE student_id=%i GROUP BY exam_id', $student_id);
$attempt_map = [];
foreach ($attempts as $row) {
    $attempt_map[$row['exam_id']] = (int)$row['total_attempts'];
}

$in_progress_attempts = DB::query('SELECT exam_id, id FROM exam_attempts WHERE student_id=%i AND status=%s', $student_id, 'in_progress');
$in_progress_map = [];
foreach ($in_progress_attempts as $row) {
    $in_progress_map[$row['exam_id']] = (int)$row['id'];
}

$error = flash('error');
?>
<h3 class="mb-3">Welcome back</h3>

<?php if ($error): ?>
    <div class="alert alert-warning" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Subject</th>
                    <th>Window</th>
                    <th>Attempts</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$exams): ?>
                    <tr><td colspan="6" class="text-center text-muted">No exams assigned yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($exams as $exam): ?>
                    <?php
                        $attempt_count = $attempt_map[$exam['id']] ?? 0;
                        $allowed_attempts = $exam['allow_multiple_attempts'] ? $exam['max_attempts'] : 1;
                        $is_open = ($now >= $exam['start_at'] && $now <= $exam['end_at']);
                        $status = 'Upcoming';
                        if ($now > $exam['end_at']) {
                            $status = 'Closed';
                        } elseif ($is_open) {
                            $status = 'Open';
                        }
                        if ($attempt_count >= $allowed_attempts) {
                            $status = 'Attempted';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                        <td><?php echo format_dt($exam['start_at']); ?> - <?php echo format_dt($exam['end_at']); ?></td>
                        <td><?php echo $attempt_count . ' / ' . $allowed_attempts; ?></td>
                        <td><span class="badge bg-<?php echo $status === 'Open' ? 'success' : 'secondary'; ?>"><?php echo $status; ?></span></td>
                        <td class="text-end">
                            <?php if (isset($in_progress_map[$exam['id']])): ?>
                                <a class="btn btn-sm btn-warning" href="/cbt/student/exam.php?attempt_id=<?php echo (int)$in_progress_map[$exam['id']]; ?>">Resume</a>
                            <?php elseif ($status === 'Open'): ?>
                                <a class="btn btn-sm btn-primary" href="/cbt/student/start_exam.php?exam_id=<?php echo (int)$exam['id']; ?>">Start</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled>Not available</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
