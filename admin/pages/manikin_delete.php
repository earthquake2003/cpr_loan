<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=manikins'); exit; }
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT id, is_active FROM cpr_manikins WHERE id = ?');
$st->execute([$id]);
$manikin = $st->fetch();
if (!$manikin) {
    flash('error', 'ไม่พบหุ่น CPR');
    header('Location: index.php?page=manikins');
    exit;
}

if (cpr_has_legacy_active_loans($pdo)) {
    flash('error', 'มีใบยืมเดิมที่ยังไม่ระบุชื่อหุ่น กรุณารับคืนก่อนลบหุ่น');
    header('Location: index.php?page=manikins');
    exit;
}

$inUse = $pdo->prepare("SELECT COUNT(*) FROM cpr_loan_manikins lm
    JOIN cpr_loans l ON l.id = lm.loan_id
    WHERE lm.manikin_id = ? AND l.status IN (0,1,4)");
$inUse->execute([$id]);
if ((int)$inUse->fetchColumn() > 0) {
    flash('error', 'หุ่นนี้อยู่ในรายการยืมหรือรอตรวจสอบ จึงยังลบไม่ได้');
    header('Location: index.php?page=manikins');
    exit;
}

$history = $pdo->prepare('SELECT 1 FROM cpr_loan_manikins WHERE manikin_id = ? LIMIT 1');
$history->execute([$id]);
if ($history->fetchColumn()) {
    $pdo->prepare('UPDATE cpr_manikins SET is_active = 0 WHERE id = ?')->execute([$id]);
    flash('success', 'ปิดใช้งานหุ่นแล้ว และเก็บประวัติใบยืมไว้');
} else {
    $pdo->prepare('DELETE FROM cpr_manikins WHERE id = ?')->execute([$id]);
    flash('success', 'ลบหุ่น CPR แล้ว');
}
header('Location: index.php?page=manikins');
