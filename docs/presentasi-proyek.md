# SimpleBookingMUA — Presentasi Final Project (PPT Outline)

> Catatan: Full-stack **Laravel + React**. Backend (Laravel REST API) dan frontend (React SPA) sudah selesai dan saling terintegrasi.

---

## Slide 1 — Introduction

### Self-Overview
- **Julio Rohmatulloh Hardiansyah** — Full Stack Web Developer.
- Minat: backend terstruktur, aman, efisien.
- Prinsip: kode harus berjalan, mudah dipelihara, terdokumentasi, teruji.
- Tujuan karir: merancang arsitektur sistem produksi-grade (database → business logic → integrasi pihak ketiga).

### Education
- **Gelar:** _(diisi)_
- **Institusi:** _(diisi)_
- **Tahun:** _(diisi)_
- **Spesialisasi:** _(diisi)_

### Working Experience
- **Peran:** _(diisi)_
- **Perusahaan:** _(diisi)_
- **Periode:** _(diisi)_
- **Tanggung Jawab & Proyek:** _(diisi)_
- **Pencapaian:** _(diisi)_

---

## Slide 2 — Overview Project

- **SimpleBookingMUA** — REST API booking Makeup Artist (MUA) dengan Laravel + React SPA.
- Final Project Dibimbing Bootcamp — Full Stack Web Developer.
- Backend dan frontend sudah terintegrasi dan berjalan.

**Capaian backend:**
- REST API terstruktur + dokumentasi OpenAPI otomatis.
- RBAC 4 tipe pengguna.
- Penjadwalan anti-race condition (row lock).
- Integrasi Midtrans + verifikasi webhook.
- Audit trail otomatis.
- Test suite Pest.

**Capaian frontend:**
- React + Vite + Tailwind, React Router.
- Booking publik tanpa akun (aliran 4 langkah) + kalender ketersediaan.
- Halaman pembayaran Midtrans Snap dengan status pembayaran real-time.
- Dashboard admin: metrics, agenda, daftar booking, manajemen layanan/pengguna, audit log.
- Booking code publik sebagai identitas tracking (bukan ID internal).

---

## Slide 3 — Background & Problem

**Kondisi:** MUA/studio masih booking manual via WhatsApp/DM, catatan terpisah, ingatan staff.

**Masalah utama:**
- Jadwal tabrak → double-booking.
- Client bebas set jam mulai → seharusnya MUA yang tentukan.
- Transaksi & status bayar sulit dilacak.
- Pembayaran manual → human error.

**Tujuan:** Sistem terintegrasi otomatisasi pemesanan → penjadwalan → eksekusi → pembayaran.

---

## Slide 4 — Target Pengguna

| Pengguna | Peran |
|---|---|
| Owner | Pemilik, akses penuh manajemen |
| Admin | Kelola user & service |
| Staff (MUA) | Eksekusi booking |
| Client (publik) | Pesan tanpa akun |

**Model bisnis:** B2B + B2C.

---

## Slide 5 — Fitur Kunci

- Client pesan publik tanpa akun (tanggal, jam selesai usulan, alamat, service).
- **MUA yang tentukan jam datang aktual** (`starts_at`) — client dilarang set jam mulai.
- Cek overlap jadwal per staff otomatis (row lock, anti race condition).
- State machine: `pending → confirmed → done` (`cancelled` = terminal).
- Pembayaran Midtrans Snap + verifikasi signature webhook.
- Audit trail: setiap aksi tercatat di `activity_logs` (snapshot before/after).

---

## Slide 6 — Alur Booking

```mermaid
flowchart TD
    A[Client buka halaman booking] --> B[Pilih layanan]
    B --> C[Pilih tanggal + cek ketersediaan]
    C --> D[Isi detail: nama, telepon, jam selesai, alamat]
    D --> E[Kirim booking]
    E --> F[pending + dapat booking_code]
    F --> G[Staff login, assign starts_at + ends_at]
    G --> H{Overlap?}
    H -->|Ya| G
    H -->|Tidak| I[confirmed]
    I --> J[Kirim link pembayaran via WhatsApp]
    J --> K[Client bayar di halaman pembayaran Midtrans Snap]
    K --> L[Webhook + verifikasi signature]
    L --> M{Valid?}
    M -->|Tidak| N[Tolak]
    M -->|Ya| O[Status pembayaran: settlement/paid]
    O --> P[Pekerjaan selesai, status done]
    P --> Q[Audit trail tercatat]
```

---

## Slide 7 — State Machine Booking

```mermaid
stateDiagram-v2
    [*] --> pending: Client create booking
    pending --> confirmed: Staff assign + cek overlap
    pending --> cancelled: Cancel
    confirmed --> done: Pekerjaan selesai
    confirmed --> cancelled: Cancel
    done --> [*]
    cancelled --> [*]
```

---

## Slide 8 — Tech Stack & Alasan

| Komponen | Pilihan | Alasan (singkat) |
|---|---|---|
| Runtime | PHP 8.3+ | Modern, cepat, typed properties |
| Framework | Laravel 13 | REST API + Eloquent + middleware + validasi bawaan |
| Frontend | React 19 + Vite | SPA cepat, komponen reusable, dev server responsif |
| Styling | Tailwind CSS 4 | Utility-first, token tema terpusat, cepat iterasi |
| Routing | React Router 7 | Routing deklaratif + guard per-role |
| Database | MySQL 8+ | ACID, trigger, CHECK constraint → integritas transaksi |
| Auth | Sanctum | Bearer token sederhana, cocok untuk API stateless |
| Payment | Midtrans Snap | Gateway lokal populer + webhook signature SHA-512 |
| Docs | l5-swagger + OpenAPI attribute | Swagger otomatis dari kode, bukan YAML manual |
| Test | Pest 5 | Ekspresif, modern, minim boilerplate |
| Style | Laravel Pint + oxlint | Format konsisten otomatis |

**Mengapa Laravel?** Ecosystem lengkap (ORM, migration, validasi, Policy, Sanctum) + convention over configuration → cepat build API terstruktur.

**Mengapa React + Tailwind?** SPA interaktif untuk booking dengan komponen reusable; Tailwind mempercepat styling konsisten dengan token desain.

**Mengapa MySQL?** Butuh trigger + CHECK constraint untuk jaminan integritas di level DB (satu cover per service, role valid).

**Mengapa Sanctum (bukan JWT)?** Token opaque di DB → bisa revoke kapan saja + cek `is_active` per request → cocok untuk kebutuhan audit & nonaktifkan user.

**Mengapa Midtrans?** Payment gateway Indonesia, dukungan Snap token + webhook + signature → integrasi aman & familiar.

---

## Slide 9 — Arsitektur Sistem

```
app/
├── Actions/          Business logic (CreateBooking, AssignBookingSchedule, dll)
├── Contracts/        PaymentGateway (di-swap saat test)
├── Http/
│   ├── Controllers/  HTTP + attribute OpenAPI
│   ├── Middleware/   AuthenticateSession
│   ├── Requests/     Validasi & guard field per-role
│   └── Resources/    Bentuk response JSON
├── Models/           Eloquent, UUID PK
├── Policies/         Otorisasi berbasis role
└── Services/         Adapter integrasi eksternal
```

**Prinsip:** Controller tipis → logic di Action class → otorisasi di Policy → response di Resource → integrasi di Service (di belakang Contract).

---

## Slide 10 — Detail Website

Frontend React sudah terimplementasi dan terintegrasi dengan backend.

**Halaman publik:**
- `/` — Hero + CTA booking.
- `/services` — Katalog layanan (foto, harga, galeri).
- `/booking` — Form booking 4 langkah: pilih layanan → pilih tanggal (dengan kalender ketersediaan) → isi detail → kirim.
- `/payment` — Pembayaran Midtrans Snap, dengan status pembayaran real-time dan ringkasan biaya.

**Halaman admin (login terlebih dahulu):**
- `/admin` — Dashboard: metrics, agenda hari ini, booking terbaru (dengan status pembayaran).
- `/admin/bookings` — Kelola booking: jadwalkan staff, atur jam, kirim link pembayaran via WhatsApp.
- `/admin/services` — CRUD layanan + galeri foto.
- `/admin/users` — Manajemen akun tim (khusus admin/owner).
- `/admin/activity` — Audit trail perubahan.

**Identitas tracking:** setiap booking mendapat `booking_code` publik (mis. `PRSDJ6FM`) yang dipakai untuk melacak status — bukan ID internal.

**Dokumentasi visual tersedia:**
- Swagger UI: `GET /api/documentation`
- OpenAPI JSON: `GET /docs`

**Endpoint publik utama:**

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/login` | Autentikasi staff/owner/admin |
| `GET` | `/api/services` | Lihat katalog service |
| `POST` | `/api/bookings` | Buat booking (publik) |
| `POST` | `/api/schedule/check` | Cek ketersediaan jadwal |
| `GET` | `/api/public/bookings/{id}/status` | Lacak status & ringkasan pembayaran |

---

## Slide 11 — Proses Development

1. **Perencanaan** — masalah, target user, state machine, skema DB.
2. **Desain** — arsitektur Controller–Action–Policy–Resource + Contract payment.
3. **Pengembangan backend** — model, migration, Action, middleware, Policy, Resource, Service Midtrans.
4. **Pengembangan frontend** — halaman React, integrasi API, kalender, Snap, dashboard admin.
4. **Pengujian** — Pest: feature test, race condition paralel, webhook signature, authorization.
5. **Peluncuran** — Swagger UI + seed data demo.

---

## Slide 12 — Issue & Problem Solving

| Masalah | Solusi |
|---|---|
| Akses per role (client tidak boleh set jadwal sendiri) | Policy berbasis role + guard field di Form Request |
| Double-booking (race condition) | `lockForUpdate` di transaksi DB + test paralel |
| Client manipulasi `starts_at` | Field `prohibited` di Request → tolak `422` |
| >1 cover per service | Trigger MySQL / partial unique index SQLite di level DB |
| Webhook dipalsukan / status di-downgrade | Verifikasi signature SHA-512 sebelum lookup + idempotent + no-downgrade |
| User nonaktif masih bisa akses | Middleware `AuthenticateSession` cek `is_active` per request |
| Tidak ada jejak aksi | Audit trail `activity_logs` otomatis (snapshot before/after) |

---

## Slide 13 — Tautan & Demo

- **GitHub:** SimpleBookingMUA
- **Frontend:** `/` (booking publik), `/admin` (dashboard, login required)
- **Swagger:** `GET /api/documentation`
- **OpenAPI JSON:** `GET /docs`
- **Akun seed (password `password123`):** `owner`, `admin`, `staff1`, `staff2`

**Demo yang disarankan:**
1. Booking publik dari halaman `/` → dapat `booking_code`.
2. Login ke `/admin` → assign staff + jam di booking, kirim link WhatsApp.
3. Buka `/payment` → bayar via Snap → status pembayaran ter-update real-time.
