<?php
$ref = $_SESSION['last_ref'] ?? '';
if ($ref === '') { echo '<script>location.href="index.php";</script>'; return; }
?>
<div class="container page-narrow py-5">
  <div class="card text-center"><div class="card-body p-5">
    <div class="icon-circle mb-3" style="width:4rem;height:4rem;font-size:2rem;background:#dcfce7;color:#16a34a"><i class="bi bi-check-lg"></i></div>
    <h3>ส่งคำขอยืมเรียบร้อย</h3>
    <p class="text-muted mb-4">เจ้าหน้าที่จะตรวจสอบและอนุมัติคำขอของคุณ<br>กรุณาจดรหัสนี้ไว้สำหรับตรวจสอบสถานะและแจ้งคืนอุปกรณ์</p>
    <div class="ref-code mb-1" id="ref"><?= h($ref) ?></div>
    <button class="btn btn-sm btn-outline-secondary mb-4" onclick="navigator.clipboard.writeText('<?= h($ref) ?>').then(()=>this.innerHTML='<i class=&quot;bi bi-check2&quot;></i> คัดลอกแล้ว')"><i class="bi bi-clipboard"></i> คัดลอกรหัส</button>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
      <a class="btn btn-brand" href="index.php?page=track&ref=<?= h($ref) ?>"><i class="bi bi-search"></i> ดูสถานะคำขอ</a>
      <a class="btn btn-light" href="index.php">กลับหน้าแรก</a>
    </div>
  </div></div>
</div>
