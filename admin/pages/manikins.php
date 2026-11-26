<?php
$id = (int)($_GET['id'] ?? 0);
$manikin = ['name' => '', 'details' => '', 'is_active' => 1];
if ($id) {
    $st = $pdo->prepare('SELECT id, name, details, is_active FROM cpr_manikins WHERE id = ?');
    $st->execute([$id]);
    $manikin = $st->fetch();
    if (!$manikin) {
        echo '<div class="alert alert-danger">ไม่พบหุ่น CPR</div>';
        return;
    }
}
$manikins = $pdo->query('SELECT id, name, details, is_active FROM cpr_manikins ORDER BY is_active DESC, name')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-1">จัดการหุ่น CPR</h3>
    <div class="text-muted small">หุ่นที่เปิดใช้งาน <?= cpr_total($pdo) ?> ตัว</div>
  </div>
</div>

<div class="card mb-4"><div class="card-body p-4">
  <h5 class="mb-3"><?= $id ? 'แก้ไขหุ่น CPR' : 'เพิ่มหุ่น CPR' ?></h5>
  <form method="post" action="index.php?page=manikin_save" class="row g-3 align-items-end">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="col-md-7">
      <label for="name" class="form-label">ชื่อหุ่น</label>
      <input name="name" id="name" class="form-control" value="<?= h($manikin['name']) ?>" maxlength="100" required>
    </div>
    <div class="col-12">
      <label for="details" class="form-label">รายละเอียด</label>
      <textarea name="details" id="details" class="form-control" rows="3" maxlength="1000" placeholder="เช่น รุ่น สภาพ หรือหมายเหตุ"><?= h($manikin['details'] ?? '') ?></textarea>
    </div>
    <?php if ($id): ?>
    <div class="col-md-3">
      <div class="form-check mb-2">
        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?= $manikin['is_active'] ? 'checked' : '' ?>>
        <label for="is_active" class="form-check-label">เปิดให้ยืม</label>
      </div>
    </div>
    <?php endif; ?>
    <div class="col-md-auto">
      <button class="btn btn-brand"><?= $id ? 'บันทึกการแก้ไข' : 'เพิ่มหุ่น' ?></button>
      <?php if ($id): ?><a href="index.php?page=manikins" class="btn btn-light">ยกเลิก</a><?php endif; ?>
    </div>
  </form>
</div></div>

<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>รายการหุ่น</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr></thead>
  <tbody>
  <?php if (!$manikins): ?>
    <tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีทะเบียนหุ่น CPR</td></tr>
  <?php endif; ?>
  <?php foreach ($manikins as $item): ?>
    <tr>
      <td>
        <div><?= h($item['name']) ?></div>
        <?php if (!empty($item['details'])): ?><div class="text-muted small"><?= nl2br(h($item['details'])) ?></div><?php endif; ?>
      </td>
      <td><?= $item['is_active'] ? '<span class="badge bg-success">เปิดใช้งาน</span>' : '<span class="badge bg-secondary">ปิดใช้งาน</span>' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="index.php?page=manikins&id=<?= (int)$item['id'] ?>">แก้ไข</a>
        <?php if ($item['is_active']): ?>
        <form method="post" action="index.php?page=manikin_delete" class="d-inline" onsubmit="return confirm('ปิดใช้งานหุ่น <?= h($item['name']) ?>?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
