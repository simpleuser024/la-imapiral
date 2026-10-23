<?php
declare(strict_types=1);

$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : './index.php';
header('Location: ./login.php?provider=google&redirect=' . urlencode($redirect));
exit;
