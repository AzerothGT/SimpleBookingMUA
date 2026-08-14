import { statusLabels } from '../pages/admin/dashboardData'

export default function StatusBadge({ status }) {
  const label = statusLabels[status] ?? status

  return <span className={`status-badge status-badge--${status}`} aria-label={`Status ${label}`}>{label}</span>
}
