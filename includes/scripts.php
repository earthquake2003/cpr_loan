<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<script>
Swal.fire({icon: <?= json_encode($f['icon']) ?>, text: <?= json_encode($f['text'], JSON_UNESCAPED_UNICODE) ?>,
           confirmButtonText: 'ตกลง', confirmButtonColor: '#e11d48'});
</script>
<?php endif; ?>
<script>
// ถ้าเลือกสภาพเป็น ชำรุด/สูญหาย ให้ช่องรายละเอียดเป็นช่องบังคับ
document.querySelectorAll('select[data-cond]').forEach(function (sel) {
  var detail = sel.closest('.row').querySelector('input[name="item_detail[]"]');
  function sync() { detail.required = sel.value !== '1'; if (sel.value !== '1') detail.classList.add('border-danger'); else detail.classList.remove('border-danger'); }
  sel.addEventListener('change', sync); sync();
});
</script>
