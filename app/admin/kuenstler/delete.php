<?php
header('Location: /admin/teilnehmer/delete.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
exit;
