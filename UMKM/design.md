# SAHAYU - Design System & UI Guidelines

## 1. Design Philosophy: "Emerald Soft-Touch"
Tema visual SAHAYU menggabungkan estetika **Lite Claymorphism** dengan kehangatan dunia kuliner UMKM. Desain ini bertujuan untuk menonjolkan kesan *tactile* (empuk dan nyaman disentuh seperti adonan) sekaligus memberikan rasa aman dan profesional dalam mengelola keuangan (dilambangkan dengan aksen hijau Zamrud/Emerald). 

Fokus utama desain adalah **Aksesibilitas (User-Friendly)**: menghindari sudut tajam, mengurangi kontras warna yang menyilaukan mata, dan memperjelas hierarki data untuk pemilik UMKM.

---

## 2. Global Color Palette (Tailwind CSS)

### A. Backgrounds & Surfaces (Warm & Clean)
Hindari penggunaan putih murni (`#FFFFFF`) atau abu-abu dingin. Gunakan warna krem/batu bersuhu hangat untuk mengurangi ketegangan mata.
- **Main App Background:** `bg-stone-50` (Warm Off-White)
- **Card / Modal Surface:** `bg-white` (Dilengkapi dengan soft shadow)
- **Table Row Alternate:** `even:bg-stone-50/50`

### B. Typography (High Legibility)
Hindari hitam pekat murni (`#000000`). Gunakan abu-abu batu gelap.
- **Primary Text (Headings/Data):** `text-stone-800`
- **Secondary Text (Subtitles/Muted):** `text-stone-500`
- **Disabled/Placeholder:** `text-stone-400`

### C. Brand & Accent Colors (Financial Growth & Action)
- **Primary / Growth (Uang Masuk/Profit):** `emerald-500` (#10b981)
- **Primary Hover:** `emerald-600`
- **Secondary Action (Warning/Pending):** `amber-500` (#f59e0b)
- **Destructive / Expense (Uang Keluar/Hapus):** `rose-500` (#f43f5e)

---

## 3. Shapes, Borders & Shadows (Lite Claymorphism)

Konsep utama Claymorphism adalah membuat elemen UI terlihat seperti mengapung lembut tanpa garis batas (border) yang kaku.

### A. Border Radius (Kelengkungan)
- **Cards, Modals, & Main Containers:** `rounded-2xl` atau `rounded-3xl`
- **Buttons & Input Fields:** `rounded-xl` atau `rounded-full` (Pill shape)
- **Badges / Status Tags:** `rounded-full`

### B. Shadows (Bayangan Mengapung)
Ganti bayangan bawaan yang kasar dengan bayangan berwarna yang menyebar (diffuse).
- **Cards & Modals:** `shadow-lg shadow-emerald-900/5` (Bayangan sangat tipis dengan sedikit semburat hijau)
- **Primary Buttons (Elevated):** `shadow-md shadow-emerald-500/20`
- **Hover State (Lift Effect):** `-translate-y-0.5 shadow-xl shadow-emerald-900/10` (Elemen seakan terangkat saat disentuh/di-hover)

### C. Borders
- Secara umum, hindari penggunaan border tebal.
- Jika border mutlak diperlukan untuk input form: `border border-stone-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20`.

---

## 4. Typography & Spacing Scale

### A. Data Tables
- Header tabel harus memiliki `bg-stone-100/50 text-stone-600 font-semibold uppercase tracking-wider text-sm`.
- Berikan jarak (*padding*) yang luas antar baris agar pengguna tidak salah baca: minimal `py-4 px-6` per sel.

### B. Buttons
- Tombol aksi utama harus terasa tebal dan mudah diklik di layar sentuh (HP).
- **Primary Button Class:** `bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 px-6 rounded-xl shadow-md shadow-emerald-500/20 transition-all duration-200`
- **Secondary Button Class:** `bg-white text-stone-700 border border-stone-200 hover:bg-stone-50 font-semibold py-2 px-4 rounded-xl shadow-sm transition-all duration-200`

### C. Contextual Tooltips
- Setiap metrik akuntansi yang rumit (seperti HPP, Accrual vs Cash) wajib memiliki ikon `(i)` dengan tooltip yang menjelaskan metrik tersebut dalam bahasa awam.

---
*Documented for SAHAYU Project Architecture.*
