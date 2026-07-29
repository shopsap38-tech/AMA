<?php
require __DIR__ . '/includes/functions.php';
require_permission('report.view');
require __DIR__ . '/includes/report_query.php';

$rows = report_rows($pdo, $_GET);
$headers = report_headers();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="non-conformites_' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
fputcsv($out, array_values($headers), ';', '"', '\\');
foreach ($rows as $row) {
    $line = [];
    foreach (array_keys($headers) as $key) { $line[] = $row[$key] ?? ''; }
    fputcsv($out, $line, ';', '"', '\\');
}
fclose($out);
exit;
