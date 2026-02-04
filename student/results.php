<?php
require_once __DIR__ . '/header.php';

$student_id = $_SESSION['student']['id'];
$now = now_mysql();

$attempts = DB::query('
    SELECT exam_attempts.*, exams.title, exams.show_result, exams.result_release_at
    FROM exam_attempts
    JOIN exams ON exams.id=exam_attempts.exam_id
    WHERE exam_attempts.student_id=%i
    ORDER BY exam_attempts.started_at DESC
', $student_id);
?>
<h3 class="mb-3">My Results</h3>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$attempts): ?>
                    <tr><td colspan="4" class="text-center text-muted">No attempts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($attempts as $attempt): ?>
                    <?php
                        $released = false;
                        if ($attempt['show_result'] === 'immediate') {
                            $released = true;
                        } elseif (!empty($attempt['result_release_at']) && $now >= $attempt['result_release_at']) {
                            $released = true;
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $attempt['status'] === 'graded' ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars($attempt['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($released && $attempt['status'] === 'graded'): ?>
                                <?php echo number_format($attempt['score'], 2) . ' / ' . number_format($attempt['total_marks'], 2); ?>
                            <?php elseif ($attempt['status'] !== 'graded'): ?>
                                Pending grading
                            <?php else: ?>
                                Not released
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_dt($attempt['submitted_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
