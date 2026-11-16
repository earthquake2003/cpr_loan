<?php
header('Content-Type: application/json; charset=utf-8');
$f = $_GET['from'] ?? ''; $t = $_GET['to'] ?? '';
if (!valid_date($f) || !valid_date($t) || $t < $f) { echo json_encode(['error' => 'bad date']); exit; }
$manikins = cpr_available_manikins($pdo, $f, $t);
echo json_encode([
    'total' => cpr_total($pdo),
    'available' => count($manikins),
    'unnamed_reserved' => cpr_legacy_reserved($pdo, $f, $t),
    'manikins' => array_map(static function ($manikin) {
        return ['id' => (int)$manikin['id'], 'name' => $manikin['name']];
    }, $manikins),
], JSON_UNESCAPED_UNICODE);
