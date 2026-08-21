# Frontend — Cantik itu Pilihan

Antarmuka web untuk booking makeup artist (tanpa akun untuk klien) beserta dashboard internal untuk owner, admin, dan staff. Dibangun dengan React 19 + Vite dan Tailwind CSS 4.

## Teknologi

- **React 19** dengan React Router 7
- **Vite 8** (dev server & build)
- **Tailwind CSS 4** (via `@tailwindcss/vite`)
- **Midtrans Snap** untuk pembayaran
- **Bun** sebagai package manager & runner
- **Oxlint** untuk linting

## Prasyarat

- [Bun](https://bun.sh) terpasang
- Backend API yang berjalan (lihat repo backend)

## Setup

```sh
bun install
cp .env.example .env
```

Isi `.env` sesuai lingkungan Anda:

| Variabel | Wajib | Keterangan |
| --- | --- | --- |
| `VITE_API_URL` | ya | URL dasar API backend, mis. `http://localhost:8000/api` |
| `VITE_MIDTRANS_CLIENT_KEY` | ya (untuk pembayaran) | Client key Snap dari dashboard Midtrans |
| `VITE_MIDTRANS_IS_PRODUCTION` | tidak | `true` untuk produksi Midtrans, selain itu memakai sandbox |

## Perintah

```sh
bun run dev      # jalankan dev server
bun run build    # build produksi ke dist/
bun run preview  # pratinjau hasil build
bun run lint     # jalankan oxlint
bun test         # jalankan unit test (node:test)
```

## Struktur

```
src/
  api/         # klien HTTP terpusat + endpoint (client.js, bookingApi.js, adminApi.js)
  components/  # komponen UI bersama (tabel, drawer, badge, dsb.)
  context/     # ToastProvider dan hook terkait
  pages/
    user/      # halaman publik: Home, Services, Booking, Payment, Login
    admin/     # dashboard internal: bookings, services, users, activity
  session.js   # penyimpanan & validasi sesi (localStorage)
```

## Autentikasi & peran

- Sesi disimpan di `localStorage` (`token`, `user`, `expires_at`) dan otomatis relogin saat token kedaluwarsa atau menerima 401.
- Rute admin dilindungi `RequireAuth`; halaman **Users** dan **Activity** dibatasi peran `admin` saja. `owner` hanya membaca daftar user untuk keperluan delegasi staff.

## Deploy (Vercel)

Konfigurasi ada di `vercel.json` — framework Vite, build via Bun, dengan SPA rewrite ke `index.html`. Pastikan variabel `.env` diset di dashboard Vercel.
