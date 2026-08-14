import { ArrowUpRightIcon, ClockIcon } from '@phosphor-icons/react'

export default function AgendaCard({ items, isLoading, onOpenBooking }) {
  return (
    <section className="dashboard-panel agenda-panel" aria-labelledby="agenda-title">
      <div className="panel-heading"><div><span className="eyebrow">Operasional</span><h2 id="agenda-title">Agenda hari ini</h2></div><ClockIcon size={22} aria-hidden="true" /></div>
      {isLoading ? <p className="dashboard-loading">Memuat agenda...</p> : items.length === 0 ? <p className="dashboard-empty">Belum ada pekerjaan untuk hari ini.</p> : <div className="agenda-list">{items.map((item) => <div className="agenda-item" key={item.id}><time>{item.time}</time><div><strong>{item.clientName}</strong><span>{item.serviceName} · {item.staffName}</span></div><button className="icon-action" type="button" onClick={() => onOpenBooking(item)} aria-label={`Buka booking ${item.clientName}`}><ArrowUpRightIcon size={17} /></button></div>)}</div>}
    </section>
  )
}
