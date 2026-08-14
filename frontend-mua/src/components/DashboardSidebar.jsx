import { ChartBarIcon, ClipboardTextIcon, GearIcon, HouseIcon, SignOutIcon } from '@phosphor-icons/react'
import { NavLink } from 'react-router-dom'

const links = [
  { to: '/admin', label: 'Dashboard', icon: HouseIcon, end: true },
  { to: '/admin/bookings', label: 'Booking', icon: ClipboardTextIcon },
  { to: '/admin/services', label: 'Layanan', icon: GearIcon },
  { to: '/admin/activity', label: 'Aktivitas', icon: ChartBarIcon },
]

export default function DashboardSidebar({ role, onRoleChange }) {
  return (
    <aside className="dashboard-sidebar">
      <div className="dashboard-brand"><span className="dashboard-brand-mark">CP</span><span>Cantik itu<br />Pilihan</span></div>
      <div className="sidebar-section-label">Ruang kerja</div>
      <nav className="dashboard-nav" aria-label="Navigasi admin">
        {links.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to} to={to} end={end} className={({ isActive }) => `dashboard-nav-link${isActive ? ' active' : ''}`}>
            <Icon size={18} weight="bold" aria-hidden="true" />
            <span>{label}</span>
          </NavLink>
        ))}
      </nav>
      <div className="sidebar-bottom">
        <label className="role-switcher">Tampilan peran<select value={role} onChange={(event) => onRoleChange(event.target.value)}><option value="admin">Admin</option><option value="owner">Owner</option><option value="staff">Staff</option></select></label>
        <a className="dashboard-logout" href="/"><SignOutIcon size={16} aria-hidden="true" /> Keluar</a>
      </div>
    </aside>
  )
}
