<?php
require __DIR__ . '/includes/functions.php';
require_permission('report.view');
require __DIR__ . '/includes/report_query.php';

$rows = report_rows($pdo, $_GET);
$headers = report_headers();
$esc = fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="non-conformites_' . date('Ymd') . '.xls"');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
echo '<Styles><Style ss:ID="head"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0A6ED1" ss:Pattern="Solid"/></Style></Styles>' . "\n";
echo '<Worksheet ss:Name="Non-conformités"><Table>' . "\n";

echo '<Row>';
foreach ($headers as $label) { echo '<Cell ss:StyleID="head"><Data ss:Type="String">' . $esc($label) . '</Data></Cell>'; }
echo '</Row>' . "\n";

foreach ($rows as $row) {
    echo '<Row>';
    foreach (array_keys($headers) as $key) {
        $value = (string) ($row[$key] ?? '');
        $type = ($key === 'quantity') ? 'Number' : 'String';
        echo '<Cell><Data ss:Type="' . $type . '">' . $esc($value) . '</Data></Cell>';
    }
    echo '</Row>' . "\n";
}
echo '</Table></Worksheet></Workbook>';
exit;
