<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

// Increase timeout for this long process
set_time_limit(300); // 5 minutes

header('Content-Type: application/json');

try {
    $result = aggregate_and_update();
    echo json_encode(['status' => 'ok', 'data' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
