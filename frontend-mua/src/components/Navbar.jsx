import { Link, useLocation } from 'react-router-dom'

export default function Navbar() {
  const location = useLocation()

  return (
    <header className="site-header">
      <Link className="brand" to="/" aria-label="Kembali ke beranda">Cantik itu Pilihan</Link>
      <nav className="nav-links" aria-label="Navigasi utama">
        <Link className={`nav-link ${location.pathname === '/services' ? 'active' : ''}`} to="/services" aria-current={location.pathname === '/services' ? 'page' : undefined}>Layanan</Link>
        <Link className="header-cta" to="/booking">Mulai booking</Link>
      </nav>
    </header>
  )
}
