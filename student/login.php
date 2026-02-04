<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (is_student()) {
    redirect('/cbt/student/dashboard.php');
}

$flash_error = flash('error'); // Session timeout or access message.
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    $student = DB::queryFirstRow('SELECT * FROM students WHERE (reg_no=%s OR email=%s) AND status=%s', $identity, $identity, 'active');
    if ($student && password_verify($password, $student['password_hash'])) {
        $_SESSION['student'] = [
            'id' => $student['id'],
            'name' => $student['full_name'],
            'class_id' => $student['class_id'],
            'reg_no' => $student['reg_no'],
            'email' => $student['email']
        ];
        redirect('/cbt/student/dashboard.php');
    }

    $error = 'Invalid login credentials.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Login - Codex CBT</title>
    <link rel="stylesheet" href="/cbt/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/cbt/assets/css/app.css">
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-sm auth-card w-100">
            <div class="card-body p-4">
                <h4 class="mb-1">Student Login</h4>
                <p class="text-muted">Access your assigned exams.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($flash_error): ?>
                    <div class="alert alert-warning" data-auto-dismiss><?php echo htmlspecialchars($flash_error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Reg No or Email</label>
                        <input type="text" name="identity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
                <div class="mt-3 small text-muted">
                    Default student: student@codexcbt.local / student123
                </div>
            </div>
        </div>
    </div>
    <script src="/cbt/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/cbt/assets/js/app.js"></script>
</body>
</html>
