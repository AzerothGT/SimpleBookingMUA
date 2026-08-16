import { CalendarCheckIcon, HouseIcon, ListBulletsIcon, PaintBrushIcon, SignOutIcon } from '@phosphor-icons/react'
import { NavLink, useNavigate } from 'react-router-dom'
import { clearSession } from '../session'

const links = [
  { to: '/admin', label: 'Dashboard', icon: HouseIcon, end: true },
  { to: '/admin/bookings', label: 'Booking', icon: CalendarCheckIcon },
  { to: '/admin/services', label: 'Layanan', icon: PaintBrushIcon },
  { to: '/admin/activity', label: 'Aktivitas', icon: ListBulletsIcon, ownerOnly: true },
]

export default function DashboardSidebar({ role, userName }) {
  const navigate = useNavigate()
  const visibleLinks = role === 'staff' ? links.filter((link) => !link.ownerOnly) : links

  const handleLogout = () => {
    clearSession()
    navigate('/login', { replace: true })
  }

  return (
    <aside className="dashboard-sidebar">
      <div className="dashboard-brand"><span className="dashboard-brand-mark">CP</span><span>Cantik itu<br />Pilihan</span></div>
      <div className="sidebar-section-label">Ruang kerja</div>
      <nav className="dashboard-nav" aria-label="Navigasi admin">
        {visibleLinks.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to} to={to} end={end} className={({ isActive }) => `dashboard-nav-link${isActive ? ' active' : ''}`}>
            <Icon size={18} weight="bold" aria-hidden="true" />
            <span>{label}</span>
          </NavLink>
        ))}
      </nav>
      <div className="sidebar-bottom">
        <div className="role-switcher"><span>{userName ?? 'Pengguna'}</span><strong className="role-value">{role}</strong></div>
        <button className="dashboard-logout" type="button" onClick={handleLogout}><SignOutIcon size={16} aria-hidden="true" /> Keluar</button>
      </div>
    </aside>
  )
}
