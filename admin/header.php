<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Codex CBT</title>
    <link rel="stylesheet" href="/cbt/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/cbt/assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/cbt/admin/dashboard.php">Codex CBT</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (admin_can('manage_classes')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/classes.php">Classes</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_students')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/students.php">Students</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_subjects')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/subjects.php">Subjects</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_questions')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/questions.php">Questions</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_exams')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/exams.php">Exams</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_assignments')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/assignments.php">Assignments</a></li>
                <?php endif; ?>
                <?php if (admin_can('view_results')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/results.php">Results</a></li>
                <?php endif; ?>
                <?php if (admin_can('manage_admins')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cbt/admin/admins.php">Admins</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text text-light me-3"><?php echo htmlspecialchars($_SESSION['admin']['name']); ?></span>
            <a class="btn btn-outline-light btn-sm" href="/cbt/admin/logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container my-4">
