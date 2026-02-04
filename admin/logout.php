<?php
require_once __DIR__ . '/../app/bootstrap.php';

unset($_SESSION['admin']);
redirect('/cbt/admin/login.php');
