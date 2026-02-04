<?php
require_once __DIR__ . '/header.php';

$error = flash('error');

$stats = [
    'students' => DB::queryFirstField('SELECT COUNT(*) FROM students'),
    'classes' => DB::queryFirstField('SELECT COUNT(*) FROM classes'),
    'subjects' => DB::queryFirstField('SELECT COUNT(*) FROM subjects'),
    'questions' => DB::queryFirstField('SELECT COUNT(*) FROM questions'),
    'exams' => DB::queryFirstField('SELECT COUNT(*) FROM exams')
];

$upcoming = DB::query('SELECT exams.*, subjects.name AS subject_name FROM exams JOIN subjects ON subjects.id=exams.subject_id ORDER BY start_at DESC LIMIT 5');
?>
<h3 class="mb-3">Dashboard</h3>
<?php if ($error): ?>
    <div class="alert alert-warning" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Students</div>
                <div class="display-6"><?php echo (int)$stats['students']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Classes</div>
                <div class="display-6"><?php echo (int)$stats['classes']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Subjects</div>
                <div class="display-6"><?php echo (int)$stats['subjects']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted">Questions</div>
                <div class="display-6"><?php echo (int)$stats['questions']; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Upcoming / Recent Exams</span>
        <a class="btn btn-sm btn-primary" href="/codexCbt/admin/exams.php">Manage Exams</a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$upcoming): ?>
                    <tr><td colspan="4" class="text-center text-muted">No exams yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($upcoming as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                        <td><?php echo format_dt($exam['start_at']); ?></td>
                        <td><?php echo format_dt($exam['end_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
