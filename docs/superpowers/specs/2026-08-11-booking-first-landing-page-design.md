# Desain Landing Page Booking-First

## Tujuan

Mengubah landing page menjadi utilitas booking yang praktis. Pengunjung datang untuk memilih layanan, mengecek tanggal, mengirim detail minimum, dan menerima kepastian bahwa pengajuan masuk sebagai `pending` untuk ditinjau staff.

## Keputusan desain

Gunakan flow **date-first booking**:

```text
Landing
  -> pilih layanan + tanggal
  -> lihat status jadwal
  -> pilih jam selesai usulan
  -> isi detail klien + lokasi
  -> tinjau ringkasan
  -> kirim pengajuan
  -> success: pending
  -> staff menetapkan jam aktual dan mengirim instruksi pembayaran
```

Landing page tidak lagi memprioritaskan testimonial, portfolio, section “kenapa memilih kami”, atau FAQ panjang. Informasi tersebut dihapus dari spesifikasi awal agar satu CTA dan satu tugas utama tetap dominan.

## Struktur halaman

1. Header minimal: nama MUA, area layanan, dan CTA `Cek jadwal & ajukan booking`.
2. Hero singkat dengan manfaat utama dan penjelasan bahwa booking belum menjadi konfirmasi final.
3. Booking flow utama dalam empat tahap yang terlihat:
   - `1 Pilih layanan`
   - `2 Pilih tanggal`
   - `3 Isi detail`
   - `4 Kirim`
4. Daftar layanan dan harga mulai.
5. Informasi singkat area layanan dan biaya perjalanan.
6. FAQ terbatas pada hambatan booking.
7. Success state yang menjelaskan status `pending` dan langkah staff berikutnya.

## UX dan guardrail

- Layanan paling umum dapat menjadi default, tetapi selalu dapat diubah. Jangan membuat default tanggal atau jam yang dapat disalahpahami sebagai ketersediaan.
- Status jadwal bersifat informatif dan tidak mengunci slot. Hindari kata “tersedia” sebagai janji final.
- Tampilkan status `Belum ada jadwal tercatat`, `Ada jadwal sibuk`, atau `Tampak penuh` dengan penjelasan singkat.
- Klien mengusulkan jam selesai; staff menentukan jam mulai aktual setelah meninjau jadwal dan kebutuhan layanan.
- Semua pilihan dipertahankan dalam ringkasan dan dapat diedit sebelum submit.
- Tidak ada countdown, urgensi palsu, atau penghalang akun.
- Harga mulai ditampilkan dekat layanan. Ketentuan biaya perjalanan dan konfirmasi akhir tetap terlihat.
- Field wajib memiliki label terlihat, error spesifik, focus state, dan target sentuh yang nyaman.

## Data form

Field wajib:

- layanan
- tanggal makeup
- jam selesai usulan
- nama klien
- nomor telepon
- alamat makeup

Field opsional:

- link Google Maps atau pin lokasi
- catatan kebutuhan, alergi, sensitivitas kulit, trial, atau booking grup

## State dan error

- Loading jadwal: `Sedang mengecek jadwal...`
- Empty: `Belum ada jadwal tercatat pada tanggal ini.`
- Busy: `Ada jadwal sibuk. Staff tetap perlu meninjau pengajuanmu.`
- Full: `Tampak penuh berdasarkan jadwal yang tercatat. Kamu tetap dapat mengajukan untuk peninjauan khusus.`
- Error jadwal: `Jadwal belum dapat dimuat. Coba lagi atau lanjutkan pengajuan tanpa hasil cek jadwal.`
- Error validasi: tampil dekat field terkait dan menjelaskan perbaikannya.
- Submit berhasil: `Pengajuan booking diterima dengan status pending.`
- Submit gagal: jelaskan bahwa pengajuan belum diterima dan sediakan tindakan coba lagi tanpa menghapus isian.

## Batas implementasi frontend

Implementasi di `frontend-mua/` harus memakai pola API dan komponen yang sudah ada. Tidak menambah autentikasi klien atau mekanisme penguncian slot. CTA, form start, submit berhasil, dan submit gagal diukur melalui event:

- `booking_cta_click`
- `booking_form_start`
- `booking_submit_success`
- `booking_submit_error`

## Validasi keberhasilan

- completion rate flow booking meningkat;
- waktu dari CTA ke submit menurun;
- error validasi dan abandonment antar-step menurun;
- tidak ada peningkatan pembatalan akibat ekspektasi palsu tentang ketersediaan.

## Di luar scope

- implementasi backend atau perubahan aturan penjadwalan;
- pembayaran langsung pada landing page;
- akun client;
- testimonial, rating, portfolio, dan klaim kredensial yang belum terverifikasi.
