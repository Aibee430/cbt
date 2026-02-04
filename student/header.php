<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_student();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student - Codex CBT</title>
    <link rel="stylesheet" href="/codexCbt/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/codexCbt/assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/codexCbt/student/dashboard.php">Codex CBT</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="studentNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/codexCbt/student/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/codexCbt/student/results.php">Results</a></li>
            </ul>
            <span class="navbar-text text-light me-3"><?php echo htmlspecialchars($_SESSION['student']['name']); ?></span>
            <a class="btn btn-outline-light btn-sm" href="/codexCbt/student/logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container my-4">
