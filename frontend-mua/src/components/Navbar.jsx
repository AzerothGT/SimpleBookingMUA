import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { clearSession, getStoredSession, hasValidSession } from '../session'

export default function Navbar() {
  const location = useLocation()
  const navigate = useNavigate()
  const [user, setUser] = useState(null)

  useEffect(() => {
    setUser(hasValidSession() ? getStoredSession()?.user ?? null : null)
  }, [location.pathname])

  const handleLogout = () => {
    clearSession()
    setUser(null)
    navigate('/', { replace: true })
  }

  return (
    <header className="site-header">
      <Link className="brand" to="/" aria-label="Kembali ke beranda">Cantik itu Pilihan</Link>
      <nav className="nav-links" aria-label="Navigasi utama">
        <Link className={`nav-link ${location.pathname === '/services' ? 'active' : ''}`} to="/services" aria-current={location.pathname === '/services' ? 'page' : undefined}>Layanan</Link>
        {user
          ? (
            <>
              <Link className="nav-link" to="/admin">Dashboard</Link>
              <span className="nav-user" aria-label={`Login sebagai ${user.name}`}>{user.name}</span>
              <button type="button" className="nav-link nav-logout" onClick={handleLogout}>Keluar</button>
            </>
          )
          : <Link className="header-cta" to="/login">Masuk</Link>}
      </nav>
    </header>
  )
}
