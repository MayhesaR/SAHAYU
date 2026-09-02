# SAHAYU Real-Time Broadcasting - Full Feature Implementation Guide

## 🎉 Implementasi Lengkap Selesai!

Semua fitur real-time broadcasting untuk SAHAYU telah diimplementasikan dengan sukses. Dokumentasi ini menjelaskan setiap fitur dan cara menggunakannya.

---

## 📋 Fitur yang Diimplementasikan

### 1. ✅ **Product Stock Updates** (Penjualan Produk Jadi)

**File Terlibat:**

- `app/Events/ProductSold.php` - Event trigger saat produk terjual
- `app/Http/Controllers/SaleController.php` - Dispatch event setelah transaksi
- `resources/views/ManajemenProduk.blade.php` - Tampil stok real-time
- `resources/js/app.js` - Listener untuk update DOM

**Cara Kerja:**

1. User membuat transaksi penjualan di POS
2. SaleController dispatch event `ProductSold` dengan data product_id dan qty_deducted
3. Frontend listener menerima event dan update stok tanpa reload
4. Stok berkurang otomatis di semua tab yang membuka halaman produk

**Channel:** `stock-updates`

**Data yang Dikirim:**

```javascript
{
  product_id: 1,
  qty_deducted: 5,
  timestamp: "2026-05-13T10:30:00.000Z"
}
```

---

### 2. ✅ **Material Stock Updates** (Penggunaan Bahan Baku)

**File Terlibat:**

- `app/Events/MaterialUsed.php` - Event saat bahan dipakai
- `app/Http/Controllers/MaterialController.php::stockOut()` - Dispatch event
- `resources/views/ManajemenBahanBaku.blade.php` - Tampil stok real-time
- `resources/js/app.js` - Listener untuk update DOM

**Cara Kerja:**

1. Admin mengklik "Pakai Stok" di dashboard bahan baku
2. MaterialController dispatch event `MaterialUsed`
3. Frontend listener update stok bahan di tabel secara real-time
4. Stok berkurang otomatis tanpa reload halaman

**Channel:** `material-stock-updates`

**Data yang Dikirim:**

```javascript
{
  material_id: 2,
  quantity_used: 10.5,
  production_id: 0,
  timestamp: "2026-05-13T10:32:00.000Z"
}
```

---

### 3. ✅ **Production Status Updates** (Status Produksi)

**File Terlibat:**

- `app/Events/ProductionStatusUpdated.php` - Event saat status berubah
- `app/Http/Controllers/ProductionController.php::updateStatus()` - Dispatch event
- `resources/views/InputProduksi.blade.php` - Badge status + qty produksi
- `resources/js/app.js` - Listener untuk update status badge

**Cara Kerja:**

1. Admin mengklik "Tandai Selesai" pada batch produksi
2. ProductionController dispatch event `ProductionStatusUpdated`
3. Frontend listener update badge status dan qty produksi real-time
4. Badge berubah dari "Dalam Proses" → "Selesai" tanpa reload

**Channel:** `production-status-updates`

**Data yang Dikirim:**

```javascript
{
  production_id: 5,
  status: "done",
  product_id: 1,
  quantity_produced: 150,
  timestamp: "2026-05-13T10:35:00.000Z"
}
```

**Status Map:**

- `pending` → Badge kuning "Menunggu"
- `in_progress` → Badge biru "Dalam Proses"
- `completed` → Badge hijau "Selesai"
- `cancelled` → Badge merah "Dibatalkan"

---

### 4. ✅ **Stock Low Alerts** (Notifikasi Stok Menipis)

**File Terlibat:**

- `app/Events/StockLowAlert.php` - Event saat stok di bawah minimum
- `app/Http/Controllers/SaleController.php` - Trigger saat jual produk
- `app/Http/Controllers/MaterialController.php` - Trigger saat pakai bahan
- `resources/js/app.js` - Listener untuk tampil notifikasi

**Cara Kerja:**

1. Setiap transaksi atau penggunaan bahan, sistem check stok vs minimum
2. Jika stok ≤ minimum threshold, dispatch event `StockLowAlert`
3. Frontend listener tampilkan notifikasi toast (popup) di sudut kanan atas
4. Notifikasi auto-dismiss setelah 5 detik

**Channel:** `stock-alerts`

**Data yang Dikirim:**

```javascript
{
  product_id: 1,
  current_stock: 5,
  minimum_threshold: 10,
  item_type: "product",  // atau "material"
  timestamp: "2026-05-13T10:40:00.000Z"
}
```

**Tampilan Notifikasi:**

```
⚠️ Stock Low Alert!
Produk Jadi ID 1 stok sisa 5 unit.
Minimum threshold: 10 unit.
[X Close]
```

---

### 5. ✅ **Sales Analytics Updates** (Dashboard Penjualan Real-Time)

**File Terlibat:**

- `app/Events/SalesAnalyticsUpdated.php` - Event dengan data penjualan harian
- `app/Http/Controllers/SaleController.php` - Dispatch event setelah transaksi
- `resources/views/PencatatanPenjualan.blade.php` - Stats cards + top products list
- `resources/js/app.js` - Listener untuk update angka dan tabel

**Cara Kerja:**

1. Setiap transaksi penjualan selesai
2. SaleController query total unit, transaksi, revenue, dan top products hari ini
3. Dispatch event `SalesAnalyticsUpdated` dengan data terbaru
4. Frontend listener update:
    - Total Unit Penjualan (dalam waktu real-time)
    - Total Transaksi (jumlah nota)
    - Omzet Hari Ini (total revenue)
    - Produk Terlaris (list dengan qty)

**Channel:** `sales-analytics`

**Data yang Dikirim:**

```javascript
{
  total_sales: 85,          // Total unit terjual hari ini
  total_transactions: 12,   // Total transaksi/nota
  total_revenue: 1250000,   // Total omzet (Rp)
  top_products: [
    { id: 1, name: "Produk A", qty: 20 },
    { id: 3, name: "Produk C", qty: 15 },
    { id: 2, name: "Produk B", qty: 12 }
  ],
  timestamp: "2026-05-13T10:45:00.000Z"
}
```

**Elemen yang Ter-update:**

- `[data-analytics="total-sales"]` → Total Unit
- `[data-analytics="total-transactions"]` → Total Transaksi
- `[data-analytics="total-revenue"]` → Omzet Hari Ini
- `[data-analytics="top-products"]` → Daftar Produk Terlaris

---

## 🔧 Konfigurasi Environment

Semua variabel sudah dikonfigurasi di `.env`:

```env
# Broadcast & Queue Configuration
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

# Reverb WebSocket Server
REVERB_APP_ID=941417
REVERB_APP_KEY=7rvabq8psc1y9iikkpbe
REVERB_APP_SECRET=b99bwpvgtwhe6qswi2cu
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

# Frontend Reverb Configuration
VITE_REVERB_APP_KEY=7rvabq8psc1y9iikkpbe
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

---

## 📂 Struktur File Baru

```
app/Events/
├── ProductSold.php                  ✅ SUDAH ADA
├── MaterialUsed.php                 ✨ BARU
├── ProductionStatusUpdated.php      ✨ BARU
├── StockLowAlert.php               ✨ BARU
└── SalesAnalyticsUpdated.php        ✨ BARU

app/Http/Controllers/
├── SaleController.php               ✏️ UPDATED (dispatch 3 events)
├── MaterialController.php            ✏️ UPDATED (dispatch 2 events)
└── ProductionController.php          ✏️ UPDATED (dispatch 1 event)

resources/js/
└── app.js                           ✏️ UPDATED (5 listeners)

resources/views/
├── PencatatanPenjualan.blade.php    ✏️ UPDATED (data attributes)
├── ManajemenBahanBaku.blade.php     ✏️ UPDATED (data attributes)
├── InputProduksi.blade.php          ✏️ UPDATED (data attributes)
└── ManajemenProduk.blade.php        ✅ SUDAH ADA (data attributes)
```

---

## 🚀 Cara Testing

### Prerequisite

Pastikan 3 services jalan:

**Terminal 1: Reverb WebSocket**

```bash
php artisan reverb:start --host=127.0.0.1 --port=8081
```

**Terminal 2: Vite Dev Server**

```bash
npm run dev
```

**Terminal 3: Laravel Dev Server**

```bash
php artisan serve --port=8000
```

---

### Test 1: Product Stock Updates ✅

1. Buka 2 tab browser:
    - **Tab A:** http://localhost:8000/produk (Manajemen Produk)
    - **Tab B:** http://localhost:8000/penjualan (POS)

2. Di Tab B:
    - Pilih produk (contoh: ID 1)
    - Qty: 5
    - Klik "Catat Transaksi"

3. Di Tab A:
    - Lihat stok produk berkurang 5 unit **tanpa reload**
    - DevTools Console (F12) menampilkan:
        ```
        ProductSold received: {product_id: 1, qty_deducted: 5}
        ```

---

### Test 2: Material Stock Updates ✅

1. Buka halaman Bahan Baku:
    - http://localhost:8000/bahan-baku

2. Pada salah satu bahan, klik menu (⋮) → "Pakai Stok"

3. Input qty (contoh: 2.5) → Klik "Pakai Stok"

4. Lihat stok bahan berkurang **tanpa reload**
    - DevTools Console menampilkan:
        ```
        MaterialUsed received: {material_id: X, quantity_used: 2.5}
        ```

---

### Test 3: Production Status Updates ✅

1. Buka halaman Produksi:
    - http://localhost:8000/produksi

2. Pada batch yang "Dalam Proses", klik ikon ✓ (check circle)

3. Lihat badge status berubah dari "Dalam Proses" → "Selesai" **tanpa reload**
    - DevTools Console menampilkan:
        ```
        ProductionStatusUpdated received: {production_id: X, status: "done"}
        ```

---

### Test 4: Stock Low Alerts ✅

1. Di POS, jual produk dengan stok yang sudah di bawah minimum

2. Lihat notifikasi orange popup di sudut kanan atas:

    ```
    ⚠️ Stock Low Alert!
    Produk Jadi ID 1 stok sisa 3 unit.
    Minimum threshold: 10 unit.
    ```

3. Notifikasi auto-dismiss setelah 5 detik

4. DevTools Console menampilkan:
    ```
    StockLowAlert received: {product_id: 1, current_stock: 3, item_type: "product"}
    ```

---

### Test 5: Sales Analytics Updates ✅

1. Buka halaman POS:
    - http://localhost:8000/penjualan

2. Di panel kanan, perhatikan kartu statistik:
    - Total Unit
    - Total Transaksi
    - Omzet Hari Ini
    - Produk Terlaris

3. Buat beberapa transaksi penjualan

4. Lihat statistik **berubah real-time** tanpa reload:
    - Total Unit bertambah
    - Total Transaksi bertambah
    - Omzet Hari Ini naik
    - Daftar produk terlaris ter-update

5. DevTools Console menampilkan:
    ```
    SalesAnalyticsUpdated received: {
      total_sales: 85,
      total_transactions: 12,
      total_revenue: 1250000,
      top_products: [{id: 1, name: "...", qty: 20}, ...]
    }
    ```

---

## 🔍 Troubleshooting

### ❌ Notifikasi tidak muncul

**Solusi:**

- Pastikan Reverb service jalan di port 8081
- Reload halaman browser (F5)
- Cek konsol browser (F12) untuk error

### ❌ Data tidak ter-update real-time

**Kemungkinan:**

1. Reverb tidak jalan → `npm run build` + restart Reverb
2. Frontend JS tidak ter-reload → Hard refresh (Ctrl+Shift+R)
3. Database trigger tidak jalan → Pastikan SaleController.php sudah ter-update

### ❌ "Echo is not defined" error

**Solusi:**

- Tunggu halaman fully load sebelum melakukan transaksi
- Reload halaman dan tunggu 2 detik

### ❌ Reverse proxy / nginx / production

**Solusi:**

- Ubah `VITE_REVERB_SCHEME=https` (jika pakai SSL)
- Ubah `REVERB_HOST=your-domain.com`
- Setup reverse proxy untuk WebSocket ke port 8081

---

## 📊 Events Broadcast Summary

| Event                       | Channel                   | Trigger                     | Data                                                         |
| --------------------------- | ------------------------- | --------------------------- | ------------------------------------------------------------ |
| **ProductSold**             | stock-updates             | Transaksi penjualan selesai | product_id, qty_deducted                                     |
| **MaterialUsed**            | material-stock-updates    | Admin pakai bahan           | material_id, quantity_used                                   |
| **ProductionStatusUpdated** | production-status-updates | Status batch berubah        | production_id, status, product_id, qty_produced              |
| **StockLowAlert**           | stock-alerts              | Stok ≤ minimum              | product_id, current_stock, minimum_threshold, item_type      |
| **SalesAnalyticsUpdated**   | sales-analytics           | Transaksi penjualan         | total_sales, total_transactions, total_revenue, top_products |

---

## 🎯 Next Steps (Opsional)

1. **Setup Production Reverb**
    - Gunakan Supervisor untuk manage process
    - Setup SSL certificate untuk wss://

2. **Add Redis**
    - Untuk horizontal scaling multi-server
    - Better performance untuk high-traffic

3. **Dashboard Enhancement**
    - Real-time chart untuk penjualan
    - Live material usage tracking
    - Production efficiency metrics

4. **Mobile Notification**
    - Push notification saat stok rendah
    - SMS alert untuk admin

5. **Historical Analytics**
    - Save real-time data ke table
    - Create charts untuk trend analysis

---

## 📝 Notes

- ✅ Semua events menggunakan **ShouldBroadcastNow** (instant, tanpa queue)
- ✅ Database driver untuk broadcast (tanpa Redis dependency)
- ✅ Listeners ter-update otomatis untuk multiple tabs
- ✅ Channel berbeda untuk setiap jenis data (scalability)
- ✅ Timestamp included di setiap event (debugging)

**Last Updated:** 13 Mei 2026
**Version:** 1.0.0 - Full Implementation
