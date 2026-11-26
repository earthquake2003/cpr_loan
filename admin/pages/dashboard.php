<?php
$today = date('Y-m-d');
$todayDate = new DateTimeImmutable($today);
$monthStart = $todayDate->modify('first day of this month');
$loanStatusSt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN status = 0 THEN cpr_qty ELSE 0 END), 0) AS pending_qty,
    COALESCE(SUM(CASE WHEN status = 1 AND (due_date >= ? OR due_date IS NULL) THEN cpr_qty ELSE 0 END), 0) AS borrowed_qty,
    COALESCE(SUM(CASE WHEN status = 1 AND due_date < ? THEN cpr_qty ELSE 0 END), 0) AS overdue_qty,
    COALESCE(SUM(CASE WHEN status = 4 THEN cpr_qty ELSE 0 END), 0) AS returning_qty
    FROM cpr_loans WHERE status IN (0, 1, 4)");
$loanStatusSt->execute([$today, $today]);
$loanStatus = $loanStatusSt->fetch();
$pendingQty = (int)$loanStatus['pending_qty'];
$borrowedQty = (int)$loanStatus['borrowed_qty'];
$overdueQty = (int)$loanStatus['overdue_qty'];
$returningQty = (int)$loanStatus['returning_qty'];
$activeLoanQty = $pendingQty + $borrowedQty + $overdueQty + $returningQty;
$loanStatusItems = [
    ['label' => 'รอตรวจสอบ', 'qty' => $pendingQty, 'color' => '#f59e0b', 'dot' => 'bg-warning'],
    ['label' => 'กำลังยืม', 'qty' => $borrowedQty, 'color' => '#2563eb', 'dot' => 'bg-primary'],
    ['label' => 'เกินกำหนดคืน', 'qty' => $overdueQty, 'color' => '#dc2626', 'dot' => 'bg-danger'],
    ['label' => 'รอยืนยันคืน', 'qty' => $returningQty, 'color' => '#0891b2', 'dot' => 'bg-info'],
];
$loanStatusSegments = array_values(array_filter($loanStatusItems, function ($item) {
    return $item['qty'] > 0;
}));
$loanStatusGradient = '#e2e8f0';
$loanStatusLabel = 'ไม่มีใบยืมที่ยังดำเนินการ';
if ($activeLoanQty > 0) {
    $gradientStops = [];
    $cumulativePct = 0;
    foreach ($loanStatusSegments as $index => $item) {
        $startPct = number_format($cumulativePct, 4, '.', '');
        $cumulativePct += $item['qty'] / $activeLoanQty * 100;
        $endPct = $index === count($loanStatusSegments) - 1
            ? '100'
            : number_format($cumulativePct, 4, '.', '');
        $gradientStops[] = "{$item['color']} {$startPct}% {$endPct}%";
    }
    $loanStatusGradient = 'conic-gradient(' . implode(', ', $gradientStops) . ')';
    $loanStatusLabel = implode(' ', array_map(function ($item) {
        return $item['label'] . ' ' . $item['qty'] . ' ตัว';
    }, $loanStatusSegments)) . ' รวม ' . $activeLoanQty . ' ตัว';
}
$avail = cpr_available($pdo, $today, $today);
$registered = (int)$pdo->query("SELECT COUNT(*) FROM cpr_manikins")->fetchColumn();
$dueSt = $pdo->prepare("SELECT * FROM cpr_loans WHERE status IN (1,4) AND due_date <= ? ORDER BY due_date LIMIT 8");
$dueSt->execute([date('Y-m-d', strtotime('+3 days'))]);
$due = $dueSt->fetchAll();

$monthCounts = [];
$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $month = $monthStart->modify("-$i months");
    $key = $month->format('Y-m');
    $monthCounts[$key] = 0;
    $monthLabels[$key] = ['name' => ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][(int)$month->format('n') - 1],
                          'year' => (int)$month->format('y') + 43];
}
$monthEnd = $monthStart->modify('+1 month');
$monthSt = $pdo->prepare("SELECT DATE_FORMAT(approved_at, '%Y-%m') AS loan_month, COUNT(*) AS loan_count
    FROM cpr_loans WHERE approved_at >= ? AND approved_at < ? AND status IN (1,2,4) GROUP BY loan_month");
$monthSt->execute([$monthStart->modify('-11 months')->format('Y-m-d'), $monthEnd->format('Y-m-d')]);
foreach ($monthSt->fetchAll() as $row) $monthCounts[$row['loan_month']] = (int)$row['loan_count'];
$monthMax = max(1, max($monthCounts));

$timelineEnd = $monthStart->modify('+6 months')->modify('-1 day');
$timelineDays = (int)$monthStart->diff($timelineEnd)->days + 1;
$timelineMonths = [];
for ($i = 0; $i < 6; $i++) $timelineMonths[] = $monthStart->modify("+$i months");
$manikins = $pdo->query("SELECT id, name FROM cpr_manikins WHERE is_active = 1 ORDER BY name, id")->fetchAll();
$timelineSt = $pdo->prepare("SELECT m.id, l.id AS loan_id, l.ref_code, l.borrower_name, l.status, l.date_from,
        GREATEST(COALESCE(l.due_date, l.date_to), ?) AS display_end
    FROM cpr_manikins m
    LEFT JOIN cpr_loan_manikins lm ON lm.manikin_id = m.id
    LEFT JOIN cpr_loans l ON l.id = lm.loan_id AND l.status IN (0,1,4)
        AND l.date_from <= ? AND GREATEST(COALESCE(l.due_date, l.date_to), ?) >= ?
    WHERE m.is_active = 1
    ORDER BY m.name, l.date_from, l.id");
$timelineSt->execute([$today, $timelineEnd->format('Y-m-d'), $today, $monthStart->format('Y-m-d')]);
$timelineLoans = [];
foreach ($timelineSt->fetchAll() as $row) {
    if ($row['loan_id'] !== null) $timelineLoans[$row['id']][] = $row;
}
$legacySt = $pdo->prepare("SELECT COALESCE(SUM(legacy_qty), 0) FROM (
    SELECT GREATEST(l.cpr_qty - COUNT(lm.manikin_id), 0) AS legacy_qty
    FROM cpr_loans l LEFT JOIN cpr_loan_manikins lm ON lm.loan_id = l.id
    WHERE l.status IN (0,1,4) AND l.date_from <= ? AND GREATEST(COALESCE(l.due_date, l.date_to), ?) >= ?
    GROUP BY l.id, l.cpr_qty
) legacy_loans");
$legacySt->execute([$timelineEnd->format('Y-m-d'), $today, $monthStart->format('Y-m-d')]);
$legacyQty = (int)$legacySt->fetchColumn();
$cards = [
  ['คงคลังทั้งหมด',     $registered, 'bi-box-seam',           '#dcfce7', '#15803d', 'manikins'],
  ['กำลังถูกยืม',       $borrowedQty, 'bi-box-arrow-up-right','#dbeafe', '#1d4ed8', 'loans&s=1'],
  ['คงเหลือวันนี้',      $avail,       'bi-check-circle',       '#fef3c7', '#b45309', 'manikins'],
  ['เกินกำหนดคืน',      $overdueQty,  'bi-exclamation-triangle','#fee2e2','#b91c1c', 'loans&s=overdue'],
];
?>
<!-- <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div><h3 class="mb-0">แดชบอร์ด</h3><div class="text-muted small">ภาพรวมวันที่ <?= thdate($today) ?></div></div>
  
</div> -->

<div class="row g-3 mb-4 mt-3">
  <?php foreach ($cards as [$t, $n, $ic, $bg, $fg, $link]): ?>
  <div class="col-6 col-md-3">
    <a href="index.php?page=<?= $link ?>" class="text-decoration-none text-reset">
      <div class="card stat-card h-100"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small mb-1"><?= $t ?></div><div class="stat-num" style="color:<?= $fg ?>"><?= $n ?><small class="fs-6 ms-1 fw-normal">ตัว</small></div></div>
        <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ic ?>"></i></div>
      </div></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <h6><i class="bi bi-box-seam text-brand"></i> สถานะหุ่นในใบยืมที่ยังดำเนินการ</h6>
      <div class="stock-donut-wrap mt-3">
        <div class="stock-donut" role="img" aria-label="<?= h($loanStatusLabel) ?>"
             style="background:<?= $loanStatusGradient ?>">
          <div class="stock-donut-center"><b><?= $activeLoanQty ?></b><span>ตัวในใบยืม</span></div>
        </div>
        <?php if ($loanStatusSegments): ?>
        <div class="stock-legend small">
          <?php foreach ($loanStatusSegments as $item): ?>
          <div><i class="stock-dot <?= $item['dot'] ?>"></i> <?= h($item['label']) ?> <b><?= $item['qty'] ?></b></div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="small text-muted">ไม่มีใบยืมที่ยังดำเนินการ</div>
        <?php endif; ?>
      </div>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6><i class="bi bi-calendar-event text-primary"></i> ใกล้ถึง/เลยกำหนดคืน (3 วัน)</h6>
      <?php if (!$due): ?><div class="text-muted small py-3">ไม่มีรายการ</div><?php else: ?>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <tbody><?php foreach ($due as $r): $lateDays = $r['due_date'] < $today ? (int)$todayDate->diff(new DateTimeImmutable($r['due_date']))->days : 0; ?>
          <tr><td><div class="fw-bold text-brand"><?= h($r['department']) ?></div><div><?= h($r['borrower_name']) ?></div></td>
              <td class="small">กำหนดคืน <?= thdate($r['due_date']) ?></td>
              <td><?php if ($lateDays && (int)$r['status'] === 1): ?>
                <span class="badge rounded-pill bg-danger">เกินมา <?= $lateDays ?> วัน</span>
                <?php else: ?><?= status_badge($r) ?><?php if ($lateDays): ?><div class="small text-danger mt-1">เกินมา <?= $lateDays ?> วัน</div><?php endif; ?><?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#borrowerPhoneModal"
                        data-phone="<?= h($r['borrower_phone']) ?>" aria-label="แสดงเบอร์โทรของ<?= h($r['borrower_name']) ?>">
                  <i class="bi bi-telephone"></i> โทร
                </button>
                <!-- <?php if ((int)$r['status'] === 4): ?>
                <a class="btn btn-sm btn-success" href="index.php?page=return_form&id=<?= (int)$r['id'] ?>"><i class="bi bi-box-arrow-in-down"></i> รับคืน</a>
                <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=loan_view&id=<?= (int)$r['id'] ?>">ดู</a>
                <?php endif; ?> -->
              </td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<div class="modal fade" id="borrowerPhoneModal" tabindex="-1" aria-labelledby="borrowerPhoneModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title fs-6" id="borrowerPhoneModalTitle">เบอร์โทรผู้ยืม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body text-center">
        <div class="text-muted small mb-1">หมายเลขโทรศัพท์</div>
        <div id="borrowerPhoneNumber" class="fs-4 fw-semibold"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('borrowerPhoneModal');
  modal.addEventListener('show.bs.modal', function (event) {
    var phone = event.relatedTarget.getAttribute('data-phone');
    document.getElementById('borrowerPhoneNumber').textContent = phone;
  });
})();
</script>

<div class="row g-3 mt-0">
  <div class="col-12">
    <div class="card"><div class="card-body">
      <h6><i class="bi bi-bar-chart-line text-primary"></i> การยืมที่อนุมัติรายเดือน (12 เดือนล่าสุด)</h6>
      <div class="monthly-chart mt-3">
        <?php foreach ($monthCounts as $key => $count): $barHeight = $count ? max(4, round($count / $monthMax * 100)) : 0; ?>
        <div class="monthly-chart-item" title="<?= h($monthLabels[$key]['name'] . ' ' . $monthLabels[$key]['year'] . ': ' . $count . ' รายการ') ?>">
          <span class="monthly-chart-count"><?= $count ?></span>
          <div class="monthly-chart-bar-wrap"><div class="monthly-chart-bar" style="height:<?= $barHeight ?>%"></div></div>
          <span class="monthly-chart-label"><?= h($monthLabels[$key]['name']) ?><small><?= $monthLabels[$key]['year'] ?></small></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div></div>
  </div>
  <div class="col-12">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-calendar-range text-brand"></i> ไทม์ไลน์หุ่น CPR รายตัว (6 เดือน)</h6>
        <!-- <div class="small text-muted">แสดงรายการกำลังยืมของหุ่น</div> -->
      </div>
      <?php if (!$manikins): ?>
      <div class="text-muted small py-3">ไม่มีหุ่นที่เปิดใช้งานในทะเบียน</div>
      <?php else: ?>
      <div class="manikin-timeline mt-3">
        <div class="row g-0 timeline-heading">
          <div class="col-3 col-md-2"></div>
          <div class="col-9 col-md-10 d-flex">
            <?php foreach ($timelineMonths as $month): ?><div class="timeline-month"><?= h($month->format('m/Y')) ?></div><?php endforeach; ?>
          </div>
        </div>
        <?php foreach ($manikins as $manikin): ?>
        <div class="row g-0 align-items-center timeline-row">
          <div class="col-3 col-md-2 text-truncate pe-2" title="<?= h($manikin['name']) ?>"><?= h($manikin['name']) ?></div>
          <div class="col-9 col-md-10 timeline-track">
            <?php foreach ($timelineLoans[$manikin['id']] ?? [] as $loan):
                $barStart = max($monthStart->format('Y-m-d'), $loan['date_from']);
                $barEnd = min($timelineEnd->format('Y-m-d'), $loan['display_end']);
                if ($barEnd < $barStart) continue;
                $startOffset = (int)$monthStart->diff(new DateTimeImmutable($barStart))->days;
                $endOffset = (int)$monthStart->diff(new DateTimeImmutable($barEnd))->days;
                $left = $startOffset / $timelineDays * 100;
                $width = ($endOffset - $startOffset + 1) / $timelineDays * 100;
                $barClass = [0 => 'timeline-pending', 1 => 'timeline-loaned', 4 => 'timeline-returning'][(int)$loan['status']];
                $barStatus = [0 => 'รอตรวจสอบ', 1 => 'กำลังยืม', 4 => 'รอยืนยันคืน'][(int)$loan['status']];
            ?>
            <span class="timeline-bar <?= $barClass ?>" style="left:<?= number_format($left, 3, '.', '') ?>%;width:<?= number_format($width, 3, '.', '') ?>%"
                  title="<?= h($loan['borrower_name'] . ' · ' . $barStatus . ' · ' . thdate($loan['date_from']) . ' – ' . thdate($loan['display_end'])) ?>">
              <?php if ($width > 12): ?><?= h($loan['ref_code']) ?><?php endif; ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex gap-3 flex-wrap small text-muted mt-3">
        <!-- <span><i class="timeline-legend timeline-pending"></i> รอตรวจสอบ</span> -->
        <span><i class="timeline-legend timeline-loaned"></i> กำลังยืม</span>
        <!-- <span><i class="timeline-legend timeline-returning"></i> รอยืนยันคืน</span> -->
      </div>
      <?php if ($legacyQty): ?><div class="small text-muted mt-2">มีหุ่น <?= $legacyQty ?> ตัวในใบยืมเดิมที่ยังไม่ระบุชื่อ จึงไม่สามารถระบุตำแหน่งในไทม์ไลน์รายตัวได้</div><?php endif; ?>
      <?php endif; ?>
    </div></div>
  </div>
</div>
