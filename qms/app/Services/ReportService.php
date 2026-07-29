<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NonConformityRepository;

/**
 * Génère les exports de rapports (CSV, Excel, impression PDF).
 */
final class ReportService
{
    private const HEADERS = [
        'reference'        => 'Référence',
        'occurred_on'      => 'Date',
        'department_name'  => 'Service',
        'product'          => 'Produit',
        'severity'         => 'Gravité',
        'origin'           => 'Origine',
        'status'           => 'Statut',
        'responsible_name' => 'Responsable',
        'quantity'         => 'Quantité',
    ];

    public function __construct(private readonly NonConformityRepository $repository)
    {
    }

    /** @param array<string, mixed> $filters @return array<int, array<string, mixed>> */
    private function dataset(array $filters): array
    {
        return $this->repository->search($filters, 1, 100000)['data'];
    }

    /** @param array<string, mixed> $filters */
    public function toCsv(array $filters): string
    {
        $rows = $this->dataset($filters);
        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 pour une ouverture correcte dans Excel.
        fwrite($handle, "\xEF\xBB\xBF");
        // Le paramètre $escape est passé explicitement (requis en PHP 8.4+).
        fputcsv($handle, array_values(self::HEADERS), ';', '"', '\\');

        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys(self::HEADERS) as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($handle, $line, ';', '"', '\\');
        }

        rewind($handle);
        return (string) stream_get_contents($handle);
    }

    /**
     * Export Excel au format SpreadsheetML 2003 (ouvrable nativement par Excel
     * et LibreOffice, sans dépendance externe).
     *
     * @param array<string, mixed> $filters
     */
    public function toExcel(array $filters): string
    {
        $rows = $this->dataset($filters);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
              . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Styles><Style ss:ID="head"><Font ss:Bold="1" ss:Color="#FFFFFF"/>'
              . '<Interior ss:Color="#0A6ED1" ss:Pattern="Solid"/></Style></Styles>' . "\n";
        $xml .= '<Worksheet ss:Name="Non-conformités"><Table>' . "\n";

        $xml .= '<Row>';
        foreach (self::HEADERS as $label) {
            $xml .= '<Cell ss:StyleID="head"><Data ss:Type="String">' . $this->esc($label) . '</Data></Cell>';
        }
        $xml .= '</Row>' . "\n";

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach (array_keys(self::HEADERS) as $key) {
                $value = (string) ($row[$key] ?? '');
                $type = ($key === 'quantity') ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . $this->esc($value) . '</Data></Cell>';
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';
        return $xml;
    }

    /** @param array<string, mixed> $filters @return array<int, array<string, mixed>> */
    public function printable(array $filters): array
    {
        return $this->dataset($filters);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
