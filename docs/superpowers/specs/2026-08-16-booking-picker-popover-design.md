# Booking Picker Popover Design

## Goal
Memperbaiki step pemilihan tanggal dan jam dengan dua popover terpisah agar kalender dan time picker tidak memenuhi layout utama.

## Behavior
- Step 2 menampilkan trigger tanggal dan jam dengan nilai yang sudah dipilih.
- Trigger tanggal membuka kalender; pemilihan tanggal menutup popover kalender.
- Trigger jam membuka analog time picker; pemilihan angka jam berpindah ke menit, dan memilih menit menutup popover.
- Hanya satu popover terbuka pada satu waktu.
- Klik di luar popover menutupnya.
- Availability tetap ditampilkan setelah tanggal dipilih.
- Error validasi tetap berada di bawah trigger terkait.

## Layout
- Desktop: dua trigger seimbang dalam grid dua kolom.
- Popover diposisikan relatif terhadap trigger dengan border, background, dan shadow ringan.
- Mobile: trigger menjadi satu kolom; popover melebar mengikuti container dan tidak keluar viewport.
- Ukuran kalender dan clock tetap besar untuk touch target, tetapi tidak mengambil tinggi layout sebelum dibuka.

## Accessibility
- Trigger memakai button dengan `aria-expanded` dan `aria-controls`.
- Popover diberi label yang jelas.
- Klik dan keyboard tetap menggunakan native button behavior.

## Files
- `frontend-mua/src/pages/user/BookingPage.jsx`: state popover, outside click, trigger/popover markup.
- `frontend-mua/src/App.css`: trigger, popover, responsive layout.

## Validation
- `npm run lint`
- `npm run build`
- `git diff --check`
