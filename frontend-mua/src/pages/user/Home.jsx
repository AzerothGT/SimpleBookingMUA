import { useState } from 'react'
import { ArrowDownIcon } from '@phosphor-icons/react'
import { Link, useNavigate } from 'react-router-dom'
import Navbar from '../../components/Navbar'

export default function Home() {
  const navigate = useNavigate()
  const [isLeaving, setIsLeaving] = useState(false)

  const handleBookingClick = (event) => {
    event.preventDefault()
    if (isLeaving) return

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      navigate('/booking')
      return
    }

    setIsLeaving(true)
    window.setTimeout(() => navigate('/booking'), 320)
  }

  return (
    <main className={`home-page ${isLeaving ? 'is-leaving' : ''}`}>
      <Navbar />
      <section className="hero-section" aria-labelledby="hero-title">
        <div className="hero-copy">
          <span className="eyebrow">Booking makeup, tanpa akun</span>
          <h1 id="hero-title">Cantik itu Pilihan</h1>
          <p>Pilih layanan dan tanggal terbaik untukmu, lalu kirim pengajuan booking dengan mudah dalam beberapa langkah.</p>
          <Link className="button button-primary" to="/booking" onClick={handleBookingClick} aria-disabled={isLeaving}>Cek jadwal & ajukan booking <ArrowDownIcon size={16} weight="bold" aria-hidden="true" /></Link>

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
