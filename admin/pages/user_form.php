<?php
$id = (int)($_GET['id'] ?? 0);
$u = ['username' => '', 'full_name' => '', 'role' => 'user'];
if ($id) {
    $st = $pdo->prepare("SELECT id, username, full_name, role FROM cpr_users WHERE id = ?");
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) { echo '<div class="alert alert-danger">ไม่พบผู้ใช้</div>'; return; }
}
?>
<a href="index.php?page=users" class="small text-muted"><i class="bi bi-arrow-left"></i> กลับ</a>
<h3 class="mb-3"><?= $id ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' ?></h3>
<form method="post" action="index.php?page=user_save" class="card card-body p-4" style="max-width:520px">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="form-floating mb-3"><input name="username" id="un" class="form-control" placeholder="u" value="<?= h($u['username']) ?>" required pattern="[A-Za-z0-9_.\-]{3,50}" title="อังกฤษ ตัวเลข _ . - ยาว 3-50 ตัว"><label for="un">ชื่อผู้ใช้ (ภาษาอังกฤษ)</label></div>
  <div class="form-floating mb-3"><input name="full_name" id="fn" class="form-control" placeholder="n" value="<?= h($u['full_name']) ?>" required><label for="fn">ชื่อ-นามสกุล</label></div>
  <div class="mb-3"><label class="form-label small">สิทธิ์</label>
    <select name="role" class="form-select">
      <option value="user" <?= $u['role'] !== 'admin' ? 'selected' : '' ?>>ผู้ใช้ (User) — จัดการใบยืม-คืน</option>
      <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ (Admin) — จัดการผู้ใช้/ตั้งค่าได้ด้วย</option>
    </select></div>
  <div class="form-floating mb-3"><input type="password" name="password" id="pw" class="form-control" placeholder="p" minlength="8" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
    <label for="pw"><?= $id ? 'รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)' : 'รหัสผ่าน (อย่างน้อย 8 ตัว)' ?></label></div>
  <div><button class="btn btn-brand">บันทึก</button> <a href="index.php?page=users" class="btn btn-light">ยกเลิก</a></div>
</form>
