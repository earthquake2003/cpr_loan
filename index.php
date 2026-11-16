<?php
// ฝั่งผู้ใช้ทั่วไป — ไม่ต้อง login
require __DIR__ . '/config/bootstrap.php';
$ASSET = '';   // path ไปยัง assets/ จากหน้านี้

$allowed   = ['home','loan_form','loan_save','loan_done','track','return_form','return_save','api_available'];
$no_layout = ['loan_save','return_save','api_available'];

$page = $_GET['page'] ?? 'home';
if (!in_array($page, $allowed, true)) $page = 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();

if (in_array($page, $no_layout, true)) {
    require __DIR__ . "/pages/$page.php";
    exit;
}
include __DIR__ . '/includes/header.php';
include __DIR__ . "/pages/$page.php";
include __DIR__ . '/includes/footer.php';
