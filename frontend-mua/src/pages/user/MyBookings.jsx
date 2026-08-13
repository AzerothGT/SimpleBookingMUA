import { Link } from 'react-router-dom'
import Navbar from '../../components/Navbar'

export default function MyBookings() {
  return (
    <main>
      <Navbar />
      <section className="booking-shell min-h-0" aria-labelledby="my-bookings-title">
        <div className="max-w-2xl">
          <span className="eyebrow">Area pengguna</span>
          <h1 id="my-bookings-title" className="m-0 mt-3 font-display text-5xl font-normal tracking-[-.06em]" style={{ color: 'var(--ink)' }}>Booking saya.</h1>
          <p className="mt-5 text-base leading-7" style={{ color: 'var(--muted)' }}>Halaman riwayat booking akan tersedia setelah akun pengguna diaktifkan.</p>
        </div>
        <Link className="button button-primary mt-8" to="/booking">Buat pengajuan booking baru</Link>
      </section>
    </main>
  )
}
