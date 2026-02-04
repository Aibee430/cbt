<?php
require_once __DIR__ . '/../app/bootstrap.php';

unset($_SESSION['student']);
redirect('/cbt/student/login.php');
