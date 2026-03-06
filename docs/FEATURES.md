# ✨ Dokumentasi Fitur Aplikasi

Dokumen ini menjelaskan setiap fitur yang tersedia dalam Sistem Informasi Manajemen Inventaris, lengkap dengan deskripsi fungsi dan alur penggunaannya.

---

## Hak Akses per Fitur

| Fitur | Viewer | Staff | Manager | Admin |
|-------|:------:|:-----:|:-------:|:-----:|
| Dashboard | ✅ Lihat | ✅ Lihat | ✅ Lihat | ✅ Lihat |
| Manajemen Barang | ✅ Lihat | ✅ Tambah | ✅ Tambah/Edit | ✅ Full |
| Manajemen Kategori | ✅ Lihat | ✅ Tambah | ✅ Tambah/Edit | ✅ Full |
| Manajemen Gudang | ✅ Lihat | ✅ Tambah | ✅ Tambah/Edit | ✅ Full |
| Manajemen Stok | ✅ Lihat | ✅ Lihat | ✅ Update/Transfer | ✅ Full |
| Manajemen Customer | ✅ Lihat | ✅ Tambah | ✅ Tambah/Edit | ✅ Full |
| Manajemen Karyawan | ✅ Lihat | ✅ Tambah | ✅ Tambah/Edit | ✅ Full |
| Manajemen Transaksi | ✅ Lihat | ✅ Buat order | ✅ Buat/Update status | ✅ Full |
| Laporan | ✅ Lihat | ✅ Lihat | ✅ Lihat/Export | ✅ Full |

---

## 1. Dashboard

### Deskripsi
Dashboard adalah halaman utama yang ditampilkan setelah pengguna berhasil login. Halaman ini menyajikan ringkasan kondisi inventaris secara menyeluruh dalam bentuk kartu KPI (Key Performance Indicators), grafik, dan tabel ringkas.

### Komponen Dashboard

**Kartu KPI Utama:**
- **Total Revenue** — Total pendapatan dari seluruh transaksi delivered
- **Total Order** — Jumlah keseluruhan transaksi yang tercatat
- **Total Produk** — Jumlah produk aktif dalam sistem
- **Total Customer** — Jumlah customer yang terdaftar

**Panel Status Order:**
- Menampilkan jumlah order berdasarkan status: Pending, Shipped, Canceled
- Memudahkan manajer memantau pipeline penjualan secara cepat

**Top 5 Produk Terlaris:**
- Daftar 5 produk dengan revenue tertinggi
- Menampilkan nama produk, kategori, jumlah unit terjual, dan total revenue

**Peringatan Stok Rendah:**
- Muncul otomatis jika ada produk dengan stok di bawah batas minimum
- Menampilkan nama produk, lokasi gudang, stok tersedia, dan batas minimum
- Berguna untuk perencanaan pengadaan barang

### Alur Penggunaan
1. Login ke sistem dengan akun apapun
2. Dashboard ditampilkan otomatis sebagai halaman pertama
3. Pantau kartu KPI untuk kondisi bisnis keseluruhan
4. Periksa panel stok rendah dan lakukan restocking jika diperlukan
5. Gunakan top produk sebagai referensi produk unggulan

---

## 2. Manajemen Barang (Produk)

### Deskripsi
Fitur untuk mengelola data produk/barang yang masuk dalam inventaris. Setiap produk memiliki SKU unik, kategori, harga modal, harga jual, dan informasi margin keuntungan.

### Fungsi Utama
- Melihat daftar seluruh produk dengan pagination
- Mencari produk berdasarkan nama atau kode SKU
- Filter produk berdasarkan kategori
- Menambahkan produk baru
- Mengedit data produk yang sudah ada
- Menonaktifkan atau menghapus produk

### Alur Penggunaan

**Menambah Produk Baru (Staff/Manager/Admin):**
1. Buka menu **Produk** di sidebar
2. Klik tombol **Tambah Produk**
3. Isi form: Nama Produk, SKU, Kategori, Harga Modal, Harga Jual, Deskripsi
4. Centang **Produk Aktif** jika produk langsung ingin diaktifkan
5. Klik **Simpan**
6. Produk baru muncul di daftar

**Mengedit Produk (Manager/Admin):**
1. Cari produk yang ingin diedit melalui kolom pencarian atau filter
2. Klik ikon pensil (✏️) pada baris produk
3. Ubah data yang diperlukan di form edit
4. Klik **Simpan**

**Menghapus Produk (Admin):**
1. Klik ikon tempat sampah (🗑️) pada baris produk
2. Konfirmasi penghapusan pada dialog konfirmasi
3. Produk dihapus secara soft-delete (tidak hilang dari database, hanya tidak aktif)

---

## 3. Manajemen Kategori

### Deskripsi
Fitur untuk mengelola pengelompokan produk berdasarkan kategori. Kategori membantu pengguna menavigasi dan memfilter data produk dengan lebih mudah.

### Fungsi Utama
- Melihat daftar seluruh kategori
- Menambah kategori baru
- Mengedit nama dan deskripsi kategori
- Mengaktifkan atau menonaktifkan kategori
- Menghapus kategori (jika tidak memiliki produk)

### Alur Penggunaan

**Menambah Kategori Baru:**
1. Buka menu **Kategori** di sidebar
2. Klik tombol **Tambah Kategori**
3. Isi Nama Kategori dan Deskripsi (opsional)
4. Centang **Aktif**
5. Klik **Simpan**
6. Slug URL-friendly dibuat otomatis dari nama kategori

> **Catatan:** Kategori yang telah memiliki produk tidak dapat dihapus untuk menjaga integritas data.

---

## 4. Manajemen Gudang

### Deskripsi
Fitur untuk mengelola data gudang tempat penyimpanan barang. Sistem mendukung multi-gudang sehingga stok setiap produk dapat dipantau per lokasi gudang.

### Fungsi Utama
- Melihat daftar gudang dalam tampilan kartu (card view)
- Menambah gudang baru
- Mengedit detail gudang (nama, alamat, kontak)
- Mengaktifkan atau menonaktifkan gudang
- Menghapus gudang

### Alur Penggunaan

**Menambah Gudang Baru:**
1. Buka menu **Gudang** di sidebar
2. Klik tombol **Tambah Gudang**
3. Isi form: Nama Gudang, Region, Negara, Kota, Alamat, Telepon
4. Centang **Gudang Aktif**
5. Klik **Simpan**

**Informasi yang Ditampilkan per Kartu Gudang:**
- Nama dan lokasi gudang (kota, negara)
- Alamat lengkap
- Status aktif/nonaktif
- Tombol Edit dan Hapus (sesuai hak akses)

---

## 5. Manajemen Stok (Inventaris)

### Deskripsi
Fitur inti yang menampilkan kondisi stok setiap produk di setiap gudang. Stok diperbarui secara otomatis ketika transaksi diproses, dan dapat diperbarui secara manual oleh Manager atau Admin.

### Fungsi Utama
- Melihat stok semua produk di semua gudang
- Filter stok berdasarkan gudang
- Menampilkan hanya produk dengan stok rendah
- Update stok manual (koreksi stok fisik)
- Transfer stok antar gudang

### Alur Penggunaan

**Memantau Stok:**
1. Buka menu **Inventaris** di sidebar
2. Gunakan dropdown filter **Gudang** untuk mempersempit tampilan
3. Centang **Stok Rendah Saja** untuk fokus pada produk yang perlu restocking
4. Kolom warna menunjukkan kondisi: **hijau** = normal, **amber** = stok rendah

**Update Stok Manual (Manager/Admin):**
1. Klik ikon pensil pada baris inventaris
2. Ubah nilai Qty Di Tangan, Min Stok, atau Max Stok
3. Klik **Simpan**

> Gunakan fitur ini untuk koreksi stok setelah stock opname fisik.

**Transfer Stok Antar Gudang (Manager/Admin):**
1. Klik tombol **Transfer Stok**
2. Pilih produk yang akan ditransfer
3. Pilih gudang asal (**Dari Gudang**) dan gudang tujuan (**Ke Gudang**)
4. Masukkan jumlah yang akan ditransfer
5. Klik **Transfer**
6. Stok gudang asal berkurang dan gudang tujuan bertambah secara otomatis

> **Validasi:** Sistem akan menolak transfer jika stok tersedia di gudang asal tidak mencukupi.

---

## 6. Barang Masuk

### Deskripsi
Pencatatan penambahan stok barang ke gudang. Dalam sistem ini, barang masuk dikelola melalui fitur **Update Stok Manual** pada halaman Inventaris, yang memungkinkan pengguna menambah nilai qty_on_hand secara langsung.

### Alur Barang Masuk

**Skenario: Penerimaan barang dari supplier**
1. Barang fisik tiba di gudang
2. Buka halaman **Inventaris**
3. Cari produk yang baru diterima
4. Klik ikon pensil untuk edit stok
5. Tambahkan jumlah barang yang diterima ke nilai qty_on_hand yang sudah ada
6. Simpan — stok otomatis terupdate

**Contoh:**
- Stok saat ini: 50 unit
- Barang masuk: 200 unit
- Nilai baru qty_on_hand: 250 unit

---

## 7. Barang Keluar

### Deskripsi
Pengurangan stok barang terjadi secara **otomatis** ketika status transaksi/order diubah menjadi `shipped`. Ini memastikan stok selalu mencerminkan barang yang benar-benar telah dikirim keluar.

### Alur Otomatis Pengurangan Stok

```
Order dibuat (pending)
    │  Stok belum berkurang
    ▼
Status → processing
    │  Stok belum berkurang
    ▼
Status → shipped          ← Stok berkurang otomatis di sini
    │  qty_on_hand -= quantity setiap item order
    ▼
Status → delivered
    │  Stok sudah berkurang, transaksi selesai
    ▼
(JIKA) Status → canceled  ← Stok dikembalikan otomatis jika sebelumnya "shipped"
         qty_on_hand += quantity setiap item order
```

---

## 8. Manajemen Customer

### Deskripsi
Fitur untuk mengelola data pelanggan yang melakukan transaksi pembelian. Setiap customer memiliki informasi credit limit yang digunakan untuk mengatur batas maksimum pembelian kredit.

### Fungsi Utama
- Melihat daftar customer dengan pencarian dan filter status
- Menambah customer baru
- Mengedit informasi customer
- Memantau credit limit dan sisa kredit tersedia
- Mengubah status customer (active/inactive/blacklisted)

### Alur Penggunaan

**Menambah Customer Baru:**
1. Buka menu **Customer** di sidebar
2. Klik **Tambah Customer**
3. Isi form: Nama, Email, Telepon, Alamat, Credit Limit, Status
4. Klik **Simpan**

**Memantau Credit:**
- Kolom **Credit Limit** = batas maksimum kredit
- Kolom **Tersedia** = kredit yang belum terpakai (hijau = aman, merah = habis/melewati limit)

---

## 9. Manajemen Karyawan

### Deskripsi
Fitur untuk mengelola data karyawan yang terlibat dalam operasional inventaris. Karyawan dapat ditugaskan ke gudang tertentu dan dapat dipilih sebagai penanggung jawab transaksi.

### Fungsi Utama
- Melihat daftar karyawan dengan filter berdasarkan gudang
- Menambah data karyawan baru
- Mengedit informasi karyawan (jabatan, gudang, status aktif)
- Menonaktifkan atau menghapus data karyawan

### Alur Penggunaan

**Menambah Karyawan:**
1. Buka menu **Karyawan** di sidebar
2. Klik **Tambah Karyawan**
3. Isi form: Nama, Email, Telepon, Jabatan, Departemen, Gudang, Tanggal Masuk
4. Centang **Karyawan Aktif**
5. Klik **Simpan**

---

## 10. Manajemen Transaksi (Order)

### Deskripsi
Fitur untuk mencatat dan mengelola seluruh transaksi penjualan. Setiap transaksi memiliki alur status yang terstruktur dari pembuatan order hingga selesai atau dibatalkan.

### Fungsi Utama
- Melihat daftar transaksi dengan filter status dan tanggal
- Melihat detail transaksi dan item-itemnya
- Membuat order baru
- Memperbarui status transaksi
- Tracking otomatis pengurangan dan pemulihan stok

### Alur Penggunaan

**Membuat Order Baru (Staff/Manager/Admin):**
1. Buka menu **Transaksi** di sidebar
2. Klik **Buat Order**
3. Pilih **Customer** dan **Gudang** yang akan digunakan
4. Tambahkan item produk: klik **+ Tambah Item**, pilih produk, isi jumlah dan harga
5. Tambahkan lebih banyak item jika diperlukan
6. Isi catatan order (opsional)
7. Klik **Buat Order**
8. Order tersimpan dengan status `pending`

**Memperbarui Status Transaksi (Manager/Admin):**
1. Temukan transaksi di daftar
2. Klik ikon refresh (🔄) pada baris transaksi
3. Pilih status berikutnya sesuai alur yang valid
4. Status berhasil diperbarui
5. Jika status diubah ke `shipped`, stok berkurang otomatis
6. Jika order dibatalkan setelah `shipped`, stok dipulihkan otomatis

**Melihat Detail Transaksi (Semua role):**
1. Klik ikon mata (👁️) pada baris transaksi
2. Dialog detail menampilkan: info order, customer, gudang, status, dan semua item beserta subtotal

---

## 11. Role Pengguna (Admin & Staff)

### Deskripsi
Sistem mengimplementasikan Role-Based Access Control (RBAC) dengan 4 tingkat hak akses untuk memastikan setiap pengguna hanya dapat mengakses fitur yang sesuai dengan tanggung jawabnya.

### Deskripsi Setiap Role

**Admin**
- Akses penuh ke seluruh fitur sistem
- Satu-satunya role yang dapat menghapus data secara permanen
- Cocok untuk: administrator sistem, IT manager

**Manager**
- Dapat membuat dan mengedit data di semua modul
- Dapat melakukan transfer stok dan update status transaksi
- Tidak dapat menghapus data
- Cocok untuk: manajer gudang, supervisor inventaris

**Staff**
- Dapat melihat semua data
- Dapat membuat data baru di semua modul
- Dapat membuat order baru
- Tidak dapat mengedit atau menghapus data yang sudah ada
- Cocok untuk: staf gudang, staf administrasi

**Viewer**
- Hanya dapat melihat data di semua modul
- Tidak dapat membuat, mengedit, atau menghapus data apapun
- Cocok untuk: auditor, manajemen tingkat atas yang hanya perlu monitoring

### Pengelolaan Pengguna
Penambahan dan pengelolaan akun pengguna dilakukan langsung di database oleh Admin melalui seeder atau command artisan. Dalam pengembangan selanjutnya, modul manajemen user dapat ditambahkan sebagai halaman terpisah.

---

## 12. Laporan Inventaris

### Deskripsi
Fitur laporan menyediakan ringkasan data inventaris dan penjualan yang dapat digunakan sebagai dasar pengambilan keputusan bisnis.

### Laporan yang Tersedia

**Laporan Penjualan** (`GET /api/reports/sales`)
- Total revenue per periode
- Jumlah order per status
- Produk terlaris berdasarkan jumlah unit dan revenue

**Laporan Inventaris** (`GET /api/reports/inventory`)
- Kondisi stok semua produk per gudang
- Daftar produk dengan stok rendah
- Nilai total inventaris (qty × harga modal)

### Alur Penggunaan
1. Akses endpoint laporan melalui API atau halaman laporan di frontend
2. Tentukan parameter filter: tanggal mulai, tanggal akhir, gudang, kategori
3. Sistem menghasilkan laporan sesuai filter
4. Laporan dapat diekspor ke format Excel atau PDF (fitur dalam pengembangan)

> **Catatan Pengembangan:** Fitur export laporan ke Excel menggunakan package `maatwebsite/excel` dan PDF menggunakan `barryvdh/laravel-dompdf`. Implementasi view template Blade untuk export sedang dalam tahap pengembangan.
