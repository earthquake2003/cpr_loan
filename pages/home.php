<?php
$today = date('Y-m-d');
$total = cpr_total($pdo);
$avail = cpr_available($pdo, $today, $today);
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h1 class="display-5 mb-3">ยืม-คืนหุ่น CPR<br>ง่าย ๆ ไม่ต้องสมัครสมาชิก</h1>
        <p class="lead mb-4">กรอกคำขอยืมอุปกรณ์ออนไลน์ รับรหัสติดตามสถานะ และแจ้งคืนได้เอง — แทนการกรอกใบยืมกระดาษ</p>
        <a href="index.php?page=loan_form" class="btn btn-light btn-lg me-2 mb-2"><i class="bi bi-pencil-square text-brand"></i> ขอยืมอุปกรณ์</a>
        <a href="index.php?page=track" class="btn btn-outline-light btn-lg mb-2"><i class="bi bi-search"></i> ตรวจสอบสถานะ</a>
      </div>
      <div class="col-lg-5">
        <div class="glass p-4">
          <div class="small opacity-75 mb-1">หุ่น CPR ที่ว่างวันนี้</div>
          <div class="d-flex align-items-end gap-2">
            <span class="display-3 fw-semibold lh-1"><?= $avail ?></span>
            <span class="fs-5 mb-1">/ <?= $total ?> ตัว</span>
          </div>
          <div class="progress mt-3" style="height:8px;background:rgba(255,255,255,.25)">
            <div class="progress-bar bg-white" style="width:<?= $total ? round($avail / $total * 100) : 0 ?>%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container overlap pb-5">
  <div class="row g-3">
    <?php foreach ([
      ['bi-pencil-square', '1. กรอกคำขอยืม', 'ระบุผู้ยืม หน่วยงาน ช่วงวันที่ และเลือกชื่อหุ่น/อุปกรณ์ที่ต้องการ'],
      ['bi-patch-check',   '2. รอเจ้าหน้าที่อนุมัติ', 'ระบบให้รหัสติดตาม 6 ตัว ใช้เช็กสถานะได้ตลอดเวลา'],
      ['bi-box-arrow-in-down', '3. ส่งคืนอุปกรณ์', 'แจ้งคืนในระบบ ตรวจสภาพ แล้วนำอุปกรณ์มาส่งเจ้าหน้าที่'],
    ] as [$ic, $t, $d]): ?>
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body d-flex gap-3">
        <span class="icon-circle"><i class="bi <?= $ic ?>"></i></span>
        <div><h6 class="mb-1"><?= $t ?></h6><p class="text-muted small mb-0"><?= $d ?></p></div>
      </div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card mt-4"><div class="card-body p-4">
    <div class="row align-items-center g-3">
      <div class="col-md-5"><h5 class="mb-1"><i class="bi bi-search text-brand"></i> มีรหัสติดตามแล้ว?</h5>
        <div class="text-muted small">กรอกรหัส 6 ตัวเพื่อดูสถานะหรือแจ้งคืนอุปกรณ์</div></div>
      <div class="col-md-7">
        <form method="get" action="index.php" class="d-flex gap-2">
          <input type="hidden" name="page" value="track">
          <input name="ref" class="form-control form-control-lg text-uppercase" maxlength="6" placeholder="เช่น K7M2QX" required>
          <button class="btn btn-brand btn-lg px-4">ตรวจสอบ</button>
        </form>
      </div>
    </div>
  </div></div>
</div>
