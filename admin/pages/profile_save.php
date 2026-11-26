<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=profile');
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$returnToProfile = function ($message) use ($username, $fullName) {
    $_SESSION['profile_form'] = ['username' => $username, 'full_name' => $fullName];
    flash('error', $message);
    header('Location: index.php?page=profile');
    exit;
};

if (!preg_match('/^[A-Za-z0-9_.\-]{3,50}$/', $username)) {
    $returnToProfile('ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ . - ความยาว 3-50 ตัว');
}
if ($fullName === '' || mb_strlen($fullName) > 150) {
    $returnToProfile('กรุณากรอกชื่อ-นามสกุลไม่เกิน 150 ตัวอักษร');
}

$accountSt = $pdo->prepare('SELECT password_hash FROM cpr_users WHERE id = ?');
$accountSt->execute([$userId]);
$account = $accountSt->fetch();
if (!$account) {
    http_response_code(404);
    die('ไม่พบบัญชีผู้ใช้ กรุณาเข้าสู่ระบบใหม่');
}

if ($currentPassword !== '' || $password !== '' || $passwordConfirm !== '') {
    if ($currentPassword === '' || !password_verify($currentPassword, $account['password_hash'])) {
        $returnToProfile('รหัสผ่านปัจจุบันไม่ถูกต้อง');
    }
    if ($password === '' || strlen($password) < 8) {
        $returnToProfile('รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัวอักษร');
    }
    if ($password !== $passwordConfirm) {
        $returnToProfile('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน');
    }
}

$duplicateSt = $pdo->prepare('SELECT 1 FROM cpr_users WHERE username = ? AND id <> ?');
$duplicateSt->execute([$username, $userId]);
if ($duplicateSt->fetchColumn()) {
    $returnToProfile('ชื่อผู้ใช้นี้ถูกใช้แล้ว');
}

if ($password !== '') {
    $updateSt = $pdo->prepare('UPDATE cpr_users SET username = ?, full_name = ?, password_hash = ? WHERE id = ?');
    $updateSt->execute([$username, $fullName, password_hash($password, PASSWORD_DEFAULT), $userId]);
} else {
    $updateSt = $pdo->prepare('UPDATE cpr_users SET username = ?, full_name = ? WHERE id = ?');
    $updateSt->execute([$username, $fullName, $userId]);
}

$_SESSION['user']['username'] = $username;
$_SESSION['user']['full_name'] = $fullName;
unset($_SESSION['profile_form']);
flash('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อย');
header('Location: index.php?page=profile');
