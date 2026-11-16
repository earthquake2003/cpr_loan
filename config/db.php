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

/* ---------- ทะเบียนหุ่น CPR ---------- */
function cpr_total($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM cpr_manikins WHERE is_active = 1")->fetchColumn();
}

function cpr_legacy_reserved($pdo, $from, $to, $excludeId = 0) {
    $st = $pdo->prepare("SELECT l.cpr_qty, COUNT(lm.manikin_id) AS assigned_qty
        FROM cpr_loans l
        LEFT JOIN cpr_loan_manikins lm ON lm.loan_id = l.id
        WHERE l.id <> ? AND l.status IN (0,1,4)
          AND l.date_from <= ? AND GREATEST(l.date_to, ?) >= ?
        GROUP BY l.id, l.cpr_qty");
    $st->execute([$excludeId, $to, date('Y-m-d'), $from]);
    $reserved = 0;
    foreach ($st->fetchAll() as $loan) {
        $reserved += max(0, (int)$loan['cpr_qty'] - (int)$loan['assigned_qty']);
    }
    return $reserved;
}

function cpr_has_legacy_active_loans($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM (
        SELECT l.id
        FROM cpr_loans l
        LEFT JOIN cpr_loan_manikins lm ON lm.loan_id = l.id
        WHERE l.status IN (0,1,4)
        GROUP BY l.id, l.cpr_qty
        HAVING l.cpr_qty > COUNT(lm.manikin_id)
    ) legacy_loans")->fetchColumn() > 0;
}

function cpr_available_manikins($pdo, $from, $to, $excludeId = 0) {
    $st = $pdo->prepare("SELECT m.id, m.name FROM cpr_manikins m
        WHERE m.is_active = 1
          AND NOT EXISTS (
              SELECT 1 FROM cpr_loan_manikins lm
              JOIN cpr_loans l ON l.id = lm.loan_id
              WHERE lm.manikin_id = m.id AND l.id <> ? AND l.status IN (0,1,4)
                AND l.date_from <= ? AND GREATEST(l.date_to, ?) >= ?
          )
        ORDER BY m.name, m.id");
    $st->execute([$excludeId, $to, date('Y-m-d'), $from]);
    $available = $st->fetchAll();

    $reserved = cpr_legacy_reserved($pdo, $from, $to, $excludeId);
    return array_slice($available, min($reserved, count($available)));
}

function cpr_available($pdo, $from, $to, $excludeId = 0) {
    return count(cpr_available_manikins($pdo, $from, $to, $excludeId));
}

function cpr_used($pdo, $from, $to, $excludeId = 0) {
    return max(0, cpr_total($pdo) - cpr_available($pdo, $from, $to, $excludeId));
}

function cpr_loan_manikin_names($pdo, $loanId) {
    $st = $pdo->prepare("SELECT manikin_name FROM cpr_loan_manikins WHERE loan_id = ? ORDER BY manikin_name");
    $st->execute([$loanId]);
    return array_column($st->fetchAll(), 'manikin_name');
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
function default_return_rows($loan, $items, $manikins = []) {
    $rows = [];
    foreach ($manikins as $name) {
        $rows[] = ['name' => 'หุ่น CPR - ' . $name, 'qty' => 1, 'cond' => 1, 'detail' => ''];
    }
    if (!$rows) $rows[] = ['name' => 'หุ่น CPR (ไม่ระบุชื่อ)', 'qty' => $loan['cpr_qty'], 'cond' => 1, 'detail' => ''];
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
