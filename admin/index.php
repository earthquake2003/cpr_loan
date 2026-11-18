<?php
$query = http_build_query($_GET);
header('Location: ../index.php' . ($query !== '' ? '?' . $query : ''));
exit;
