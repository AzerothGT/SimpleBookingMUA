import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'

export default function Navbar() {
  const location = useLocation()
  const navigate = useNavigate()
  const [user, setUser] = useState(null)

  useEffect(() => {
    try {
      const stored = window.localStorage.getItem('auth_user')
      setUser(stored ? JSON.parse(stored) : null)
    } catch {
      setUser(null)
    }
  }, [location.pathname])

  const handleLogout = () => {
    window.localStorage.removeItem('auth_token')
    window.localStorage.removeItem('auth_user')
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
              <span className="nav-user" aria-label={`Login sebagai ${user.name}`}>{user.name}</span>
              <button type="button" className="nav-link nav-logout" onClick={handleLogout}>Keluar</button>
            </>
          )
          : <Link className="header-cta" to="/login">Masuk</Link>}
      </nav>
    </header>
  )
}
