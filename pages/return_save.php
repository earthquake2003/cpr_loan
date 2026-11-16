<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$ref = strtoupper(trim($_POST['ref'] ?? ''));
$to  = "index.php?page=return_form&ref=$ref";
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE ref_code = ?");
$st->execute([$ref]);
$loan = $st->fetch();
if (!$loan || (int)$loan['status'] !== 1) { flash('error', 'ไม่พบรายการ หรือแจ้งคืนไปแล้ว'); header('Location: index.php?page=track'); exit; }

$date = $_POST['return_date'] ?? '';
$name = trim($_POST['returner_name'] ?? '');
$items = collect_return_items($_POST);
$err = '';
if (!valid_date($date) || $date > date('Y-m-d')) $err = 'วันที่คืนไม่ถูกต้อง';
elseif ($name === '') $err = 'กรุณากรอกชื่อผู้ส่งคืน';
else $err = return_items_error($items);
if ($err) { flash('error', $err); header("Location: $to"); exit; }

try {
    $pdo->beginTransaction();
    replace_return_items($pdo, $loan['id'], $items);
    $pdo->prepare("UPDATE cpr_loans SET status=4, return_date=?, returner_name=? WHERE id=? AND status=1")
        ->execute([$date, mb_substr($name, 0, 150), $loan['id']]);
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่'); header("Location: $to"); exit;
}
flash('success', 'แจ้งคืนเรียบร้อย กรุณานำอุปกรณ์มาส่งเจ้าหน้าที่เพื่อยืนยันการรับคืน');
header("Location: index.php?page=track&ref=$ref");
