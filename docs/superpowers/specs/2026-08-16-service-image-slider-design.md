# Service Image Slider & Gallery Design Spec

## 1. Overview
Fitur ini memungkinkan penambahan, pengelolaan, dan penayangan slide image (carousel galeri) untuk setiap layanan MUA, baik pada dashboard admin (pengelolaan galeri gambar) maupun antarmuka publik/klien (halaman Services katalog dan halaman Booking).

## 2. Arsitektur & Data Flow

### Backend API
Backend Laravel sudah memiliki resource dan relasi `Service` -> `ServiceImage`:
- `GET /services`: Menghasilkan daftar layanan dengan relasi `images` (`serviceImages` via `ServiceResource`).
- `GET /services/{service}`: Menghasilkan detail layanan beserta daftar gambar terurut.
- `POST /services/{service}/serviceImages`: Menambahkan entri gambar baru (`image_url`, `image_source`='external', `is_cover`, `sort_order`).
- `PUT /services/{service}/serviceImages/{serviceImage}`: Memperbarui urutan, status cover, atau URL gambar.
- `DELETE /services/{service}/serviceImages/{serviceImage}`: Menghapus gambar layanan.

### Frontend API Client (`frontend-mua/src/api/adminApi.js` & `bookingApi.js`)
- Menambahkan fungsi helper API:
  - `addAdminServiceImage(serviceId, data)`
  - `updateAdminServiceImage(serviceId, imageId, data)`
  - `deleteAdminServiceImage(serviceId, imageId)`

### Komponen Frontend
1. **`ImageSlider` Component (`frontend-mua/src/components/ImageSlider.jsx`)**:
   - Reusable untuk kartu layanan publik, booking page, dan preview.
   - Mendukung:
     - Navigasi Next & Prev (panah)
     - Pagination dots (indikator slide aktif & clickable)
     - Fallback placeholder jika layanan belum memiliki gambar
     - Badge "Cover" atau aspect ratio responsif yang elegan (object-cover)
     - Touch/swipe friendly dan tombol ramah aksesibilitas (aria-label).

2. **Admin Service Gallery Manager (`frontend-mua/src/pages/admin/ServicesPage.jsx`)**:
   - Menambahkan tombol aksi "Kelola Galeri" di tabel layanan admin.
   - Modal/Drawer Galeri Layanan:
     - Menampilkan grid/list gambar yang sudah ada dengan thumbnail, badge cover, dan sort order.
     - Form tambah gambar baru via URL (Image URL, status Is Cover, Sort Order).
     - Tombol set as cover dan hapus gambar.
     - Refresh data instan setelah operasi gambar.

3. **Client Services & Booking UI (`Services.jsx`, `ServiceCard.jsx`, `BookingPage.jsx`)**:
   - `ServiceCard.jsx`: Menampilkan `ImageSlider` di bagian atas/sisi kartu layanan.
   - `BookingPage.jsx`: Menampilkan thumbnail/slider gambar layanan yang dipilih agar klien dapat melihat visual hasil makeup sebelum konfirmasi pemesanan.

## 3. Error Handling & Edge Cases
- Gambar gagal dimuat (broken URL): Fallback image placeholder elegan dengan ikon kamera/foto.
- Layanan tanpa gambar: Tampilan default placeholder terpadu yang serasi dengan tema UI.
- Hanya 1 gambar: Sembunyikan kontrol prev/next & pagination dots.
- Cover image: Otomatis menempatkan cover image sebagai slide pertama.
