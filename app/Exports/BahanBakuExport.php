<?php

namespace App\Exports;

use App\Models\Material;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BahanBakuExport
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function download(): StreamedResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $this->filters['search'] ?? null;
        $categoryId = $this->filters['raw_material_category_id'] ?? null;

        $query = Material::with('rawMaterialCategory')
            ->where('company_id', $companyId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('default_supplier', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhereHas('rawMaterialCategory', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('raw_material_category_id', $categoryId);
        }

        $materials = $query->orderBy('name')->get();

        // Construct PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok Bahan Baku');
        $sheet->setShowGridlines(true);

        // Title & Metadata Header
        $sheet->setCellValue('A1', 'LAPORAN DAFTAR STOK & VALUASI BAHAN BAKU');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF005050'));

        $companyName = auth()->user()->company->name ?? 'SAHAYU Bakery';
        $sheet->setCellValue('A2', 'UMKM: ' . $companyName);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF475569'));

        $sheet->setCellValue('A3', 'Dicetak Oleh: ' . auth()->user()->name . ' | Waktu: ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->getStyle('A3')->getFont()->setSize(8)->setColor(new Color('FF94A3B8'));

        // Table headers
        $sheet->setCellValue('A5', 'Nama Bahan');
        $sheet->setCellValue('B5', 'Kategori');
        $sheet->setCellValue('C5', 'Stok Saat Ini');
        $sheet->setCellValue('D5', 'Satuan');
        $sheet->setCellValue('E5', 'Harga Satuan (Rp)');
        $sheet->setCellValue('F5', 'Total Nilai Aset (Rp)');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF005050'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A5:F5')->applyFromArray($headerStyle);

        $rowNum = 6;
        foreach ($materials as $m) {
            $sheet->setCellValue('A' . $rowNum, $m->name);
            $sheet->setCellValue('B' . $rowNum, $m->rawMaterialCategory->name ?? $m->category ?? '-');
            $sheet->setCellValue('C' . $rowNum, (float)$m->stock);
            $sheet->setCellValue('D' . $rowNum, $m->unit);
            $sheet->setCellValue('E' . $rowNum, (float)$m->price);
            // Dynamic Excel formula for Total Nilai Aset: C * E
            $sheet->setCellValue('F' . $rowNum, '=C' . $rowNum . '*E' . $rowNum);
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

        if ($lastRow >= 6) {
            // Summary row
            $sheet->setCellValue('C' . $rowNum, 'TOTAL NILAI INVENTARIS');
            $sheet->setCellValue('F' . $rowNum, '=SUM(F6:F' . $lastRow . ')');

            $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0F2F1');

            $sheet->getStyle('C6:C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E6:F' . $rowNum)->getNumberFormat()->setFormatCode('Rp #,##0');
            $sheet->getStyle('E6:F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A5:F' . $rowNum)->applyFromArray($borderStyle);
        } else {
            $sheet->setCellValue('A6', 'Belum ada data bahan baku.');
            $sheet->mergeCells('A6:F6');
            $sheet->getStyle('A6')->getFont()->setItalic(true);
            $sheet->getStyle('A5:F6')->applyFromArray($borderStyle);
        }

        for ($col = 'A'; $col <= 'F'; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $safeFilename = 'Stok_Bahan_Baku_' . now()->format('Y-m-d');
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
