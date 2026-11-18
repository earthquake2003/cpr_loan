<?php
$nPending = (int)$pdo->query("SELECT COUNT(*) FROM cpr_loans WHERE status=0")->fetchColumn();
$nReturn  = (int)$pdo->query("SELECT COUNT(*) FROM cpr_loans WHERE status=4")->fetchColumn();
$styleVersion = filemtime(__DIR__ . '/../../assets/style.css');
function nav_cls($page, $names) { return in_array($page, (array)$names, true) ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เจ้าหน้าที่ · ระบบยืม-คืนหุ่น CPR</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="assets/style.css?v=<?= $styleVersion ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-xxl navbar-dark admin-nav">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-heart-pulse-fill text-danger"></i> CPR Loan <span class="badge bg-danger align-middle ms-1" style="font-size:.6rem">แอดมิน</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto gap-lg-1 mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link <?= nav_cls($page, 'dashboard') ?>" href="index.php"><i class="bi bi-speedometer2"></i> แดชบอร์ด</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_cls($page, ['loans','loan_view','return_form']) ?>" href="index.php?page=loans">
          <i class="bi bi-card-checklist"></i> รายการยืม-คืน
          <?php if ($nPending): ?><span class="badge bg-warning text-dark"><?= $nPending ?></span><?php endif; ?>
          <?php if ($nReturn): ?><span class="badge bg-info text-dark"><?= $nReturn ?></span><?php endif; ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=loan_form"><i class="bi bi-plus-circle"></i> บันทึกการยืมแทนผู้ยืม</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_cls($page, 'manikins') ?>" href="index.php?page=manikins"><i class="bi bi-person-standing"></i> จัดการหุ่น CPR</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_cls($page, 'settings') ?>" href="index.php?page=settings"><i class="bi bi-gear"></i> ตั้งค่า</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 text-light small">
        <span><i class="bi bi-person-circle"></i> <?= h(current_user()) ?></span>
        <a class="btn btn-sm btn-danger" href="index.php?page=logout">ออกจากระบบ</a>
      </div>
    </div>
  </div>
</nav>
<main class="flex-grow-1"><div class="container py-4">
