# Design: Drawer Detail Booking di Admin Dashboard

Tanggal: 2026-08-16

## Konteks

Tombol "Detail" (`table-action`) pada tabel "Semua booking" (`BookingTable.jsx`) dan ikon buka di `AgendaCard.jsx` di AdminDashboard masih memanggil `window.alert` dengan ringkasan teks. Halaman lain sudah memakai `AdminDrawer` (drawer samping kanan dengan overlay, tutup via Escape/klik luar).

## Tujuan

Ganti alert dengan drawer samping berisi detail booking read-only.

## Desain

- `AdminDashboard` menambah state `selected`, `detailLoading`, `detailError`.
- `openBookingDetail(item)` menyimpan id, lalu fetch `getAdminBooking(item.id)` (`GET /api/bookings/{id}`) dan menyimpan resource lengkap.
- Render `<AdminDrawer open title={client_name}>` dengan isi read-only memakai class `detail-*` yang sudah ada:
  - Ringkasan klien: nama, telepon, alamat.
  - Usulan client: tanggal, jam selesai.
  - Jadwal aktual: mulai/selesai + nama staff (bila sudah dijadwalkan).
  - Layanan: nama × qty + subtotal per baris, total di bawah.
  - Checklist task (bila ada): judul + status selesai.
  - Status (`StatusBadge`), catatan, dan booking ID.
- Handler sama dipakai `BookingTable` dan `AgendaCard`.
- Loading memakai `.admin-state`, error memakai `.admin-state-error`.

## Yang tidak termasuk (YAGNI)

- Mengedit booking dari drawer — tetap lewat `/admin/bookings`.
- Komponen drawer baru — reuse `AdminDrawer`.
- CSS baru — reuse class yang ada.

## Verifikasi

oxlint, `vite build`, dan uji browser: buka drawer dari tabel dan agenda, cek isi field, tutup via tombol X/Escape/klik overlay.
