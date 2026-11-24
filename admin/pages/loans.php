<?php
$today = date('Y-m-d');
$s = $_GET['s'] ?? 'all';
$q = trim($_GET['q'] ?? '');
$pg = max(1, (int)($_GET['p'] ?? 1));
$per = 15;

$where = []; $params = [];
if ($s === 'overdue') { $where[] = "status=1 AND due_date < ?"; $params[] = $today; }
elseif (in_array($s, ['0','1','2','3','4'], true)) { $where[] = "status = ?"; $params[] = (int)$s; }
else $s = 'all';
if ($q !== '') {
    $where[] = "(borrower_name LIKE ? OR department LIKE ? OR ref_code LIKE ? OR borrower_phone LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$cs = $pdo->prepare("SELECT COUNT(*) FROM cpr_loans $w"); $cs->execute($params);
$totalRows = (int)$cs->fetchColumn();
$pages = max(1, (int)ceil($totalRows / $per));
$pg = min($pg, $pages);
$offset = ($pg - 1) * $per;
$st = $pdo->prepare("SELECT * FROM cpr_loans $w ORDER BY FIELD(status,0,4,1,2,3), id DESC LIMIT $per OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

$cnt = [];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM cpr_loans GROUP BY status") as $r) $cnt[(int)$r['status']] = (int)$r['c'];
$od = $pdo->prepare("SELECT COUNT(*) FROM cpr_loans WHERE status=1 AND due_date < ?"); $od->execute([$today]);
$tabs = ['all' => ['ทั้งหมด', array_sum($cnt)], '1' => ['กำลังยืม', $cnt[1] ?? 0],
         'overdue' => ['เกินกำหนด', (int)$od->fetchColumn()],
         '2' => ['คืนแล้ว', $cnt[2] ?? 0]];
function lurl($s, $q, $p = 1) { return 'index.php?page=loans&s=' . urlencode($s) . '&q=' . urlencode($q) . '&p=' . $p; }
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h3 class="mb-0">รายการยืม-คืน</h3>
  <form method="get" class="d-flex gap-2 loan-search">
    <input type="hidden" name="page" value="loans"><input type="hidden" name="s" value="<?= h($s) ?>">
    <input class="form-control" name="q" placeholder="ค้นหาชื่อ / หน่วยงาน / รหัส / เบอร์" value="<?= h($q) ?>">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
</div>
<ul class="nav nav-pills mb-3 gap-1 flex-wrap">
  <?php foreach ($tabs as $k => [$t, $n]): ?>
  <li class="nav-item"><a class="nav-link <?= (string)$s === (string)$k ? 'active' : 'bg-white' ?>" href="<?= lurl($k, $q) ?>"><?= $t ?> <span class="badge bg-secondary bg-opacity-25 text-dark"><?= $n ?></span></a></li>
  <?php endforeach; ?>
</ul>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead><tr><th>รหัส</th><th>ผู้ยืม</th><th>ช่วงเวลา</th><th>จำนวน</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><code class="text-brand"><?= h($r['ref_code']) ?></code><div class="small text-muted">#<?= $r['id'] ?></div></td>
      <td><div class="fw-bold"><?= h($r['department']) ?></div><div><?= h($r['borrower_name']) ?></div><div class="small text-muted"><?= h($r['borrower_phone']) ?></div></td>
      <td class="small"><?= thdate($r['date_from']) ?> – <?= thdate($r['date_to']) ?><div class="text-muted"><?= (int)$r['total_days'] ?> วัน</div></td>
      <td><?= (int)$r['cpr_qty'] ?> ตัว</td>
      <td><?= status_badge($r) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=loan_view&id=<?= $r['id'] ?>">ดู</a>
        <?php if ($r['status'] == 0): ?><a class="btn btn-sm btn-brand" href="index.php?page=loan_view&id=<?= $r['id'] ?>">อนุมัติ</a>
        <?php elseif ($r['status'] == 4): ?><a class="btn btn-sm btn-success" href="index.php?page=return_form&id=<?= $r['id'] ?>">รับคืน</a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">ไม่พบรายการ</td></tr><?php endif; ?>
  </tbody>
</table></div></div>

<?php if ($pages > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center flex-wrap">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
  <li class="page-item <?= $i === $pg ? 'active' : '' ?>"><a class="page-link" href="<?= lurl($s, $q, $i) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
