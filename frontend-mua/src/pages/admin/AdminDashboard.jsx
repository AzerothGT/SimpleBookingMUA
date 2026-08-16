import { useCallback, useEffect, useMemo, useState } from 'react'
import { ArrowClockwiseIcon, CalendarBlankIcon, CheckCircleIcon, ClipboardTextIcon, CurrencyCircleDollarIcon, WarningCircleIcon } from '@phosphor-icons/react'
import { formatCurrency, formatDashboardDate, getAdminBooking, normalizeBookings } from '../../api/adminApi'
import { listBookings } from '../../api/bookingApi'
import AgendaCard from '../../components/AgendaCard'
import AdminDrawer from '../../components/AdminDrawer'
import BookingTable from '../../components/BookingTable'
import DashboardSidebar from '../../components/DashboardSidebar'
import MetricCard from '../../components/MetricCard'
import StatusBadge from '../../components/StatusBadge'
import { getStoredSession } from '../../session'
const todayKey = new Date().toISOString().slice(0, 10)

export default function AdminDashboard() {
  const [records, setRecords] = useState([])
  const [sessionUser] = useState(() => getStoredSession()?.user)
  const [statusFilter, setStatusFilter] = useState('all')
  const [isLoading, setIsLoading] = useState(true)
  const [selected, setSelected] = useState(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState('')
  const role = sessionUser?.role ?? 'staff'

  const loadDashboard = useCallback(async () => {
    setIsLoading(true)

    try {
      const payload = await listBookings()
      const normalized = normalizeBookings(payload)
      setRecords(normalized)
    } catch {
      setRecords([])
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => { loadDashboard() }, [loadDashboard])

  const visibleRecords = useMemo(() => {
    if (role !== 'staff') return records
    return records.filter((record) => record.staffId === sessionUser?.id)
  }, [records, role, sessionUser])

  const filteredRecords = useMemo(() => statusFilter === 'all' ? visibleRecords : visibleRecords.filter((record) => record.status === statusFilter), [statusFilter, visibleRecords])
  const agenda = visibleRecords.filter((record) => record.date === todayKey)
  const metrics = useMemo(() => ({
    attention: visibleRecords.filter((record) => record.status === 'pending').length,
    today: agenda.length,
    confirmed: visibleRecords.filter((record) => record.status === 'confirmed').length,
    revenue: visibleRecords.filter((record) => record.status !== 'cancelled').reduce((sum, record) => sum + record.amount, 0),
  }), [agenda.length, visibleRecords])

  const openBookingDetail = useCallback(async (item) => {
    setSelected({ id: item.id })
    setDetailError('')
    setDetailLoading(true)
    try {
      const payload = await getAdminBooking(item.id)
      setSelected(payload?.data ?? payload)
    } catch (error) {
      setDetailError(error.message)
    } finally {
      setDetailLoading(false)
    }
  }, [])

  const closeBookingDetail = () => {
    setSelected(null)
    setDetailError('')
  }

  return (
    <main className="admin-page">
      <div className="admin-shell">
        <DashboardSidebar role={role} userName={sessionUser?.name} />
        <section className="dashboard-main">
          <header className="dashboard-topbar">
            <div><span className="eyebrow">Ruang kendali</span><h1>Halo, {sessionUser?.name ?? 'tim'}.</h1><p>{formatDashboardDate()}</p></div>
            <div className="dashboard-actions"><span className="dashboard-date"><CalendarBlankIcon size={17} aria-hidden="true" /> Hari ini</span><button className="refresh-button" type="button" onClick={loadDashboard} disabled={isLoading}><ArrowClockwiseIcon size={17} className={isLoading ? 'is-spinning' : ''} aria-hidden="true" /> {isLoading ? 'Memuat' : 'Refresh'}</button></div>
          </header>

          <div className="dashboard-metrics">
            <MetricCard label="Perlu ditindaklanjuti" value={metrics.attention} caption="Pengajuan menunggu jadwal" icon={WarningCircleIcon} />
            <MetricCard label="Agenda hari ini" value={metrics.today} caption="Pekerjaan terjadwal" icon={CalendarBlankIcon} />
            <MetricCard label="Terkonfirmasi" value={metrics.confirmed} caption="Booking siap dikerjakan" icon={CheckCircleIcon} />
            <MetricCard label="Estimasi pemasukan" value={formatCurrency(metrics.revenue)} caption="Dari booking aktif" icon={CurrencyCircleDollarIcon} />
          </div>

          <div className="dashboard-grid">
            <AgendaCard items={agenda} isLoading={isLoading} onOpenBooking={openBookingDetail} />
            <section className="dashboard-panel quick-panel"><span className="eyebrow">Fokus berikutnya</span><h2>Jaga jadwal tetap rapi.</h2><p>Periksa pengajuan baru dan pastikan setiap booking punya staff serta jam kerja yang jelas.</p><a className="dashboard-action" href="#booking-table-title"><ClipboardTextIcon size={17} aria-hidden="true" /> Periksa booking</a></section>
          </div>

          <div className="booking-toolbar"><div><span className="eyebrow">Alur kerja</span><h2>Semua booking</h2></div><label className="dashboard-filter">Filter status<select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}><option value="all">Semua status</option><option value="pending">Menunggu jadwal</option><option value="confirmed">Terkonfirmasi</option><option value="done">Selesai</option><option value="cancelled">Dibatalkan</option></select></label></div>
          <BookingTable items={filteredRecords.slice(0, 6)} isLoading={isLoading} onOpenBooking={openBookingDetail} />
          <AdminDrawer open={Boolean(selected)} title={selected?.client_name ?? 'Booking'} onClose={closeBookingDetail}>
            {detailLoading ? <div className="admin-state">Memuat detail...</div> : detailError ? <div className="admin-state admin-state-error" role="alert">{detailError}</div> : selected && <div className="detail-content"><div className="detail-summary"><span className="eyebrow">Klien</span><strong>{selected.client_name}</strong><span>{selected.client_phone}</span><span>{selected.client_address}</span></div><div className="detail-grid"><div><span className="detail-label">Tanggal usulan</span><strong>{selected.client_requested_date}</strong></div><div><span className="detail-label">Jam selesai</span><strong>{selected.client_requested_end_time}</strong></div></div>{selected.starts_at && selected.ends_at && <div className="detail-grid"><div><span className="detail-label">Mulai</span><strong>{new Date(selected.starts_at).toLocaleString('id-ID')}</strong></div><div><span className="detail-label">Selesai</span><strong>{new Date(selected.ends_at).toLocaleString('id-ID')}</strong></div></div>}<div className="detail-block"><span className="detail-label">Layanan</span>{(selected.services ?? []).map((service) => <div className="detail-line" key={service.id}><span>{service.name} × {service.qty}</span><strong>{formatCurrency(service.subtotal)}</strong></div>)}<div className="detail-line"><span>Total</span><strong>{formatCurrency((selected.services ?? []).reduce((sum, service) => sum + Number(service.subtotal ?? 0), 0))}</strong></div></div>{selected.staff?.name && <div className="detail-block"><span className="detail-label">Staff</span><div className="detail-line"><span>{selected.staff.name}</span></div></div>}{selected.tasks?.length > 0 && <div className="detail-block"><span className="detail-label">Checklist</span>{selected.tasks.map((task) => <div className="detail-line" key={task.id}><span>{task.title}</span><strong>{task.is_done ? 'Selesai' : 'Belum'}</strong></div>)}</div>}<div className="detail-block"><span className="detail-label">Status</span><div className="detail-line"><StatusBadge status={selected.status} /></div></div>{selected.notes && <div className="detail-block"><span className="detail-label">Catatan</span><p>{selected.notes}</p></div>}<div className="detail-block"><span className="detail-label">Booking ID</span><div className="detail-line"><code>{selected.id}</code></div></div></div>}
          </AdminDrawer>
        </section>
      </div>
    </main>
  )
}
