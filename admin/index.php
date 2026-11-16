<?php
// ฝั่งเจ้าหน้าที่ — ต้อง login
require __DIR__ . '/../config/bootstrap.php';
$ASSET = '../';

$allowed    = ['dashboard','loans','loan_view','loan_action','return_form','return_save','loan_print',
               'users','user_form','user_save','user_delete','settings','settings_save','login','logout'];
$admin_only = ['users','user_form','user_save','user_delete','settings','settings_save'];
$no_layout  = ['login','logout','loan_action','return_save','loan_print','user_save','user_delete','settings_save'];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowed, true)) $page = 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();

if (!in_array($page, ['login', 'logout'], true)) {
    require_login();
    if (in_array($page, $admin_only, true)) require_admin();
}

if (in_array($page, $no_layout, true)) {
    require __DIR__ . "/pages/$page.php";
    exit;
}
include __DIR__ . '/includes/header.php';
include __DIR__ . "/pages/$page.php";
include __DIR__ . '/includes/footer.php';
