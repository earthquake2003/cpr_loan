<?php
$today = date('Y-m-d');
$cnt = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM cpr_loans GROUP BY status") as $r) $cnt[(int)$r['status']] = (int)$r['c'];
$od = $pdo->prepare("SELECT COUNT(*) FROM cpr_loans WHERE status=1 AND due_date < ?");
$od->execute([$today]);
$overdue = (int)$od->fetchColumn();
$total = cpr_total($pdo);
$avail = cpr_available($pdo, $today, $today);
$pending = $pdo->query("SELECT * FROM cpr_loans WHERE status=0 ORDER BY id DESC LIMIT 6")->fetchAll();
$dueSt = $pdo->prepare("SELECT * FROM cpr_loans WHERE status IN (1,4) AND due_date <= ? ORDER BY due_date LIMIT 8");
$dueSt->execute([date('Y-m-d', strtotime('+3 days'))]);
$due = $dueSt->fetchAll();
$cards = [
  ['รอตรวจสอบ',        $cnt[0],   'bi-hourglass-split',   '#fef3c7', '#b45309', 'loans&s=0'],
  ['กำลังยืม',          $cnt[1],   'bi-box-arrow-up-right','#dbeafe', '#1d4ed8', 'loans&s=1'],
  ['รอยืนยันรับคืน',    $cnt[4],   'bi-box-arrow-in-down', '#cffafe', '#0e7490', 'loans&s=4'],
  ['เกินกำหนดคืน',      $overdue,  'bi-exclamation-triangle','#fee2e2','#b91c1c', 'loans&s=overdue'],
];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div><h3 class="mb-0">แดชบอร์ด</h3><div class="text-muted small">ภาพรวมวันที่ <?= thdate($today) ?></div></div>
  <a class="btn btn-brand" href="../index.php?page=loan_form"><i class="bi bi-plus-lg"></i> บันทึกการยืมแทนผู้ยืม</a>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($cards as [$t, $n, $ic, $bg, $fg, $link]): ?>
  <div class="col-6 col-lg-3">
    <a href="index.php?page=<?= $link ?>" class="text-decoration-none text-reset">
      <div class="card stat-card h-100"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small mb-1"><?= $t ?></div><div class="stat-num" style="color:<?= $fg ?>"><?= $n ?></div></div>
        <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ic ?>"></i></div>
      </div></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-body">
      <h6><i class="bi bi-box-seam text-brand"></i> หุ่น CPR วันนี้</h6>
      <div class="d-flex align-items-end gap-2 mt-3"><span class="display-4 fw-semibold lh-1"><?= $avail ?></span><span class="text-muted mb-1">/ <?= $total ?> ตัว ว่าง</span></div>
      <div class="progress mt-3" style="height:10px"><div class="progress-bar bg-danger" style="width:<?= $total ? round(($total - $avail) / $total * 100) : 0 ?>%"></div></div>
      <div class="small text-muted mt-2">ถูกยืมอยู่ <?= $total - $avail ?> ตัว</div>
      <?php if (is_admin()): ?><a href="index.php?page=settings" class="small">แก้จำนวนหุ่นทั้งหมด</a><?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <h6><i class="bi bi-hourglass-split text-warning"></i> คำขอรอตรวจสอบ</h6>
      <?php if (!$pending): ?><div class="text-muted small py-3">ไม่มีคำขอค้างอยู่ 🎉</div><?php else: ?>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <tbody><?php foreach ($pending as $r): ?>
          <tr><td><b><?= h($r['borrower_name']) ?></b><div class="small text-muted"><?= h($r['department']) ?></div></td>
              <td class="small"><?= thdate($r['date_from']) ?> – <?= thdate($r['date_to']) ?><br><?= (int)$r['cpr_qty'] ?> ตัว</td>
              <td class="text-end"><a class="btn btn-sm btn-brand" href="index.php?page=loan_view&id=<?= $r['id'] ?>">ตรวจสอบ</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </div></div>
    <div class="card"><div class="card-body">
      <h6><i class="bi bi-calendar-event text-primary"></i> ใกล้ถึง/เลยกำหนดคืน (3 วัน)</h6>
      <?php if (!$due): ?><div class="text-muted small py-3">ไม่มีรายการ</div><?php else: ?>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <tbody><?php foreach ($due as $r): ?>
          <tr><td><b><?= h($r['borrower_name']) ?></b><div class="small text-muted"><?= h($r['department']) ?></div></td>
              <td class="small">กำหนดคืน <?= thdate($r['due_date']) ?></td>
              <td><?= status_badge($r) ?></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="index.php?page=loan_view&id=<?= $r['id'] ?>">ดู</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </div></div>
  </div>
</div>
