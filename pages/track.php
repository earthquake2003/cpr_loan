<?php
$ref = strtoupper(trim($_GET['ref'] ?? ''));
$loan = null; $err = '';
if ($ref !== '') {
    $_SESSION['track_fail'] = array_filter($_SESSION['track_fail'] ?? [], fn($t) => $t > time() - 600);
    if (count($_SESSION['track_fail']) >= 10) {
        $err = 'ค้นหาผิดหลายครั้ง กรุณารอ 10 นาทีแล้วลองใหม่';
    } else {
        $st = $pdo->prepare("SELECT * FROM cpr_loans WHERE ref_code = ?");
        $st->execute([$ref]);
        $loan = $st->fetch();
        if (!$loan) { $_SESSION['track_fail'][] = time(); $err = 'ไม่พบรหัสนี้ กรุณาตรวจสอบอีกครั้ง'; }
    }
}
?>
<div class="container page-narrow py-4">
  <h3 class="mb-3">ตรวจสอบสถานะ / แจ้งคืน</h3>
  <form method="get" action="index.php" class="card card-body p-3 mb-3">
    <input type="hidden" name="page" value="track">
    <div class="input-group input-group-lg">
      <input name="ref" class="form-control text-uppercase" maxlength="6" placeholder="กรอกรหัสติดตาม 6 ตัว" value="<?= h($ref) ?>" required>
      <button class="btn btn-brand px-4"><i class="bi bi-search"></i> ตรวจสอบ</button>
    </div>
  </form>
  <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

<?php if ($loan):
    $items = $pdo->prepare("SELECT * FROM cpr_loan_items WHERE loan_id = ?"); $items->execute([$loan['id']]); $items = $items->fetchAll();
    $manikins = cpr_loan_manikin_names($pdo, $loan['id']);
    $s = (int)$loan['status'];
    $cur = [0 => 0, 1 => 2, 4 => 3, 2 => 4][$s] ?? 0;
    $steps = [['ส่งคำขอ','bi-send'], ['อนุมัติ','bi-patch-check'], ['กำลังยืม','bi-box-arrow-up-right'], ['แจ้งคืน','bi-box-arrow-in-down'], ['คืนแล้ว','bi-check2-all']];
?>
  <div class="card mb-3"><div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><span class="text-muted small">รหัสติดตาม</span> <b class="fs-5 text-brand"><?= h($loan['ref_code']) ?></b></div>
      <?= status_badge($loan) ?>
    </div>
    <?php if ($s === 3): ?>
      <div class="alert alert-secondary mt-3 mb-0"><b>ไม่อนุมัติ</b> เนื่องจาก: <?= h($loan['reject_reason'] ?: '-') ?></div>
    <?php else: ?>
      <div class="tl">
        <?php foreach ($steps as $i => [$t, $ic]): ?>
          <div class="tl-item <?= $i <= $cur ? 'done' : '' ?>"><div class="tl-dot"><i class="bi <?= $ic ?>"></i></div><span><?= $t ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div></div>

  <div class="card mb-3"><div class="card-body p-4">
    <h6 class="mb-3">รายละเอียดคำขอ</h6>
    <dl class="row mb-0 small">
      <dt class="col-sm-4 text-muted fw-normal">ผู้ยืม</dt><dd class="col-sm-8"><?= h($loan['borrower_name']) ?> (<?= h(mask_phone($loan['borrower_phone'])) ?>)</dd>
      <dt class="col-sm-4 text-muted fw-normal">หน่วยงาน</dt><dd class="col-sm-8"><?= h($loan['department']) ?></dd>
      <dt class="col-sm-4 text-muted fw-normal">ภารกิจ/โครงการ</dt><dd class="col-sm-8"><?= h($loan['purpose']) ?></dd>
      <dt class="col-sm-4 text-muted fw-normal">ช่วงเวลาที่ยืม</dt><dd class="col-sm-8"><?= thdate($loan['date_from']) ?> – <?= thdate($loan['date_to']) ?> (<?= (int)$loan['total_days'] ?> วัน)</dd>
      <dt class="col-sm-4 text-muted fw-normal">หุ่น CPR</dt>
      <dd class="col-sm-8"><?php if ($manikins): ?><?= implode('<br>', array_map('h', $manikins)) ?><?php else: ?><?= (int)$loan['cpr_qty'] ?> ตัว (รายการเดิมไม่ระบุชื่อ)<?php endif; ?></dd>
      <?php if ($items): ?><dt class="col-sm-4 text-muted fw-normal">อุปกรณ์เพิ่มเติม</dt>
      <dd class="col-sm-8"><?php foreach ($items as $x) echo h($x['item_name']) . ' × ' . (int)$x['qty'] . '<br>'; ?></dd><?php endif; ?>
      <dt class="col-sm-4 text-muted fw-normal">กำหนดส่งคืน</dt><dd class="col-sm-8"><?= thdate($loan['due_date']) ?></dd>
    </dl>
  </div></div>

  <?php if ($s === 1): ?>
    <a class="btn btn-brand btn-lg w-100" href="index.php?page=return_form&ref=<?= h($loan['ref_code']) ?>"><i class="bi bi-box-arrow-in-down"></i> แจ้งส่งคืนอุปกรณ์</a>
  <?php elseif ($s === 4): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> แจ้งคืนแล้วเมื่อ <?= thdate($loan['return_date']) ?> — กรุณานำอุปกรณ์มาส่งเจ้าหน้าที่เพื่อตรวจสอบและยืนยันการรับคืน</div>
  <?php elseif ($s === 0): ?>
    <div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> คำขออยู่ระหว่างรอเจ้าหน้าที่ตรวจสอบ</div>
  <?php elseif ($s === 2): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> คืนอุปกรณ์เรียบร้อยเมื่อ <?= thdate($loan['return_date']) ?> ขอบคุณที่ใช้บริการ</div>
  <?php endif; ?>
<?php endif; ?>
</div>
