<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=settings'); exit; }
$n = (int)($_POST['total_cpr'] ?? 0);
if ($n < 1 || $n > 999) { flash('error', 'จำนวนต้องอยู่ระหว่าง 1-999'); }
else {
    $pdo->prepare("INSERT INTO cpr_settings (skey, svalue) VALUES ('total_cpr', ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)")->execute([$n]);
    flash('success', 'บันทึกการตั้งค่าแล้ว');
}
header('Location: index.php?page=settings');
