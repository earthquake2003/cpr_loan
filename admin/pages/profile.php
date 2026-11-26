<?php
$profileSt = $pdo->prepare('SELECT username, full_name, role, created_at FROM cpr_users WHERE id = ?');
$profileSt->execute([(int)($_SESSION['user']['id'] ?? 0)]);
$profile = $profileSt->fetch();
if (!$profile) {
    http_response_code(404);
    echo '<div class="alert alert-danger">ไม่พบข้อมูลโปรไฟล์</div>';
    return;
}
$roleLabel = $profile['role'] === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ใช้ (User)';
$formValues = $_SESSION['profile_form'] ?? $profile;
unset($_SESSION['profile_form']);
?>
<div class="mb-3">
  <a href="index.php" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> กลับแดชบอร์ด</a>
  <h3 class="mt-2 mb-0"><i class="bi bi-person-vcard text-brand"></i> แก้ไขโปรไฟล์</h3>
</div>
<div class="card" style="max-width:640px">
  <div class="card-body p-4">
    <div class="d-flex align-items-center gap-3 mb-4">
      <i class="bi bi-person-circle fs-1 text-brand"></i>
      <div>
        <h5 class="mb-1"><?= h($profile['full_name']) ?></h5>
        <span class="badge bg-danger-subtle text-danger-emphasis"><?= h($roleLabel) ?></span>
      </div>
    </div>
    <form method="post" action="index.php?page=profile_save">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="profileUsername" class="form-label">ชื่อผู้ใช้</label>
        <input id="profileUsername" name="username" class="form-control" value="<?= h($formValues['username']) ?>"
               required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]{3,50}"
               title="ใช้อักษรอังกฤษ ตัวเลข _ . - ความยาว 3-50 ตัว" autocomplete="username">
      </div>
      <div class="mb-3">
        <label for="profileFullName" class="form-label">ชื่อ-นามสกุล</label>
        <input id="profileFullName" name="full_name" class="form-control" value="<?= h($formValues['full_name']) ?>"
               required maxlength="150" autocomplete="name">
      </div>
      <div class="mb-3">
        <label for="profileCurrentPassword" class="form-label">รหัสผ่านปัจจุบัน</label>
        <input id="profileCurrentPassword" name="current_password" type="password" class="form-control"
               autocomplete="current-password" aria-describedby="profileCurrentPasswordHelp">
        <div id="profileCurrentPasswordHelp" class="form-text">กรอกเมื่อต้องการเปลี่ยนรหัสผ่าน</div>
      </div>
      <div class="mb-3">
        <label for="profilePassword" class="form-label">รหัสผ่านใหม่</label>
        <input id="profilePassword" name="password" type="password" class="form-control"
               minlength="8" autocomplete="new-password" aria-describedby="profilePasswordHelp">
        <div id="profilePasswordHelp" class="form-text">เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
      </div>
      <div class="mb-4">
        <label for="profilePasswordConfirm" class="form-label">ยืนยันรหัสผ่านใหม่</label>
        <input id="profilePasswordConfirm" name="password_confirm" type="password" class="form-control"
               minlength="8" autocomplete="new-password">
      </div>
      <dl class="row border-top pt-3 mb-4">
        <dt class="col-sm-4 text-muted fw-normal">สิทธิ์</dt>
        <dd class="col-sm-8"><?= h($roleLabel) ?></dd>
        <dt class="col-sm-4 text-muted fw-normal">วันที่สร้างบัญชี</dt>
        <dd class="col-sm-8 mb-0"><?= h(thdate($profile['created_at'])) ?></dd>
      </dl>
      <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg me-1"></i>บันทึกข้อมูล</button>
    </form>
  </div>
</div>
