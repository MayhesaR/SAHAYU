# Panduan Test Real-Time Broadcasting SAHAYU

## Status Setup

✅ **Implementasi sudah selesai** - Event broadcast, listener Echo, dan DOM hooks sudah aktif.

---

## Cara Menjalankan Test

### 1. Mulai 3 Service Sekaligus

Buka **3 terminal PowerShell / CMD** berbeda dan jalankan masing-masing:

#### Terminal 1: Reverb WebSocket Server

```bash
php artisan reverb:start --host=127.0.0.1 --port=8081
```

**Tanda sukses:**

```
INFO  Starting server on 127.0.0.1:8081 (localhost).
```

#### Terminal 2: Vite Frontend Dev Server

```bash
npm run dev
```

**Tanda sukses:**

```
VITE v7.3.2  ready in X ms
  ➜  Local:   http://localhost:5174/
```

#### Terminal 3: Laravel Development Server

```bash
php artisan serve --port=8000
```

**Tanda sukses:**

```
INFO  Server running on [http://127.0.0.1:8000].
```

---

## Test Method 1: Via Tinker (CLI)

Buka **terminal baru keempat**:

```bash
php artisan tinker
```

Lalu copy-paste command ini di Tinker prompt:

```php
event(new \App\Events\ProductSold(productId: 1, qtyDeducted: 5));
```

**Expected:**

- Output: `= []`
- Reversible broadcast ke channel `stock-updates`

---

## Test Method 2: Via Browser UI (Recommended)

### Setup

1. Buka **2 tab browser** di `http://localhost:8000`
    - **Tab A:** Manajemen Produk Jadi (`/produk`)
    - **Tab B:** Pencatatan Penjualan (`/penjualan`)

2. Di **Tab A**, buka **DevTools → Console** untuk lihat log real-time.

### Langkah Test

1. **Tab B:** Isi form penjualan:
    - Pilih produk dari dropdown (contoh: Produk dengan ID = 1)
    - Qty: 3 unit
    - Metode: Tunai
    - Klik tombol **"Catat Transaksi"**

2. **Tab A (DevTools Console):** Amati output:

    ```
    ProductSold received: {product_id: 1, qty_deducted: 3}
    ```

3. **Tab A (Tabel Stok):** Lihat kolom stok produk berkurang **otomatis tanpa reload**.

---

## File-File yang Terlibat

| File                                        | Fungsi                                                |
| ------------------------------------------- | ----------------------------------------------------- |
| `app/Events/ProductSold.php`                | Event class yang broadcast ke channel `stock-updates` |
| `app/Http/Controllers/SaleController.php`   | Dispatch event setelah transaksi                      |
| `resources/js/echo.js`                      | Setup Reverb connection                               |
| `resources/js/app.js`                       | Listener yang update DOM stok                         |
| `resources/views/ManajemenProduk.blade.php` | Tabel stok dengan atribut `data-product-stock`        |
| `resources/views/layouts/app.blade.php`     | Layout yang load JS Vite                              |

---

## Troubleshooting

### ❌ "Failed to listen on 'tcp://0.0.0.0:8080'"

**Solusi:** Gunakan port lain (sudah done di `.env` pakai port 8081)

### ❌ Console log "ProductSold received" tidak muncul

**Kemungkinan:**

1. Reverb belum jalan → cek Terminal 1
2. Vite belum rebuild → cek Terminal 2
3. JavaScript belum load di browser → reload halaman
4. Event tidak ter-dispatch → cek di `SaleController.php` apakah `event()` dipanggil

**Fix:**

```bash
# Terminal 2
npm run build

# Browser
F5 / Ctrl+Shift+R (hard refresh)
```

### ❌ DevTools error "Echo is not defined"

**Solusi:** Echo hanya ready setelah halaman sepenuhnya load. Tunggu 2 detik sebelum kirim event.

---

## Environment Variables (Sudah Dikonfigurasi)

```env
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
REVERB_APP_ID=941417
REVERB_APP_KEY=7rvabq8psc1y9iikkpbe
REVERB_APP_SECRET=b99bwpvgtwhe6qswi2cu
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http
VITE_REVERB_APP_KEY=7rvabq8psc1y9iikkpbe
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

---

## Hasil yang Diharapkan ✅

**Dari POS → Stock Dashboard Update Realtime**

1. Stok berkurang **tanpa refresh halaman**
2. Console menampilkan event yang diterima
3. Perubahan stok **instant** di semua tab yang terbuka

---

## Next Steps (Optional)

Untuk production:

1. Setup process manager (Supervisor) untuk Reverb
2. Gunakan SSL (wss://)
3. Add Redis untuk horizontal scaling Reverb
4. Setup rate limiting untuk broadcast channel
