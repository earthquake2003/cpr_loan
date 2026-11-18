<?php
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
function o($k, $d = '') { global $old; return h($old[$k] ?? $d); }
$oldManikinIds = array_map('intval', (array)($old['manikin_ids'] ?? []));
$today = date('Y-m-d');
$oldItems = $old['item_name'] ?? [''];
if (!$oldItems) $oldItems = [''];
?>
<div class="container page-narrow py-4">
  <h3 class="mb-1">ขอยืมอุปกรณ์หุ่น CPR</h3>
  <p class="text-muted"></p>
  <?php if (is_logged_in()): ?>
    <!--<div class="alert alert-info"><i class="bi bi-person-check"></i> เจ้าหน้าที่ <b><?= h(current_user()) ?></b> กำลังบันทึกแทนผู้ยืม — ใบนี้จะ<b>อนุมัติทันที</b></div>-->
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
      <label class="form-label">เลือกหุ่น CPR ที่ต้องการยืม *</label>
      <div id="manikinOptions" class="row g-2 mb-2" data-selected="<?= h(implode(',', $oldManikinIds)) ?>" aria-live="polite">
        <div class="col-12 text-muted small">เลือกช่วงวันที่ก่อน เพื่อดูหุ่นที่ว่าง</div>
      </div>
      <div id="manikinCount" class="form-text mb-3">เลือกได้หลายตัวโดยแตะ/คลิกที่ชื่อหุ่น</div>
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
      <button type="submit" class="btn btn-brand btn-lg px-5" disabled><i class="bi bi-send"></i> ส่งคำขอยืม</button>
    </div>
  </form>
</div>

<script>
(function () {
  var df = document.getElementById('df'), dt = document.getElementById('dt'), days = document.getElementById('days'),
      box = document.getElementById('availBox'), options = document.getElementById('manikinOptions'),
      count = document.getElementById('manikinCount'),
      selectedInitially = options.getAttribute('data-selected').split(',').filter(Boolean),
      requestId = 0, loading = false;
  var submitButton = document.querySelector('#loanForm button[type="submit"]');

  function getCheckboxes() {
    return Array.from(options.querySelectorAll('input[name="manikin_ids[]"]'));
  }

  function syncSelectionState() {
    var checkboxes = getCheckboxes();
    var selectedCount = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
    checkboxes.forEach(function (checkbox) {
      checkbox.disabled = loading;
      checkbox.closest('label').classList.toggle('border-primary', checkbox.checked);
      checkbox.closest('label').classList.toggle('bg-primary-subtle', checkbox.checked);
    });
    count.textContent = 'เลือกแล้ว ' + selectedCount + ' ตัว';
    submitButton.disabled = loading || selectedCount === 0;
  }

  function renderManikins(manikins, selectedIds) {
    options.innerHTML = '';
    if (!manikins.length) {
      var empty = document.createElement('div');
      empty.className = 'col-12 text-muted small';
      empty.textContent = 'ไม่มีหุ่น CPR ที่ว่างในช่วงวันที่นี้';
      options.appendChild(empty);
      syncSelectionState();
      return;
    }

    manikins.forEach(function (manikin) {
      var column = document.createElement('div');
      column.className = 'col-6 col-md-4 col-lg-3';
      var label = document.createElement('label');
      label.className = 'w-100 h-100 border rounded-3 p-3 d-flex align-items-center gap-2';
      label.style.cursor = 'pointer';
      var checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.name = 'manikin_ids[]';
      checkbox.value = String(manikin.id);
      checkbox.className = 'form-check-input m-0 flex-shrink-0';
      checkbox.checked = selectedIds.indexOf(String(manikin.id)) !== -1;
      checkbox.addEventListener('change', syncSelectionState);
      var name = document.createElement('span');
      name.textContent = manikin.name;
      label.appendChild(checkbox);
      label.appendChild(name);
      column.appendChild(label);
      options.appendChild(column);
    });
    syncSelectionState();
  }

  function update() {
    if (df.value) dt.min = df.value;
    if (!df.value || !dt.value || dt.value < df.value) {
      requestId++;
      loading = true;
      syncSelectionState();
      renderManikins([], []);
      days.value = '';
      box.classList.add('d-none');
      return;
    }
    var n = Math.round((new Date(dt.value) - new Date(df.value)) / 86400000) + 1;
    days.value = n > 0 ? n : '';
    if (n > 0) check();
  }

  function check() {
    var currentRequest = ++requestId;
    loading = true;
    syncSelectionState();
    box.classList.remove('d-none');
    box.textContent = 'กำลังตรวจสอบหุ่น CPR...';

    fetch('index.php?page=api_available&from=' + encodeURIComponent(df.value) + '&to=' + encodeURIComponent(dt.value), { cache: 'no-store' })
      .then(function (r) {
        if (!r.ok) throw new Error('ตรวจสอบหุ่น CPR ไม่สำเร็จ');
        return r.json();
      })
      .then(function (j) {
        if (currentRequest !== requestId) return;
        if (j.error || !Array.isArray(j.manikins)) throw new Error('ข้อมูลหุ่น CPR ไม่ถูกต้อง');
        var oldSelected = getCheckboxes().filter(function (checkbox) { return checkbox.checked; })
          .map(function (checkbox) { return checkbox.value; });
        var selected = oldSelected.length ? oldSelected : selectedInitially;
        loading = false;
        var availableIds = j.manikins.map(function (item) { return String(item.id); });
        renderManikins(j.manikins, selected.filter(function (id) { return availableIds.indexOf(id) !== -1; }));
        selectedInitially = [];
        box.classList.remove('d-none');
        box.classList.toggle('none', j.available < 1);
        box.innerHTML = j.available > 0
          ? '<i class="bi bi-check-circle"></i> ช่วงวันที่นี้มีหุ่นว่าง <b>' + j.available + '</b> ตัว จากทั้งหมด ' + j.total + ' ตัว'
          : '<i class="bi bi-exclamation-circle"></i> ช่วงวันที่นี้หุ่นถูกจองหมดแล้ว กรุณาเลือกช่วงอื่น';
        if (j.unnamed_reserved > 0) {
          box.innerHTML += '<br><span class="small">มีหุ่น ' + j.unnamed_reserved + ' ตัวในใบยืมเดิมที่ยังไม่ระบุชื่อ ระบบกันจำนวนไว้แล้ว</span>';
        }
        if (j.manikins.length === 0) box.textContent = 'ไม่มีหุ่น CPR ที่ว่างในช่วงวันที่นี้';
      })
      .catch(function () {
        if (currentRequest !== requestId) return;
        loading = true;
        syncSelectionState();
        box.classList.remove('none');
        box.textContent = 'ตรวจสอบหุ่น CPR ไม่สำเร็จ กรุณาลองอีกครั้ง';
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
