<?php
$ref = strtoupper(trim($_GET['ref'] ?? ''));
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE ref_code = ?");
$st->execute([$ref]);
$loan = $st->fetch();
if (!$loan || (int)$loan['status'] !== 1) {
    echo '<div class="container page-narrow py-5"><div class="alert alert-warning">ไม่พบรายการ หรือรายการนี้ไม่อยู่ในสถานะที่แจ้งคืนได้ <a href="index.php?page=track">กลับไปตรวจสอบสถานะ</a></div></div>';
    return;
}
$it = $pdo->prepare("SELECT * FROM cpr_loan_items WHERE loan_id = ?"); $it->execute([$loan['id']]);
$rows = default_return_rows($loan, $it->fetchAll(), cpr_loan_manikin_names($pdo, $loan['id']));
?>
<div class="container page-narrow py-4">
  <h3 class="mb-1">แจ้งส่งคืนอุปกรณ์</h3>
  <p class="text-muted">รหัส <b class="text-brand"><?= h($loan['ref_code']) ?></b> · <?= h($loan['borrower_name']) ?> · กำหนดคืน <?= thdate($loan['due_date']) ?></p>
  <form method="post" action="index.php?page=return_save" class="card card-body p-4">
    <?= csrf_field() ?>
    <input type="hidden" name="ref" value="<?= h($loan['ref_code']) ?>">
    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="form-floating">
        <input type="date" name="return_date" id="rd" class="form-control" value="<?= date('Y-m-d') ?>" required>
        <label for="rd">วันที่คืนอุปกรณ์ *</label></div></div>
      <div class="col-md-8"><div class="form-floating">
        <input name="returner_name" id="rn" class="form-control" placeholder="ผู้ส่งคืน" value="<?= h($loan['borrower_name']) ?>" required maxlength="150">
        <label for="rn">ชื่อ-นามสกุล ผู้ส่งคืน *</label></div></div>
    </div>
    <div class="form-section-title"><i class="bi bi-clipboard2-check text-brand"></i> ตรวจสอบสภาพอุปกรณ์ ณ วันที่คืน</div>
    <div class="row g-2 text-muted small fw-semibold d-none d-md-flex mb-1">
      <div class="col-md-4">รายการ</div><div class="col-md-2">จำนวนที่คืน</div><div class="col-md-2">สภาพ</div><div class="col-md-4">รายละเอียด</div>
    </div>
    <?php return_rows($rows); ?>
    <div class="text-muted small mb-3">เจ้าหน้าที่จะตรวจสอบสภาพอุปกรณ์อีกครั้งตอนรับคืน</div>
    <div class="d-flex gap-2 justify-content-end">
      <a class="btn btn-light" href="index.php?page=track&ref=<?= h($loan['ref_code']) ?>">ยกเลิก</a>
      <button class="btn btn-brand px-4"><i class="bi bi-check2-circle"></i> ยืนยันแจ้งคืน</button>
    </div>
  </form>
</div>
