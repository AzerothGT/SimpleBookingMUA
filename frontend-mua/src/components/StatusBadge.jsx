const statusLabels = {
  pending: 'Menunggu jadwal',
  confirmed: 'Terkonfirmasi',
  done: 'Selesai',
  cancelled: 'Dibatalkan',
}

export default function StatusBadge({ status }) {
  const label = statusLabels[status] ?? status

  return <span className={`status-badge status-badge--${status}`} aria-label={`Status ${label}`}>{label}</span>
}
