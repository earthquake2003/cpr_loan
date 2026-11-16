<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=cpr_loan_db;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

const COND = [1 => 'สมบูรณ์', 2 => 'ชำรุด', 3 => 'สูญหาย'];
// สถานะ => [ข้อความ, สี bootstrap, ไอคอน]
const STATUS = [
    0 => ['รอตรวจสอบ',            'warning text-dark', 'bi-hourglass-split'],
    1 => ['อนุมัติ / กำลังยืม',    'primary',           'bi-box-arrow-up-right'],
    4 => ['แจ้งคืนแล้ว รอยืนยัน',  'info text-dark',    'bi-box-arrow-in-down'],
    2 => ['คืนแล้ว',               'success',           'bi-check-circle'],
    3 => ['ไม่อนุมัติ',            'secondary',         'bi-x-circle'],
];

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function thdate($d) {
    if (!$d) return '-';
    $t = strtotime($d);
    return date('d/m/', $t) . (date('Y', $t) + 543);
}
function valid_date($d) {
    $x = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $x && $x->format('Y-m-d') === $d;
}
function days_between($a, $b) { return (int)((strtotime($b) - strtotime($a)) / 86400) + 1; }
function mask_phone($p) {
    $d = preg_replace('/\D/', '', (string)$p);
    return strlen($d) >= 7 ? substr($d, 0, 3) . '-xxx-' . substr($d, -3) : '-';
}

function status_badge($row) {
    $s = (int)$row['status'];
    if ($s === 1 && $row['due_date'] && $row['due_date'] < date('Y-m-d')) {
        return '<span class="badge rounded-pill bg-danger"><i class="bi bi-exclamation-triangle"></i> เกินกำหนดคืน</span>';
    }
    [$t, $c, $i] = STATUS[$s] ?? ['-', 'secondary', 'bi-question'];
    return '<span class="badge rounded-pill bg-' . $c . '"><i class="bi ' . $i . '"></i> ' . $t . '</span>';
}

/* ---------- จำนวนหุ่น ---------- */
function cpr_total($pdo) {
    $v = $pdo->query("SELECT svalue FROM cpr_settings WHERE skey='total_cpr'")->fetchColumn();
    return $v === false ? 5 : (int)$v;
}
// จำนวนหุ่นที่ถูกจองในช่วงวันที่ (นับเฉพาะใบที่อนุมัติแล้ว/ยังไม่คืน; ถ้าเลยกำหนดแต่ยังไม่คืนถือว่ายังอยู่ข้างนอก)
function cpr_used($pdo, $from, $to, $excludeId = 0) {
    $st = $pdo->prepare("SELECT COALESCE(SUM(cpr_qty),0) FROM cpr_loans
        WHERE status IN (1,4) AND id <> ? AND date_from <= ? AND GREATEST(date_to, ?) >= ?");
    $st->execute([$excludeId, $to, date('Y-m-d'), $from]);
    return (int)$st->fetchColumn();
}
function cpr_available($pdo, $from, $to, $excludeId = 0) {
    return max(0, cpr_total($pdo) - cpr_used($pdo, $from, $to, $excludeId));
}

function new_ref($pdo) {
    $a = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // ไม่มี I, O, 0, 1 กันอ่านสับสน
    do {
        $c = '';
        for ($i = 0; $i < 6; $i++) $c .= $a[random_int(0, 31)];
        $st = $pdo->prepare("SELECT 1 FROM cpr_loans WHERE ref_code = ?");
        $st->execute([$c]);
    } while ($st->fetch());
    return $c;
}

/* ---------- รายการตรวจสอบตอนคืน (ใช้ทั้งฝั่งผู้ยืมและเจ้าหน้าที่) ---------- */
function default_return_rows($loan, $items) {
    $rows = [['name' => 'หุ่น CPR', 'qty' => $loan['cpr_qty'], 'cond' => 1, 'detail' => '']];
    foreach ($items as $x) $rows[] = ['name' => $x['item_name'], 'qty' => $x['qty'], 'cond' => 1, 'detail' => ''];
    return $rows;
}
function return_rows($rows) {
    foreach ($rows as $i => $r) { ?>
  <div class="row g-2 mb-2 align-items-center">
    <div class="col-md-4"><input name="item_name[]" class="form-control" value="<?= h($r['name']) ?>" <?= $i === 0 ? 'readonly' : '' ?> placeholder="ชื่ออุปกรณ์"></div>
    <div class="col-5 col-md-2"><input type="number" min="0" name="item_qty[]" class="form-control" value="<?= h($r['qty']) ?>" placeholder="จำนวน"></div>
    <div class="col-7 col-md-2"><select name="item_cond[]" class="form-select" data-cond>
      <?php foreach (COND as $k => $v): ?><option value="<?= $k ?>" <?= (int)$r['cond'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-4"><input name="item_detail[]" class="form-control" value="<?= h($r['detail']) ?>" placeholder="รายละเอียด (ถ้าชำรุด/สูญหาย)"></div>
  </div>
<?php }
}
function collect_return_items($p) {
    $out = [];
    foreach (($p['item_name'] ?? []) as $i => $n) {
        $n = trim($n);
        if ($n === '') continue;
        $c = (int)($p['item_cond'][$i] ?? 1);
        if (!isset(COND[$c])) $c = 1;
        $out[] = ['name' => mb_substr($n, 0, 200), 'qty' => max(0, (int)($p['item_qty'][$i] ?? 0)),
                  'cond' => $c, 'detail' => mb_substr(trim($p['item_detail'][$i] ?? ''), 0, 255)];
    }
    return $out;
}
function return_items_error($items) {
    if (!$items) return 'กรุณากรอกรายการอุปกรณ์ที่คืนอย่างน้อย 1 รายการ';
    foreach ($items as $x) {
        if ($x['cond'] !== 1 && $x['detail'] === '') return 'กรุณาระบุรายละเอียดของอุปกรณ์ที่ชำรุด/สูญหาย';
    }
    return '';
}
function replace_return_items($pdo, $loanId, $items) {
    $pdo->prepare("DELETE FROM cpr_return_items WHERE loan_id = ?")->execute([$loanId]);
    $ins = $pdo->prepare("INSERT INTO cpr_return_items (loan_id, item_name, qty, item_cond, detail) VALUES (?,?,?,?,?)");
    foreach ($items as $x) $ins->execute([$loanId, $x['name'], $x['qty'], $x['cond'], $x['detail']]);
}
