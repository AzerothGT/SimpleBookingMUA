import { ArrowClockwiseIcon } from '@phosphor-icons/react'
import DashboardSidebar from './DashboardSidebar'
import { getStoredSession } from '../session'

export default function AdminLayout({ eyebrow = 'Ruang kerja', title, description, action, onRefresh, isLoading = false, children }) {
  const sessionUser = getStoredSession()?.user

  return (
    <main className="admin-page">
      <div className="admin-shell">
        <DashboardSidebar role={sessionUser?.role ?? 'staff'} userName={sessionUser?.name} />
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
