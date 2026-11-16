<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=users'); exit; }
$id   = (int)($_POST['id'] ?? 0);
$user = trim($_POST['username'] ?? '');
$name = trim($_POST['full_name'] ?? '');
$role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
$pass = $_POST['password'] ?? '';
$back = 'index.php?page=user_form' . ($id ? "&id=$id" : '');
function fail($msg, $back) { flash('error', $msg); header("Location: $back"); exit; }

if (!preg_match('/^[A-Za-z0-9_.\-]{3,50}$/', $user)) fail('ชื่อผู้ใช้ไม่ถูกต้อง', $back);
if ($name === '') fail('กรุณากรอกชื่อ-นามสกุล', $back);
if (($id === 0 || $pass !== '') && strlen($pass) < 8) fail('รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร', $back);

$dup = $pdo->prepare("SELECT COUNT(*) FROM cpr_users WHERE username = ? AND id <> ?");
$dup->execute([$user, $id]);
if ($dup->fetchColumn() > 0) fail('ชื่อผู้ใช้นี้ถูกใช้แล้ว', $back);

if ($id === 0) {
    $pdo->prepare("INSERT INTO cpr_users (username, password_hash, full_name, role) VALUES (?,?,?,?)")
        ->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $name, $role]);
} else {
    if ($role !== 'admin') {   // ห้ามลดสิทธิ์ admin คนสุดท้าย
        $cur = $pdo->prepare("SELECT role FROM cpr_users WHERE id = ?"); $cur->execute([$id]);
        if ($cur->fetchColumn() === 'admin' && (int)$pdo->query("SELECT COUNT(*) FROM cpr_users WHERE role='admin'")->fetchColumn() <= 1)
            fail('ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน', $back);
    }
    if ($pass !== '') {
        $pdo->prepare("UPDATE cpr_users SET username=?, full_name=?, role=?, password_hash=? WHERE id=?")
            ->execute([$user, $name, $role, password_hash($pass, PASSWORD_DEFAULT), $id]);
    } else {
        $pdo->prepare("UPDATE cpr_users SET username=?, full_name=?, role=? WHERE id=?")->execute([$user, $name, $role, $id]);
    }
    if ($id == $_SESSION['user']['id']) {
        $_SESSION['user'] = ['id' => $id, 'username' => $user, 'full_name' => $name, 'role' => $role];
    }
}
flash('success', 'บันทึกผู้ใช้เรียบร้อย');
header('Location: index.php?page=users');
