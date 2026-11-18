<?php
require __DIR__ . '/config/bootstrap.php';
$styleVersion = filemtime(__DIR__ . '/assets/style.css');

$adminExists = (int)$pdo->query("SELECT COUNT(*) FROM cpr_users WHERE role = 'admin'")->fetchColumn() > 0;
$error = '';
$username = '';
$fullName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($adminExists) {
        $error = 'มีบัญชีผู้ดูแลระบบแล้ว กรุณาเข้าสู่ระบบ';
    } elseif (!preg_match('/^[A-Za-z0-9_.\-]{3,50}$/', $username)) {
        $error = 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ . - ความยาว 3-50 ตัว';
    } elseif ($fullName === '' || mb_strlen($fullName) > 150) {
        $error = 'กรุณากรอกชื่อ-นามสกุลไม่เกิน 150 ตัวอักษร';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    } else {
        $duplicate = $pdo->prepare('SELECT 1 FROM cpr_users WHERE username = ?');
        $duplicate->execute([$username]);

        if ($duplicate->fetchColumn()) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว';
        } else {
            $lockName = 'cpr_loan_initial_admin';
            $locked = (int)$pdo->query("SELECT GET_LOCK('$lockName', 5)")->fetchColumn() === 1;

            if (!$locked) {
                $error = 'ไม่สามารถสร้างบัญชีได้ในขณะนี้ กรุณาลองอีกครั้ง';
            } else {
                try {
                    $adminExists = (int)$pdo->query("SELECT COUNT(*) FROM cpr_users WHERE role = 'admin'")->fetchColumn() > 0;
                    if ($adminExists) {
                        $error = 'มีบัญชีผู้ดูแลระบบแล้ว กรุณาเข้าสู่ระบบ';
                    } else {
                        $insert = $pdo->prepare(
                            'INSERT INTO cpr_users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)'
                        );
                        $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, 'admin']);
                    }
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('$lockName')");
                }

                if ($error === '') {
                    header('Location: index.php?page=login');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>สร้างผู้ดูแลระบบ · ระบบยืม-คืนหุ่น CPR</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="assets/style.css?v=<?= $styleVersion ?>" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#9f1239,#e11d48 60%,#fb7185);min-height:100vh">
<div class="container" style="max-width:520px;padding-top:8vh">
  <div class="card shadow-lg"><div class="card-body p-4 p-md-5">
    <h3 class="mb-1">สร้างผู้ดูแลระบบ</h3>
    <p class="text-muted mb-4">แบบฟอร์มนี้ใช้สร้างแอดมินเริ่มต้น และจะปิดใช้งานหลังมีแอดมินแล้ว</p>
    <?php if ($adminExists): ?>
      <div class="alert alert-info">มีบัญชีผู้ดูแลระบบแล้ว ไม่สามารถสร้างจากแบบฟอร์มนี้ได้</div>
      <a class="btn btn-brand w-100" href="index.php?page=login">เข้าสู่ระบบแอดมิน</a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
      <form method="post" action="create_admin.php">
        <?= csrf_field() ?>
        <div class="form-floating mb-3">
          <input name="username" id="username" class="form-control" placeholder="admin" value="<?= h($username) ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]{3,50}" autocomplete="username">
          <label for="username">ชื่อผู้ใช้ (ภาษาอังกฤษ)</label>
        </div>
        <div class="form-floating mb-3">
          <input name="full_name" id="full_name" class="form-control" placeholder="ชื่อ-นามสกุล" value="<?= h($fullName) ?>" required maxlength="150" autocomplete="name">
          <label for="full_name">ชื่อ-นามสกุล</label>
        </div>
        <div class="form-floating mb-3">
          <input type="password" name="password" id="password" class="form-control" placeholder="รหัสผ่าน" required minlength="8" autocomplete="new-password">
          <label for="password">รหัสผ่าน (อย่างน้อย 8 ตัว)</label>
        </div>
        <button class="btn btn-brand btn-lg w-100">สร้างบัญชีแอดมิน</button>
      </form>
    <?php endif; ?>
  </div></div>
</div>
</body>
</html>
