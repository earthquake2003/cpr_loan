<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=loans'); exit; }
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE id = ?");
$st->execute([$id]);
$l = $st->fetch();
$to = "index.php?page=loan_view&id=$id";
if (!$l) { flash('error', 'ไม่พบรายการ'); header('Location: index.php?page=loans'); exit; }

if ($action === 'approve' && (int)$l['status'] === 0) {
    $due = $_POST['due_date'] ?? '';
    if (!valid_date($due) || $due < $l['date_from']) { flash('error', 'กำหนดส่งคืนไม่ถูกต้อง'); header("Location: $to"); exit; }
    if (cpr_available($pdo, $l['date_from'], $l['date_to'], $id) < (int)$l['cpr_qty']) {
        flash('error', 'หุ่นไม่เพียงพอในช่วงวันที่นี้'); header("Location: $to"); exit;
    }
    $pdo->prepare("UPDATE cpr_loans SET status=1, lender_name=?, approved_at=NOW(), due_date=? WHERE id=? AND status=0")
        ->execute([current_user(), $due, $id]);
    flash('success', 'อนุมัติเรียบร้อย');
} elseif ($action === 'reject' && (int)$l['status'] === 0) {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') { flash('error', 'กรุณาระบุเหตุผล'); header("Location: $to"); exit; }
    $pdo->prepare("UPDATE cpr_loans SET status=3, reject_reason=?, lender_name=? WHERE id=? AND status=0")
        ->execute([mb_substr($reason, 0, 255), current_user(), $id]);
    flash('success', 'บันทึกการไม่อนุมัติแล้ว');
} elseif ($action === 'delete' && is_admin()) {
    $pdo->prepare("DELETE FROM cpr_loans WHERE id=?")->execute([$id]);
    flash('success', 'ลบใบยืมแล้ว');
    header('Location: index.php?page=loans'); exit;
} else {
    flash('error', 'ดำเนินการไม่ได้ (สถานะเปลี่ยนไปแล้ว?)');
}
header("Location: $to");
