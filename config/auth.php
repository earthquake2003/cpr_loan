<?php
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('คำขอไม่ถูกต้อง (CSRF) กรุณากลับไปโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}
function is_logged_in() { return !empty($_SESSION['user']); }
function is_admin()     { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function current_user() { return $_SESSION['user']['full_name'] ?? ''; }
function require_login() {            // ใช้ในส่วนเจ้าหน้าที่ (admin/index.php)
    if (!is_logged_in()) { header('Location: index.php?page=login'); exit; }
}
function require_admin() {
    if (!is_admin()) { http_response_code(403); die('เฉพาะผู้ดูแลระบบ (Admin) เท่านั้น <a href="index.php">กลับ</a>'); }
}
function flash($icon, $text) { $_SESSION['flash'] = ['icon' => $icon, 'text' => $text]; }
