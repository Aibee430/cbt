<?php
require_once __DIR__ . '/app/bootstrap.php';

if (is_admin()) {
    redirect('/codexCbt/admin/dashboard.php');
}

if (is_student()) {
    redirect('/codexCbt/student/dashboard.php');
}

redirect('/codexCbt/student/login.php');
