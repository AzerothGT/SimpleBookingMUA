# TODO Audit Frontend vs Backend

Hasil audit implementasi frontend terhadap API backend.

## Prioritas Tinggi

- [x] **Perbaiki field checklist booking**
  - Backend `BookingResource` mengirim field `tasks`.
  - Frontend `BookingsPage.jsx` masih membaca `booking.booking_tasks`.
  - Ubah pembacaan frontend ke `booking.tasks`.
  - Verifikasi checklist tampil pada detail booking admin.
  - Referensi: `backend-mua/app/Http/Resources/BookingResource.php`, `frontend-mua/src/pages/admin/BookingsPage.jsx`.
  - **Selesai.** Diverifikasi via browser: task "Siapkan kit makeup" tampil di drawer detail booking admin.

- [x] **Amankan idempotensi transaksi publik setelah pembayaran berhasil**
  - `PublicBookingController` hanya menggunakan transaksi existing jika statusnya `pending`.
  - Jika transaksi sudah `capture` atau `settlement`, request Snap berikutnya berpotensi membuat transaksi baru.
  - Kembalikan transaksi paid yang sudah ada dan jangan panggil Midtrans lagi.
  - Tambahkan test untuk request berulang setelah `capture` dan `settlement`.
  - Referensi: `backend-mua/app/Http/Controllers/PublicBookingController.php`.
  - **Selesai.** Reuse kini berlaku untuk `isPending()` ATAU `isPaid()` (paid = `paid_at` terisi, konsisten dengan webhook). Test dataset `settlement` + `capture` ditambahkan (test-first).

- [x] **Tambahkan route guard untuk halaman admin**
  - Route `/admin`, `/admin/bookings`, `/admin/services`, dan `/admin/activity` saat ini dapat dibuka tanpa login.
  - Buat guard berbasis `auth_token` dan arahkan user unauthenticated ke `/login`.
  - Tangani token expired atau response `401` dengan membersihkan session dan redirect ke login.
  - Referensi: `frontend-mua/src/App.jsx`, `frontend-mua/src/api/client.js`.
  - **Selesai.** Route layout `RequireAuth` di `App.jsx`; `client.js` memeriksa expiry sebelum request dan auto-logout + redirect saat `401` (hanya untuk request ber-auth — endpoint publik memakai flag `auth: false` agar tidak salah logout).

- [x] **Gunakan role user dari backend, bukan role simulasi**
  - `AdminDashboard` masih membaca `demo_role` dari localStorage.
  - Filter staff masih hardcoded ke `staff-1` atau nama `Sinta`.
  - Ambil user dari `GET /api/user` atau `auth_user` hasil login.
  - Terapkan role dan user ID aktual dari backend.
  - Referensi: `frontend-mua/src/pages/admin/AdminDashboard.jsx`, `frontend-mua/src/pages/user/Login.jsx`.
  - **Selesai.** Role & user diambil dari session login (`auth_user`); filter staff memakai `staffId === user.id`; role switcher dihapus; tombol Keluar benar-benar clear session. Lokasi kedua yang luput dari audit awal — `AdminLayout.jsx` — juga memakai `demo_role` dan ikut diperbaiki. Nav "Aktivitas" disembunyikan untuk staff. Diverifikasi via browser login owner & staff.

## Prioritas Sedang

- [x] **Simpan dan validasi expiry token login**
  - Backend mengembalikan `expires_at` saat login.
  - Frontend belum menyimpan atau memeriksa expiry tersebut.
  - Simpan expiry bersama `auth_token` dan perlakukan token sebagai expired sebelum request admin.
  - Referensi: `backend-mua/app/Http/Controllers/AuthController.php`, `frontend-mua/src/pages/user/Login.jsx`.
  - **Selesai.** `session.js` menyimpan `auth_expires_at`; `hasValidSession()` dipakai guard; `client.js` menolak request bila token expired. Key `demo_role` dibersihkan otomatis.

- [x] **Perbaiki recovery saat public booking token invalid/expired**
  - Saat polling menerima `401`, session dihapus tetapi state halaman belum memberi pesan recovery yang jelas.
  - Tampilkan pesan bahwa link tracking sudah expired/invalid.
  - Sediakan tombol untuk membuat booking baru.
  - Pastikan state `success` tidak membuat user diam-diam kembali ke form tanpa konteks.
  - Referensi: `frontend-mua/src/pages/user/BookingPage.jsx`.
  - **Selesai.** State `trackingLost` menampilkan kartu recovery "Link pelacakan sudah kedaluwarsa." + tombol "Buat booking baru".

- [x] **Validasi konfigurasi Midtrans Snap client key**
  - Loader Snap tetap memasukkan script jika `VITE_MIDTRANS_CLIENT_KEY` kosong.
  - Berikan error konfigurasi yang jelas sebelum membuka pembayaran.
  - Pastikan hanya client key yang digunakan di frontend; server key tidak boleh masuk bundle.
  - Referensi: `frontend-mua/src/api/bookingApi.js`.
  - **Selesai.** `loadMidtransSnap` menolak dengan pesan "Konfigurasi pembayaran belum lengkap..." bila client key kosong. Diverifikasi tidak ada referensi server key di frontend. E2E penuh Snap belum bisa diuji: backend lokal belum punya `MIDTRANS_SERVER_KEY` dan cURL PHP Windows gagal verifikasi SSL ke sandbox (perlu `curl.cainfo` di php.ini).

- [x] **Rapikan lifecycle polling status booking**
  - Hindari request tambahan akibat interval yang sudah terjadwal ketika response pertama mencapai status terminal.
  - Stop polling untuk `cancelled`, `done`, `capture`, dan `settlement`.
  - Pertahankan tombol refresh manual.
  - Tambahkan test atau validasi manual untuk unmount dan perubahan session.
  - Referensi: `frontend-mua/src/pages/user/BookingPage.jsx`.
  - **Selesai.** Interval kini baru dijadwalkan setelah response pertama dan tidak dijadwalkan bila status terminal; flag `stopped` mencegah request lanjutan; cleanup unmount tetap. Reload halaman success card terverifikasi memulihkan status via polling.

## Temuan tambahan saat pengerjaan

- [x] **Gate status `confirmed` di Snap publik mematukan alur bayar** — `PublicBookingController::createSnap` dulu mensyaratkan `status === 'confirmed'`, padahal transisi `pending → confirmed` (di `ChangeBookingStatus`) mensyaratkan settlement terlebih dahulu. Client tidak akan pernah bisa membayar untuk booking scheduled-pending. Gate diubah menjadi cukup `starts_at` + `ends_at` terisi (selaras dengan kondisi tombol bayar frontend dan endpoint Snap internal). Test "scheduled pending booking" ditambahkan (test-first).
- [x] **`BookingSeeder` sudah diperbaiki** (lihat Catatan Existing): mengisi pivot `booking_service` dengan qty, tidak lagi menyentuh kolom `service_id` yang sudah dihapus. Test `DatabaseSeedingTest` menjamin full seed berjalan.

## Validasi Setelah Perbaikan

- [x] Jalankan backend test terkait booking dan transaksi: lulus (29 test / 117 assertion).
- [x] Jalankan seluruh backend test: **134 passed, 7 skipped** (MySQL-only schema test) — 685 assertion, naik dari baseline 125 test.
- [x] Jalankan frontend lint dan build: oxlint 0 warning/error, `vite build` sukses.
- [x] Pastikan endpoint frontend sesuai route backend: route `public/bookings/{id}/status` dan `.../transactions/snap` cocok dengan `bookingApi.js`.
- [x] Uji manual alur publik (via browser): submit booking sukses (4 tahap), booking ID tampil, reload memulihkan status via polling, booking dijadwalkan dari admin → tombol bayar aktif → klik bayar memanggil endpoint snap tanpa 422. Snap sandbox belum bisa dibuka (butuh kredensial + CA bundle, lihat item Midtrans di atas).
- [x] Uji manual alur admin: akses `/admin` tanpa login → redirect `/login`; login owner & staff → role/nama asli dipakai, nav Aktivitas hanya owner/admin; checklist tampil di detail booking.

## Catatan Existing

- [x] `BookingSeeder` sudah diperbaiki — `migrate:fresh --seed` kini berjalan (dijamin test `DatabaseSeedingTest`).
- Folder `.superpowers/` adalah artefak sesi mockup lokal dan tidak termasuk TODO aplikasi. (Catatan: di repo saat ini foldernya `docs/superpowers`.)
