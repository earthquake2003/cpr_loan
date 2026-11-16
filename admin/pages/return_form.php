<?php
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE id = ?");
$st->execute([$id]);
$l = $st->fetch();
if (!$l || (int)$l['status'] !== 4) {
    echo '<div class="alert alert-warning">ไม่พบรายการ หรือรายการนี้ไม่อยู่ในสถานะรับคืน <a href="index.php?page=loans">กลับ</a></div>'; return;
}
$r = $pdo->prepare("SELECT * FROM cpr_return_items WHERE loan_id = ?"); $r->execute([$id]);
$rows = array_map(fn($x) => ['name' => $x['item_name'], 'qty' => $x['qty'], 'cond' => (int)$x['item_cond'], 'detail' => $x['detail']], $r->fetchAll());
if (!$rows) {
    $items = $pdo->prepare("SELECT * FROM cpr_loan_items WHERE loan_id = ?"); $items->execute([$id]);
    $rows = default_return_rows($l, $items->fetchAll(), cpr_loan_manikin_names($pdo, $id));
}
?>
<a href="index.php?page=loan_view&id=<?= $id ?>" class="small text-muted"><i class="bi bi-arrow-left"></i> กลับ</a>
<h3 class="mb-1">ส่วนที่ 2: รับคืนอุปกรณ์</h3>
<p class="text-muted"><?= h($l['borrower_name']) ?> · <?= h($l['department']) ?> · กำหนดคืน <?= thdate($l['due_date']) ?></p>
<form method="post" action="index.php?page=return_save" class="card card-body p-4" style="max-width:900px">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="form-floating">
      <input type="date" name="return_date" id="rd" class="form-control" value="<?= h($l['return_date'] ?: date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required><label for="rd">วันที่คืนอุปกรณ์</label></div></div>
    <div class="col-md-8"><div class="form-floating">
      <input name="returner_name" id="rn" class="form-control" placeholder="ผู้ส่งคืน" value="<?= h($l['returner_name'] ?: $l['borrower_name']) ?>" required><label for="rn">ชื่อ-นามสกุล ผู้ส่งคืน</label></div></div>
  </div>
  <div class="form-section-title"><i class="bi bi-clipboard2-check text-brand"></i> ตรวจสอบสภาพอุปกรณ์ ณ วันที่คืน</div>
  <div class="row g-2 text-muted small fw-semibold d-none d-md-flex mb-1">
    <div class="col-md-4">รายการ</div><div class="col-md-2">จำนวนที่คืน</div><div class="col-md-2">สภาพ</div><div class="col-md-4">รายละเอียด</div>
  </div>
  <?php return_rows($rows); ?>
  <div class="mt-3"><label class="form-label small text-muted">ผู้รับคืน (เจ้าหน้าที่)</label>
    <input class="form-control" value="<?= h(current_user()) ?>" readonly style="max-width:360px"></div>
  <div class="d-flex gap-2 justify-content-end mt-3">
    <a class="btn btn-light" href="index.php?page=loan_view&id=<?= $id ?>">ยกเลิก</a>
    <button class="btn btn-success px-4"><i class="bi bi-check2-circle"></i> ยืนยันรับคืน</button>
  </div>
</form>
