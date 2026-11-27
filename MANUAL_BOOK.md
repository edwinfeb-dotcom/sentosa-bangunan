# BUKU PANDUAN PENGGUNA (MANUAL BOOK)
## Sistem ERP, Kasir POS, Armada & Katalog Online — Sentosa Bangunan

**Alamat Resmi Toko:**  
Jl. Raya Km 6, Desa Amboyo Inti, Kecamatan Ngabang, Kabupaten Landak, Kalimantan Barat 79357  
**Zona Waktu Operasional:** `Asia/Pontianak` (WIB)

---

## Daftar Isi
1. [Cara Masuk (Login) & Daftar Akun Bawaan](#1-cara-masuk-login--daftar-akun-bawaan)
2. [Hak Akses Menu Setiap Pengguna (Role Matrix)](#2-hak-akses-menu-setiap-pengguna-role-matrix)
3. [Panduan Kerja Harian per Role (SOP Singkat)](#3-panduan-kerja-harian-per-role-sop-singkat)
4. [Penjelasan Lengkap Semua Menu & Kegunaannya](#4-penjelasan-lengkap-semua-menu--kegunaannya)
   - [4.1 Utama & Strategi](#41-utama--strategi)
   - [4.2 Kasir (POS) & Toko Online](#42-kasir-pos--toko-online)
   - [4.3 Master Data](#43-master-data)
   - [4.4 Gudang & Pembelian (Inventori)](#44-gudang--pembelian-inventori)
   - [4.5 Keuangan](#45-keuangan)
   - [4.6 SDM & Payroll](#46-sdm--payroll)
   - [4.7 Armada, Aset & Proyek](#47-armada-aset--proyek)
   - [4.8 Pengaturan Sistem](#48-pengaturan-sistem)
   - [4.9 Katalog Publik & Portal Pembeli](#49-katalog-publik--portal-pembeli)
5. [Panduan Import Data Excel Lama Toko](#5-panduan-import-data-excel-lama-toko)
6. [Pemecahan Masalah Umum (FAQ)](#6-pemecahan-masalah-umum-faq)

---

## 1. Cara Masuk (Login) & Daftar Akun Bawaan

### A. Login Staf Toko (Owner, Admin, Kasir, Gudang, Driver, Karyawan)
1. Buka alamat login staf: **`/login`** (contoh lokal: `http://127.0.0.1:8001/login`).
2. Masukkan **Email** dan **Password**.
3. Setelah masuk, sistem otomatis mengarahkan ke ruang kerja (*workspace*) sesuai jabatan masing-masing:
   - **Owner / Admin:** Dashboard Utama Strategi & Keuangan.
   - **Kasir:** Beranda Khusus Kasir & Sesi Laci.
   - **Gudang:** Workspace Operasional Gudang & Stok.
   - **Driver:** Workspace Driver & Pengiriman Armada.
   - **Karyawan:** Halaman Absensi & Scan QR.

> **Catatan Keamanan:** Saat sistem mulai dipakai resmi (*Go-Live*), Owner wajib segera mengganti semua sandi bawaan di menu **Pengaturan → Akses & Sandi**. Akun yang dinonaktifkan oleh Owner akan otomatis ditolak saat mencoba login.

### B. Akun Bawaan Awal (Seeder)
| Role | Email Default | Password Default | Ruang Kerja Tujuan |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@sentosa.test` | `password` | `/dashboard` (Full Akses) |
| **Admin Toko** | `admin@sentosa.test` | `password` | `/dashboard` (Operasional & Laporan) |
| **Kasir** | `kasir@sentosa.test` | `password` | `/dashboard` (Beranda Kasir) |
| **Gudang** | `gudang@sentosa.test` | `password` | `/dashboard` (Workspace Gudang) |
| **Driver** | `driver@sentosa.test` | `password` | `/dashboard` (Workspace Driver) |
| **Karyawan** | `karyawan@sentosa.test` | `password` | `/hr/attendance` (Absensi & QR) |
| **Pembeli (Katalog)** | `pembeli@sentosa.test` | `password` | `/akun` (Melalui `/masuk`, bukan `/login`) |

> Karyawan baru hasil import Excel laporan harian memiliki sandi awal `password123` dan dapat diubah lewat menu **Data Karyawan** atau **Akses & Sandi**.

### C. Akses Pembeli / Pelanggan Umum (Katalog Publik)
- Buka **`/katalog`** (atau scan poster **QR Web Pelanggan** di toko).
- Pembeli **tidak wajib login** untuk melihat barang atau belanja; cukup isi **Nama, Nomor WA, dan Alamat** saat checkout.
- Jika ingin menyimpan keranjang antar perangkat, melacak riwayat pesanan, dan melihat **Bintang Gold / Saldo Deposit / Sisa Bon**, pembeli dapat daftar di **`/daftar`** atau login di **`/masuk`**.

---

## 2. Hak Akses Menu Setiap Pengguna (Role Matrix)

| Menu / Modul | Owner | Admin | Kasir | Gudang | Driver | Karyawan |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| Dashboard Analitik & Profit | ✓ | ✓ | — | — | — | — |
| Dashboard Kasir / Gudang / Driver | — | — | ✓ (Kasir) | ✓ (Gudang) | ✓ (Driver) | — |
| Strategi & Target Bisnis | ✓ | ✓ | — | — | — | — |
| Laporan Harian (8 Langkah) | ✓ (+Verifikasi) | ✓ (+Verifikasi) | ✓ | ✓ | ✓ | ✓ |
| Laporan Penjualan Detail | ✓ | ✓ | ✓ | — | — | — |
| Kasir (POS) & Buka/Tutup Shift | ✓ | ✓ | ✓ | — | — | — |
| Pesanan Online (Notifikasi & Proses) | ✓ | ✓ | ✓ | ✓ | — | — |
| QR Web Pelanggan | ✓ | ✓ | ✓ | — | — | ✓ |
| Kategori, Produk & Barcode | ✓ | ✓ | ✓ | ✓ (Stok/PO) | — | — |
| Master Pelanggan, Deposit & Bon | ✓ | ✓ | ✓ | — | — | — |
| Master Supplier | ✓ | ✓ | — | ✓ | — | — |
| PO, Nota Supplier, Retur & Kartu Stok | ✓ | ✓ | ✓ | ✓ | — | — |
| Dompet & Arus Kas | ✓ | ✓ | — | — | — | — |
| Buku Kas | ✓ | ✓ | ✓ | — | — | — |
| Absensi & Scan QR Pribadi | ✓ (+Kiosk/Rekap) | ✓ (+Kiosk/Rekap) | ✓ | ✓ | ✓ | ✓ |
| Data Karyawan & Payroll | ✓ | ✓ | — | — | — | — |
| Armada, Perjalanan, Aset & Proyek | ✓ | ✓ | — | ✓ | ✓ (Armada) | — |
| Profil Toko & Rekening, Ceklis, Backup | ✓ | ✓ | — | — | — | — |
| Akses & Sandi (Buat/Nonaktifkan Staf) | ✓ | — | — | — | — | — |

---

## 3. Panduan Kerja Harian per Role (SOP Singkat)

### 👔 Owner
1. **Pagi hari:** Buka **Dashboard** untuk melihat target omzet bulanan, pace harian, stok kritis, dan pesanan online baru.
2. **Atur Strategi:** Buka **Strategi & Target** setiap awal bulan untuk menetapkan target omzet, target profit, plafon biaya, kategori fokus, dan catatan rencana toko.
3. **Kontrol Rekening & Toko:** Jika ada rekening transfer bermasalah, masuk ke **Pengaturan → Profil Toko & Rekening** untuk menonaktifkan rekening lama atau mengganti nomor rekening baru secara instan.
4. **Akhir Bulan:** Buka **SDM & Payroll → Payroll & Bonus**, klik **Generate Gaji**, cek potongan bolos (alpha) dan potongan kasbon, tambahkan bonus bila ada, lalu klik **Bayar**.
5. **Keamanan Data:** Cek **Pengaturan → Backup & Export** untuk memastikan snapshot backup SQL harian tersedia dan unduh salinannya secara berkala.

### 🧑‍💼 Admin / Kepala Toko
1. Pantau **Pesanan Online**, verifikasi **Laporan Harian** dari kasir menggunakan kode verifikasi unik, serta kelola **Purchase Order (PO)** ke supplier.
2. Catat pembayaran hutang supplier di **Nota Supplier (Bon)** agar saldo dompet dan arus kas akurat.
3. Kelola absensi tim (**Sync Alpha**, edit status Izin/Sakit) di **Absensi & Scan QR**.

### 🧾 Kasir
1. **Awal Shift:** Scan QR absen masuk di menu **Absensi & Scan QR**, lalu buka **Kasir (POS) → Buka Sesi Kasir** dan masukkan jumlah uang modal laci awal.
2. **Melayani Pembeli di Toko:**
   - Scan barcode atau ketik nama barang (`F2` fokus pencarian), klik produk ke keranjang.
   - Jika pembeli membeli eceran (misal `Kg` atau `Meter`), ubah pilihan satuan di sebelah item keranjang.
   - Pilih Pelanggan (opsional, wajib jika pakai **Deposit** atau **Bon Pelanggan**).
   - Isi **Potongan Manual** jika ada diskon khusus, pilih **Metode Bayar**, lalu klik **Proses & Cetak Struk**.
   - Jika terjadi kesalahan transaksi, buka struknya (atau lewat tombol **Struk Shift** di layar kasir) lalu klik **Batalkan / Retur Struk Ini**.
3. **Pantau Pesanan Online:** Badge notifikasi pada menu **Pesanan Online** diperbarui otomatis tiap 20 detik. Klik **Kemas → Kirim → Selesai**, cetak nota pesanan, atau hubungi pembeli lewat tombol WhatsApp.
4. **Akhir Shift:** Tutup laci melalui **Tutup Kasir**, hitung uang fisik laci, cocokan dengan sistem, lalu isi **Laporan Harian** (gunakan tombol **Isi Otomatis dari POS** agar angka penjualan langsung terisi).

### 📦 Staff Gudang
1. Cek **Dashboard Gudang** untuk daftar barang dengan stok menipis dan PO yang sedang menunggu kedatangan barang.
2. Saat barang datang dari supplier, buka **Purchase Order**, klik **Detail** untuk terima sebagian (*parsial*) atau klik **Terima Penuh**. Stok dan nota hutang otomatis tercatat.
3. Siapkan barang untuk **Pesanan Online** atau pengambilan material **Proyek**, dan lakukan penyesuaian stok fisik melalui **Kartu Stok & Opname** (satuan maupun **Opname Massal**).

### 🚚 Driver
1. Cek tugas perjalanan di **Dashboard Driver** atau **Perjalanan Armada**.
2. Sebelum berangkat, cetak **Surat Jalan** (ikon printer) untuk dibawa dan ditandatangani penerima barang.
3. Setelah pengiriman selesai, klik **Selesai** agar waktu kembali tercatat dan komisi perjalanan terkunci.

### 👷 Karyawan
1. Saat datang dan pulang kerja, buka menu **Absensi & Scan QR** dari HP dan arahkan kamera ke **Poster QR Absensi** di toko (atau gunakan tombol manual sesuai kebijakan toko).
2. Jika bertugas piket, isi **Laporan Harian** dan serahkan kode verifikasi ke Admin/Owner.

---

## 4. Penjelasan Lengkap Semua Menu & Kegunaannya

### 4.1 Utama & Strategi
* **Dashboard (`/dashboard`):**
  - *Owner/Admin:* Menampilkan ringkasan transaksi, omzet, margin kotor/bersih, kas tunai vs transfer, belanja hari ini, grafik tren omzet 7 hari, progress target bulanan + pace harian, daftar stok kritis, pelanggan poin tertinggi, dan saldo dompet aktif. Tersedia filter *Hari Ini*, *Bulan Ini*, dan *Semua*.
  - *Kasir / Gudang / Driver:* Otomatis menampilkan beranda tugas masing-masing role tanpa data sensitif owner.
* **Strategi & Target (`/setting/targets`):**
  - Mengatur target omzet, target laba kotor, plafon maksimal biaya operasional, jumlah hari kerja, kategori fokus, dan catatan rencana strategi bulanan.
  - Menampilkan **Simulasi Pace Harian** (sisa target dibagi sisa hari kerja) serta analisis **Top 5 Barang Terlaris** vs **Top 5 Barang Mengendap** (stok > 0 tidak laku dalam 30 hari).
* **Laporan Harian (`/daily-reports`):**
  - Form 8 langkah checklist operasional toko (Piket, Laporan Kasir dengan tombol **Isi Otomatis dari POS**, Barang Datang, Cek Aset, Absensi, 3 Foto dokumentasi, Ceklis Sore, dan Kirim).
  - Menghasilkan kode verifikasi unik 8 karakter untuk validasi Admin/Owner. Tersedia **Export Excel** dan hapus draft sebelum diverifikasi.
* **Laporan Penjualan (`/reports/sales`):**
  - Analisa omzet, HPP (otomatis memperhitungkan modal eceran vs grosir), dan laba kotor per produk & kategori dalam rentang tanggal tertentu, dilengkapi rekap metode bayar, ringkasan pesanan online, **Cetak**, dan **Export CSV/Excel**.

---

### 4.2 Kasir (POS) & Toko Online
* **Kasir (POS) (`/kasir/sesi` & `/kasir`):**
  - Manajemen shift laci kasir (*Buka Sesi* & *Tutup Kasir* dengan perhitungan selisih uang fisik + kas penjualan tunai + mutasi laci manual).
  - Layar transaksi cepat (barcode `F2`, filter kategori, pilih satuan utama/eceran, diskon karyawan, diskon manual + catatan, pembayaran **Tunai**, **Transfer Bank**, **Potong Deposit**, **Bon / Piutang Pelanggan**, **Potong Gaji Karyawan**, tombol **Struk Shift**, cetak struk thermal, serta fitur **Batalkan / Retur Struk**).
* **Pesanan Online (`/public-orders`):**
  - Daftar seluruh order yang masuk dari website katalog pelanggan (dengan polling notifikasi popup otomatis tiap 20 detik).
  - Fitur: filter status/pencarian, tombol **WA Pembeli**, **Cetak Nota Pesanan**, lihat detail item, tombol **Tugaskan Armada** untuk pesan-antar, ubah alur status (*Menunggu Konfirmasi → Dikemas → Dikirim → Selesai*), serta **Batalkan** (yang otomatis mengembalikan stok & kas bila sempat terpotong).
* **QR Web Pelanggan (`/qr-katalog`):**
  - Poster QR siap cetak untuk ditempel di meja kasir/dinding toko agar pembeli tinggal scan untuk membuka katalog online Sentosa Bangunan dari HP mereka.

---

### 4.3 Master Data
* **Kategori (`/master/categories`):**
  - Mengelola kode (misal `01`–`16`) dan nama pengelompokan kategori barang toko.
* **Produk (`/master/products`):**
  - Mengelola daftar barang lengkap (foto, barcode, satuan besar & eceran + rasio konversi, harga beli, kode margin `K5`/`K50`, harga jual otomatis/manual, gudang/rak, stok minimum/maksimum, supplier).
  - Fitur tambahan: filter berdasarkan **Kategori**, **Supplier**, dan **Kondisi Stok** (Kritis / Habis); **Cetak Label Barcode** (`/master/products/barcode/print`); **Export Excel**; **Import Excel** (mendukung multi-template sekaligus foto tertanam atau upload banyak foto JPG/PNG batch sekaligus).
* **Supplier (`/master/suppliers`):**
  - Mengelola data pemasok (nama, telepon, alamat). Halaman **Detail Supplier** menampilkan daftar barang dari supplier tersebut, riwayat PO, serta total sisa hutang dagang yang belum lunas.
* **Pelanggan (`/master/customers`):**
  - Mengelola pelanggan (*Reguler*, *Toko*, *Kontraktor*), poin loyalitas **Bintang Gold**, **Saldo Deposit** (uang titipan), dan **Sisa Bon** (piutang pelanggan).
  - Terdapat kartu ringkasan Total Deposit & Total Piutang Beredar, filter **Punya Sisa Bon**, tombol **Follow-Up WA**, **Import Excel (Bon Pelanggan)**, serta halaman Detail Pelanggan berisi mutasi deposit, mutasi bon, modal **Top Up Deposit**, dan **Catat / Lunasi Bon** (masuk ke Buku Kas).

---

### 4.4 Gudang & Pembelian (Inventori)
* **Purchase Order (`/inventory/po`):**
  - Membuat surat pesanan pembelian barang ke supplier. Mendukung **Edit PO** (selama belum diterima), **Batalkan PO**, **Cetak PO** untuk dikirim ke supplier, serta **Terima Barang** (baik **Terima Penuh** maupun **Terima Parsial** per item).
  - Saat barang diterima, **stok barang langsung bertambah** di Kartu Stok dan **Nota Supplier (Bon)** tercipta otomatis.
* **Nota Supplier (Bon) (`/inventory/invoices`):**
  - Memantau tagihan/hutang dagang ke supplier, filter status (*Belum Bayar / Sebagian / Lunas*), mencatat **Nota Manual** di luar PO, dan mencatat pembayaran cicilan/pelunasan (otomatis mengurangi kas di dompet terkait).
* **Retur Supplier (`/inventory/returns`):**
  - Mencatat pengembalian barang rusak/salah kirim ke supplier beserta penyelesaiannya (*Potong Hutang Nota*, *Refund Uang Tunai/Transfer ke Buku Kas*, atau *Tukar Barang*). Stok otomatis berkurang saat retur dibuat.
* **Kartu Stok & Opname (`/inventory/stock-mutations`):**
  - Log lengkap riwayat pergerakan setiap unit masuk & keluar lengkap dengan petugas, waktu, dan keterangannya.
  - Menampilkan badge barang stok menipis, tombol **Input Stock Opname** satuan, **Opname Massal** untuk banyak produk sekaligus, dan **Export Kartu Stok (CSV)**.

---

### 4.5 Keuangan
* **Dompet & Kas (`/finance/wallets`):**
  - Mengelola dompet kas fisik (*Laci Kas*, *Brankas*), rekening bank (*BCA, BRI, Mandiri, dll.*), dan *E-Wallet*, melihat saldo berjalan masing-masing, melakukan **Transfer Antar Dompet**, serta mengatur **atas nama** dan apakah rekening tampil di Katalog Pembeli.
* **Buku Kas (`/finance/cash-ledger`):**
  - Catatan kronologis seluruh uang masuk & keluar. Transaksi penjualan POS, pesanan online, pelunasan bon, pembayaran supplier, termin proyek, dan gaji masuk otomatis ke sini. Admin/Kasir dapat menambah catatan biaya operasional manual (listrik, bensin, makan, dll.), menghapus entri manual yang salah ketik, **Cetak**, dan **Export CSV**.
* **Laporan Arus Kas (`/finance/cash-flow`):**
  - Rekapitulasi kas masuk & keluar yang dikelompokkan berdasarkan kategori transaksi (Penjualan POS, Pesanan Online, Pelunasan Bon, Pembayaran Proyek, Pembelian Supplier, Retur, Gaji, Operasional, Transfer Kas) beserta surplus/defisit bersih dalam periode terpilih.

---

### 4.6 SDM & Payroll
* **Absensi & Scan QR (`/hr/attendance`):**
  - Halaman karyawan untuk scan QR absen masuk/pulang (plus opsi fallback manual).
  - Bagi Owner/Admin: tersedia tombol **Buka Kiosk QR** (`/hr/attendance/qr-kiosk` dengan mode **QR Permanen** siap tempel atau **QR Harian** dinamis), tabel absensi harian tim, form **Ubah Status (Hadir/Izin/Sakit/Alpha)**, tombol **Sync Alpha** (menandai karyawan yang belum absen menjadi Alpha agar ikut terpotong gaji), serta **Rekap Bulanan & Export Excel Absensi**.
* **Data Karyawan (`/hr/employees`):**
  - Mengelola biodata karyawan, foto, role jabatan, tanggal masuk, diskon khusus karyawan di POS, gaji harian, dan status (*Aktif / Training / Nonaktif*). Dilengkapi **Import Excel / Laporan Harian (.xlsm)**.
* **Payroll & Bonus (`/hr/payroll`):**
  - Panel pengaturan **Gaji Full Bulanan**, **Potongan per Hari Bolos (Alpha)**, dan **Gaji Harian**.
  - Generate slip gaji bulanan (menghitung hadir, lembur, potongan alpha, potongan kasbon aktif, dan bonus manual), cetak **Slip Gaji**, **Export CSV Payroll**, dan tombol **Bayar Gaji** (otomatis mengunci lunas kasbon karyawan yang dipotong dan mencatat pengeluaran ke **Buku Kas**).

---

### 4.7 Armada, Aset & Proyek
* **Armada (`/fleet/vehicles`):**
  - Master kendaraan toko (*Internal*, *Komersial*, *Vendor Luar*), nomor polisi, status aktif, serta pencatatan **Tanggal Servis Terakhir**, **Jadwal Servis Berikutnya**, **Jatuh Tempo Pajak/KIR**, dan catatan perawatan.
* **Perjalanan Armada (`/fleet/jobs`):**
  - Manajemen trip pengiriman (menugaskan kendaraan & driver, ceklis kelayakan sebelum jalan, pendapatan trip, biaya operasional BBM/tol, dan **Komisi Driver otomatis 10%**).
  - Tersedia dropdown **Tarif Rute Tersimpan (4m³ / 6m³)** yang otomatis mengisi tujuan & ongkos, tombol **Edit / Hapus Perjalanan**, cetak **Surat Jalan Armada**, **Tandai Selesai**, serta **Import Excel Angkutan** dan tabel kelola **Daftar Tarif Ongkos**.
* **Register Aset (`/fleet/assets`):**
  - Pendataan inventaris aset toko (alat berat, mesin, rak, komputer, printer, dll.), kode aset, nilai perolehan, dan kondisi (*Baik / Perlu Perbaikan / Rusak*).
* **Proyek & Material (`/project/projects`):**
  - Mengelola proyek kontraktor maupun proyek pembangunan internal owner beserta pagu anggarannya.
  - Di dalam detail proyek: catat **Pengambilan Material** (memotong stok barang otomatis), **Retur Sisa Material** (mengembalikan stok barang), **Catat Pembayaran / Termin Proyek** (masuk ke **Buku Kas**), serta cetak **Rekap Material** maupun **Invoice Tagihan Proyek**. Dilengkapi **Import Excel Proyek (`MASTER`)**.

---

### 4.8 Pengaturan Sistem
* **Profil Toko & Rekening (`/setting/store`):**
  - Mengatur Nama Toko, Alamat Resmi, Nomor Telepon/WA toko (otomatis tampil di katalog, struk POS, dan surat jalan), catatan instruksi transfer bank, opsi tampil/sembunyikan tombol akun pembeli di katalog publik, serta kontrol cepat daftar rekening bank aktif yang muncul saat pembeli checkout online.
* **Master Form Ceklis (`/setting/checklists`):**
  - Mengatur item daftar tugas (*Piket*, *Cek Aset*, *Barang Datang*, *Ceklis Sore*) yang muncul di form Laporan Harian 8 Langkah.
* **Akses & Sandi (`/setting/access`):** *(Khusus Owner)*
  - Membuat akun login baru, mengubah role/hak akses, reset password staf, dan **Aktif / Nonaktifkan Status Login** akun staf.
* **Backup & Export (`/setting/backup`):**
  - Mengunduh Excel Laporan Harian, mengunduh langsung dump **Database SQL**, membuat **Snapshot Backup Server Sekarang**, dan mengelola daftar hasil **Backup Otomatis Harian (Pukul 00:00 — Retensi 14 Hari)**.

---

### 4.9 Katalog Publik & Portal Pembeli
* **Katalog Produk (`/katalog`):** Etalase online untuk pembeli dengan pencarian, filter kategori, urutan harga, stok tersedia, dan keranjang belanja lokal.
* **Checkout Publik (`/katalog/checkout` & `/katalog/sukses/{kode}`):** Form pengisian Nama, No. WA, Alamat, metode kirim (*Ambil di Toko* atau *Antar Armada* beserta ongkos kirim otomatis), pilihan metode bayar (*Transfer* dengan rekening bank toko yang dinamis atau *Cash/COD*), serta kode order unik.
* **Lacak Pesanan (`/lacak-pesanan`):** Pembeli memasukkan kode pesanan (atau nomor WA) untuk memantau status terkini (*Menunggu Konfirmasi → Sedang Dikemas → Sedang Dikirim → Selesai*).
* **Akun Pembeli (`/daftar`, `/masuk`, `/akun`):** Portal pembeli untuk melihat jumlah order, **Bintang Gold**, **Saldo Deposit**, **Sisa Bon**, riwayat pesanan, dan ubah profil.

---

## 5. Panduan Import Data Excel Lama Toko

| Menu Tujuan | File Excel yang Didukung | Cara Singkat |
| :--- | :--- | :--- |
| **Produk → Import Excel** | `UPDATE HARGA BARANG (.xlsx)`, Template 19-Kolom, Template ERP, & Excel Bergambar | Pilih file → centang **"Hanya baca sheet `kategori` & `BARANG`"** bila memakai file master Owner agar baris contoh tidak ikut masuk → Upload. |
| **Pelanggan → Import Excel** | `BON PELANGGAN (.xlsx / .xlsm)` | Upload langsung; sistem membaca sheet `master` (`Nama Pelanggan`, `ALAMAT`, `NOMOR HP`, `deposit`, `SISA BON`). |
| **Perjalanan Armada → Import Excel** | `ANGKUTAN (.xlsx)` | Upload untuk menarik tarif rute 4m³/6m³ (sheet `HARGA`) & daftar armada (`DAM`, `STRADA`, `K1/K2/CARRY`). Centang *"Sekaligus import riwayat perjalanan"* jika perlu menarik log trip lama. |
| **Proyek → Import Excel** | `MASTER PROJECT SENTOSA (.xlsx)` | Upload; sistem hanya mem-parsing sheet `MASTER` (`NAMA`, `SUMBER`, `DEPO`, `DEBET`, `SALDO`) sehingga tetap ringan. |
| **Data Karyawan → Import Excel** | `LAPORAN HARIAN SENTOSA (.xlsm)` | Upload file `.xlsm`; sistem membaca sheet `MANAGEMENT` untuk nama karyawan aktif/nonaktif, estimasi upah, dan kasbon berjalan. |

---

## 6. Pemecahan Masalah Umum (FAQ)

1. **Kamera HP gagal terbuka saat Scan QR Absen?**
   - Pastikan website diakses menggunakan **`https://`** (SSL / Cloudflare Tunnel), bukan `http://` IP lokal (seperti `http://192.168.x.x`), karena browser Chrome/Safari di HP memblokir kamera pada koneksi non-HTTPS. Sebagai alternatif sementara, gunakan input manual / token jika diizinkan admin.
2. **Rekening bank di sistem error atau mau diganti nomor tujuannya?**
   - Login sebagai **Owner/Admin**, masuk ke **Pengaturan → Profil Toko & Rekening** (atau **Keuangan → Dompet & Kas**), nonaktifkan centang *"Tampilkan di Katalog"* atau ubah nomor rekeningnya, lalu simpan. Perubahan langsung berlaku di halaman checkout pembeli.
3. **Salah input transaksi atau item di Kasir (POS)?**
   - Klik **Struk Shift** di kanan atas layar kasir (atau buka detail struknya), klik **Batalkan / Retur Struk Ini**, isi alasan pembatalan, lalu simpan. Stok barang serta kas/deposit/bon pelanggan otomatis dikembalikan.
4. **Sudah import file Excel tapi muncul pesan *"Tidak ada data yang terbaca"*?**
   - Pastikan file di-upload di menu yang tepat (misal file bon pelanggan di menu **Pelanggan**, bukan di menu **Produk**) dan baris judul kolom (header) pada Excel tidak terhapus. Data toko yang sudah ada dijamin aman karena setiap proses import memakai transaksi database (*rollback* otomatis jika gagal).
5. **Backup otomatis jam 00:00 tidak muncul file barunya di server toko?**
   - Pastikan *scheduler* OS di server (`cron` di Linux atau *Task Scheduler* di Windows) sudah aktif memanggil `php artisan schedule:run` setiap 1 menit, dan komputer server menyala pada pukul `00:00`. Anda juga dapat menekan tombol **Buat Snapshot Backup Sekarang** kapan saja di **Pengaturan → Backup & Export**.
