<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$p = $_POST;
$admin = is_logged_in();

function back($msg) {
    $_SESSION['old'] = $_POST;
    flash('error', $msg);
    header('Location: index.php?page=loan_form');
    exit;
}

// กันบอท: ช่อง honeypot + จำกัดจำนวนครั้งต่อชั่วโมง (เฉพาะผู้ใช้ทั่วไป)
if (!empty($p['website'])) { header('Location: index.php'); exit; }
if (!$admin) {
    $_SESSION['submits'] = array_filter($_SESSION['submits'] ?? [], fn($t) => $t > time() - 3600);
    if (count($_SESSION['submits']) >= 5) back('ส่งคำขอบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
}

$name  = trim($p['borrower_name'] ?? '');
$phone = trim($p['borrower_phone'] ?? '');
$dept  = trim($p['department'] ?? '');
$purp  = trim($p['purpose'] ?? '');
$from  = $p['date_from'] ?? '';
$to    = $p['date_to'] ?? '';
$qty   = (int)($p['cpr_qty'] ?? 0);
$today = date('Y-m-d');

if ($name === '' || $dept === '' || $purp === '') back('กรุณากรอกข้อมูลให้ครบทุกช่องที่มี *');
if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) back('เบอร์โทรศัพท์ไม่ถูกต้อง');
if (!valid_date($from) || !valid_date($to)) back('กรุณาเลือกวันที่ให้ถูกต้อง');
if ($to < $from) back('วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มยืม');
if (!$admin && $from < $today) back('ไม่สามารถเลือกวันที่ย้อนหลังได้');
if ($qty < 1) back('จำนวนหุ่น CPR ต้องอย่างน้อย 1 ตัว');

$avail = cpr_available($pdo, $from, $to);
if ($qty > $avail) {
    back($avail > 0 ? "ช่วงวันที่นี้เหลือหุ่นว่างเพียง $avail ตัว" : 'ช่วงวันที่นี้หุ่นถูกจองหมดแล้ว กรุณาเลือกช่วงอื่น');
}

try {
    $pdo->beginTransaction();
    $ref = new_ref($pdo);
    $pdo->prepare("INSERT INTO cpr_loans
        (ref_code, loan_date, borrower_name, borrower_phone, department, purpose, date_from, date_to,
         total_days, cpr_qty, due_date, status, lender_name, approved_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$ref, $today, mb_substr($name,0,150), $phone, mb_substr($dept,0,150), mb_substr($purp,0,255),
                   $from, $to, days_between($from, $to), $qty, $to,
                   $admin ? 1 : 0, $admin ? current_user() : null, $admin ? date('Y-m-d H:i:s') : null]);
    $loanId = $pdo->lastInsertId();

    $ins = $pdo->prepare("INSERT INTO cpr_loan_items (loan_id, item_name, qty) VALUES (?,?,?)");
    foreach (array_slice($p['item_name'] ?? [], 0, 8, true) as $i => $n) {
        $n = trim($n);
        if ($n !== '') $ins->execute([$loanId, mb_substr($n, 0, 200), max(1, (int)($p['item_qty'][$i] ?? 1))]);
    }
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    back('เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่');
}

if (!$admin) $_SESSION['submits'][] = time();
$_SESSION['last_ref'] = $ref;
header($admin ? "Location: admin/index.php?page=loan_view&id=$loanId" : 'Location: index.php?page=loan_done');
