<?php
require __DIR__ . '/config/bootstrap.php';

$admin_pages = ['dashboard','loans','loan_view','loan_action','return_form','return_save','loan_print',
                'manikins','manikin_save','manikin_delete','settings','settings_save','login','logout'];
$staff_loan_pages = ['loan_form','loan_save','api_available'];
$allowed = array_merge($admin_pages, $staff_loan_pages);
$no_layout = ['login','logout','loan_action','return_save','loan_print','manikin_save','manikin_delete',
              'settings_save','loan_save','api_available'];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowed, true)) $page = 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();

if (!in_array($page, ['login', 'logout'], true)) {
    require_login();
    require_admin();
}

$is_admin_page = in_array($page, $admin_pages, true);
$page_file = ($is_admin_page ? __DIR__ . '/admin/pages/' : __DIR__ . '/pages/') . "$page.php";

if (in_array($page, $no_layout, true)) {
    require $page_file;
    exit;
}

include __DIR__ . '/admin/includes/header.php';
include $page_file;
include __DIR__ . '/admin/includes/footer.php';
