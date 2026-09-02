<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExportService
{
    /**
     * Export data ke file XLSX dengan styling, statistik, dan grafik.
     */
    public function exportAsXlsx(string $filename, array $headers, array $data, array $options = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Export');

        // 1. Tulis Headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            
            // Styling Header
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488'); // Teal-600
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $col++;
        }
        $lastCol = chr(ord('A') + count($headers) - 1);

        // 2. Tulis Data
        $rowNum = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $value) {
                // Check if value is numeric for alignment
                if (is_numeric($value)) {
                    $sheet->setCellValue($col . $rowNum, $value);
                } else {
                    $sheet->setCellValue($col . $rowNum, $value);
                }
                $col++;
            }
            $rowNum++;
        }
        $lastDataRow = $rowNum - 1;

        // 3. Auto-size columns
        for ($i = 0; $i < count($headers); $i++) {
            $sheet->getColumnDimension(chr(ord('A') + $i))->setAutoSize(true);
        }

        // 4. Tambah Statistik (Jika diminta)
        if (isset($options['statistics']) && !empty($options['statistics'])) {
            $rowNum++; // Gap row
            $statsStartRow = $rowNum;
            
            foreach ($options['statistics'] as $statColIdx => $statLabel) {
                $colLetter = chr(ord('A') + $statColIdx);
                
                // Label
                $sheet->setCellValue('A' . $rowNum, $statLabel);
                $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
                
                // Formula (Sum/Avg)
                if (str_contains(strtolower($statLabel), 'total') || str_contains(strtolower($statLabel), 'jumlah')) {
                    $sheet->setCellValue($colLetter . $rowNum, "=SUM({$colLetter}2:{$colLetter}{$lastDataRow})");
                } else if (str_contains(strtolower($statLabel), 'rata-rata') || str_contains(strtolower($statLabel), 'average')) {
                    $sheet->setCellValue($colLetter . $rowNum, "=AVERAGE({$colLetter}2:{$colLetter}{$lastDataRow})");
                }
                
                $sheet->getStyle($colLetter . $rowNum)->getFont()->setBold(true);
                $sheet->getStyle($colLetter . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9'); // Slate-100
                
                $rowNum++;
            }
        }

        // 5. Tambah Grafik (Jika ada kolom chart yang ditentukan)
        if (isset($options['chart']) && $lastDataRow > 2) {
            $this->addChart($sheet, $lastDataRow, $options['chart']);
        }

        // Set response
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '-' . now()->format('Y-m-d');
        
        $response = new StreamedResponse(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function addChart($sheet, $lastDataRow, $chartConfig)
    {
        // $chartConfig = ['label_col' => 0, 'data_col' => 2, 'title' => 'Grafik Penjualan']
        $labelCol = chr(ord('A') + ($chartConfig['label_col'] ?? 0));
        $dataCol = chr(ord('A') + ($chartConfig['data_col'] ?? 1));
        
        $labels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$' . $labelCol . '$2:$' . $labelCol . '$' . $lastDataRow, null, $lastDataRow - 1)];
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$' . $labelCol . '$2:$' . $labelCol . '$' . $lastDataRow, null, $lastDataRow - 1)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$' . $dataCol . '$2:$' . $dataCol . '$' . $lastDataRow, null, $lastDataRow - 1)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title($chartConfig['title'] ?? 'Grafik Analisis');

        $chart = new Chart(
            'chart1',
            $title,
            $legend,
            $plotArea
        );

        // Position chart
        $chart->setTopLeftPosition('G2');
        $chart->setBottomRightPosition('O20');

        $sheet->addChart($chart);
    }

    /**
     * Fallback to CSV if needed (existing logic)
     */
    public function exportAsCsv(string $filename, array $headers, array $data): StreamedResponse
    {
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '-' . now()->format('Y-m-d');

        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$safeFilename}.csv\"",
        ];

        $callback = function () use ($headers, $data) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ';');
            foreach ($data as $row) {
                fputcsv($file, array_values($row), ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }
}
