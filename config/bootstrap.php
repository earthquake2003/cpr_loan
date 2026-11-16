<?php
date_default_timezone_set('Asia/Bangkok');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),   // เปิดอัตโนมัติเมื่อใช้ HTTPS
]);
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
