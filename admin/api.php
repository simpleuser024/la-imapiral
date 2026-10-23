<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/store.php';

ensure_store_state();

function admin_api_count_rows(mysqli $connection, string $table, string $where = '1=1'): int
{
    if (!store_table_exists($connection, $table)) {
        return 0;
    }

    $result = @mysqli_query($connection, 'SELECT COUNT(*) AS total FROM ' . $table . ' WHERE ' . $where);
    if (!($result instanceof mysqli_result)) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

if (!store_is_logged_in() || !($con instanceof mysqli) || (!store_is_admin() && !store_user_has_permission($con, 'view_admin_dashboard'))) {
    store_json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
}

$action = trim((string) ($_GET['action'] ?? 'activity_feed'));

if ($action === 'summary') {
    store_json_response([
        'success' => true,
        'summary' => [
            'products' => admin_api_count_rows($con, 'products'),
            'users' => admin_api_count_rows($con, 'users'),
            'reviews' => admin_api_count_rows($con, 'reviews'),
            'inquiries' => admin_api_count_rows($con, 'contact_inquiries'),
            'notifications' => admin_api_count_rows($con, 'activity_notifications'),
        ],
    ]);
}

if ($action === 'activity_feed') {
    store_json_response([
        'success' => true,
        'items' => store_activity_feed($con, 24),
    ]);
}

store_json_response(['success' => false, 'message' => 'Unknown action.'], 404);
