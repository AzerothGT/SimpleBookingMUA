# Admin Booking Staff Column Design

## Goal

Kolom `Staff` pada tabel booking admin harus menampilkan semua staf yang ditugaskan pada sebuah booking.

## Data Selection

1. Jika `row.staff_schedules` berisi jadwal staf, ambil nama staf dari setiap item dan gabungkan dengan `, `.
2. Jika tidak ada jadwal staf, gunakan `row.staff.name` sebagai fallback untuk data booking lama.
3. Jika kedua sumber tidak memiliki nama staf, tampilkan `Belum ditugaskan`.

Item jadwal yang tidak memiliki nama staf diabaikan agar kolom tidak menampilkan nilai kosong.

## Scope

Perubahan hanya dilakukan pada pemformatan kolom `Staff` di `frontend-mua/src/pages/admin/BookingsPage.jsx`. Endpoint API, struktur tabel, detail booking, dan proses penjadwalan tidak berubah.

## Validation

- Booking dengan beberapa `staff_schedules` menampilkan seluruh nama staf.
- Booking lama yang hanya memiliki `staff` tetap menampilkan nama staf.
- Booking tanpa staf menampilkan `Belum ditugaskan`.
- Frontend lolos pemeriksaan lint dan build.
