import { ArrowUpRightIcon } from '@phosphor-icons/react'
import { formatCurrency } from '../pages/admin/dashboardData'
import StatusBadge from './StatusBadge'

export default function BookingTable({ items, isLoading, onOpenBooking }) {
  return (
    <section className="dashboard-panel booking-panel" aria-labelledby="booking-table-title">
      <div className="panel-heading"><div><span className="eyebrow">Paling baru</span><h2 id="booking-table-title">Booking terbaru</h2></div><span className="panel-count">{items.length} booking</span></div>
      {isLoading ? <p className="dashboard-loading">Memuat booking...</p> : items.length === 0 ? <p className="dashboard-empty">Tidak ada booking dengan filter ini.</p> : <div className="booking-table-wrap"><table className="booking-table"><thead><tr><th>Klien</th><th>Layanan</th><th>Tanggal</th><th>Staff</th><th>Total</th><th>Status</th><th><span className="sr-only">Aksi</span></th></tr></thead><tbody>{items.map((item) => <tr key={item.id}><td data-label="Klien"><strong>{item.clientName}</strong></td><td data-label="Layanan">{item.serviceName}</td><td data-label="Tanggal">{item.date}<small>{item.time}</small></td><td data-label="Staff">{item.staffName}</td><td data-label="Total">{formatCurrency(item.amount)}</td><td data-label="Status"><StatusBadge status={item.status} /></td><td data-label="Aksi"><button className="table-action" type="button" onClick={() => onOpenBooking(item)}>Detail <ArrowUpRightIcon size={14} /></button></td></tr>)}</tbody></table></div>}
    </section>
  )
}
