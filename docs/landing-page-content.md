# Booking `[Nama MUA]`

> **Tujuan halaman:** membantu client memilih layanan, mengecek tanggal, dan mengajukan booking tanpa akun.
>
> **CTA utama:** [Cek jadwal & ajukan booking](#booking)
>
> **Area layanan:** `[Kota/area layanan]`

---

## Mulai booking

### Pilih jadwal makeup-mu

Pilih layanan dan tanggal yang kamu inginkan. Sistem akan menampilkan jadwal yang sudah tercatat agar kamu dapat memilih tanggal dengan informasi yang lebih jelas.

**[Cek jadwal & ajukan booking](#booking)**

> Pengajuan booking belum menjadi konfirmasi final. Staff `[Nama MUA]` akan meninjau jadwal dan lokasi terlebih dahulu.

---

## Flow booking

Gunakan empat tahap yang terlihat di frontend agar client tahu posisi dan langkah berikutnya:

```text
1 Pilih layanan  →  2 Pilih tanggal  →  3 Isi detail  →  4 Kirim
```

### Tahap 1 — Pilih layanan

Pilih satu layanan makeup. Pilihan ini masih dapat diubah sebelum pengajuan dikirim.

| Layanan | Harga mulai |
|---|---:|
| Makeup Natural | Rp500.000 |
| Makeup Party | Rp750.000 |
| Makeup Wedding | Rp1.500.000 |
| Makeup Graduation | Rp600.000 |
| Makeup Photoshoot | Rp800.000 |

Harga dan ketersediaan tetap dikonfirmasi saat booking ditinjau.

### Tahap 2 — Pilih tanggal dan cek jadwal

Pilih tanggal makeup, lalu tampilkan status jadwal:

- **Belum ada jadwal tercatat** — belum ada rentang waktu yang sudah ditetapkan staff pada tanggal tersebut.
- **Ada jadwal sibuk** — sebagian jadwal sudah terisi. Staff tetap perlu meninjau apakah kebutuhan makeup-mu dapat dijadwalkan.
- **Tampak penuh** — seluruh rentang yang terlihat sudah sibuk. Kamu tetap dapat mengajukan untuk peninjauan khusus atau memilih tanggal lain.

State yang harus tersedia:

- Loading: `Sedang mengecek jadwal...`
- Empty: `Belum ada jadwal tercatat pada tanggal ini.`
- Error: `Jadwal belum dapat dimuat. Coba lagi atau lanjutkan pengajuan tanpa hasil cek jadwal.`

> Hasil cek jadwal hanya informasi awal. Sistem tidak mengunci slot dan tidak menjamin booking tersedia.

### Tahap 3 — Isi detail client, waktu, dan lokasi

Masukkan **jam selesai yang kamu usulkan** serta data client. Jam mulai aktual tidak dipilih client; staff akan menentukannya berdasarkan jadwal dan kebutuhan layanan.

Field wajib:

- Nama
- Nomor telepon
- Layanan
- Tanggal makeup
- Jam selesai yang diusulkan
- Alamat makeup

Field opsional:

- Link Google Maps atau pin lokasi
- Catatan kebutuhan makeup
- Alergi atau sensitivitas kulit
- Kebutuhan trial makeup
- Jumlah orang dan detail kebutuhan masing-masing untuk booking grup

Informasi lokasi:

- Area layanan: `[Kota/area layanan]`
- Biaya perjalanan: `[ketentuan biaya perjalanan]`

### Tahap 4 — Tinjau dan kirim

Sebelum mengirim, tampilkan ringkasan yang dapat diperiksa dan diubah:

- layanan dan harga mulai;
- tanggal makeup;
- jam selesai usulan;
- nama dan nomor telepon;
- alamat makeup;
- catatan tambahan.

Tombol akhir: **Kirim**

Teks pendamping tombol:

> Dengan mengirim pengajuan, kamu memahami bahwa booking masih ditinjau staf. Kamu akan menerima link pembayaran setelah jadwal ditetapkan, dan bisa melacaknya lewat booking code.

---

## Setelah mengirim pengajuan

### Success state

**Pengajuan booking diterima.**

Kamu langsung mendapat **booking code** (mis. `PRSDJ6FM`) beserta link tracking untuk memantau status pembayaran.

Langkah berikutnya:

1. Staff `[Nama MUA]` meninjau tanggal, layanan, dan lokasi.
2. Staff menentukan jam mulai aktual berdasarkan kebutuhan layanan dan jadwal.
3. Kamu menerima link pembayaran Midtrans via WhatsApp, atau langsung dari halaman sukses.
4. Status pembayaran ter-update secara real-time setelah pembayaran selesai.

Waktu respons: `[waktu respons konfirmasi]`.

### Jika pengajuan gagal

Tampilkan pesan bahwa booking belum diterima, jelaskan penyebabnya bila tersedia, pertahankan semua isian, dan sediakan tombol **Coba lagi**.

---

## FAQ singkat

### Apakah hasil cek jadwal merupakan konfirmasi?

Bukan. Hasil cek jadwal hanya menunjukkan rentang waktu yang sudah tercatat. Slot tidak otomatis dikunci dan keputusan akhir dilakukan setelah staff meninjau pengajuan.

### Apa arti status pending?

Pengajuan sudah diterima, tetapi belum menjadi konfirmasi final. Staff masih perlu mengecek jadwal, menentukan jam aktual, dan menyelesaikan detail booking.

### Apakah saya bisa menentukan jam mulai?

Tidak. Client mengusulkan tanggal dan jam selesai. Staff menentukan jam mulai aktual setelah mengecek jadwal dan kebutuhan layanan.

### Apakah ada biaya perjalanan?

Ketentuannya adalah `[ketentuan biaya perjalanan]`. Biaya akhir bergantung pada lokasi makeup dan dikonfirmasi bersama detail booking.

### Bagaimana cara pembayarannya?

Pembayaran diproses melalui Midtrans setelah staff menetapkan jadwal. Ketentuan nominal dan batas pembayaran: `[ketentuan pembayaran]`.

### Bagaimana kebijakan pembatalan atau reschedule?

Kebijakan pembatalan dan perubahan jadwal: `[kebijakan pembatalan/reschedule]`. Hubungi tim secepatnya jika ada perubahan.

### Bagaimana jika saya memiliki alergi atau kulit sensitif?

Tuliskan alergi, sensitivitas kulit, atau produk yang perlu dihindari pada catatan booking. Staff akan meninjau kebutuhan tersebut.

### Apakah bisa booking untuk beberapa orang?

Tuliskan jumlah orang, jenis acara, dan kebutuhan makeup masing-masing pada catatan booking agar staff dapat meninjau jadwalnya.

---

## Catatan implementasi frontend

- Gunakan `Cek jadwal & ajukan booking` sebagai satu-satunya CTA utama di hero dan header.
- Arahkan CTA langsung ke section `#booking`.
- Gunakan progress yang jujur: `1 Pilih layanan → 2 Pilih tanggal → 3 Isi detail → 4 Kirim`.
- Mulai form dengan layanan yang paling umum bila konfigurasi bisnis sudah menentukannya; default harus mudah diubah.
- Jangan menetapkan default tanggal atau jam yang dapat dianggap sebagai slot tersedia.
- Label semua field secara terlihat dan tampilkan error spesifik di dekat field.
- Pertahankan isian ketika cek jadwal gagal atau submit perlu dicoba ulang.
- Sediakan state loading, empty, busy, full, error, dan success yang ramah mobile.
- Gunakan `aria-live` untuk perubahan status jadwal dan hasil submit.
- Pastikan tombol/input nyaman disentuh, focus state terlihat, dan kontras terbaca.
- Hormati `prefers-reduced-motion`.
- Jangan tambahkan autentikasi client atau mekanisme penguncian slot di landing page.
- Ukur event `booking_cta_click`, `booking_form_start`, `booking_submit_success`, dan `booking_submit_error`.
