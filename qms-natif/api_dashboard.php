<?php
require __DIR__ . '/includes/functions.php';
require_login();
require __DIR__ . '/includes/dashboard_data.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(dashboard_data($pdo), JSON_UNESCAPED_UNICODE);
