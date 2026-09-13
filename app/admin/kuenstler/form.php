<?php
$id = $_GET['id'] ?? $_GET['copy'] ?? null;
header('Location: /admin/teilnehmer/form.php' . ($id ? '?' . http_build_query($_GET) : ''));
exit;
