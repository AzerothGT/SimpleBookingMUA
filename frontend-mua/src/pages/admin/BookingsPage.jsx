import { useCallback, useEffect, useMemo, useState } from 'react'
import { EyeIcon, PencilSimpleIcon, PlusIcon, TrashIcon } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'
import AdminDataTable from '../../components/AdminDataTable'
import AdminDrawer from '../../components/AdminDrawer'
import AdminLayout from '../../components/AdminLayout'
import ConfirmDialog from '../../components/ConfirmDialog'
import StatusBadge from '../../components/StatusBadge'
import { assignAdminBooking, changeAdminBookingStatus, deleteAdminBooking, formatCurrency, getAdminBooking, listAdminBookings, listAdminUsers, updateAdminBooking, unwrap, unwrapList } from '../../api/adminApi'

const statusOptions = [['', 'Semua status'], ['pending', 'Menunggu jadwal'], ['confirmed', 'Terkonfirmasi'], ['done', 'Selesai'], ['cancelled', 'Dibatalkan']]
const getError = (error) => error?.status === 401 ? 'Sesi login berakhir. Silakan masuk kembali.' : error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function BookingsPage() {
  const [rows, setRows] = useState([])
  const [filters, setFilters] = useState({ status: '', client_name: '', client_phone: '' })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [selected, setSelected] = useState(null)
  const [drawerLoading, setDrawerLoading] = useState(false)
  const [feedback, setFeedback] = useState('')
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [deleteLoading, setDeleteLoading] = useState(false)
  const [staff, setStaff] = useState([])

  const loadBookings = useCallback(async () => {
    if (!window.localStorage.getItem('auth_token')) { setError('Silakan masuk untuk mengakses data booking.'); return }
    setIsLoading(true); setError('')
    try { setRows(unwrapList(await listAdminBookings(filters))) } catch (requestError) { if (requestError.status === 401) window.localStorage.removeItem('auth_token'); setError(getError(requestError)) } finally { setIsLoading(false) }
  }, [filters])

  useEffect(() => { const timeout = window.setTimeout(loadBookings, 300); return () => window.clearTimeout(timeout) }, [loadBookings])
  useEffect(() => { listAdminUsers().then((payload) => setStaff(unwrapList(payload).filter((user) => ['owner', 'admin', 'staff'].includes(user.role)))).catch(() => setStaff([])) }, [])

  const openBooking = async (id) => {
    setDrawerLoading(true); setFeedback('')
    try { setSelected(unwrap(await getAdminBooking(id))) } catch (requestError) { setFeedback(getError(requestError)) } finally { setDrawerLoading(false) }
  }

  const mutate = async (operation, message) => {
    setFeedback('')
    try { const result = await operation(); setSelected(result ? unwrap(result) : selected); setFeedback(message); await loadBookings() } catch (requestError) { setFeedback(getError(requestError)) }
  }

  const handleDelete = async () => { setDeleteLoading(true); try { await deleteAdminBooking(selected.id); setConfirmOpen(false); setSelected(null); await loadBookings() } catch (requestError) { setFeedback(getError(requestError)) } finally { setDeleteLoading(false) } }

  const columns = useMemo(() => [
    { key: 'client_name', label: 'Klien', render: (row) => <strong>{row.client_name}</strong> },
    { key: 'requested', label: 'Pengajuan', render: (row) => <span>{row.client_requested_date}<small>{row.client_requested_end_time}</small></span> },
    { key: 'services', label: 'Layanan', render: (row) => <span>{(row.services ?? []).map((service) => service.name).join(', ') || '—'}</span> },
    { key: 'staff', label: 'Staff', render: (row) => row.staff?.name ?? 'Belum ditugaskan' },
    { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'actions', label: 'Aksi', render: (row) => <button className="table-action" type="button" onClick={() => openBooking(row.id)}><EyeIcon size={15} /> Detail</button> },
  ], [])

  return <AdminLayout title="Booking" description="Kelola pengajuan, jadwal, dan status pekerjaan." onRefresh={loadBookings} isLoading={isLoading} action={<Link className="admin-button admin-button-primary" to="/booking"><PlusIcon size={16} /> Booking publik</Link>}>
    <div className="admin-toolbar"><div><span className="eyebrow">Filter data</span><h2>Daftar booking</h2></div><div className="admin-filters"><select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })} aria-label="Filter status">{statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><input value={filters.client_name} onChange={(event) => setFilters({ ...filters, client_name: event.target.value })} placeholder="Cari nama klien" aria-label="Cari nama klien" /><input value={filters.client_phone} onChange={(event) => setFilters({ ...filters, client_phone: event.target.value })} placeholder="Nomor telepon" aria-label="Cari nomor telepon" /></div></div>
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadBookings} emptyMessage="Belum ada booking yang cocok." />
    <AdminDrawer open={Boolean(selected)} title={selected?.client_name ?? 'Booking'} onClose={() => setSelected(null)}>
      {drawerLoading ? <div className="admin-state">Memuat detail...</div> : selected && <BookingDetail booking={selected} staff={staff} feedback={feedback} onUpdate={(body) => mutate(() => updateAdminBooking(selected.id, body), 'Booking diperbarui.')} onStatus={(status) => mutate(() => changeAdminBookingStatus(selected.id, status), 'Status diperbarui.')} onAssign={(body) => mutate(() => assignAdminBooking(selected.id, body), 'Staff dan jadwal diperbarui.')} onDelete={() => setConfirmOpen(true)} />}
    </AdminDrawer>
    <ConfirmDialog open={confirmOpen} title="Hapus booking?" message={`Booking ${selected?.client_name ?? ''} akan dihapus permanen.`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={handleDelete} />
  </AdminLayout>
}

function BookingDetail({ booking, staff, feedback, onUpdate, onStatus, onAssign, onDelete }) {
  const [notes, setNotes] = useState(booking.notes ?? '')
  const [address, setAddress] = useState(booking.client_address ?? '')
  const [status, setStatus] = useState(booking.status)
  const [staffId, setStaffId] = useState(booking.user_id ?? booking.staff?.id ?? '')
  const [startsAt, setStartsAt] = useState(booking.starts_at ? booking.starts_at.slice(0, 16) : '')
  const [endsAt, setEndsAt] = useState(booking.ends_at ? booking.ends_at.slice(0, 16) : '')

  return <div className="detail-content"><div className="detail-summary"><span className="eyebrow">Klien</span><strong>{booking.client_name}</strong><span>{booking.client_phone}</span><span>{booking.client_address}</span></div><div className="detail-grid"><div><span className="detail-label">Tanggal usulan</span><strong>{booking.client_requested_date}</strong></div><div><span className="detail-label">Jam selesai</span><strong>{booking.client_requested_end_time}</strong></div></div><div className="detail-block"><span className="detail-label">Layanan</span>{(booking.services ?? []).map((service) => <div className="detail-line" key={service.id}><span>{service.name} × {service.qty}</span><strong>{formatCurrency(service.subtotal)}</strong></div>)}</div><div className="admin-form"><label className="admin-field"><span>Alamat</span><textarea value={address} onChange={(event) => setAddress(event.target.value)} /></label><label className="admin-field"><span>Catatan</span><textarea value={notes} onChange={(event) => setNotes(event.target.value)} /></label><button className="admin-button admin-button-primary" type="button" onClick={() => onUpdate({ client_address: address, notes })}><PencilSimpleIcon size={16} /> Simpan perubahan</button></div><div className="admin-form"><label className="admin-field"><span>Status</span><select value={status} onChange={(event) => setStatus(event.target.value)}>{statusOptions.slice(1).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label><button className="admin-button admin-button-secondary" type="button" onClick={() => onStatus(status)}>Ubah status</button></div><div className="admin-form"><span className="detail-label">Penjadwalan staff</span><label className="admin-field"><span>Staff</span><select value={staffId} onChange={(event) => setStaffId(event.target.value)}><option value="">Pilih staff</option>{staff.map((user) => <option key={user.id} value={user.id}>{user.name} ({user.role})</option>)}</select></label><div className="detail-grid"><label className="admin-field"><span>Mulai</span><input type="datetime-local" value={startsAt} onChange={(event) => setStartsAt(event.target.value)} /></label><label className="admin-field"><span>Selesai</span><input type="datetime-local" value={endsAt} onChange={(event) => setEndsAt(event.target.value)} /></label></div><button className="admin-button admin-button-secondary" type="button" disabled={!staffId || !startsAt || !endsAt} onClick={() => onAssign({ user_id: staffId, starts_at: new Date(startsAt).toISOString(), ends_at: new Date(endsAt).toISOString() })}>Simpan jadwal</button></div>{booking.tasks?.length > 0 && <div className="detail-block"><span className="detail-label">Checklist</span>{booking.tasks.map((task) => <div className="detail-line" key={task.id}><span>{task.title}</span><strong>{task.is_done ? 'Selesai' : 'Belum'}</strong></div>)}</div>}{feedback && <div className="admin-alert" role="status">{feedback}</div>}<button className="admin-button admin-button-danger" type="button" onClick={onDelete}><TrashIcon size={16} /> Hapus booking</button></div>
}
