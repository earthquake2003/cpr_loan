<?php
$users = $pdo->query("SELECT id, username, full_name, role, created_at FROM cpr_users ORDER BY id")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">จัดการผู้ใช้</h3>
  <a class="btn btn-brand" href="index.php?page=user_form"><i class="bi bi-person-plus"></i> เพิ่มผู้ใช้</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>สิทธิ์</th><th>สร้างเมื่อ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): $me = $u['id'] == $_SESSION['user']['id']; ?>
    <tr>
      <td><?= h($u['username']) ?> <?= $me ? '<span class="badge bg-secondary">คุณ</span>' : '' ?></td>
      <td><?= h($u['full_name']) ?></td>
      <td><?= $u['role'] === 'admin' ? '<span class="badge bg-danger">ผู้ดูแลระบบ (Admin)</span>' : '<span class="badge bg-info text-dark">ผู้ใช้ (User)</span>' ?></td>
      <td><?= thdate($u['created_at']) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="index.php?page=user_form&id=<?= $u['id'] ?>">แก้ไข</a>
        <?php if (!$me): ?>
        <form method="post" action="index.php?page=user_delete" class="d-inline" onsubmit="return confirm('ลบผู้ใช้ <?= h($u['username']) ?> ?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
