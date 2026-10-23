<?php
declare(strict_types=1);

header('Location: ./callback.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : ''));
exit;
