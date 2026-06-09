@echo off
title Running SAHAYU Keuangan UMKM (Local)
echo =======================================================
echo     MENJALANKAN SAHAYU KEUANGAN UMKM DI LOCAL
echo =======================================================
echo.
echo Membuka aplikasi di browser default Anda...
start http://127.0.0.1:8000
echo.
echo Memulai server lokal, Vite, dan queue runner...
echo Tekan Ctrl+C di jendela ini jika ingin menghentikan server.
echo.
composer dev
pause
