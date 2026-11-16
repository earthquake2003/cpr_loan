<?php
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM cpr_loans WHERE id = ?");
$st->execute([$id]);
$l = $st->fetch();
if (!$l) { echo '<div class="alert alert-danger">ไม่พบรายการ</div>'; return; }
$items = $pdo->prepare("SELECT * FROM cpr_loan_items WHERE loan_id = ?"); $items->execute([$id]); $items = $items->fetchAll();
$ret = $pdo->prepare("SELECT * FROM cpr_return_items WHERE loan_id = ?"); $ret->execute([$id]); $ret = $ret->fetchAll();
$s = (int)$l['status'];
$avail = cpr_available($pdo, $l['date_from'], $l['date_to'], $id);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <a href="index.php?page=loans" class="small text-muted"><i class="bi bi-arrow-left"></i> กลับรายการ</a>
    <h3 class="mb-0">ใบยืม #<?= $l['id'] ?> <code class="fs-6 text-brand"><?= h($l['ref_code']) ?></code></h3>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?= status_badge($l) ?>
    <a class="btn btn-outline-secondary btn-sm" target="_blank" href="index.php?page=loan_print&id=<?= $id ?>"><i class="bi bi-printer"></i> พิมพ์ใบยืม-คืน</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-body p-4">
      <h6 class="mb-3">ส่วนที่ 1: รายการขอยืม</h6>
      <dl class="row mb-0">
        <dt class="col-sm-4 text-muted fw-normal">วันที่ส่งคำขอ</dt><dd class="col-sm-8"><?= thdate($l['loan_date']) ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">ผู้ยืม</dt><dd class="col-sm-8"><?= h($l['borrower_name']) ?> · <a href="tel:<?= h($l['borrower_phone']) ?>"><?= h($l['borrower_phone']) ?></a></dd>
        <dt class="col-sm-4 text-muted fw-normal">หน่วยงาน</dt><dd class="col-sm-8"><?= h($l['department']) ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">ภารกิจ/โครงการ</dt><dd class="col-sm-8"><?= h($l['purpose']) ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">ระยะเวลา</dt><dd class="col-sm-8"><?= thdate($l['date_from']) ?> – <?= thdate($l['date_to']) ?> (<?= (int)$l['total_days'] ?> วัน)</dd>
        <dt class="col-sm-4 text-muted fw-normal">หุ่น CPR</dt><dd class="col-sm-8"><?= (int)$l['cpr_qty'] ?> ตัว</dd>
        <dt class="col-sm-4 text-muted fw-normal">อุปกรณ์เพิ่มเติม</dt>
        <dd class="col-sm-8"><?php foreach ($items as $x) echo h($x['item_name']) . ' × ' . (int)$x['qty'] . '<br>'; if (!$items) echo '-'; ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">กำหนดส่งคืน</dt><dd class="col-sm-8"><?= thdate($l['due_date']) ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">ผู้ให้ยืม</dt><dd class="col-sm-8"><?= h($l['lender_name'] ?: '-') ?></dd>
        <?php if ($s === 3): ?><dt class="col-sm-4 text-muted fw-normal">เหตุผลที่ไม่อนุมัติ</dt><dd class="col-sm-8"><?= h($l['reject_reason']) ?></dd><?php endif; ?>
      </dl>
    </div></div>

    <?php if ($ret): ?>
    <div class="card"><div class="card-body p-4">
      <h6 class="mb-3">ส่วนที่ 2: รายการส่งคืน <?= $s === 4 ? '<span class="badge bg-info text-dark">ผู้ยืมแจ้งเอง — รอตรวจสอบ</span>' : '' ?></h6>
      <p class="small mb-2">วันที่คืน: <b><?= thdate($l['return_date']) ?></b> · ผู้ส่งคืน: <b><?= h($l['returner_name']) ?></b>
        <?php if ($s === 2): ?> · ผู้รับคืน: <b><?= h($l['receiver_name']) ?></b><?php endif; ?></p>
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>รายการ</th><th>จำนวน</th><th>สภาพ</th><th>รายละเอียด</th></tr></thead>
        <tbody><?php foreach ($ret as $x): ?>
          <tr class="<?= $x['item_cond'] == 1 ? '' : 'table-danger' ?>"><td><?= h($x['item_name']) ?></td><td><?= (int)$x['qty'] ?></td>
              <td><?= h(COND[$x['item_cond']] ?? '-') ?></td><td><?= h($x['detail']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table>
    </div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <?php if ($s === 0): ?>
    <div class="card mb-3 border-start border-4 border-warning"><div class="card-body p-4">
      <h6><i class="bi bi-patch-check text-success"></i> อนุมัติคำขอ</h6>
      <div class="avail-box <?= $avail < $l['cpr_qty'] ? 'none' : '' ?> mb-3 small">
        ช่วงวันที่นี้หุ่นว่าง <b><?= $avail ?></b> ตัว (ขอยืม <?= (int)$l['cpr_qty'] ?> ตัว)
      </div>
      <form method="post" action="index.php?page=loan_action">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="approve">
        <label class="form-label small">กำหนดส่งคืน</label>
        <input type="date" name="due_date" class="form-control mb-3" value="<?= h($l['due_date'] ?: $l['date_to']) ?>" min="<?= h($l['date_from']) ?>" required>
        <button class="btn btn-success w-100" <?= $avail < $l['cpr_qty'] ? 'disabled' : '' ?>><i class="bi bi-check-lg"></i> อนุมัติ (ผู้ให้ยืม: <?= h(current_user()) ?>)</button>
      </form>
    </div></div>
    <div class="card mb-3"><div class="card-body p-4">
      <h6><i class="bi bi-x-circle text-danger"></i> ไม่อนุมัติ</h6>
      <form method="post" action="index.php?page=loan_action">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="reject">
        <input name="reason" class="form-control mb-3" placeholder="เหตุผล เช่น หุ่นไม่ว่าง / ข้อมูลไม่ครบ" required maxlength="255">
        <button class="btn btn-outline-danger w-100">ไม่อนุมัติคำขอ</button>
      </form>
    </div></div>
    <?php elseif ($s === 4): ?>
    <div class="card mb-3 border-start border-4 border-success"><div class="card-body p-4">
      <h6><i class="bi bi-box-arrow-in-down text-success"></i> ผู้ยืมแจ้งคืนแล้ว — ตรวจสอบและยืนยัน</h6>
      <p class="small text-muted">ตรวจสภาพอุปกรณ์ที่คืน (สมบูรณ์/ชำรุด/สูญหาย) แล้วบันทึกเป็นผู้รับคืน</p>
      <a class="btn btn-success w-100" href="index.php?page=return_form&id=<?= $id ?>"><i class="bi bi-clipboard2-check"></i> ตรวจสอบและยืนยันรับคืน</a>
    </div></div>
    <?php elseif ($s === 1): ?>
    <div class="alert alert-primary"><i class="bi bi-box-arrow-up-right"></i> กำลังยืมอยู่ — รอผู้ยืมแจ้งคืนก่อนจึงจะรับคืนได้</div>
    <?php elseif ($s === 2): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> คืนเรียบร้อยเมื่อ <?= thdate($l['return_date']) ?></div>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <form method="post" action="index.php?page=loan_action" onsubmit="return confirm('ลบใบยืมนี้ถาวร?')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete">
      <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i> ลบใบยืมนี้ (Admin)</button>
    </form>
    <?php endif; ?>
  </div>
</div>
