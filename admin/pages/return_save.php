<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=loans'); exit; }
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE id = ? AND status IN (1,4)");
$st->execute([$id]);
$l = $st->fetch();
if (!$l) { flash('error', 'ไม่พบรายการ หรือรับคืนไปแล้ว'); header('Location: index.php?page=loans'); exit; }

$date = $_POST['return_date'] ?? '';
$name = trim($_POST['returner_name'] ?? '');
$items = collect_return_items($_POST);
$err = '';
if (!valid_date($date) || $date > date('Y-m-d')) $err = 'วันที่คืนไม่ถูกต้อง';
elseif ($name === '') $err = 'กรุณากรอกชื่อผู้ส่งคืน';
else $err = return_items_error($items);
if ($err) { flash('error', $err); header("Location: index.php?page=return_form&id=$id"); exit; }

try {
    $pdo->beginTransaction();
    replace_return_items($pdo, $id, $items);
    $update = $pdo->prepare("UPDATE cpr_loans SET status=2, return_date=?, returner_name=?, receiver_name=? WHERE id=? AND status IN (1,4)");
    $update->execute([$date, mb_substr($name, 0, 150), current_user(), $id]);
    if ($update->rowCount() !== 1) throw new RuntimeException('สถานะรายการเปลี่ยนไปแล้ว');
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่'); header("Location: index.php?page=return_form&id=$id"); exit;
}
flash('success', 'บันทึกการรับคืนเรียบร้อย');
header("Location: index.php?page=loan_view&id=$id");
