# SAHAYU – Sistem Manajemen Keuangan & Analisis HPP UMKM Kuliner

**Versi:** `main` (commit 55b1787)  
**Framework:** Laravel 13 (PHP 8.5)  
**Frontend:** Blade + Vite + Tailwind (CSS Lite Claymorphism)  
**Realtime:** Laravel Reverb + Echo (WebSocket)  
**AI Assistant:** *SAHAYU Assistant* (Groq Llama 3.3 70B) – auditor + forecaster  
**Database:** MySQL (migration‑first)  

---

## 🎯 Tujuan & Manfaat

### Mengapa SAHAYU?
SAHAYU (**Sistem Analisis HPP dan Arus Yield UMKM**) dirancang khusus untuk **UMKM kuliner manufaktur** yang selama ini kesulitan:
- Memantau **HPP (Harga Pokok Penjualan)** secara akurat.
- Mengelola **stok bahan baku** dan **barang jadi** tanpa spreadsheet manual.
- Memisahkan **laporan arus kas** dari pembukuan akrual agar lebih mudah dipahami pemilik usaha.
- Mendapatkan **insight strategis** tanpa harus menjadi akuntan.

### Manfaat Utama
1. **Cash‑Basis Real‑Time** – Setiap transaksi tunai, QRIS, transfer, atau cicilan piutang langsung masuk ke laporan arus kas digital.
2. **HPP Otomatis & Terukur** – Sistem menghitung `unit_hpp_snapshot` dari material, overhead, dan tenaga kerja setiap batch produksi.
3. **Manajemen Stok Cerdas** – Stok bahan & produk bergerak otomatis lewat `lockForUpdate` + tabel `*_stock_movements` (audit trail lengkap).
4. **Realtime Dashboard & Notifikasi** – Perubahan stok & penjualan di‑broadcast via **Laravel Reverb** ke semua tab browser tanpa refresh.
5. **AI Auditor & Forecaster** – Mendeteksi anomali (margin > 60 %, reject > 20 %, lonjakan biaya > 50 %) dan memprediksi bulan depan.
6. **Piutang & Cicilan** – Catat penjualan tempo, kelola jadwal jatuh tempo, terima cicilan parsial, dan pantau sisa piutang per pelanggan.
7. **Manajemen Pelanggan (CRM Mini)** – Data pelanggan, histori transaksi, dan analisis loyalitas tersentral.
8. **Multi‑User & Role** – Hak akses `admin` vs `staff` via `RoleMiddleware` (admin dapat CRUD, staff hanya input transaksi).
9. **Ekspor Multi‑Format** – Excel (`.xlsx`), PDF (domPDF), dan Google Sheets untuk penjualan, produksi, bahan baku, overhead, dan laporan keuangan.
10. **Desain Lite Claymorphism** – UI hangat, rounded‑2xl, warna emerald – enak dipandang dan mudah diakses di layar sentuh HP.

### Fungsi & Kegunaan Tiap Modul

| Modul | Fungsi Utama | Kegunaan untuk Pemilik UMKM |
|-------|--------------|------------------------------|
| **Dashboard (`/`)** | Ringkasan KPI, chart penjualan vs biaya, top‑5 produk terlaris, AI insight, BCG‑Matrix. | Melihat kesehatan bisnis dalam satu layar, menentukan prioritas harian. |
| **POS / Penjualan (`/penjualan`)** | Input multi‑item, metode bayar (cash/transfer/QRIS/debt), auto‑kurangi stok, cetak struk. | Mempercepat transaksi di kasir, mengurangi человеческий error, mendukung penjualan tempo. |
| **Produksi (`/produksi`)** | Input batch produksi, resep (bahan), biaya overhead & TK, hitung HPP, status `process/done/cancelled`. | Memantau hasil produksi, reject rate, dan yield per batch untuk efisiensi. |
| **Bahan Baku (`/bahan-baku`)** | CRUD bahan, kategori, stok masuk/keluar, minimum stok, import/export. | Menjaga ketersediaan bahan, menghindari kehabisan stok mendadak. |
| **Produk Jadi (`/produk`)** | CRUD produk, gambar, harga jual, stok minimum, resep. | Mengelola katalog produk jadi dengan cepat. |
| **Pelanggan (`/pelanggan`)** | CRUD pelanggan, histori, export Excel. | Membangun database pelanggan untuk promo & loyalitas. |
| **Utang / Piutang (`/catatan-utang`)** | Buat piutang dari penjualan tempo, catat cicilan, pantau jatuh tempo, bayar banyak piutang sekaligus. | Mengurangi piutang macet, mengetahui cash‑flow yang akan datang. |
| **HPP Otomatis (`/hpp-otomatis`)** | Rekapitulasi HPP per produk dari produksi selesai, bandingkan dengan harga jual. | Menentukan harga jual ideal, mengetahui margin tiap produk. |
| **Overhead (`/overhead`)** | Catat biaya operasional (listrik, sewa, gaji) per kategori & tanggal. | Mengontrol biaya tetap, analisis biaya per periode. |
| **Pengeluaran (`/pengeluaran`)** | Petty‑cash harian, kas keluar operasional. | Memisahkan kas keluar modal vs operasional. |
| **AI Assistant (`/assistant`)** | Analisis bulanan otomatis + chatbot interaktif berbasis Groq LLM. | Mendapat konsultasi keuangan & operasional 24/7 tanpa biaya konsultan. |
| **Laporan (`/laporan`)** | Laporan laba/rugi, arus kas, penjualan, produksi, inventori – export Excel/PDF/Sheets. | Pelaporan ke pemilik, investor, atau pajak dengan format siap cetak. |
| **Pengaturan (`/settings`)** | Info toko (nama, alamat, logo), printer struk, ganti password, backup DB. | Personalisasi sistem sesuai identitas UMKM. |
| **Manajemen Akun (`/akun`)** | CRUD user, role admin/staff (khusus admin). | Mengatur tim pengguna sistem dengan hak akses yang tepat. |

---

## 🏗️ Arsitektur Ringkas

```
src/
├─ app/
│  ├─ Http/Controllers/          ← Logika bisnis (Dashboard, Sale, Production, AiReport, …)
│  ├─ Models/                    ← Eloquent models (Product, Material, Production, Sale, …)
│  ├─ Services/                ← AIService, SpreadsheetExportService, GoogleSheetsService
│  └─ Events/                  ← ProductSold, StockLowAlert, MaterialUsed, …
├─ resources/
│  ├─ views/                   ← Blade templates (dashboard, sales, produksi, ai‑assistant)
│  └─ js/ & css/               ← Vite entry points, Tailwind (Lite Claymorphism)
├─ routes/
│  ├─ web.php                  ← Semua route (auth, CRUD, AI, export)
│  └─ channels.php             ← Broadcast channel definitions
├─ database/
│  ├─ migrations/              ← Skema tabel (produk, bahan, produksi, penjualan, …)
│  └─ seeders/                ← Data contoh (kategori, produk contoh)
└─ config/
   ├─ app.php, broadcasting.php, openai.php, …  ← Konfigurasi framework & layanan eksternal
```

**Komponen utama**

| Komponen | Fungsinya |
|----------|-----------|
| `DashboardController` | Hitung KPI (penjualan, biaya, growth, stok) & kirim ke view **DashboardUtama**. |
| `SaleController` | CRUD penjualan, handling **cash**, **transfer**, **QRIS**, **debt**; update stok produk. |
| `ProductionController` | Input batch produksi, lock material stock, hitung HPP, emit event `ProductSold`. |
| `AiReportController` | Render halaman AI Assistant, kirim data bulanan ke Groq LLM, parsing JSON response. |
| `AIService` | Analisis BCG‑Matrix untuk menu (produk) – dipanggil oleh `AiReportController`. |
| `Events` (`ProductSold`, `StockLowAlert`, `MaterialUsed`, …) | Broadcast via Reverb → UI real‑time (stok, notifikasi). |
| `Broadcasting` | Channel `stock-updates` dipakai oleh frontend **echo.js**. |
| `Reverb` | Server WebSocket (port 8081 secara default). |
| `Vite` | Build assets (JS + Tailwind) dalam mode **dev** (`npm run dev`) atau **prod** (`npm run build`). |

---

## 💻 Persiapan Lingkungan (Windows 11)

1. **Clone repository**
   ```bash
   git clone <repo‑url> D:\SAHAYU
   cd D:\SAHAYU
   ```
2. **Instalasi PHP & Composer** (pastikan `php -v` ≥ 8.5). Disarankan pakai **uv** (PEP 668) untuk virtual‑env:
   ```bash
   uv venv .venv
   .venv\Scripts\activate
   uv pip install -r requirements.txt   # bila ada file requirements
   ```
3. **Instalasi dependensi Laravel**
   ```bash
   composer install
   ```
4. **Buat file `.env`**
   ```bash
   cp .env.example .env
   # edit .env (DB, MAIL, REVERB, OPENAI_API_KEY, dll.)
   php artisan key:generate
   ```
5. **Database**
   - Buat database MySQL (mis. `sahayu`).
   - Jalankan migrasi & seeder:
     ```bash
     php artisan migrate --force
     php artisan db:seed   # opsional, mengisi data contoh
     ```
6. **Node & Vite (frontend)**
   ```bash
   npm install
   npm run dev          # dev server (http://localhost:5174)
   # atau untuk produksi
   npm run build
   ```
7. **Realtime (Reverb)** – jalankan server WebSocket:
   ```bash
   php artisan reverb:start --host=127.0.0.1 --port=8081
   ```
8. **Jalankan aplikasi**
   ```bash
   php artisan serve --port=8000
   ```
   Buka `http://127.0.0.1:8000` di browser.
9. **Shortcut Windows** – `run-local.bat` di root men‑start semua service (Vite, Laravel, Reverb) sekaligus.

---

## 🛠️ Penggunaan Utama

| Modul | URL (contoh) | Aksi |
|-------|---------------|------|
| **Dashboard** | `/` | Ringkasan KPI, chart penjualan vs biaya, AI‑insight. |
| **Penjualan** | `/penjualan` | Tambah transaksi, pilih produk, metode pembayaran (cash, transfer, qris, debt). |
| **Produksi** | `/produksi` | Input batch, pilih bahan, hitung HPP, status `process / done / cancelled`. |
| **Bahan Baku** | `/bahan-baku` | Kelola inventori bahan, import/export (Excel/Sheets). |
| **Produk** | `/produk` | CRUD produk jadi, set stok minimum, harga jual. |
| **Utang / Piutang** | `/catatan-utang` | Buat cicilan, lihat status pembayaran. |
| **AI Assistant** | `/assistant` | Lihat analisis bulan ini, kirim pertanyaan lanjutan via chat. |
| **Export** | Various `/export‑*` | Excel, PDF, Google Sheets untuk penjualan, produksi, laporan keuangan. |

**Catatan UI** – Semua halaman menggunakan tema **Lite Claymorphism** (warna emerald, rounded‑2xl) yang didefinisikan di `resources/css/app.css`.

---

## 🤖 AI Assistant – SAHAYU

1. **Analisis Bulanan** (`GET /assistant`) – Mengambil data penjualan, produksi, biaya, stok, mengirim ke model Groq Llama 3.3 70B dengan prompt yang memaksa **output JSON**. Hasil berisi:
   - `is_anomaly` & `anomaly_reason`
   - `health_status` (Sehat / Waspada / Kritis)
   - `summary`
   - `prediction` (trend revenue, HPP, reject rate)
   - `advice` (saran actionable)
2. **Chat Interaktif** (`POST /assistant/chat`) – Menggunakan konteks lengkap (data keuangan, operasional, produk, bahan). Prompt men‑enforce bahasa Indonesia, tanpa filler, markdown bullet, serta *best‑practice* bila data tidak ada.

> **Pastikan** variabel `OPENAI_API_KEY` (atau Groq) dan konfigurasi `REVERB_*` di `.env` sudah terisi.

---

## 📦 Deploy ke Production

| Langkah | Penjelasan |
|--------|------------|
| **Web Server** | Apache/Nginx → arahkan ke folder `public/`. Aktifkan `mod_rewrite` (Apache) atau `try_files` (Nginx). |
| **Queue & Broadcast** | Jalankan `php artisan queue:work --daemon` (atau Supervisor) untuk job queue. <br> Jalankan `php artisan reverb:start` sebagai service (Supervisor). |
| **SSL** | Set `REVERB_SCHEME=https` & `REVERB_PORT=443` di `.env`. |
| **Env** | `APP_ENV=production`, `APP_DEBUG=false`. |
| **Cache** | `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. |
| **Scheduler** | Tambahkan `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1` ke crontab (untuk jobs periodik). |
| **Backup DB** | Gunakan `php artisan backup:run` (Laravel‑Backup) atau mekanisme eksternal. |

---

## 🧩 Pengembangan & Extensi

| Area | Tips |
|------|------|
| **Model / Migration** | `php artisan make:model ModelName -m` untuk menambah tabel baru. |
| **Service Layer** | Tempatkan logika bisnis berat (AI, reporting) di `app/Services/`. |
| **Event‑Driven** | Emit event via `$this->dispatchEvent(new EventName(...))`; listener dapat meng‑update UI realtime. |
| **Testing** | `php artisan test` – basis PHPUnit (di `tests/`). Pastikan coverage untuk controller & service. |
| **Static Analysis** | Jalankan `php artisan pint` untuk format kode, `phpstan` atau `phpcs` untuk quality. |
| **CI/CD** | Contoh pipeline di `.github/workflows/laravel.yml` (install PHP, composer, npm, run tests). |

---

## 📚 Dokumentasi Tambahan

- **Design System** – `design.md` (tema Emerald Soft‑Touch, palette, border‑radius). 
- **Broadcast Guide** – `BROADCAST_TEST_GUIDE.md` (cara menguji realtime). 
- **API & AI Prompt** – Lihat kode di `app/Http/Controllers/AiReportController.php` & `app/Services/AIService.php`. 

---

## 📜 Lisensi

Proyek ini dilisensikan di bawah **MIT License** – lihat file `LICENSE`.

---

*Dibuat untuk membantu UMKM kuliner Indonesia menjadi lebih *tactile*, efisien, dan cerdas.*