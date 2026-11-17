<?php
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE id = ?");
$st->execute([$id]);
$l = $st->fetch();
if (!$l) die('ไม่พบรายการ');
$items = $pdo->prepare("SELECT * FROM cpr_loan_items WHERE loan_id = ?"); $items->execute([$id]); $items = $items->fetchAll();
$manikins = cpr_loan_manikin_names($pdo, $id);
$ret = $pdo->prepare("SELECT * FROM cpr_return_items WHERE loan_id = ?"); $ret->execute([$id]); $ret = $ret->fetchAll();
function f($v, $min = 120) { return '<span class="fill" style="min-width:' . $min . 'px">' . ($v === null || $v === '' ? '&nbsp;' : h($v)) . '</span>'; }
?>
<!DOCTYPE html>
<html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ใบยืม-คืน #<?= $id ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<style>
  body { font-family:'Sarabun','TH Sarabun New',sans-serif; font-size:16px; line-height:2; margin:0; background:#eee; }
  .page { width:210mm; min-height:297mm; margin:10px auto; background:#fff; padding:15mm 18mm; box-sizing:border-box; }
  h2 { text-align:center; margin:0 0 6px; font-size:22px; }
  h4 { margin:10px 0 0; font-size:17px; }
  .fill { display:inline-block; border-bottom:1px dotted #333; padding:0 6px; text-align:center; }
  .sign { display:flex; justify-content:space-between; margin-top:14px; text-align:center; }
  .sign > div { width:48%; }
  hr { border:0; border-top:1px solid #333; margin:14px 0; }
  .bar { text-align:center; padding:10px; } .bar button { font-size:16px; padding:6px 22px; cursor:pointer; }
  @media screen and (max-width:760px) {
    .page { width:auto; min-height:0; margin:.5rem; padding:1rem; }
    .fill { min-width:0 !important; max-width:100%; overflow-wrap:anywhere; }
    .page > div { overflow-wrap:anywhere; }
    .sign { flex-wrap:wrap; gap:1rem; }
    .sign > div { width:100%; }
  }
  @media print { body { background:#fff; } .page { margin:0; box-shadow:none; } .bar { display:none; } }
</style></head>
<body>
<div class="bar"><button onclick="window.print()">🖨 พิมพ์</button></div>
<div class="page">
  <h2>ใบยืม-คืน อุปกรณ์หุ่น CPR รพ.อ.ป.ร.</h2>
  <div style="text-align:right;font-size:13px;color:#666">รหัสอ้างอิง <?= h($l['ref_code']) ?></div>

  <h4>ส่วนที่ 1: รายการขอยืมอุปกรณ์</h4>
  <div><b>ข้อมูลผู้ขอยืมและหน่วยงาน</b></div>
  <div>วันที่มายืม: <?= f(thdate($l['loan_date']), 200) ?></div>
  <div>ชื่อ-นามสกุล ผู้ยืม: <?= f($l['borrower_name'], 220) ?> เบอร์โทรศัพท์ <?= f($l['borrower_phone'], 140) ?></div>
  <div>หน่วยงาน/สังกัด: <?= f($l['department'], 260) ?></div>
  <div>นำไปใช้ในภารกิจ/โครงการ: <?= f($l['purpose'], 330) ?></div>
  <div>ระยะเวลาที่ยืม: ตั้งแต่วันที่ <?= f(thdate($l['date_from']), 110) ?> ถึงวันที่ <?= f(thdate($l['date_to']), 110) ?> (รวม <?= f($l['total_days'], 40) ?> วัน)</div>
  <div><b>รายการอุปกรณ์ที่ขอยืม</b></div>
  <div>หุ่น CPR ที่ยืม: <?= $manikins ? implode(', ', array_map('h', $manikins)) : f($l['cpr_qty'], 70) . ' ตัว (รายการเดิมไม่ระบุชื่อ)' ?></div>
  <div>อุปกรณ์ที่ยืมเพิ่มเติม:</div>
  <?php for ($i = 0; $i < 3; $i++): $x = $items[$i] ?? null; ?>
  <div style="padding-left:10px"><?= $i + 1 ?>. <?= f($x['item_name'] ?? '', 300) ?> จำนวน <?= f($x['qty'] ?? '', 60) ?> ชิ้น/ชุด</div>
  <?php endfor; ?>
  <div>กำหนดส่งคืนอุปกรณ์: <?= f(thdate($l['due_date']), 150) ?></div>
  <div class="sign">
    <div>ลงชื่อ <?= f($l['borrower_name'], 170) ?> ผู้ยืม<br>( <?= f($l['borrower_name'], 170) ?> )<br>วันที่ <?= f(thdate($l['loan_date']), 110) ?></div>
    <div>ลงชื่อ <?= f($l['lender_name'], 170) ?> ผู้ให้ยืม (เจ้าหน้าที่)<br>สง.วิชาการ<br>วันที่ <?= f($l['approved_at'] ? thdate($l['approved_at']) : '', 110) ?></div>
  </div>

  <hr>
  <h4>ส่วนที่ 2: รายการส่งคืนอุปกรณ์</h4>
  <div>วันที่คืนอุปกรณ์: <?= f(thdate($l['return_date']), 200) ?></div>
  <div>ชื่อ-นามสกุล ผู้ส่งคืน: <?= f($l['returner_name'], 260) ?></div>
  <div><b>ตรวจสอบสภาพอุปกรณ์ ณ วันที่คืน</b> (รายการ / จำนวนที่คืน / สภาพสมบูรณ์ / ชำรุด-สูญหาย ระบุรายละเอียด)</div>
  <?php for ($i = 0; $i < 4; $i++): $x = $ret[$i] ?? null; ?>
  <div style="padding-left:10px"><?= $i + 1 ?>. <?= f($x['item_name'] ?? '', 200) ?> <?= f($x['qty'] ?? '', 40) ?> ชิ้น
    <?= $x ? ($x['item_cond'] == 1 ? '☑' : '☐') : '☐' ?> สมบูรณ์
    <?= $x ? ($x['item_cond'] != 1 ? '☑' : '☐') : '☐' ?> ชำรุด/สูญหาย <?= f($x && $x['item_cond'] != 1 ? COND[$x['item_cond']] . ': ' . $x['detail'] : '', 160) ?></div>
  <?php endfor; ?>
  <div class="sign">
    <div>ลงชื่อ <?= f($l['returner_name'], 170) ?> ผู้ส่งคืน<br>( <?= f($l['returner_name'], 170) ?> )<br>วันที่ <?= f(thdate($l['return_date']), 110) ?></div>
    <div>ลงชื่อ <?= f($l['receiver_name'], 170) ?> ผู้รับคืน (เจ้าหน้าที่)<br>สง.วิชาการ<br>วันที่ <?= f(thdate($l['return_date']), 110) ?></div>
  </div>
</div>
</body></html>
