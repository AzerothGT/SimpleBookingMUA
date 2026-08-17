# Booking Admin Toast Feedback Design

## Goal

Semua umpan balik operasi pada halaman booking admin menggunakan toast yang sudah tersedia, bukan pesan inline di dalam drawer.

## Behavior

- Pembaruan booking dan jadwal yang berhasil menampilkan toast sukses berjudul `Booking diperbarui` dengan pesan `Booking dan jadwal berhasil diperbarui.`
- Kegagalan membuka detail, memperbarui booking, menghapus booking, dan membuat link pembayaran menampilkan toast error dengan pesan dari API.
- Drawer tetap terbuka setelah pembaruan berhasil agar hasil jadwal terbaru tetap terlihat.
- Toast mengikuti durasi bawaan `ToastProvider` dan dapat ditutup manual.

## Implementation

`BookingsPage` menggunakan hook `useToast` yang sudah tersedia. State `feedback`, prop `feedback`, dan elemen `.admin-alert` khusus feedback di `BookingDetail` dihapus karena tidak lagi diperlukan. State dan tampilan error untuk pemuatan tabel tetap dipertahankan karena merupakan status halaman, bukan feedback operasi sementara.

Tidak ada dependency, komponen toast, atau perubahan API baru.

## Error Handling

Setiap blok `catch` untuk aksi booking memanggil toast bertipe `error`. Fungsi `getError` tetap menjadi satu-satunya formatter pesan error.

## Validation

- Pembaruan berhasil memicu toast sukses.
- Kegagalan aksi booking memicu toast error.
- Drawer tidak lagi merender feedback inline.
- Lint dan build frontend berhasil.
