<?php
require_once __DIR__ . '/../connections/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!empty($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    return null;
}

function is_admin() {
    return !empty($_SESSION['admin']);
}

function is_student() {
    return !empty($_SESSION['student']);
}

function require_admin() {
    if (!is_admin()) {
        redirect('/cbt/admin/login.php');
    }
}

function admin_role() {
    return $_SESSION['admin']['role'] ?? 'viewer';
}

function admin_permissions() {
    // Role capability map used by admin_can().
    return [
        'super_admin' => ['*'],
        'exam_manager' => [
            'manage_classes',
            'manage_students',
            'manage_subjects',
            'manage_questions',
            'manage_exams',
            'manage_assignments'
        ],
        'result_manager' => [
            'view_results',
            'grade_results'
        ],
        'viewer' => []
    ];
}

function admin_can($permission) {
    if (!is_admin()) {
        return false;
    }

    $role = admin_role();
    $permissions = admin_permissions();
    $allowed = $permissions[$role] ?? [];

    return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
}

function require_admin_permission($permission) {
    if (!admin_can($permission)) {
        flash('error', 'You do not have access to that section.');
        redirect('/cbt/admin/dashboard.php');
    }
}

function require_student() {
    if (!is_student()) {
        redirect('/cbt/student/login.php');
    }
}

function now_mysql() {
    return date('Y-m-d H:i:s');
}

function format_dt($datetime) {
    if (!$datetime) {
        return '-';
    }
    return date('M d, Y H:i', strtotime($datetime));
}

function enforce_session_timeout() {
    // Enforce idle logout for authenticated users only.
    if (!is_admin() && !is_student()) {
        return;
    }

    $timeout_seconds = 600;
    if (is_admin()) {
        $timeout_seconds = defined('ADMIN_SESSION_IDLE_TIMEOUT_SECONDS') ? ADMIN_SESSION_IDLE_TIMEOUT_SECONDS : 600;
    } elseif (is_student()) {
        $timeout_seconds = defined('STUDENT_SESSION_IDLE_TIMEOUT_SECONDS') ? STUDENT_SESSION_IDLE_TIMEOUT_SECONDS : 600;
    }

    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > $timeout_seconds) {
        if (is_student()) {
            // Keep sessions alive during active exams to avoid mid-exam logouts.
            $in_progress = DB::queryFirstField(
                'SELECT id FROM exam_attempts WHERE student_id=%i AND status=%s LIMIT 1',
                $_SESSION['student']['id'],
                'in_progress'
            );
            if ($in_progress) {
                $_SESSION['last_activity'] = $now;
                return;
            }
        }
        $was_admin = is_admin();
        $was_student = is_student();
        session_unset();
        session_regenerate_id(true);
        // Flash message survives redirect so login can display a friendly reason.
        $_SESSION['flash']['error'] = 'Your session expired due to inactivity. Please sign in again.';
        if ($was_admin) {
            redirect('/cbt/admin/login.php');
        }
        if ($was_student) {
            redirect('/cbt/student/login.php');
        }
    }

    // Update activity timestamp on every request.
    $_SESSION['last_activity'] = $now;
}

// Run on bootstrap for every request.
enforce_session_timeout();
