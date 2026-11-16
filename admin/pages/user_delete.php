<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=users'); exit; }
$id = (int)($_POST['id'] ?? 0);
if ($id === (int)$_SESSION['user']['id']) {
    flash('error', 'ลบบัญชีของตัวเองไม่ได้');
} else {
    $st = $pdo->prepare("SELECT role FROM cpr_users WHERE id = ?"); $st->execute([$id]);
    $role = $st->fetchColumn();
    $admins = (int)$pdo->query("SELECT COUNT(*) FROM cpr_users WHERE role='admin'")->fetchColumn();
    if ($role === false) flash('error', 'ไม่พบผู้ใช้');
    elseif ($role === 'admin' && $admins <= 1) flash('error', 'ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน');
    else { $pdo->prepare("DELETE FROM cpr_users WHERE id = ?")->execute([$id]); flash('success', 'ลบผู้ใช้เรียบร้อย'); }
}
header('Location: index.php?page=users');
