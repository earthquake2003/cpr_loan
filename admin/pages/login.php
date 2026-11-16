<?php
if (is_logged_in()) { header('Location: index.php'); exit; }
$error = '';
$locked = ($_SESSION['lock_until'] ?? 0) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $st = $pdo->prepare("SELECT * FROM cpr_users WHERE username = ?");
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'username' => $u['username'],
                             'full_name' => $u['full_name'], 'role' => $u['role']];
        unset($_SESSION['fails'], $_SESSION['lock_until']);
        header('Location: index.php');
        exit;
    }
    usleep(400000);
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; $locked = true; }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบเจ้าหน้าที่</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#9f1239,#e11d48 60%,#fb7185);min-height:100vh">
<div class="container" style="max-width:420px;padding-top:11vh">
  <div class="card shadow-lg"><div class="card-body p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="icon-circle mb-2" style="width:3.6rem;height:3.6rem;font-size:1.8rem"><i class="bi bi-heart-pulse-fill"></i></div>
      <h4 class="mb-0">เข้าสู่ระบบเจ้าหน้าที่</h4>
      <div class="text-muted small">ระบบยืม-คืนหุ่น CPR</div>
    </div>
    <?php if ($locked): ?><div class="alert alert-warning py-2">ลองผิดหลายครั้ง กรุณารอ 5 นาทีแล้วลองใหม่</div>
    <?php elseif ($error): ?><div class="alert alert-danger py-2"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="index.php?page=login">
      <?= csrf_field() ?>
      <div class="form-floating mb-3"><input name="username" id="u" class="form-control" placeholder="u" required autofocus><label for="u">ชื่อผู้ใช้</label></div>
      <div class="form-floating mb-3"><input type="password" name="password" id="p" class="form-control" placeholder="p" required><label for="p">รหัสผ่าน</label></div>
      <button class="btn btn-brand btn-lg w-100" <?= $locked ? 'disabled' : '' ?>>เข้าสู่ระบบ</button>
    </form>
    <div class="text-center mt-3"><a href="../index.php" class="small text-muted"><i class="bi bi-arrow-left"></i> กลับหน้าผู้ใช้</a></div>
  </div></div>
</div>
</body></html>
