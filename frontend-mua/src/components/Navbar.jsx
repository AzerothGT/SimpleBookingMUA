import { Link, useLocation } from 'react-router-dom'

export default function Navbar() {
  const location = useLocation()

  return (
    <header className="site-header">
      <Link className="brand" to="/" aria-label="Kembali ke beranda">[Nama MUA]</Link>
      <nav className="flex items-center gap-6" aria-label="Navigasi utama">
        <Link className={location.pathname === '/services' ? 'header-cta' : 'location-label'} to="/services">Layanan</Link>
        <Link className="header-cta" to="/booking">Mulai booking</Link>
      </nav>
    </header>
  )
}
