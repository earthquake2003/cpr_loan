<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?page=manikins'); exit; }
$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$active = $id === 0 || !empty($_POST['is_active']) ? 1 : 0;
$back = 'index.php?page=manikins' . ($id ? "&id=$id" : '');

if ($name === '' || mb_strlen($name) > 100) {
    flash('error', 'กรุณากรอกชื่อหุ่นไม่เกิน 100 ตัวอักษร');
    header("Location: $back");
    exit;
}

$duplicate = $pdo->prepare('SELECT id FROM cpr_manikins WHERE name = ? AND id <> ?');
$duplicate->execute([$name, $id]);
if ($duplicate->fetchColumn()) {
    flash('error', 'มีชื่อหุ่นนี้ในระบบแล้ว');
    header("Location: $back");
    exit;
}

if ($id) {
    $exists = $pdo->prepare('SELECT id, is_active FROM cpr_manikins WHERE id = ?');
    $exists->execute([$id]);
    $current = $exists->fetch();
    if (!$current) {
        flash('error', 'ไม่พบหุ่น CPR');
        header('Location: index.php?page=manikins');
        exit;
    }

    if (!$active && $current['is_active']) {
        if (cpr_has_legacy_active_loans($pdo)) {
            flash('error', 'มีใบยืมเดิมที่ยังไม่ระบุชื่อหุ่น กรุณารับคืนก่อนปิดใช้งานหุ่น');
            header("Location: $back");
            exit;
        }
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM cpr_loan_manikins lm
            JOIN cpr_loans l ON l.id = lm.loan_id
            WHERE lm.manikin_id = ? AND l.status IN (0,1,4)");
        $inUse->execute([$id]);
        if ((int)$inUse->fetchColumn() > 0) {
            flash('error', 'หุ่นนี้อยู่ในรายการยืมหรือรอตรวจสอบ จึงยังปิดใช้งานไม่ได้');
            header("Location: $back");
            exit;
        }
    }

    $pdo->prepare('UPDATE cpr_manikins SET name = ?, is_active = ? WHERE id = ?')
        ->execute([$name, $active, $id]);
} else {
    $pdo->prepare('INSERT INTO cpr_manikins (name) VALUES (?)')->execute([$name]);
}

flash('success', $id ? 'บันทึกหุ่น CPR แล้ว' : 'เพิ่มหุ่น CPR แล้ว');
header('Location: index.php?page=manikins');
