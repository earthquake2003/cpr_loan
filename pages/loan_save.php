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
$rawManikinIds = $p['manikin_ids'] ?? [];
$today = date('Y-m-d');

if ($name === '' || $dept === '' || $purp === '') back('กรุณากรอกข้อมูลให้ครบทุกช่องที่มี *');
if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) back('เบอร์โทรศัพท์ไม่ถูกต้อง');
if (!valid_date($from) || !valid_date($to)) back('กรุณาเลือกวันที่ให้ถูกต้อง');
if ($to < $from) back('วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มยืม');
if (!$admin && $from < $today) back('ไม่สามารถเลือกวันที่ย้อนหลังได้');
if (!is_array($rawManikinIds) || !$rawManikinIds) back('กรุณาเลือกหุ่น CPR อย่างน้อย 1 ตัว');
$manikinIds = [];
foreach ($rawManikinIds as $rawId) {
    $manikinId = filter_var($rawId, FILTER_VALIDATE_INT);
    if ($manikinId === false || $manikinId < 1) back('รายการหุ่น CPR ไม่ถูกต้อง');
    $manikinIds[] = $manikinId;
}
if (count(array_unique($manikinIds)) !== count($manikinIds)) back('มีการเลือกหุ่น CPR ซ้ำกัน');

$availableIds = array_column(cpr_available_manikins($pdo, $from, $to), 'id');
if (array_diff($manikinIds, $availableIds)) back('หุ่น CPR ที่เลือกไม่ว่างแล้ว กรุณาเลือกใหม่');

try {
    $pdo->beginTransaction();
    $pdo->query('SELECT id FROM cpr_manikins WHERE is_active = 1 ORDER BY id FOR UPDATE')->fetchAll();
    $available = cpr_available_manikins($pdo, $from, $to);
    $availableById = [];
    foreach ($available as $manikin) $availableById[(int)$manikin['id']] = $manikin['name'];
    if (array_diff($manikinIds, array_keys($availableById))) {
        $pdo->rollBack();
        back('หุ่น CPR ที่เลือกถูกจองแล้ว กรุณาเลือกใหม่');
    }

    $ref = new_ref($pdo);
    $pdo->prepare("INSERT INTO cpr_loans
        (ref_code, loan_date, borrower_name, borrower_phone, department, purpose, date_from, date_to,
         total_days, cpr_qty, due_date, status, lender_name, approved_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$ref, $today, mb_substr($name,0,150), $phone, mb_substr($dept,0,150), mb_substr($purp,0,255),
                   $from, $to, days_between($from, $to), count($manikinIds), $to,
                   $admin ? 1 : 0, $admin ? current_user() : null, $admin ? date('Y-m-d H:i:s') : null]);
    $loanId = $pdo->lastInsertId();

    $manikinInsert = $pdo->prepare('INSERT INTO cpr_loan_manikins (loan_id, manikin_id, manikin_name) VALUES (?, ?, ?)');
    foreach ($manikinIds as $manikinId) {
        $manikinInsert->execute([$loanId, $manikinId, $availableById[$manikinId]]);
    }

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
header($admin ? "Location: index.php?page=loan_view&id=$loanId" : 'Location: index.php?page=loan_done');
