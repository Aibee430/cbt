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

function sanitize_rich_content($html) {
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    if (!class_exists('DOMDocument')) {
        return strip_tags($html, '<p><br><strong><em><b><i><u><sub><sup><ul><ol><li><div><span><table><thead><tbody><tr><th><td><img>');
    }

    $allowed_tags = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'u', 'sub', 'sup',
        'ul', 'ol', 'li', 'div', 'span', 'table', 'thead', 'tbody',
        'tr', 'th', 'td', 'img'
    ];
    $allowed_attributes = [
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => ['class'],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'div' => ['class'],
        'span' => ['class'],
        'p' => ['class']
    ];

    $previous = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<!doctype html><html><body><div id="cbt-rich-root">' .
        mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') .
        '</div></body></html>';
    $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $root = $doc->getElementById('cbt-rich-root');
    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    $nodes = iterator_to_array($root->getElementsByTagName('*'));
    foreach ($nodes as $node) {
        $tag = strtolower($node->nodeName);
        if (!in_array($tag, $allowed_tags, true)) {
            $parent = $node->parentNode;
            if ($parent) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
            }
            continue;
        }

        if ($node->hasAttributes()) {
            $remove = [];
            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = trim($attribute->nodeValue);
                $allowed = $allowed_attributes[$tag] ?? [];

                if (!in_array($name, $allowed, true)) {
                    $remove[] = $name;
                    continue;
                }

                if ($tag === 'img' && $name === 'src') {
                    $is_safe_src =
                        strpos($value, '/cbt/') === 0 ||
                        strpos($value, '/') === 0 ||
                        strpos($value, 'http://') === 0 ||
                        strpos($value, 'https://') === 0 ||
                        strpos($value, 'data:image/') === 0;
                    if (!$is_safe_src) {
                        $remove[] = $name;
                    }
                }
            }

            foreach ($remove as $name) {
                $node->removeAttribute($name);
            }
        }
    }

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $doc->saveHTML($child);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return $output;
}

function render_rich_content($html) {
    $html = sanitize_rich_content($html);
    if ($html === '') {
        return '';
    }
    return '<div class="cbt-rich-content">' . $html . '</div>';
}

function rich_text_preview($html, $limit = 90) {
    $plain = trim(html_entity_decode(strip_tags(sanitize_rich_content($html)), ENT_QUOTES, 'UTF-8'));
    if ($plain === '') {
        return '';
    }
    return mb_strimwidth($plain, 0, $limit, '...');
}

function render_plain_response($text) {
    $text = trim((string)$text);
    if ($text === '') {
        return '-';
    }
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

function question_class_scope_enabled() {
    static $enabled = null;
    if ($enabled !== null) {
        return $enabled;
    }

    try {
        $enabled = (bool)DB::queryFirstField("SHOW COLUMNS FROM questions LIKE 'class_id'");
    } catch (Throwable $e) {
        $enabled = false;
    }

    return $enabled;
}

function enforce_session_timeout() {
    // Enforce idle logout for authenticated users only.
    if (!is_admin() && !is_student()) {
        return;
    }

    $timeout_seconds = 600;
    if (is_admin()) {
        $timeout_seconds = defined('ADMIN_SESSION_IDLE_TIMEOUT_SECONDS') ? ADMIN_SESSION_IDLE_TIMEOUT_SECONDS : 900;
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
