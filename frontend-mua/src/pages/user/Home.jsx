import { ArrowDown } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'
import Navbar from '../../components/Navbar'

export default function Home() {
  return (
    <main>
      <Navbar />
      <section className="hero-section" aria-labelledby="hero-title">
        <div className="hero-copy">
          <span className="eyebrow">Booking makeup, tanpa akun</span>
          <h1 id="hero-title">Pilih tanggal. Kami bantu atur sisanya.</h1>
          <p>Pilih layanan dan tanggal yang kamu inginkan, lihat jadwal yang sudah tercatat, lalu kirim pengajuan dalam beberapa langkah singkat.</p>
          <Link className="button button-primary" to="/booking">Cek jadwal & ajukan booking <ArrowDown size={16} weight="bold" aria-hidden="true" /></Link>
          <p className="hero-note"><span className="dot" /> Pengajuan awal berstatus pending — bukan konfirmasi final.</p>
        </div>
        <div className="hero-art" aria-hidden="true">
          <div className="art-orbit orbit-one" />
          <div className="art-orbit orbit-two" />
          <div className="art-card"><span>01</span><strong>your<br />moment</strong><em>made beautiful</em></div>
        </div>
      </section>
    </main>
  )
}
