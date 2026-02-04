<?php
require_once __DIR__ . '/app/bootstrap.php';

if (is_admin()) {
    redirect('/cbt/admin/dashboard.php');
}

if (is_student()) {
    redirect('/cbt/student/dashboard.php');
}

redirect('/cbt/student/login.php');
