# TODO Audit Frontend vs Backend

Hasil audit implementasi frontend terhadap API backend.

## Prioritas Tinggi

- [ ] **Perbaiki field checklist booking**
  - Backend `BookingResource` mengirim field `tasks`.
  - Frontend `BookingsPage.jsx` masih membaca `booking.booking_tasks`.
  - Ubah pembacaan frontend ke `booking.tasks`.
  - Verifikasi checklist tampil pada detail booking admin.
  - Referensi: `backend-mua/app/Http/Resources/BookingResource.php`, `frontend-mua/src/pages/admin/BookingsPage.jsx`.

- [ ] **Amankan idempotensi transaksi publik setelah pembayaran berhasil**
  - `PublicBookingController` hanya menggunakan transaksi existing jika statusnya `pending`.
  - Jika transaksi sudah `capture` atau `settlement`, request Snap berikutnya berpotensi membuat transaksi baru.
  - Kembalikan transaksi paid yang sudah ada dan jangan panggil Midtrans lagi.
  - Tambahkan test untuk request berulang setelah `capture` dan `settlement`.
  - Referensi: `backend-mua/app/Http/Controllers/PublicBookingController.php`.

- [ ] **Tambahkan route guard untuk halaman admin**
  - Route `/admin`, `/admin/bookings`, `/admin/services`, dan `/admin/activity` saat ini dapat dibuka tanpa login.
  - Buat guard berbasis `auth_token` dan arahkan user unauthenticated ke `/login`.
  - Tangani token expired atau response `401` dengan membersihkan session dan redirect ke login.
  - Referensi: `frontend-mua/src/App.jsx`, `frontend-mua/src/api/client.js`.

- [ ] **Gunakan role user dari backend, bukan role simulasi**
  - `AdminDashboard` masih membaca `demo_role` dari localStorage.
  - Filter staff masih hardcoded ke `staff-1` atau nama `Sinta`.
  - Ambil user dari `GET /api/user` atau `auth_user` hasil login.
  - Terapkan role dan user ID aktual dari backend.
  - Referensi: `frontend-mua/src/pages/admin/AdminDashboard.jsx`, `frontend-mua/src/pages/user/Login.jsx`.

## Prioritas Sedang

- [ ] **Simpan dan validasi expiry token login**
  - Backend mengembalikan `expires_at` saat login.
  - Frontend belum menyimpan atau memeriksa expiry tersebut.
  - Simpan expiry bersama `auth_token` dan perlakukan token sebagai expired sebelum request admin.
  - Referensi: `backend-mua/app/Http/Controllers/AuthController.php`, `frontend-mua/src/pages/user/Login.jsx`.

- [ ] **Perbaiki recovery saat public booking token invalid/expired**
  - Saat polling menerima `401`, session dihapus tetapi state halaman belum memberi pesan recovery yang jelas.
  - Tampilkan pesan bahwa link tracking sudah expired/invalid.
  - Sediakan tombol untuk membuat booking baru.
  - Pastikan state `success` tidak membuat user diam-diam kembali ke form tanpa konteks.
  - Referensi: `frontend-mua/src/pages/user/BookingPage.jsx`.

- [ ] **Validasi konfigurasi Midtrans Snap client key**
  - Loader Snap tetap memasukkan script jika `VITE_MIDTRANS_CLIENT_KEY` kosong.
  - Berikan error konfigurasi yang jelas sebelum membuka pembayaran.
  - Pastikan hanya client key yang digunakan di frontend; server key tidak boleh masuk bundle.
  - Referensi: `frontend-mua/src/api/bookingApi.js`.

- [ ] **Rapikan lifecycle polling status booking**
  - Hindari request tambahan akibat interval yang sudah terjadwal ketika response pertama mencapai status terminal.
  - Stop polling untuk `cancelled`, `done`, `capture`, dan `settlement`.
  - Pertahankan tombol refresh manual.
  - Tambahkan test atau validasi manual untuk unmount dan perubahan session.
  - Referensi: `frontend-mua/src/pages/user/BookingPage.jsx`.

## Validasi Setelah Perbaikan

- [ ] Jalankan backend test terkait booking dan transaksi:
  - `cd backend-mua && php artisan test --compact tests/Feature/PublicBookingTest.php tests/Feature/PublicBookingPaymentTest.php tests/Feature/CreateSnapTransactionTest.php`
- [ ] Jalankan seluruh backend test:
  - `cd backend-mua && php artisan test --compact`
- [ ] Jalankan frontend lint dan build:
  - `cd frontend-mua && npm run lint && npm run build`
- [ ] Pastikan endpoint frontend sesuai route backend melalui:
  - `cd backend-mua && php artisan route:list --path=api`
- [ ] Uji manual alur publik:
  - Submit booking.
  - Simpan booking token.
  - Reload halaman dan pulihkan status.
  - Jadwalkan booking dari admin.
  - Pastikan tombol pembayaran aktif.
  - Buka Snap atau fallback redirect.
  - Pastikan status settlement menghentikan polling.
- [ ] Uji manual alur admin:
  - Akses route admin tanpa login.
  - Login dengan role backend.
  - Pastikan role dan data staff aktual digunakan.
  - Buka detail booking dan pastikan checklist tampil.

## Catatan Existing

- `php artisan migrate:fresh --seed` sebelumnya gagal karena `BookingSeeder` masih mengisi kolom `service_id` yang sudah dihapus oleh migration booking service pivot. Ini perlu diperbaiki terpisah jika fresh seed digunakan sebagai workflow deployment/testing.
- Folder `.superpowers/` adalah artefak sesi mockup lokal dan tidak termasuk TODO aplikasi.
