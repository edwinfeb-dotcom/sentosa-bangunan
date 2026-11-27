# Buku Panduan Uji Coba Semua Menu — Sistem ERP Sentosa Bangunan

Panduan ini disusun khusus sebagai **panduan praktik langkah demi langkah (*step-by-step*)** supaya Anda bisa mencoba sendiri seluruh menu, tombol, alur transaksi, hingga hasil laporan di dalam sistem sebelum mempresentasikannya ke Owner.

> **Tips Uji Coba:** Ikuti urutan **Bagian 0 sampai Bagian 10** secara berurutan karena data yang Anda buat di bagian awal (seperti produk, pelanggan, saldo kas) akan langsung dipakai di transaksi pada bagian berikutnya.

---

## BAGIAN 0 — Persiapan & Daftar Akun Login

### 0.1 Menjalankan Sistem di Lokal
1. Buka **Laragon**, klik **Start All** (pastikan layanan **MySQL** menyala).
2. Jika server Laravel belum jalan, buka **Terminal** di Laragon (`cd c:\laragon\www\sentosa-bangunan`) lalu jalankan:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8001
   ```
3. Buka browser (Chrome/Edge) ke alamat:
   - Halaman Login Staff/Owner: **`http://127.0.0.1:8001/login`**
   - Halaman Katalog Publik Pembeli: **`http://127.0.0.1:8001/katalog`**

### 0.2 Akun Uji Coba (Seeder Bawaan)
Semua akun bawaan memakai password: **`password`**

| Peran (Role) | Email Login | Password | Halaman Awal Setelah Login |
| :--- | :--- | :--- | :--- |
| **Owner** | `owner@sentosa.test` | `password` | `/dashboard` (Akses penuh seluruh menu + Akses & Sandi) |
| **Admin** | `admin@sentosa.test` | `password` | `/dashboard` (Akses penuh kecuali menu Akses & Sandi) |
| **Kasir** | `kasir@sentosa.test` | `password` | `/dashboard` (Kasir, Katalog/Pesanan Online, Master terbatas, Gudang terbatas, Laporan) |
| **Gudang** | `gudang@sentosa.test` | `password` | `/dashboard` (Dashboard Gudang, Pesanan Online, Stok, PO, Nota Supplier, Retur, Pengiriman) |
| **Driver** | `driver@sentosa.test` | `password` | `/dashboard` (Dashboard Driver, Jadwal Pengiriman, Master Armada, Aset, Absensi, Laporan Harian) |
| **Karyawan** | `karyawan@sentosa.test` | `password` | `/hr/attendance` (Absensi & Scan QR, Laporan Harian, QR Web Pelanggan) |
| **Pembeli (Online)** | `pembeli@sentosa.test` | `password` | `/katalog` (Login melalui `/masuk`) |

> **Mulai Bagian 1 sampai Bagian 9 dengan login sebagai `owner@sentosa.test`**, lalu di **Bagian 10** Anda bisa mencoba login sebagai role lain untuk melihat perbedaan tampilan menu masing-masing.

---

## BAGIAN 1 — Pengaturan Sistem & Manajemen Akses (Menu *Pengaturan*)

Sebelum bertransaksi, rapikan identitas toko dan cek pengaturan akses:

### 1.1 Profil Toko & Rekening (`/setting/store`)
- **Cara buka:** Sidebar kiri → bagian **Pengaturan** → **Profil Toko & Rekening**.
- **Cara mencoba:**
  1. Periksa data identitas toko (Nama Toko, Alamat Resmi, Telepon/WA, Catatan Transfer). Contoh alamat resmi yang sudah disiapkan: `Jl. Raya Km 6, Desa Amboyo Inti, Kecamatan Ngabang, Kabupaten Landak, Kalimantan Barat 79357`.
  2. Cek toggle **`show_customer_accounts`** dan daftar rekening bank untuk katalog. Jika diaktifkan, nomor rekening akan tampil agar pembeli online bisa melihat tujuan transfer.
  3. Klik tombol simpan dan pastikan muncul notifikasi toast sukses di layar.

### 1.2 Master Form Ceklis (`/setting/checklists`)
- **Cara buka:** Sidebar → **Pengaturan** → **Master Form Ceklis**.
- **Cara mencoba:**
  1. Lihat daftar item ceklis kebersihan/operasional toko (misalnya ceklis buka toko, kebersihan area semen, kunci gudang).
  2. Tambahkan 1 item ceklis uji coba (atau edit item yang ada), lalu simpan. Item ini nantinya akan otomatis muncul di Form **Laporan Harian** (Bagian 7).

### 1.3 Akses & Sandi (`/setting/access`) — *Khusus Owner*
- **Cara buka:** Sidebar → **Pengaturan** → **Akses & Sandi**.
- **Cara mencoba:**
  1. Klik **`Buat Akun Staff / Kasir Baru`** → isi nama, email, role (`kasir`, `gudang`, `driver`, `karyawan`, atau `admin`), dan sandi → simpan.
  2. Pada salah satu baris user, coba tombol **`Ubah Akses / Reset Sandi`** untuk mengubah peran atau mereset kata sandi.
  3. Coba tombol **`Nonaktifkan`** lalu **`Aktifkan`** kembali untuk melihat cara memblokir akses karyawan yang sudah keluar tanpa menghapus riwayat datanya.

### 1.4 Backup & Export (`/setting/backup`)
- **Cara buka:** Sidebar → **Pengaturan** → **Backup & Export**.
- **Cara mencoba:**
  1. Klik **`Buat Snapshot Backup Sekarang`** untuk menguji pembuatan file cadangan database manual (di sistem juga berjalan jadwal otomatis pukul `00:00`).
  2. Pada daftar snapshot yang muncul, coba tombol **Unduh** atau **Hapus** (untuk snapshot uji coba).
  3. Coba juga tombol **`Download Backup SQL`** dan **`Export Laporan Harian`**.

---

## BAGIAN 2 — Master Data & Import Excel (Menu *Master Data*)

Semua menu master berada di kelompok **Master Data** pada sidebar.

### 2.1 Kategori (`/master/categories`)
- **Cara buka:** Sidebar → **Master Data** → **Kategori**.
- **Cara mencoba:**
  1. Tambahkan kategori baru (contoh: `Semen & Mortar` atau `Cat Tembok`) atau edit kategori yang sudah ada.
  2. Pastikan kategori baru langsung tersedia di dropdown produk.

### 2.2 Produk & Barcode (`/master/products`)
- **Cara buka:** Sidebar → **Master Data** → **Produk**.
- **Cara mencoba fitur manual & filter:**
  1. Coba kotak filter **`Cari Nama atau Barcode`**, dropdown **`Kategori`**, **`Supplier`**, dan **`Kondisi Stok`** (`Stok Kritis` / `Stok Habis`).
  2. Klik **`Tambah Produk`**: isi nama (misal `Semen Uji Coba 50Kg`), kategori, satuan utama (`Sak`), satuan eceran jika ada (misal `Kg` dengan konversi `50`), harga modal, harga jual, dan stok awal (misal `20`). Simpan.
  3. Centang atau pilih produk lalu klik **`Cetak Label`** untuk melihat tampilan cetak label barcode siap tempel di rak toko.
- **Cara mencoba Export & Import Excel Produk:**
  1. Klik **`Export Excel`** untuk mengunduh daftar barang saat ini.
  2. Klik **`Import Excel`**:
     - Anda bisa mengunduh template terlebih dahulu, atau langsung mengunggah file Excel dari folder sampel Pak Nelson (`UPDATE HARGA BARANG (2).xlsx` di `c:\Users\SJC.COM\Documents\dokumen dari pak nelson`).
     - **Penting:** Jika mengunggah file `UPDATE HARGA BARANG`, centang opsi **`Hanya baca sheet kategori & BARANG`** agar sheet lain tidak terbaca sebagai barang.
- **Cara mencoba Import Gambar Produk:**
  1. Klik **`Import Gambar (Excel)`**.
  2. Di modal tersebut tersedia dua tab: tab unggah **Excel Bergambar** dan tab **Pilih Banyak Foto Sekaligus** (batch upload foto yang namanya disesuaikan dengan kode/nama produk).

### 2.3 Supplier (`/master/suppliers`)
- **Cara buka:** Sidebar → **Master Data** → **Supplier**.
- **Cara mencoba:**
  1. Tambahkan supplier baru (misal `PT Distributor Jaya`, No. HP, Alamat).
  2. Klik detail supplier untuk melihat riwayat Purchase Order (PO) dan nota bon ke supplier tersebut.

### 2.4 Pelanggan, Deposit & Bon Pelanggan (`/master/customers`)
- **Cara buka:** Sidebar → **Master Data** → **Pelanggan**.
- **Cara mencoba:**
  1. Perhatikan 2 kartu KPI di atas halaman: **`Total Deposit Pelanggan`** dan **`Total Sisa Bon`**.
  2. Klik tambah pelanggan baru (misal `Bapak Budi`, No. WA `081234567890`).
  3. Uji coba **`Top Up Deposit`**: klik tombol `Top Up Deposit` pada `Bapak Budi`, masukkan nominal misal `500000`, pilih dompet kas penerima, lalu simpan. Saldo deposit `Bapak Budi` akan bertambah Rp500.000.
  4. Uji coba **`Import Excel (Bon Pelanggan)`**: klik tombol tersebut jika ingin mencoba impor data bon lama dari file `BON PELANGGAN.xlsm (1).xlsx`.
  5. Uji coba **`Catat / Lunasi Bon`**:
     - Klik ikon mata (**Detail**) pada pelanggan → klik **`Catat / Lunasi Bon`**.
     - Tersedia 2 pilihan: **`Tambah Bon / Piutang Baru`** dan **`Pelunasan / Bayar Bon (Masuk Kas)`**.
  6. Di daftar pelanggan, aktifkan filter **`Punya Sisa Bon`** lalu klik **`Follow-Up WA`** pada pelanggan yang masih punya hutang untuk melihat pesan penagihan WhatsApp yang terisi otomatis.

---

## BAGIAN 3 — Kasir (POS), Diskon, Bon & Tutup Shift (Menu *Kasir & Toko Online*)

Ini adalah menu inti operasional kasir harian.

### 3.1 Membuka Sesi / Shift Kasir (`/kasir/sesi` → `/kasir`)
- **Cara buka:** Sidebar → **Kasir & Toko Online** → **Kasir (POS)**.
- **Cara mencoba:**
  1. Jika belum ada sesi kasir aktif, sistem akan meminta Anda **Buka Sesi Kasir**. Masukkan modal awal laci kasir (misal `300000`) lalu buka sesi.
  2. Setelah masuk layar POS:
     - Tekan tombol **`F2`** di keyboard untuk langsung memfokuskan kursor ke kolom scan barcode / cari barang.
     - Klik salah satu **kategori pills** di atas daftar produk untuk menyaring barang.
     - Klik barang (misal `Semen Uji Coba 50Kg`) agar masuk ke keranjang kanan.
     - Di baris keranjang, coba ubah pilihan **satuan utama vs satuan eceran** (jika produk punya satuan eceran) serta ubah jumlah (Qty).

### 3.2 Uji Coba Variasi Pembayaran & Diskon di POS
1. **Transaksi Tunai + Diskon Manual:**
   - Pilih Pelanggan (opsional, atau biarkan Umum).
   - Isi **`Potongan / Diskon Manual (Rp)`** (misal `5000`) dan **`Catatan Diskon`** (misal `Diskon langganan`).
   - Pilih Metode Bayar **`Tunai (Cash)`**, masukkan uang diterima, lalu simpan transaksi.
   - Halaman Struk (`/kasir/struk/...`) akan terbuka: cek tombol **`Cetak Struk`**.
2. **Transaksi Bon / Piutang Pelanggan (Kasus Pelanggan Ngebon):**
   - Kembali ke layar POS, masukkan barang senilai misal Rp60.000 (contoh 1 sak semen).
   - **Wajib pilih Pelanggan** di dropdown (misal `Bapak Budi`).
   - Pilih Metode Bayar **`Bon / Piutang Pelanggan`** → simpan.
   - **Cara Melunasi Bon Besoknya:**
     1. Buka menu **Master Data** → **Pelanggan**.
     2. Filter **`Punya Sisa Bon`** atau cari `Bapak Budi` → klik ikon mata (**Detail**).
     3. Klik tombol **`Catat / Lunasi Bon`** → pilih mode **`Pelunasan / Bayar Bon (Masuk Kas)`** → isi nominal `60000` (atau sesuai sisa bon) dan pilih dompet masuk kas → simpan.
     4. Saldo sisa bon `Bapak Budi` berubah menjadi **Rp0 (Lunas)** dan uang Rp60.000 langsung tercatat masuk ke **Buku Kas**!
3. **Metode Bayar Lain yang Bisa Dicoba di POS:**
   - **`Transfer Bank`** (pastikan memilih akun dompet bank).
   - **`Potong Saldo Deposit`** (gunakan `Bapak Budi` yang sudah di-top up Rp500.000 di Bagian 2.4).
   - **`Potong Gaji`** + centang **`Diskon Karyawan`** jika pembeli adalah karyawan internal.
4. **Uji Void / Retur Struk & Struk Shift:**
   - Di halaman struk yang baru dicetak, coba klik **`Batalkan / Retur Struk Ini`** untuk melihat stok kembali ke gudang dan pembatalan tercatat rapi.
   - Di header layar POS, klik **`Struk Shift`** untuk melihat ringkasan omzet shift berjalan.
   - Akhiri dengan klik **`Tutup Kasir`**, masukkan uang fisik di laci untuk melihat selisih kas (lebih/kurang/pas) dan penutupan sesi.

---

## BAGIAN 4 — QR Web Pelanggan, Katalog Publik & Pesanan Online

Di bagian ini Anda mencoba alur pembeli scan QR dari HP hingga pesanan diproses di toko.

### 4.1 QR Web Pelanggan (`/qr-katalog`)
- **Cara buka:** Sidebar → **Kasir & Toko Online** → **QR Web Pelanggan**.
- **Cara mencoba:**
  1. Halaman akan menampilkan QR Code toko yang mengarah ke `/katalog` (halaman katalog produk publik).
  2. Anda bisa klik cetak poster QR untuk ditempel di meja kasir/toko.

### 4.2 Simulasi Pembeli di Katalog Online (`/katalog`)
- **Cara buka:** Buka tab browser baru (atau mode Incognito agar tidak bentrok sesi staff) ke `http://127.0.0.1:8001/katalog`.
- **Cara mencoba:**
  1. **Langsung ke Katalog:** Pastikan Anda melihat daftar produk, harga, stok, dan kategori tanpa harus login terlebih dahulu.
  2. *(Opsional)* Klik **`Masuk`** (`/masuk`) lalu login memakai akun pembeli (`pembeli@sentosa.test` / `password`), atau daftar akun baru lewat **`/daftar`**, lalu cek halaman profil pembeli di **`/akun`**.
  3. Tambahkan 1–2 barang ke keranjang belanja, lalu lanjut ke Checkout (`/katalog/checkout`).
  4. Isi nama pembeli, nomor WhatsApp, alamat kirim/ambil di toko, dan metode pembayaran, lalu kirim pesanan.
  5. Anda akan diarahkan ke halaman sukses (`/katalog/sukses/{order_code}`) berisi kode pesanan. Catat kode tersebut lalu coba buka **`http://127.0.0.1:8001/lacak-pesanan`** untuk melacak status pesanan layaknya pelanggan.

### 4.3 Menangani Pesanan Masuk di Sistem Toko (`/public-orders`)
- **Cara buka:** Kembali ke tab Staff/Owner → tunggu maksimal 20 detik atau buka Sidebar → **Kasir & Toko Online** → **Pesanan Online**.
- **Cara mencoba:**
  1. Perhatikan badge jumlah pesanan masuk otomatis dan popup notifikasi suara/toast kustom di pojok layar.
  2. Di baris pesanan baru tadi, coba tombol-tombol aksi berikut secara berurutan:
     - Ikon **Mata (Detail)**: melihat item pesanan dan catatan pembeli.
     - Ikon **Printer (`Cetak Nota Pesanan`)**: mencetak nota untuk disiapkan di gudang/kasir.
     - Tombol **`WA`**: membuka template WhatsApp konfirmasi langsung ke nomor pembeli.
     - Ikon **Truk (`Tugaskan Armada`)**: jika pesanan perlu diantar oleh armada toko.
     - Tombol alur status: klik **`Kemas`** → lalu **`Kirim`** → lalu **`Selesai`** (atau tombol **`X`** untuk membatalkan pesanan).
  3. Cek kembali halaman `/lacak-pesanan` di tab pembeli: status pesanan ikut berubah secara real-time!

---

## BAGIAN 5 — Gudang, Pembelian & Stok (Menu *Gudang & Pembelian*)

### 5.1 Purchase Order (`/inventory/po`)
- **Cara buka:** Sidebar → **Gudang & Pembelian** → **Purchase Order**.
- **Cara mencoba:**
  1. Klik **`Buat PO Baru`**, pilih Supplier, tambahkan barang (misal `Semen Uji Coba 50Kg` sebanyak `10` sak), lalu simpan.
  2. Coba tombol **`Print PO`** untuk cetak surat pesanan ke supplier, dan **`Edit PO`** bila ada revisi.
  3. Uji penerimaan barang:
     - **Terima Sebagian:** Klik **`Detail`**, lalu gunakan form **`Terima Parsial`** (misal baru datang `4` dari `10` sak) → status PO menjadi diterima sebagian dan stok bertambah `4`.
     - **Terima Penuh:** Klik **`Terima Penuh`** untuk melunasi sisa pengiriman (`6` sak) → stok bertambah lagi `6` dan otomatis bisa membentuk tagihan/nota supplier.
  4. Buat satu PO dummy lagi lalu klik **`Batalkan PO`** untuk menguji pembatalan.

### 5.2 Nota Supplier / Bon (`/inventory/invoices`)
- **Cara buka:** Sidebar → **Gudang & Pembelian** → **Nota Supplier (Bon)**.
- **Cara mencoba:**
  1. Gunakan filter status: **`Belum Bayar`**, **`Sebagian`**, **`Lunas`**.
  2. Klik **`Catat Nota Manual`** jika ada pembelian langsung tanpa PO sebelumnya.
  3. Klik tombol **`Bayar`** pada salah satu nota yang belum lunas, pilih dompet kas/bank pembayar, isi nominal (bisa cicil sebagian atau langsung lunas), lalu simpan. Saldo dompet akan berkurang dan status nota berubah.

### 5.3 Retur Supplier (`/inventory/returns`)
- **Cara buka:** Sidebar → **Gudang & Pembelian** → **Retur Supplier**.
- **Cara mencoba:**
  1. Catat retur barang rusak/cacat ke supplier (misal retur `1` sak semen).
  2. Pastikan stok produk otomatis berkurang dan tercatat di riwayat mutasi stok.

### 5.4 Kartu Stok & Opname (`/inventory/stock-mutations`)
- **Cara buka:** Sidebar → **Gudang & Pembelian** → **Kartu Stok & Opname**.
- **Cara mencoba:**
  1. Perhatikan seluruh riwayat masuk-keluar barang (dari Penjualan POS, Void POS, Penerimaan PO, Retur, dll) tercatat otomatis di tabel mutasi.
  2. Klik **`Input Stock Opname`** untuk menyesuaikan stok 1 barang (misal stok fisik beda karena pecah/hilang).
  3. Klik **`Opname Massal`** untuk melihat tampilan penyesuaian banyak barang sekaligus dalam satu layar.
  4. Klik **`Export Kartu Stok`** untuk mengunduh laporan Excel/CSV kartu stok.

---

## BAGIAN 6 — Keuangan & Laporan Penjualan

### 6.1 Dompet & Kas (`/finance/wallets`)
- **Cara buka:** Sidebar → **Keuangan** → **Dompet & Kas**.
- **Cara mencoba:**
  1. Lihat daftar dompet/rekening toko (Kas Laci, Bank BCA/Mandiri, dll) beserta saldonya masing-masing.
  2. Tambah dompet baru atau edit dompet yang ada.
  3. Uji fitur **Transfer Antar Dompet** (misal setor tunai Rp200.000 dari Kas Toko ke Bank) untuk melihat mutasi keluar-masuk antar akun tetap seimbang.

### 6.2 Buku Kas (`/finance/cash-ledger`)
- **Cara buka:** Sidebar → **Keuangan** → **Buku Kas**.
- **Cara mencoba:**
  1. Klik **`Catat Kas / Pengeluaran`** (tambah transaksi kas masuk/keluar manual, misal biaya listrik, bensin operasional, atau pembelian ATK).
  2. Cek bahwa pembayaran bon pelanggan (Bagian 3.2) dan pembayaran nota supplier (Bagian 5.2) otomatis masuk di sini.
  3. Coba tombol **Export** dan tombol **Hapus** (untuk entri manual uji coba).

### 6.3 Laporan Arus Kas (`/finance/cash-flow`)
- **Cara buka:** Sidebar → **Keuangan** → **Laporan Arus Kas**.
- **Cara mencoba:**
  1. Pilih rentang tanggal untuk melihat ringkasan uang masuk, uang keluar, dan arus kas bersih.

### 6.4 Laporan Penjualan (`/reports/sales`)
- **Cara buka:** Sidebar → **Utama & Strategi** → **Laporan Penjualan**.
- **Cara mencoba:**
  1. Filter berdasarkan tanggal/periode untuk melihat omzet penjualan, HPP/profit kotor, metode pembayaran, dan daftar transaksi.
  2. Coba tombol **Export** untuk mengunduh laporannya.

---

## BAGIAN 7 — Laporan Harian 8 Langkah & Strategi Target (Menu *Utama & Strategi*)

### 7.1 Dashboard Utama (`/dashboard`)
- **Cara buka:** Sidebar → **Utama & Strategi** → **Dashboard**.
- **Cara mencoba:** Periksa angka omzet hari ini, profit, grafik tren, notifikasi stok menipis, bon jatuh tempo, dan ringkasan cepat sistem.

### 7.2 Strategi & Target Bisnis (`/setting/targets`)
- **Cara buka:** Sidebar → **Utama & Strategi** → **Strategi & Target**.
- **Cara mencoba:**
  1. Pilih **Bulan** dan **Tahun** berjalan.
  2. Isi **`Target Omzet`**, **`Target Laba Kotor (Profit)`**, **`Plafon Biaya Operasional`**, **`Hari Kerja`**, **`Kategori Fokus`**, dan **`Catatan Rencana Strategi`**.
  3. Simpan lalu lihat hasilnya di panel **Simulasi Pace Harian** (berapa target omzet yang harus dicapai per hari berdasarkan sisa hari kerja) serta analisis **Top 5 Barang Terlaris vs Mengendap**.

### 7.3 Laporan Harian 8 Langkah (`/daily-reports`)
- **Cara buka:** Sidebar → **Utama & Strategi** → **Laporan Harian**.
- **Cara mencoba:**
  1. Klik buat **Laporan Harian** baru (`/daily-reports/create`).
  2. Ikuti **Form 8 Langkah** dari awal sampai akhir.
  3. Pada **Langkah 2**, klik tombol **`Isi Otomatis dari POS`** agar sistem menarik data penjualan tunai/transfer hari ini langsung dari modul kasir.
  4. Centang item ceklis (yang diatur di Bagian 1.2), unggah/lengkapi isian yang diperlukan, lalu kirim/simpan laporan (sistem menghasilkan **kode verifikasi 8 karakter**).
  5. Di halaman daftar laporan (`/daily-reports`), sebagai Owner/Admin coba klik **`Verifikasi`** untuk mengesahkan laporan harian. Anda juga bisa mencoba hapus laporan yang masih berstatus draft (`submitted`) atau ekspor laporannya.

---

## BAGIAN 8 — SDM, Absensi QR & Payroll (Menu *SDM & Payroll*)

### 8.1 Data Karyawan (`/hr/employees`)
- **Cara buka:** Sidebar → **SDM & Payroll** → **Data Karyawan**.
- **Cara mencoba:**
  1. Tambahkan karyawan baru atau edit data karyawan yang sudah ada (jabatan, kontak, status aktif).
  2. Tersedia juga tombol **Import Excel** jika data karyawan berasal dari spreadsheet.

### 8.2 Absensi & Scan QR (`/hr/attendance`)
- **Cara buka:** Sidebar → **SDM & Payroll** → **Absensi & Scan QR**.
- **Cara mencoba:**
  1. Uji tombol **`Clock In`** dan **`Clock Out`** langsung pada halaman.
  2. Uji **`Buka Kiosk QR`**: layar akan menampilkan QR absensi dengan mode **`POSTER QR PERMANEN`** maupun mode QR Harian untuk ditempel di pintu masuk/meja kasir agar karyawan scan saat datang & pulang.
  3. Uji tombol ubah status manual (`mark-status`, misal Izin/Sakit), lalu coba **`Sync Alpha`** untuk menandai karyawan yang tidak absen menjadi Alpha otomatis.
  4. Klik **`Export Bulanan`** untuk mengunduh rekap kehadiran bulanan.

### 8.3 Payroll & Bonus (`/hr/payroll`)
- **Cara buka:** Sidebar → **SDM & Payroll** → **Payroll & Bonus**.
- **Cara mencoba:**
  1. Klik **`Atur Gaji Full & Potongan Bolos`** untuk menentukan gaji pokok/tunjangan dan nominal potongan per hari tidak hadir/alpha per karyawan.
  2. Klik **`Generate Gaji Bulanan`**: sistem otomatis menghitung gaji bersih berdasarkan absensi (termasuk potongan bolos dan kasbon/potong gaji dari POS jika ada!).
  3. Klik tombol **`Bonus`** pada salah satu karyawan untuk menambah bonus kinerja/lembur.
  4. Klik **`Bayar`** (pilih dompet pembayar) untuk menandai gaji sudah dibayarkan dan saldonya terpotong di kas/bank.
  5. Klik **`Slip Gaji`** untuk melihat/mencetak slip gaji karyawan tersebut, lalu coba **`Export CSV`**.

---

## BAGIAN 9 — Armada, Pengiriman, Aset & Proyek (Menu *Armada, Aset & Proyek*)

### 9.1 Armada (`/fleet/vehicles`)
- **Cara buka:** Sidebar → **Armada, Aset & Proyek** → **Armada**.
- **Cara mencoba:**
  1. Tambah atau edit kendaraan (misal Dump Truck atau Pick-up Strada): isi plat nomor, jenis, kapasitas, **`Tgl Servis Terakhir`**, **`Jadwal Servis Berikut`**, **`Jatuh Tempo Pajak/KIR`**, dan **`Catatan Servis`**.
  2. Simpan dan lihat pengingat jadwal servis/pajak yang tercatat.

### 9.2 Perjalanan Armada & Surat Jalan (`/fleet/jobs`)
- **Cara buka:** Sidebar → **Armada, Aset & Proyek** → **Perjalanan Armada**.
- **Cara mencoba:**
  1. Klik **`Import Excel Angkutan`** jika ingin menguji impor dari file `ANGKUTAN (2).xlsx` (membaca sheet `HARGA`, `DAM`, dan `STRADA`).
  2. Cek tabel **`Daftar Tarif Ongkos Angkutan`**: tambahkan 1 tarif rute baru (misal rute `Ngabang - Amboyo`, jenis kendaraan/kapasitas) lalu simpan.
  3. Klik **`Buat Perjalanan`**:
     - Pilih armada dan driver.
     - Pada **`Pilih Tarif Rute Tersimpan`**, pilih tarif yang tadi dibuat dan pilih **`Kapasitas Angkutan`** (`4m³` / `6m³`) — ongkos/biaya akan terisi otomatis!
     - Simpan perjalanan.
  4. Pada baris perjalanan tersebut, coba ikon **Printer (`Surat Jalan`)** untuk cetak surat jalan resmi, lalu tombol **`Edit`**, **`Selesai`** (saat armada kembali), dan **`Hapus`** (jika batal).

### 9.3 Register Aset (`/fleet/assets`)
- **Cara buka:** Sidebar → **Armada, Aset & Proyek** → **Register Aset**.
- **Cara mencoba:**
  1. Tambahkan data aset toko/gudang (misal forklift, komputer kasir, genset, alat bor), tanggal beli, nilai aset, dan kondisinya.

### 9.4 Proyek & Material (`/project/projects`)
- **Cara buka:** Sidebar → **Armada, Aset & Proyek** → **Proyek & Material**.
- **Cara mencoba:**
  1. Klik **`Import Excel Proyek`** jika ingin mencoba impor dari `MASTER PROJECT SENTOSA (1).xlsx`, atau buat proyek baru (misal `Proyek Pembangunan Ruko A`, nilai kontrak/estimasi, nama klien).
  2. Klik tombol **`Material`** pada proyek tersebut untuk masuk ke detail proyek:
     - Uji **`Catat Pengambilan Material`**: pilih barang toko (misal semen/besi) yang diambil proyek → stok toko berkurang dan masuk ke tagihan material proyek.
     - Uji **`Retur Sisa Material`**: kembalikan sisa barang proyek yang tidak terpakai ke stok gudang.
     - Uji **`Catat Pembayaran / Termin Proyek`**: catat pembayaran termin DP atau pelunasan dari kontraktor/klien proyek.
     - Klik **`Invoice Tagihan Proyek`** untuk mencetak invoice tagihan lengkap proyek tersebut.

---

## BAGIAN 10 — Uji Coba Tampilan Khusus Per Role (Untuk Demo ke Owner)

Supaya Anda makin lancar menjelaskan pembagian hak akses saat bertemu Owner, lakukan **Logout** lalu coba login sebentar dengan akun berikut dan perhatikan sidebar-nya yang otomatis menyederhana sesuai tugas masing-masing:

1. **Login sebagai `kasir@sentosa.test` (Password: `password`):**
   - Sidebar hanya menampilkan menu kerja kasir: `Dashboard`, `Kasir (POS)`, `Pesanan Online`, `QR Web Pelanggan`, `Kategori`, `Produk & Barcode`, `Pelanggan & Poin`, `Purchase Order`, `Nota Supplier (Bon)`, `Retur Supplier`, `Kartu Stok & Opname`, `Laporan Penjualan`, `Laporan Harian`, `Buku Kas Shift`, dan `Absensi & Scan QR`.
   - Menu rahasia Owner seperti `Payroll & Bonus`, `Dompet & Kas`, `Strategi & Target`, dan `Akses & Sandi` **otomatis tersembunyi**.
2. **Login sebagai `gudang@sentosa.test` (Password: `password`):**
   - Masuk ke `Dashboard Gudang`, lalu hanya melihat menu operasional gudang: `Pesanan Online`, `Kartu Stok & Opname`, `Purchase Order`, `Nota Supplier (Bon)`, `Retur Supplier`, `Jadwal Pengiriman`, `Absensi & Scan QR`, dan `Laporan Harian`.
3. **Login sebagai `driver@sentosa.test` (Password: `password`):**
   - Masuk ke `Dashboard Driver`, dengan menu: `Jadwal Pengiriman`, `Master Armada`, `Aset & Peralatan`, `Absensi & Scan QR`, dan `Laporan Harian`.
4. **Login sebagai `karyawan@sentosa.test` (Password: `password`):**
   - Langsung masuk ke `Beranda Karyawan` (`/hr/attendance`) dan hanya memiliki akses `Absensi & Scan QR`, `Laporan Harian`, serta `QR Web Pelanggan`.

---

## Ringkasan Urutan Presentasi Singkat ke Owner (Opsional)
Jika nanti waktu presentasi terbatas, Anda bisa mendemokan 5 alur paling berdampak dalam 10–15 menit:
1. **QR Pelanggan (`/qr-katalog`) → Katalog Online (`/katalog`) → Pesanan Online (`/public-orders`)** (menunjukkan pelanggan tinggal scan QR langsung belanja dan kasir dapat notifikasi popup otomatis).
2. **Kasir POS (`/kasir`) → Transaksi Bon → Pelunasan Bon di `/master/customers`** (menunjukkan kasus kasir sehari-hari beres dalam beberapa klik dan otomatis masuk Buku Kas).
3. **Import Excel Produk/Bon/Angkutan/Proyek** (menunjukkan data lama Pak Nelson di Excel tidak hilang dan bisa langsung masuk ke sistem).
4. **Laporan Harian 8 Langkah (`/daily-reports`) dengan tombol `Isi Otomatis dari POS` + `Strategi & Target` (`/setting/targets`)**.
5. **Absensi QR (`/hr/attendance`) → Payroll Otomatis (`/hr/payroll`)** (menunjukkan rekap absen dan potongan/kasbon langsung terhitung ke slip gaji).
