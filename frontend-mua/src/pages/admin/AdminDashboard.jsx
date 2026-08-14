import { useCallback, useEffect, useMemo, useState } from 'react'
import { ArrowClockwiseIcon, CalendarBlankIcon, CheckCircleIcon, ClipboardTextIcon, CurrencyCircleDollarIcon, WarningCircleIcon } from '@phosphor-icons/react'
import { listBookings } from '../../api/bookingApi'
import AgendaCard from '../../components/AgendaCard'
import BookingTable from '../../components/BookingTable'
import DashboardSidebar from '../../components/DashboardSidebar'
import MetricCard from '../../components/MetricCard'
import { dashboardFixture, formatCurrency, formatDashboardDate, normalizeBookings } from './dashboardData'

const todayKey = new Date().toISOString().slice(0, 10)

export default function AdminDashboard() {
  const [records, setRecords] = useState(dashboardFixture)
  const [role, setRole] = useState(() => window.localStorage.getItem('demo_role') || 'admin')
  const [statusFilter, setStatusFilter] = useState('all')
  const [isLoading, setIsLoading] = useState(false)
  const [isFallback, setIsFallback] = useState(true)

  const loadDashboard = useCallback(async () => {
    setIsLoading(true)
    const token = window.localStorage.getItem('auth_token')

    if (!token) {
      setIsFallback(true)
      setIsLoading(false)
      return
    }

    try {
      const payload = await listBookings()
      const normalized = normalizeBookings(payload)
      if (normalized.length > 0) {
        setRecords(normalized)
        setIsFallback(false)
      } else {
        setIsFallback(true)
      }
    } catch {
      setIsFallback(true)
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => { loadDashboard() }, [loadDashboard])

  const handleRoleChange = (nextRole) => {
    setRole(nextRole)
    window.localStorage.setItem('demo_role', nextRole)
  }

  const visibleRecords = useMemo(() => {
    if (role !== 'staff') return records
    return records.filter((record) => record.staffId === 'staff-1' || record.staffName === 'Sinta')
  }, [records, role])

  const filteredRecords = useMemo(() => statusFilter === 'all' ? visibleRecords : visibleRecords.filter((record) => record.status === statusFilter), [statusFilter, visibleRecords])
  const agenda = visibleRecords.filter((record) => record.date === todayKey)
  const metrics = useMemo(() => ({
    attention: visibleRecords.filter((record) => record.status === 'pending').length,
    today: agenda.length,
    confirmed: visibleRecords.filter((record) => record.status === 'confirmed').length,
    revenue: visibleRecords.filter((record) => record.status !== 'cancelled').reduce((sum, record) => sum + record.amount, 0),
  }), [agenda.length, visibleRecords])

  const handleOpenBooking = (item) => window.alert(`Booking ${item.clientName}\nID: ${item.id}`)

  return (
    <main className="admin-page">
      <div className="admin-shell">
        <DashboardSidebar role={role} onRoleChange={handleRoleChange} />
        <section className="dashboard-main">
          <header className="dashboard-topbar">
            <div><span className="eyebrow">Ruang kendali</span><h1>Halo, {role === 'staff' ? 'tim' : role}.</h1><p>{formatDashboardDate()}</p></div>
            <div className="dashboard-actions"><span className="dashboard-date"><CalendarBlankIcon size={17} aria-hidden="true" /> Hari ini</span><button className="refresh-button" type="button" onClick={loadDashboard} disabled={isLoading}><ArrowClockwiseIcon size={17} className={isLoading ? 'is-spinning' : ''} aria-hidden="true" /> {isLoading ? 'Memuat' : 'Refresh'}</button></div>
          </header>

          {isFallback && <div className="dashboard-notice" role="status"><WarningCircleIcon size={19} aria-hidden="true" /><span>Mode pratinjau aktif. Data contoh ditampilkan karena dashboard belum terhubung ke sesi admin.</span></div>}

          <div className="dashboard-metrics">
            <MetricCard label="Perlu ditindaklanjuti" value={metrics.attention} caption="Pengajuan menunggu jadwal" icon={WarningCircleIcon} />
            <MetricCard label="Agenda hari ini" value={metrics.today} caption="Pekerjaan terjadwal" icon={CalendarBlankIcon} />
            <MetricCard label="Terkonfirmasi" value={metrics.confirmed} caption="Booking siap dikerjakan" icon={CheckCircleIcon} />
            <MetricCard label="Estimasi pemasukan" value={formatCurrency(metrics.revenue)} caption="Dari booking aktif" icon={CurrencyCircleDollarIcon} />
          </div>

          <div className="dashboard-grid">
            <AgendaCard items={agenda} isLoading={isLoading} onOpenBooking={handleOpenBooking} />
            <section className="dashboard-panel quick-panel"><span className="eyebrow">Fokus berikutnya</span><h2>Jaga jadwal tetap rapi.</h2><p>Periksa pengajuan baru dan pastikan setiap booking punya staff serta jam kerja yang jelas.</p><a className="dashboard-action" href="#booking-table-title"><ClipboardTextIcon size={17} aria-hidden="true" /> Periksa booking</a></section>
          </div>

          <div className="booking-toolbar"><div><span className="eyebrow">Alur kerja</span><h2>Semua booking</h2></div><label className="dashboard-filter">Filter status<select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}><option value="all">Semua status</option><option value="pending">Menunggu jadwal</option><option value="confirmed">Terkonfirmasi</option><option value="done">Selesai</option><option value="cancelled">Dibatalkan</option></select></label></div>
          <BookingTable items={filteredRecords.slice(0, 6)} isLoading={isLoading} onOpenBooking={handleOpenBooking} />
        </section>
      </div>
    </main>
  )
}
