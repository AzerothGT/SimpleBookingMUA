import { ArrowClockwiseIcon } from '@phosphor-icons/react'
import { useState } from 'react'
import DashboardSidebar from './DashboardSidebar'

export default function AdminLayout({ eyebrow = 'Ruang kerja', title, description, action, onRefresh, isLoading = false, children }) {
  const [role, setRole] = useState(() => window.localStorage.getItem('demo_role') || 'admin')

  const handleRoleChange = (nextRole) => {
    setRole(nextRole)
    window.localStorage.setItem('demo_role', nextRole)
  }

  return (
    <main className="admin-page">
      <div className="admin-shell">
        <DashboardSidebar role={role} onRoleChange={handleRoleChange} />
        <section className="admin-page-content">
          <header className="admin-page-heading">
            <div><span className="eyebrow">{eyebrow}</span><h1>{title}</h1>{description && <p>{description}</p>}</div>
            <div className="admin-page-actions">{onRefresh && <button className="refresh-button" type="button" onClick={onRefresh} disabled={isLoading}><ArrowClockwiseIcon size={17} className={isLoading ? 'is-spinning' : ''} aria-hidden="true" /> {isLoading ? 'Memuat' : 'Refresh'}</button>}{action}</div>
          </header>
          {children}
        </section>
      </div>
    </main>
  )
}
