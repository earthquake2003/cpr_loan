<?php
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
function o($k, $d = '') { global $old; return h($old[$k] ?? $d); }
$total = cpr_total($pdo);
$today = date('Y-m-d');
$oldItems = $old['item_name'] ?? [''];
if (!$oldItems) $oldItems = [''];
?>
<div class="container page-narrow py-4">
  <h3 class="mb-1">ขอยืมอุปกรณ์หุ่น CPR</h3>
  <p class="text-muted">กรอกข้อมูลให้ครบ เจ้าหน้าที่จะตรวจสอบและอนุมัติ — ไม่ต้อง login</p>
  <?php if (is_logged_in()): ?>
    <div class="alert alert-info"><i class="bi bi-person-check"></i> เจ้าหน้าที่ <b><?= h(current_user()) ?></b> กำลังบันทึกแทนผู้ยืม — ใบนี้จะ<b>อนุมัติทันที</b></div>
  <?php endif; ?>

  <form method="post" action="index.php?page=loan_save" id="loanForm" novalidate class="needs-validation">
    <?= csrf_field() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>

    <div class="card mb-3"><div class="card-body p-4">
      <div class="form-section-title"><span class="step-num">1</span> ข้อมูลผู้ขอยืม</div>
      <div class="row g-3">
        <div class="col-md-7"><div class="form-floating">
          <input name="borrower_name" id="bn" class="form-control" placeholder="ชื่อ" value="<?= o('borrower_name') ?>" required maxlength="150">
          <label for="bn">ชื่อ-นามสกุล ผู้ยืม *</label></div></div>
        <div class="col-md-5"><div class="form-floating">
          <input name="borrower_phone" id="bp" type="tel" class="form-control" placeholder="โทร" value="<?= o('borrower_phone') ?>" required maxlength="20" pattern="[0-9+\-\s]{9,20}">
          <label for="bp">เบอร์โทรศัพท์ *</label></div></div>
        <div class="col-12"><div class="form-floating">
          <input name="department" id="dp" class="form-control" placeholder="หน่วยงาน" value="<?= o('department') ?>" required maxlength="150">
          <label for="dp">หน่วยงาน/สังกัด *</label></div></div>
        <div class="col-12"><div class="form-floating">
          <input name="purpose" id="pp" class="form-control" placeholder="ภารกิจ" value="<?= o('purpose') ?>" required maxlength="255">
          <label for="pp">นำไปใช้ในภารกิจ/โครงการ *</label></div></div>
      </div>
    </div></div>

    <div class="card mb-3"><div class="card-body p-4">
      <div class="form-section-title"><span class="step-num">2</span> ระยะเวลาที่ยืม</div>
      <div class="row g-3">
        <div class="col-md-4"><div class="form-floating">
          <input type="date" name="date_from" id="df" class="form-control" value="<?= o('date_from', $today) ?>" min="<?= is_logged_in() ? '' : $today ?>" required>
          <label for="df">ตั้งแต่วันที่ *</label></div></div>
        <div class="col-md-4"><div class="form-floating">
          <input type="date" name="date_to" id="dt" class="form-control" value="<?= o('date_to') ?>" min="<?= $today ?>" required>
          <label for="dt">ถึงวันที่ *</label></div></div>
        <div class="col-md-4"><div class="form-floating">
          <input id="days" class="form-control" placeholder="วัน" readonly>
          <label for="days">รวมระยะเวลา (วัน)</label></div></div>
      </div>
      <div id="availBox" class="avail-box mt-3 d-none"></div>
    </div></div>

    <div class="card mb-3"><div class="card-body p-4">
      <div class="form-section-title"><span class="step-num">3</span> รายการอุปกรณ์</div>
      <div class="row g-3 mb-3">
        <div class="col-md-5"><div class="form-floating">
          <input type="number" name="cpr_qty" id="qty" class="form-control" min="1" max="<?= $total ?>" value="<?= o('cpr_qty', 1) ?>" placeholder="จำนวน" required>
          <label for="qty">จำนวนหุ่น CPR ที่ยืม (ตัว) *</label></div></div>
      </div>
      <label class="form-label fw-semibold">อุปกรณ์ที่ยืมเพิ่มเติม <span class="text-muted fw-normal">(ถ้ามี)</span></label>
      <div id="extra">
        <?php foreach ($oldItems as $i => $nm): ?>
        <div class="row g-2 mb-2 extra-row">
          <div class="col-8 col-md-9"><input name="item_name[]" class="form-control" placeholder="ชื่ออุปกรณ์ เช่น เครื่อง AED ฝึก" value="<?= h($nm) ?>" maxlength="200"></div>
          <div class="col-4 col-md-3"><input type="number" name="item_qty[]" class="form-control" min="1" placeholder="จำนวน" value="<?= h($old['item_qty'][$i] ?? '') ?>"></div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="addRow"><i class="bi bi-plus-lg"></i> เพิ่มรายการ</button>
    </div></div>

    <div class="d-flex gap-2 justify-content-end mb-5">
      <a href="index.php" class="btn btn-light">ยกเลิก</a>
      <button class="btn btn-brand btn-lg px-5"><i class="bi bi-send"></i> ส่งคำขอยืม</button>
    </div>
  </form>
</div>

<script>
(function () {
  var df = document.getElementById('df'), dt = document.getElementById('dt'), days = document.getElementById('days'),
      box = document.getElementById('availBox'), qty = document.getElementById('qty');
  function update() {
    if (df.value) dt.min = df.value;
    if (df.value && dt.value) {
      var n = Math.round((new Date(dt.value) - new Date(df.value)) / 86400000) + 1;
      days.value = n > 0 ? n : '';
      if (n > 0) check();
    }
  }
  function check() {
    fetch('index.php?page=api_available&from=' + df.value + '&to=' + dt.value)
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.error) { box.classList.add('d-none'); return; }
        box.classList.remove('d-none');
        box.classList.toggle('none', j.available < 1);
        box.innerHTML = j.available > 0
          ? '<i class="bi bi-check-circle"></i> ช่วงวันที่นี้ว่าง <b>' + j.available + '</b> ตัว (จากทั้งหมด ' + j.total + ' ตัว)'
          : '<i class="bi bi-exclamation-circle"></i> ช่วงวันที่นี้หุ่นถูกจองหมดแล้ว กรุณาเลือกช่วงอื่น';
        qty.max = Math.max(1, j.available);
      });
  }
  df.addEventListener('change', update); dt.addEventListener('change', update); update();

  document.getElementById('addRow').addEventListener('click', function () {
    var rows = document.querySelectorAll('.extra-row');
    if (rows.length >= 8) return;
    var c = rows[0].cloneNode(true);
    c.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    document.getElementById('extra').appendChild(c);
  });
})();
</script>
