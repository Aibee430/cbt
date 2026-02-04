<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (is_admin()) {
    redirect('/codexCbt/admin/dashboard.php');
}

$flash_error = flash('error'); // Session timeout or access message.
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $admin = DB::queryFirstRow('SELECT * FROM admin_users WHERE email=%s', $email);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => $admin['role'] ?? 'super_admin'
        ];
        redirect('/codexCbt/admin/dashboard.php');
    }

    $error = 'Invalid login credentials.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Codex CBT</title>
    <link rel="stylesheet" href="/codexCbt/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/codexCbt/assets/css/app.css">
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-sm auth-card w-100">
            <div class="card-body p-4">
                <h4 class="mb-1">Admin Login</h4>
                <p class="text-muted">Manage exams, questions, and students.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger" data-auto-dismiss><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($flash_error): ?>
                    <div class="alert alert-warning" data-auto-dismiss><?php echo htmlspecialchars($flash_error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
                <div class="mt-3 small text-muted">
                    Default admin: admin@codexcbt.local / admin123
                </div>
            </div>
        </div>
    </div>
    <script src="/codexCbt/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/codexCbt/assets/js/app.js"></script>
</body>
</html>
