<?php
require_once __DIR__ . '/meekro/db.class.php';

DB::$host = 'localhost';
DB::$user = 'root';
DB::$password = '';
DB::$dbName = 'codexcbt';
DB::$encoding = 'utf8mb4';
DB::$port = 3306;
DB::$dbType = 'mysql';

date_default_timezone_set('Africa/Lagos');

if (!defined('SESSION_IDLE_TIMEOUT_SECONDS')) {
    // 600 seconds = 10 minutes of inactivity before session expires.
    define('SESSION_IDLE_TIMEOUT_SECONDS', 600);
}
