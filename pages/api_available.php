<?php
header('Content-Type: application/json; charset=utf-8');
$f = $_GET['from'] ?? ''; $t = $_GET['to'] ?? '';
if (!valid_date($f) || !valid_date($t) || $t < $f) { echo json_encode(['error' => 'bad date']); exit; }
echo json_encode(['total' => cpr_total($pdo), 'available' => cpr_available($pdo, $f, $t)]);
