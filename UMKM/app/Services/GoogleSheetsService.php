<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleSheetsService
{
    protected $client;
    protected $sheetsService;
    protected $driveService;

    public function __construct()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName('UMKM Pancasila Exporter');
            $this->client->setScopes([Sheets::SPREADSHEETS, Drive::DRIVE]);

            // Menggunakan file kredensial dari root directory
            $credentialsPath = base_path('google-credentials.json');

            if (!file_exists($credentialsPath)) {
                throw new Exception("File google-credentials.json tidak ditemukan di direktori root.");
            }

            $this->client->setAuthConfig($credentialsPath);
            $this->client->setAccessType('offline');

            $this->sheetsService = new Sheets($this->client);
            $this->driveService = new Drive($this->client);
        } catch (Exception $e) {
            Log::error("Gagal inisialisasi GoogleSheetsService: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Membuat spreadsheet baru, mengisinya dengan data, dan memberikan hak akses publik (read-only).
     *
     * @param string $title Judul spreadsheet
     * @param array $headers Header tabel (1D array)
     * @param array $data Data baris (2D array)
     * @return string URL Spreadsheet yang berhasil dibuat
     */
    public function exportData(string $title, array $headers, array $data): string
    {
        try {
            // 1. Buat Spreadsheet Baru
            $spreadsheet = new Sheets\Spreadsheet([
                'properties' => [
                    'title' => $title . ' - ' . now()->format('Y-m-d H:i:s')
                ]
            ]);

            $spreadsheet = $this->sheetsService->spreadsheets->create($spreadsheet);
            $spreadsheetId = $spreadsheet->spreadsheetId;

            // 2. Siapkan Data (gabungkan header dan isi data)
            $values = [$headers];
            foreach ($data as $row) {
                $values[] = array_values($row);
            }

            $body = new Sheets\ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            // 3. Masukkan Data ke Sheet (Mulai dari A1)
            $this->sheetsService->spreadsheets->values->update($spreadsheetId, 'Sheet1!A1', $body, $params);

            // 4. Ubah Hak Akses agar bisa dilihat oleh publik (Anyone with the link can view)
            try {
                $permission = new Drive\Permission([
                    'type' => 'anyone',
                    'role' => 'reader'
                ]);
                $this->driveService->permissions->create($spreadsheetId, $permission);
                Log::info("Permission granted for spreadsheet: {$spreadsheetId}");
            } catch (Exception $permError) {
                // Service account mungkin tidak punya akses untuk set public permission
                // Spreadsheet sudah berhasil dibuat, hanya permission yang gagal
                Log::warning("Gagal set public permission untuk spreadsheet {$spreadsheetId}: " . $permError->getMessage());
            }

            // 5. Kembalikan URL
            return $spreadsheet->spreadsheetUrl;

        } catch (Exception $e) {
            // Jika spreadsheet sudah dibuat, return URLnya meski gagal
            if (isset($spreadsheet) && isset($spreadsheet->spreadsheetId) && $spreadsheet->spreadsheetId) {
                Log::warning("Spreadsheet dibuat tapi ada error: " . $e->getMessage());
                // Coba return URL meski error permission
                try {
                    return 'https://docs.google.com/spreadsheets/d/' . $spreadsheet->spreadsheetId . '/edit';
                } catch (Exception $urlErr) {
                    Log::error("Gagal construct URL: " . $urlErr->getMessage());
                }
            }
            Log::error("Gagal export Google Sheets: " . $e->getMessage());
            throw $e;
        }
    }
}
