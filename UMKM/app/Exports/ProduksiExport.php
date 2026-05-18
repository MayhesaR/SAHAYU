<?php

namespace App\Exports;

use App\Models\Production;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProduksiExport
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function download(): StreamedResponse
    {
        $companyId = auth()->user()->company_id;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $status = $this->filters['status'] ?? null;
        $search = $this->filters['search'] ?? null;

        $query = Production::with('product')
            ->where('company_id', $companyId);

        if ($startDate) {
            $query->whereDate('production_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('production_date', '<=', $endDate);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('batch_code', 'like', "%{$search}%")
                  ->orWhere('supervisor_name', 'like', "%{$search}%")
                  ->orWhereHas('product', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $productions = $query->orderByDesc('production_date')->orderByDesc('id')->get();

        // Construct PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Produksi');
        $sheet->setShowGridlines(true);

        // Title & Metadata Header
        $sheet->setCellValue('A1', 'LAPORAN RIWAYAT BATCH PRODUKSI & HPP');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF0F766E'));

        $companyName = auth()->user()->company->name ?? 'SAHAYU Bakery';
        $sheet->setCellValue('A2', 'UMKM: ' . $companyName);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF475569'));

        $periodStr = 'Semua Periode';
        if ($startDate && $endDate) {
            $periodStr = \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y');
        } elseif ($startDate) {
            $periodStr = 'Mulai ' . \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y');
        } elseif ($endDate) {
            $periodStr = 'Sampai ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y');
        }
        $sheet->setCellValue('A3', 'Periode Laporan: ' . $periodStr);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('FF64748B'));

        $sheet->setCellValue('A4', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->getStyle('A4')->getFont()->setSize(8)->setColor(new Color('FF94A3B8'));

        // Table headers
        $sheet->setCellValue('A6', 'Tanggal Produksi');
        $sheet->setCellValue('B6', 'ID / Kode Batch');
        $sheet->setCellValue('C6', 'Nama Produk');
        $sheet->setCellValue('D6', 'Jumlah Dihasilkan');
        $sheet->setCellValue('E6', 'Status');
        $sheet->setCellValue('F6', 'Total Biaya Produksi (Rp)');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F766E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A6:F6')->applyFromArray($headerStyle);

        $rowNum = 7;
        foreach ($productions as $p) {
            $sheet->setCellValue('A' . $rowNum, $p->production_date->format('Y-m-d'));
            $sheet->setCellValue('B' . $rowNum, $p->batch_code);
            $sheet->setCellValue('C' . $rowNum, $p->product->name ?? '-');
            $sheet->setCellValue('D' . $rowNum, (int)$p->good_quantity);
            $sheet->setCellValue('E' . $rowNum, strtoupper($p->status));
            $sheet->setCellValue('F' . $rowNum, (float)$p->total_cost_snapshot);
            $rowNum++;
        }

        $lastRow = $rowNum - 1;

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];

        if ($lastRow >= 7) {
            // Summary row
            $sheet->setCellValue('C' . $rowNum, 'TOTAL SELURUH BIAYA');
            $sheet->setCellValue('D' . $rowNum, '=SUM(D7:D' . $lastRow . ')');
            $sheet->setCellValue('F' . $rowNum, '=SUM(F7:F' . $lastRow . ')');

            $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0F2F1');

            $sheet->getStyle('D7:D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F7:F' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet->getStyle('F7:F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A6:F' . $rowNum)->applyFromArray($borderStyle);
        } else {
            $sheet->setCellValue('A7', 'Belum ada data produksi.');
            $sheet->mergeCells('A7:F7');
            $sheet->getStyle('A7')->getFont()->setItalic(true);
            $sheet->getStyle('A6:F7')->applyFromArray($borderStyle);
        }

        for ($col = 'A'; $col <= 'F'; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $safeFilename = 'Riwayat_Produksi_' . now()->format('Y-m-d');
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $safeFilename . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
