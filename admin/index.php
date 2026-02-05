<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (is_admin()) {
    redirect('/cbt/admin/dashboard.php');
}

redirect('/cbt/admin/login.php');
