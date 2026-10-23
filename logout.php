<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_logout_user();
store_flash('success', 'You have been logged out.');

header('Location: ./index.php');
exit;
