# Backend ERD Compliance — TODO

> Target: membuat `backend-mua` sesuai `docs/erd-database.md`.
>
> Kerjakan berurutan. Setiap perubahan perilaku wajib memakai alur test-first: buat test gagal, implementasi minimal, lalu pastikan test lulus.

## Definition of Done

- [ ] Semua tabel, kolom, relasi, FK, index, dan constraint sesuai ERD.
- [ ] Semua aturan booking, jadwal, pembayaran, session, dan activity log memiliki feature test.
- [ ] Webhook Midtrans hanya menerima payload valid.
- [ ] `php artisan migrate:fresh --seed` berhasil.
- [ ] `php artisan test` lulus tanpa failure.
- [ ] `vendor/bin/pint --test` lulus.
- [ ] `php artisan route:list --path=api` menampilkan seluruh endpoint yang dibutuhkan.
- [ ] Diagnostics editor tidak memiliki error pada file backend yang diubah.

---

## Phase 1 — Baseline dan Test Harness ✅

**Files:**
- `backend-mua/tests/Feature/**`
- `backend-mua/tests/Unit/**`
- `backend-mua/phpunit.xml`

- [x] Catat baseline `php artisan test` sebelum perubahan. Baseline: 2 tests, 2 assertions, semua lulus.
- [x] Buat helper autentikasi test yang membuat `User` aktif dan opaque `Session` valid.
- [x] Buat helper booking valid dengan service aktif dan waktu usulan masa depan.
- [x] Pastikan factory tidak menghasilkan data yang melanggar aturan ERD.
- [x] Perbaiki `BookingFactory`: lat/lng selalu berpasangan, requested end selalu masa depan, dan `ends_at > starts_at`.
- [x] Perbaiki `ActivityLogFactory::bookingRelated()` agar mengisi `entity_id` dan `booking_id` konsisten.
- [x] Perbaiki `ServiceImageFactory` agar `image_source` cocok dengan bentuk `image_url`.

**Acceptance:** ✅ factory utama dapat dipakai oleh feature test tanpa menghasilkan data invalid atau kontradiktif.

**Hasil verifikasi:**
- `php artisan test --compact`: 9 tests, 273 assertions, semua lulus.
- Test khusus Phase 1: 7 tests, 271 assertions, semua lulus.
- Pint untuk 5 file Phase 1: lulus.
- Diagnostics seluruh file Phase 1: tidak ada error atau warning.
- Pint global masih memiliki 19 style issues lama di luar scope Phase 1.

---

## Phase 2 — Schema dan Migration ✅

**Files:**
- `backend-mua/database/migrations/0001_01_01_000000_create_users_table.php`
- `backend-mua/database/migrations/2026_08_06_000001_create_sessions_table.php`
- `backend-mua/database/migrations/2026_08_06_000003_create_service_images_table.php`
- `backend-mua/database/migrations/2026_08_06_000004_create_bookings_table.php`
- `backend-mua/database/migrations/2026_08_06_000006_create_transactions_table.php`
- `backend-mua/database/migrations/2026_08_06_000007_create_activity_logs_table.php`
- `backend-mua/app/Models/Session.php`

- [x] Rename tabel `auth_sessions` menjadi `sessions` agar sama dengan ERD.
- [x] Update `Session` agar memakai tabel `sessions` secara konvensional.
- [x] Tambahkan DB constraint role hanya `owner|admin|staff`.
- [x] Tambahkan DB constraint booking: `starts_at IS NULL OR ends_at > starts_at`.
- [x] Tambahkan DB constraint transaction: `gross_amount > 0`.
- [x] Batasi `transactions.order_id` maksimum 50 karakter.
- [x] Hapus index biasa `order_id` yang redundan karena `unique()` sudah membuat index.
- [x] Terapkan jaminan satu cover per service. MySQL memakai trigger dengan parent-row lock; SQLite test memakai partial unique index.
- [x] Putuskan timestamp tambahan `updated_at`: dihapus dari tabel yang ERD-nya hanya memiliki `created_at`; model terkait memakai `UPDATED_AT = null`.
- [x] Verifikasi seluruh FK delete action: CASCADE, RESTRICT, dan SET NULL sesuai tabel ERD.
- [x] Tambahkan schema tests untuk unique, check constraint, FK, dan index penting.

**Acceptance:** ✅ `php artisan migrate:fresh` berhasil dan schema tests membuktikan constraint menolak data invalid.

**Hasil verifikasi:**
- MySQL 8.4.8 `php artisan migrate:fresh --force`: seluruh migration lulus.
- MySQL schema tests: 8 tests, 39 assertions, semua lulus.
- SQLite/full suite: 12 tests lulus, 5 MySQL-specific tests skipped, 283 assertions.
- Pint untuk 15 file Phase 2: lulus.
- Diagnostics file schema utama dan model session: tidak ada error atau warning.

---

## Phase 3 — Model dan Relasi Eloquent ✅

**Files:**
- `backend-mua/app/Models/ActivityLog.php`
- `backend-mua/app/Models/Booking.php`
- `backend-mua/app/Models/BookingTask.php`
- `backend-mua/app/Models/Service.php`
- `backend-mua/app/Models/Session.php`
- `backend-mua/app/Models/User.php`
- `backend-mua/app/Providers/AppServiceProvider.php`

- [x] Daftarkan morph map:
  - `booking` → `Booking`
  - `transaction` → `Transaction`
  - `service` → `Service`
  - `user` → `User`
  - `task` → `BookingTask`
- [x] Ganti `Service::activityLogs()` menjadi relasi polymorphic melalui `entity_type/entity_id`.
- [x] Bedakan relasi booking sebagai entity log (`entityActivityLogs`) dan relasi denormalized `booking_id` (`activityLogs`).
- [x] Tambahkan `done_at` ke mass-assignment yang aman pada `BookingTask`.
- [x] Pastikan toggle task ke false mengosongkan `done_at` melalui model saving hook.
- [x] Update `Session::isActive()` agar juga mensyaratkan user aktif.
- [x] Override kontrak password Laravel agar menunjuk `password_hash`.
- [x] Cegah penyimpanan password mentah melalui cast `hashed` pada `password_hash`.
- [x] Tambahkan tests untuk relasi, morph map, session aktif, task timestamp, dan password handling.

**Acceptance:** ✅ semua relasi dapat di-load tanpa query ke kolom yang tidak ada dan password tidak pernah tersimpan plaintext.

**Hasil verifikasi:**
- Test khusus Phase 3: 6 tests, 17 assertions, semua lulus.
- Full suite: 18 tests lulus, 5 MySQL-specific tests skipped, 300 assertions.
- Pint untuk 8 file Phase 3: lulus.
- Diagnostics test/model/provider utama: tidak ada error atau warning.

---

## Phase 4 — Opaque Session Authentication ✅

**Files:**
- `backend-mua/app/Http/Controllers/AuthController.php`
- `backend-mua/app/Http/Middleware/AuthenticateSession.php`
- `backend-mua/app/Http/Requests/LoginRequest.php`
- `backend-mua/routes/api.php`
- `backend-mua/bootstrap/app.php`

- [x] Daftarkan `POST /api/login` sebagai public route.
- [x] Test login username/password valid menghasilkan opaque token unik dan expiry.
- [x] Test credential salah ditolak `401`.
- [x] Test user nonaktif tidak dapat login.
- [x] Middleware eager-load/check user dan menolak session milik user nonaktif.
- [x] Test token tanpa row, expired, revoked, dan milik user nonaktif ditolak `401`.
- [x] Pastikan logout revoke session yang sedang dipakai.
- [x] Pastikan controller mengambil user dari `$request->user()`, bukan default guard yang tidak diset middleware custom.
- [x] Hapus migration personal access token, `LogoutRequest`, dan dependency Sanctum yang tidak digunakan.

**Acceptance:** ✅ lifecycle login → authenticated request → logout bekerja hanya dengan opaque token dari tabel `sessions`.

**Hasil verifikasi:**
- Test khusus Phase 4: 10 tests, 18 assertions, semua lulus.
- Full suite: 28 tests lulus, 5 MySQL-specific tests skipped, 318 assertions.
- MySQL `migrate:fresh --force`: seluruh migration lulus tanpa tabel Sanctum.
- Route `POST /api/login`: terdaftar.
- Pint untuk 5 file Phase 4: lulus.
- PHP autoload membuktikan `OpenApi\\Attributes\\Info` tersedia; diagnostics OpenAPI yang tersisa berasal dari cache/index language server dan perlu restart setelah Composer autoload berubah.

---

## Phase 5 — Authorization dan Route Scoping ✅

**Files:**
- `backend-mua/app/Policies/**`
- `backend-mua/app/Providers/AppServiceProvider.php`
- `backend-mua/routes/api.php`
- `backend-mua/app/Http/Controllers/**`

- [x] Definisikan policy user, service, booking, task, transaction, dan activity log.
- [x] Lindungi user management: owner/admin boleh akses; staff ditolak; admin tidak dapat membuat/mempromosikan owner.
- [x] Lindungi service write endpoints untuk owner/admin; public read tetap tersedia dan filter aktif diselesaikan di Phase 6.
- [x] Lindungi booking internal, assign schedule, tasks, transactions, dan activity logs.
- [x] Aktifkan scoped bindings untuk nested resources.
- [x] Pastikan service image benar-benar milik service pada update/delete.
- [x] Pastikan booking task benar-benar milik booking pada update/delete.
- [x] Pastikan transaction benar-benar milik booking pada show.
- [x] Tambahkan authorization tests untuk setiap role dan cross-parent resource access.

**Acceptance:** ✅ user tidak dapat membaca atau mengubah resource di luar role dan parent relationship yang diizinkan.

**Hasil verifikasi:**
- Test khusus Phase 5: 19 tests, 23 assertions, semua lulus.
- Full suite: 47 tests lulus, 5 MySQL-specific tests skipped, 341 assertions.
- Route list: 33 endpoint terdaftar; service image dan booking task memakai nested scoped routes.
- Pint untuk 16 file Phase 5: lulus.
- Diagnostics policy dan authorization test: tidak ada error atau warning.

**Matriks role:**
- Owner: seluruh operasi internal.
- Admin: user non-owner, service, booking, task, transaction, activity log.
- Staff: booking, task, dan transaction; user/service write/activity log ditolak.

---

## Phase 6 — Public Service dan Booking Creation ✅

**Files:**
- `backend-mua/app/Http/Controllers/ServiceController.php`
- `backend-mua/app/Http/Controllers/BookingController.php`
- `backend-mua/app/Http/Requests/StoreBookingRequest.php`
- `backend-mua/app/Http/Requests/CheckScheduleRequest.php`

- [x] Public service index selalu memfilter `is_active = true`.
- [x] Public service show mengembalikan `404` untuk service nonaktif.
- [x] Hapus kemampuan public `include_inactive=true`.
- [x] Booking create hanya menerima service aktif.
- [x] Validasi gabungan `client_requested_date + client_requested_end_time > now()`.
- [x] Ganti hook koordinat menjadi hook Form Request resmi `after()`.
- [x] Pastikan lat/lng keduanya ada atau keduanya null dan range valid.
- [x] Tolak input public `user_id`, `starts_at`, `ends_at`, atau `status` dengan rule `prohibited`.
- [x] Set default create secara atomik:
  - `starts_at = null`
  - `ends_at = client_requested_ends_at`
  - `status = pending`
- [x] Samakan nama field schedule check dengan ERD: `client_requested_date`.
- [x] Test schedule check menampilkan busy windows tanpa memblok booking creation.

**Acceptance:** ✅ public hanya dapat membuat booking pending valid berdasarkan usulan akhir dan tidak dapat mengatur jadwal internal.

**Hasil verifikasi:**
- Test khusus Phase 6: 10 tests, 38 assertions, semua lulus.
- Full suite: 57 tests lulus, 5 MySQL-specific tests skipped, 379 assertions.
- Pint untuk 5 file Phase 6: lulus.
- Diagnostics request dan feature test: tidak ada error atau warning.

---

## Phase 7 — Staff Scheduling dan Overlap Protection ✅

**Files:**
- `backend-mua/app/Http/Controllers/BookingController.php`
- `backend-mua/app/Http/Requests/AssignStaffRequest.php`
- `backend-mua/app/Actions/Bookings/AssignBookingSchedule.php` (create)
- `backend-mua/tests/Feature/BookingScheduleTest.php` (create)

- [x] Extract assign/schedule logic ke `AssignBookingSchedule` agar semua write path memakai validasi yang sama.
- [x] Require `user_id`, `starts_at`, dan `ends_at` saat menetapkan jadwal final.
- [x] Validasi staff aktif dan role termasuk `owner|admin|staff`.
- [x] Validasi `ends_at > starts_at`.
- [x] Cek overlap per staff untuk booking `pending|confirmed`, kecuali booking yang sedang diedit.
- [x] Gunakan database transaction dan lock pada row staff untuk mencegah dua request bersamaan lolos overlap check.
- [x] Tolak schedule invalid dengan `422` yang konsisten.
- [x] Catat `booking.schedule_adjusted` dalam transaction yang sama.
- [x] `booking.rejected` tidak dicatat; fitur ini opsional di ERD dan gagal validasi tidak menghasilkan write baru.
- [x] Test boundary non-overlap: existing end sama dengan new start diterima.
- [x] Test true overlap dan concurrent assignment ditolak.

**Acceptance:** ✅ satu staff tidak dapat memiliki dua booking aktif yang waktunya beririsan, termasuk saat request concurrent.

**Hasil verifikasi:**
- Test jadwal Phase 7: 6 tests, 21 assertions, semua lulus.
- MySQL process-concurrency test: 1 test, 1 assertion, lulus; hasil satu accepted dan satu rejected.
- Full suite: 63 tests lulus, 6 driver-specific tests skipped, 400 assertions.
- Pint untuk 8 file Phase 7: lulus.
- Diagnostics action dan feature test: tidak ada error atau warning.

---

## Phase 8 — Booking State Machine ✅

**Files:**
- `backend-mua/app/Http/Controllers/BookingController.php`
- `backend-mua/app/Http/Requests/UpdateBookingRequest.php`
- `backend-mua/app/Actions/Bookings/ChangeBookingStatus.php` (create)
- `backend-mua/tests/Feature/BookingStatusTest.php` (create)

- [x] Hapus perubahan status bebas dari generic update endpoint; status hanya melalui `PATCH /api/bookings/{booking}/status` atau cancel endpoint.
- [x] Terapkan transition eksplisit:
  - `pending → confirmed` hanya jika jadwal lengkap dan transaction settlement + fraud accepted tersedia.
  - `confirmed → done`.
  - `pending|confirmed → cancelled`.
- [x] Tolak `pending → done`.
- [x] Tolak `cancelled|done → pending`.
- [x] Pisahkan koreksi alamat/maps/notes melalui `UpdateBooking` action.
- [x] Jaga `client_requested_date`, `client_requested_end_time`, dan `client_requested_ends_at` immutable dengan rule `prohibited`.
- [x] Catat `booking.updated` dan `booking.status_changed` dengan before/after meta dalam transaction yang sama.

**Acceptance:** ✅ semua status hanya berubah melalui transition yang diizinkan dan setiap perubahan tercatat.

**Hasil verifikasi:**
- Test khusus Phase 8: 11 tests, 44 assertions, semua lulus.
- Full suite: 74 tests lulus, 6 driver-specific tests skipped, 444 assertions.
- Pint untuk 7 file Phase 8: lulus.
- Diagnostics actions dan feature test: tidak ada error atau warning.

---

## Phase 9 — Service Deactivation dan Cover Image ✅

**Files:**
- `backend-mua/app/Http/Controllers/ServiceController.php`
- `backend-mua/app/Http/Controllers/ServiceImageController.php`
- `backend-mua/app/Actions/Services/DeactivateService.php` (create jika logic tidak trivial)

- [x] Tolak deactivation service yang masih memiliki booking `pending|confirmed`.
- [x] Gunakan transaction saat membuat atau mengganti cover image.
- [x] Lock parent service dan image rows saat reset cover dan set cover baru.
- [x] Tolak `service_id` pada payload image sehingga image tidak dapat dipindahkan.
- [x] Tambahkan tests untuk deactivation, payload `false|0`, satu-cover invariant, dan concurrent cover update.

**Acceptance:** ✅ service aktif yang sedang dipakai tidak dapat dinonaktifkan dan setiap service maksimal punya satu cover.

**Hasil verifikasi:**
- Test khusus Phase 9: 7 tests, 23 assertions, semua lulus.
- MySQL cover concurrency test: 1 test, 2 assertions, lulus; dua worker sukses dan final hanya satu cover.
- Full suite: 81 tests lulus, 7 driver-specific tests skipped, 467 assertions.
- Pint untuk 9 file Phase 9: lulus.
- Diagnostics actions dan feature test: tidak ada error atau warning.

---

## Phase 10 — Booking Tasks ✅

**Files:**
- `backend-mua/app/Http/Controllers/BookingTaskController.php`
- `backend-mua/app/Models/BookingTask.php`
- `backend-mua/tests/Feature/BookingTaskTest.php` (create)

- [x] Saat `is_done=true`, set `done_at=now()` melalui model hook.
- [x] Saat `is_done=false`, set `done_at=null`.
- [x] Catat `task.created`, `task.toggled`, dan `task.deleted` dalam transaction yang sama.
- [x] Pastikan cascade delete task ketika booking benar-benar dihapus.
- [x] Endpoint delete booking adalah business soft-cancel agar histori booking tetap ada; physical model/database delete tetap cascade ke task sesuai FK ERD.

**Acceptance:** ✅ status task dan `done_at` selalu sinkron serta semua perubahan tercatat.

**Hasil verifikasi:**
- Test khusus Phase 10: 4 tests, 16 assertions, semua lulus.
- Lifecycle create/toggle/delete menghasilkan activity logs dan physical booking delete menghapus task.

---

## Phase 11 — Midtrans Snap Integration ✅

**Files:**
- `backend-mua/config/services.php`
- `backend-mua/.env.example`
- `backend-mua/app/Contracts/PaymentGateway.php` (create)
- `backend-mua/app/Services/MidtransPaymentGateway.php` (create)
- `backend-mua/app/Http/Controllers/TransactionController.php`
- `backend-mua/app/Http/Requests/MidtransWebhookRequest.php` (create)
- `backend-mua/app/Providers/AppServiceProvider.php`

- [x] Tambahkan konfigurasi Midtrans dari env; server key tidak di-hardcode.
- [x] Buat `PaymentGateway` contract dan `MidtransPaymentGateway` HTTP adapter agar gateway dapat di-fake dalam test.
- [x] Gunakan authenticated `$request->user()->id` sebagai creator transaction.
- [x] Tolak create Snap untuk booking cancelled atau done.
- [x] Pastikan `gross_amount` integer positif.
- [x] Buat `order_id` unik `MUA-{UUID}` dengan panjang maksimal 50.
- [x] Panggil `POST /snap/v1/transactions` dengan Basic Auth, timeout 10 detik, retry, dan HTTP error handling.
- [x] Simpan `snap_token` dan `redirect_url`; response tidak lengkap dianggap gagal.
- [x] Catat `transaction.created` dalam transaction yang sama.
- [x] Tidak ada endpoint callback frontend yang dapat mengonfirmasi booking; confirmation hanya melalui webhook tervalidasi.

**Acceptance:** ✅ create Snap menghasilkan transaction pending lengkap dan kegagalan Midtrans tidak meninggalkan data parsial.

**Environment yang wajib tersedia:**
- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_SNAP_URL` (default sandbox: `https://app.sandbox.midtrans.com`)

**Hasil verifikasi:**
- Create Snap tests: 4 tests, 17 assertions, semua lulus.
- Gateway adapter tests: 3 tests, 4 assertions, semua lulus.
- `.env.example` tidak dapat diedit oleh agent karena termasuk protected/private file; runtime config melalui `config/services.php` sudah lengkap.

---

## Phase 12 — Secure Midtrans Webhook ✅

**Files:**
- `backend-mua/app/Http/Controllers/TransactionController.php`
- `backend-mua/app/Http/Requests/MidtransWebhookRequest.php`
- `backend-mua/app/Actions/Transactions/HandleMidtransWebhook.php` (create)
- `backend-mua/tests/Feature/MidtransWebhookTest.php` (create)

- [x] Validasi field wajib webhook melalui `MidtransWebhookRequest`.
- [x] Verifikasi `signature_key` memakai SHA-512 resmi Midtrans dan fail-closed bila server key kosong.
- [x] Bandingkan `gross_amount` webhook dengan transaction lokal.
- [x] Tolak order ID yang tidak dikenal dengan `404`.
- [x] Proses webhook secara idempotent; replay status sama tidak membuat log baru.
- [x] Terapkan transition transaction yang diizinkan; settlement/capture tidak dapat didowngrade oleh webhook lama.
- [x] Set `paid_at` hanya untuk capture/settlement dengan `fraud_status=accept`; refund mempertahankan waktu pembayaran.
- [x] Confirm booking hanya untuk capture/settlement accepted.
- [x] Booking hanya dikonfirmasi bila staff, starts_at, dan ends_at sudah lengkap.
- [x] Catat `transaction.webhook` tanpa menyimpan signature/secret.
- [x] Test required fields, missing key, forged signature, replay, amount mismatch, unknown order, settlement, capture, fraud deny, incomplete schedule, deny, expire, downgrade, dan refund.

**Acceptance:** ✅ payload palsu tidak dapat mengubah transaksi atau booking; webhook valid aman diproses ulang.

**Hasil verifikasi Phase 10–12:**
- Webhook tests: 13 tests, 45 assertions, semua lulus.
- Target gabungan sebelum hardening akhir: 20 tests, 73 assertions, semua lulus.
- Full suite final: 105 tests lulus, 7 driver-specific tests skipped, 549 assertions.
- MySQL `migrate:fresh --force`: seluruh migration lulus.
- Pint untuk 15 file Phase 10–12: lulus.
- Diagnostics actions, gateway, dan webhook tests: tidak ada error atau warning.

---

## Phase 13 — Activity Logging ✅

**Files:**
- `backend-mua/app/Models/ActivityLog.php`
- `backend-mua/app/Actions/ActivityLogs/RecordActivity.php` (create)
- `backend-mua/app/Actions/Bookings/CreateBooking.php` (create)
- `backend-mua/app/Actions/Users/ManageUser.php` (create)
- `backend-mua/database/migrations/2026_08_06_000007_create_activity_logs_table.php`
- `backend-mua/tests/Feature/ActivityLoggingTest.php` (create)
- Semua action write pada booking, task, transaction, dan user

- [x] Buat satu action/helper minimal untuk penulisan log yang konsisten (`RecordActivity`).
- [x] Catat actor `user_id`, `entity_type`, `entity_id`, optional `booking_id`, action, detail, dan before/after `meta`.
- [x] `activity_logs.user_id` nullable agar booking publik tanpa actor tetap tercatat.
- [x] Implementasikan seluruh action ERD:
  - `booking.created`
  - `booking.updated`
  - `booking.status_changed`
  - `booking.schedule_adjusted`
  - `task.created`
  - `task.toggled`
  - `task.deleted`
  - `transaction.created`
  - `transaction.webhook`
  - `user.created`
  - `user.updated`
  - `user.deactivated`
- [x] Pastikan logging penting berada dalam transaction yang sama dengan perubahan domain.
- [x] Tambahkan tests untuk actor, entity morph, denormalized booking ID, dan before/after meta.

**Acceptance:** ✅ setiap write domain yang diwajibkan ERD menghasilkan tepat satu log konsisten.

**Keputusan sengaja:**
- `booking.rejected` tidak diimplementasikan; ERD menandainya opsional dan penolakan sudah dikembalikan sebagai `422` tanpa side effect.
- `service.*` tidak ada dalam daftar action ERD, sehingga create/update/delete service tidak menulis log.
- `DELETE /api/users/{user}` sekarang deactivate (`is_active = false`) dan menulis `user.deactivated`, bukan physical delete, agar FK RESTRICT ke bookings/transactions/activity_logs tetap aman.

**Hasil verifikasi:**
- `tests/Feature/ActivityLoggingTest.php`: 4 tests, 19 assertions, semua lulus.
- Full suite: 109 tests lulus, 7 driver-specific tests skipped, 568 assertions.
- MySQL `migrate:fresh --force`: seluruh migration lulus dengan `user_id` nullable.
- MySQL run `SchemaComplianceTest` + `ActivityLoggingTest`: 12 tests, 58 assertions, semua lulus.
- Pint 21 file actions/controllers/test: lulus.

---

## Phase 14 — API Responses dan Error Consistency

**Files:**
- `backend-mua/app/Http/Resources/**`
- `backend-mua/bootstrap/app.php`
- `backend-mua/app/Http/Requests/**`

- [x] Gunakan API Resources untuk mencegah kebocoran token, password hash, dan payload internal.
- [x] Hilangkan duplikasi `failedValidation()` dari setiap Form Request jika Laravel JSON exception handling global sudah cukup.
- [x] Standarkan response `401`, `403`, `404`, `409`, dan `422`.
- [x] Pastikan pagination dan eager loading mencegah N+1.
- [x] Pastikan public response tidak menampilkan data internal user, transaction, atau activity log.

**Acceptance:** ✅ semua endpoint memiliki format response/error konsisten dan tidak membocorkan field sensitif.

**Implementasi:**
- 7 API Resource baru di `app/Http/Resources/`: `UserResource`, `ServiceResource`, `ServiceImageResource`, `BookingResource`, `BookingTaskResource`, `TransactionResource`, `ActivityLogResource`. `UserResource` tidak pernah memuat `password_hash`; relasi selalu lewat `whenLoaded()`.
- `JsonResource::withoutWrapping()` di `AppServiceProvider::boot()` — single resource unwrapped, collection paginated tetap punya `data` + `links` + `meta` dari `PaginatedResourceResponse`.
- 15 Form Request kehilangan `failedValidation()` duplikat. Envelope validasi sekarang default Laravel `{message, errors}` karena `bootstrap/app.php` sudah punya `shouldRenderJsonWhen(fn ($request) => $request->is('api/*'))`.
- Eager load ditambah: `ServiceController::index` (`serviceImages`), `BookingController::show` (`activityLogs.user`), `TransactionController::index` (`user`).
- `StoreUserRequest::prepareForValidation()` no-op dihapus.

**Hasil verifikasi:**
- `tests/Feature/ApiResponseConsistencyTest.php` baru: 7 tests (envelope 422, envelope message-only untuk 401/403/404, no credential leak di 3 bentuk payload user, field list service publik, relasi internal tersembunyi di booking publik, shape activity log, pagination + query count konstan 2 vs 7 booking).
- Full suite SQLite: **118 passed, 7 skipped, 609 assertions**.
- MySQL run `SchemaComplianceTest` + `ApiResponseConsistencyTest` + kedua concurrency test: 19 passed, 83 assertions.
- Pint 33 file (Requests, Resources, Controllers, provider, test): lulus.

---

## Phase 15 — Final Verification

- [x] Jalankan `php artisan migrate:fresh --seed`.
- [x] Jalankan test per domain setelah tiap phase.
- [x] Jalankan seluruh suite: `php artisan test`.
- [x] Jalankan formatter check: `vendor/bin/pint --test`.
- [x] Jalankan `php artisan route:list --path=api`; pastikan login, public booking, schedule check, webhook, dan protected resources terdaftar.
- [x] Jalankan diagnostics editor pada semua file backend yang diubah.
- [x] Audit ulang terhadap setiap bagian `docs/erd-database.md`.
- [x] Update ERD bila keputusan final sengaja berbeda, khususnya nama tabel session dan timestamps tambahan.
- [x] Hapus TODO Midtrans dan dead auth code/dependency yang tidak lagi digunakan.

**Acceptance:** ✅ backend lulus audit penuh terhadap ERD; seluruh deviasi terdokumentasi.

**Hasil verifikasi:**
- `migrate:fresh --seed` di MySQL `backend_mua`: 10 migration + 6 seeder lulus.
- Full suite SQLite: **118 passed, 7 skipped, 609 assertions**.
- MySQL run (`SchemaComplianceTest`, `ApiResponseConsistencyTest`, kedua concurrency test): 19 passed, 83 assertions.
- `vendor/bin/pint --test`: **PASS, 125 files** (tidak ada style debt tersisa).
- `route:list --path=api`: 35 route — `POST api/login`, `POST api/bookings` (publik), `POST api/schedule/check`, `POST api/webhooks/midtrans`, sisanya di grup `auth.session`.
- `l5-swagger:generate`: 21 path terdokumentasi, `securitySchemes` terisi.
- Diagnostics: satu-satunya sisa error adalah `Undefined type 'OpenApi\Attributes\*'` di controller dan `L5Swagger\Generator` di `config/l5-swagger.php` — **false positive language server**; `class_exists()` untuk keduanya mengembalikan `true` di runtime.

**Temuan audit yang diperbaiki di phase ini:**
- `HandleMidtransWebhook`: signature diverifikasi **sebelum** lookup `order_id`, menutup order-existence oracle.
- `HandleMidtransWebhook`: `activity_logs.user_id` untuk `transaction.webhook` jadi `null` (actor = Midtrans, bukan pembuat Snap).
- `CreateSnapTransaction`: `gross_amount` pakai `(int) round((float) $price)` — sebelumnya truncate bagian desimal `decimal(12,2)`.
- `UpdateBookingRequest`: `user_id`, `starts_at`, `ends_at` jadi `prohibited` (sebelumnya senyap di-strip tanpa 422).
- Migration users: tabel `password_reset_tokens` dihapus — tidak ada kolom `email` di `users`, jadi mati total.
- `ServiceController::index`: parameter OpenAPI `include_inactive` dihapus karena tidak pernah dibaca.
- `StoreUserRequest::prepareForValidation()` no-op dihapus.

**Sisa gap yang sengaja tidak ditutup (di luar scope ERD):**
- `BookingPolicy` mengizinkan setiap user aktif untuk update/cancel/assign booking apa pun; ERD tidak menentukan ownership scoping per `bookings.user_id`.
- `booking.rejected` dan `service.*` activity action tetap absen (lihat Deviasi di ERD).

---

## Catatan pasca-phase — Swagger UI

- UI Swagger dilayani penuh oleh l5-swagger: **`GET /api/documentation`** (UI) dan **`GET /docs`** (spec JSON dari `storage/api-docs/api-docs.json`).
- `routes/web.php`: `Route::redirect('/', '/api/documentation')` — root langsung ke docs karena backend ini API-only.
- Dihapus: `public/api-docs.html` + `public/openapi.json`. Keduanya UI swagger-ui hand-rolled dari CDN unpkg yang membaca copy JSON **stale**, dan redirect manual `/api/documentation` di `routes/web.php` menutupi route l5-swagger asli.
- Regenerate spec setelah ubah attribute `OA\*`: `php artisan l5-swagger:generate`. Set `L5_SWAGGER_GENERATE_ALWAYS=true` di `.env` bila mau otomatis saat dev.
- **Peringatan keamanan:** `config/l5-swagger.php` punya `'middleware' => ['api' => [], 'docs' => [], 'asset' => []]` — docs terbuka tanpa autentikasi. Siapa pun yang bisa mencapai host dapat membaca seluruh permukaan API. Gate di belakang `auth.session` atau batasi ke env lokal sebelum deploy.

## Recommended Execution Order

1. Baseline tests dan factories.
2. Schema + model relations.
3. Session authentication + authorization.
4. Public booking creation.
5. Staff scheduling + state machine.
6. Service/task invariants.
7. Midtrans Snap + secure webhook.
8. Activity logs.
9. API consistency + full verification.
