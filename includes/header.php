<?php $styleVersion = filemtime(__DIR__ . '/../assets/style.css'); ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ระบบยืม-คืนหุ่น CPR</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="<?= $ASSET ?>assets/style.css?v=<?= $styleVersion ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-md navbar-dark" style="background:#9f1239">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-heart-pulse-fill"></i> ยืม-คืนหุ่น CPR</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-md-center gap-md-1">
        <li class="nav-item"><a class="nav-link" href="index.php">หน้าแรก</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=loan_form">ขอยืมอุปกรณ์</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=track">ตรวจสอบสถานะ / แจ้งคืน</a></li>
        <li class="nav-item"><a class="btn btn-sm btn-light ms-md-2" href="admin/"><i class="bi bi-person-lock"></i> เจ้าหน้าที่</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="flex-grow-1">
