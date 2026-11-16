<h3 class="mb-3">ตั้งค่า</h3>
<form method="post" action="index.php?page=settings_save" class="card card-body p-4" style="max-width:480px">
  <?= csrf_field() ?>
  <div class="form-floating mb-2"><input type="number" name="total_cpr" id="t" class="form-control" min="1" max="999" value="<?= cpr_total($pdo) ?>" required placeholder="n">
    <label for="t">จำนวนหุ่น CPR ทั้งหมดของโรงพยาบาล (ตัว)</label></div>
  <div class="text-muted small mb-3">ใช้คำนวณจำนวนหุ่นที่ว่างตามช่วงวันที่ และป้องกันการยืมเกินจำนวน</div>
  <div><button class="btn btn-brand">บันทึก</button></div>
</form>
