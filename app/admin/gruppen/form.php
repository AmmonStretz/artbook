<?php
header('Location: /admin/teilnehmer/form.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
exit;
