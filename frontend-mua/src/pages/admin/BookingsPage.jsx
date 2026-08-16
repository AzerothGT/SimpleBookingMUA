import { useCallback, useEffect, useMemo, useState } from 'react'
import { CalendarBlankIcon, ClockIcon, EyeIcon, PaintBrushIcon, PencilSimpleIcon, PlusIcon, TrashIcon } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'
import AdminDataTable from '../../components/AdminDataTable'
import AdminDrawer from '../../components/AdminDrawer'
import AdminLayout from '../../components/AdminLayout'
import ConfirmDialog from '../../components/ConfirmDialog'
import StatusBadge from '../../components/StatusBadge'
import { getStoredSession } from '../../session'
import { assignAdminBooking, createAdminPaymentLink, deleteAdminBooking, formatCurrency, getAdminBooking, listAdminBookings, listAdminUsers, updateAdminBooking, unwrap, unwrapList } from '../../api/adminApi'

const statusOptions = [['', 'Semua status'], ['pending', 'Menunggu jadwal'], ['confirmed', 'Terkonfirmasi'], ['done', 'Selesai'], ['cancelled', 'Dibatalkan']]
const formatDate = (value) => value ? value.split('-').reverse().join('-') : '—'
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
  const sessionUser = getStoredSession()?.user

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

  const sendPaymentLink = async () => {
    try {
      const payload = unwrap(await createAdminPaymentLink(selected.id))
      const baseUrl = window.location.origin
      const paymentUrl = `${baseUrl}/payment?booking=${encodeURIComponent(payload.booking_id)}&token=${encodeURIComponent(payload.payment_access_token)}`
      const whatsappUrl = `https://wa.me/${selected.client_phone.replace(/\D/g, '')}?text=${encodeURIComponent(`Halo ${selected.client_name}, silakan selesaikan pembayaran booking melalui link berikut: ${paymentUrl}`)}`
      window.open(whatsappUrl, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
      setFeedback(getError(requestError))
    }
  }

  const mutate = async (operation, message) => {
    setFeedback('')
    try { const result = await operation(); setSelected(result ? unwrap(result) : selected); setFeedback(message); await loadBookings() } catch (requestError) { setFeedback(getError(requestError)) }
  }

  const handleDelete = async () => { setDeleteLoading(true); try { await deleteAdminBooking(selected.id); setConfirmOpen(false); setSelected(null); await loadBookings() } catch (requestError) { setFeedback(getError(requestError)) } finally { setDeleteLoading(false) } }
  const saveBooking = (body) => mutate(async () => { await updateAdminBooking(selected.id, body.details); return assignAdminBooking(selected.id, body.schedule) }, 'Booking dan jadwal diperbarui.')

  const columns = useMemo(() => [
    { key: 'client_name', label: 'Klien', render: (row) => <strong>{row.client_name}</strong> },
    { key: 'requested', label: 'Pengajuan', render: (row) => <span>{formatDate(row.client_requested_date)}<small>{row.client_requested_end_time}</small></span> },
    { key: 'services', label: 'Layanan', render: (row) => <span>{(row.services ?? []).map((service) => service.name).join(', ') || '—'}</span> },
    { key: 'staff', label: 'Staff', render: (row) => row.staff?.name ?? 'Belum ditugaskan' },
    { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'actions', label: 'Aksi', render: (row) => <button className="table-action" type="button" onClick={() => openBooking(row.id)} aria-label={`Lihat detail booking ${row.client_name}`} title="Lihat detail"><EyeIcon size={15} aria-hidden="true" /></button> },
  ], [])

  return <AdminLayout title="Booking" description="Kelola pengajuan, jadwal, dan status pekerjaan." onRefresh={loadBookings} isLoading={isLoading} action={<Link className="admin-button admin-button-primary" to="/booking"><PlusIcon size={16} /> Booking publik</Link>}>
    <div className="admin-toolbar"><div><span className="eyebrow">Filter data</span><h2>Daftar booking</h2></div><div className="admin-filters"><select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })} aria-label="Filter status">{statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><input value={filters.client_name} onChange={(event) => setFilters({ ...filters, client_name: event.target.value })} placeholder="Cari nama klien" aria-label="Cari nama klien" /><input value={filters.client_phone} onChange={(event) => setFilters({ ...filters, client_phone: event.target.value })} placeholder="Nomor telepon" aria-label="Cari nomor telepon" /></div></div>
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadBookings} emptyMessage="Belum ada booking yang cocok." />
    <AdminDrawer open={Boolean(selected)} title={selected?.client_name ?? 'Booking'} onClose={() => setSelected(null)}>
      {drawerLoading ? <div className="admin-state">Memuat detail...</div> : selected && <BookingDetail booking={selected} staff={staff} role={sessionUser?.role ?? 'staff'} currentUser={sessionUser} feedback={feedback} onSave={saveBooking} onDelete={() => setConfirmOpen(true)} onSendPaymentLink={sendPaymentLink} />}
    </AdminDrawer>
    <ConfirmDialog open={confirmOpen} title="Hapus booking?" message={`Booking ${selected?.client_name ?? ''} akan dihapus permanen.`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={handleDelete} />
  </AdminLayout>
}

function BookingDetail({ booking, staff, role, currentUser, feedback, onSave, onDelete, onSendPaymentLink }) {
  const [notes, setNotes] = useState(booking.notes ?? '')
  const [address, setAddress] = useState(booking.client_address ?? '')

  const [staffId, setStaffId] = useState(role === 'staff' ? currentUser?.id ?? '' : booking.user_id ?? booking.staff?.id ?? '')
  const [startsAt, setStartsAt] = useState(booking.starts_at ? booking.starts_at.slice(11, 16) : '')
  const [endsAt, setEndsAt] = useState(booking.ends_at ? booking.ends_at.slice(11, 16) : '')
  const canManageSchedule = role === 'owner' || role === 'admin'
  const canSave = canManageSchedule && Boolean(staffId && startsAt && endsAt)
  const buildScheduleDate = (time) => new Date(`${booking.client_requested_date}T${time}:00`).toISOString()

  return <div className="detail-content"><div className="detail-summary"><span className="eyebrow">Klien</span><strong>{booking.client_name}</strong><span>{booking.client_phone}</span><span>{booking.client_address}</span></div><div className="detail-grid"><div className="detail-icon-value"><span className="detail-icon-label" title="Tanggal usulan"><CalendarBlankIcon size={16} aria-hidden="true" /><span className="sr-only">Tanggal usulan</span></span><strong>{formatDate(booking.client_requested_date)}</strong></div><div className="detail-icon-value"><span className="detail-icon-label" title="Jam selesai"><ClockIcon size={16} aria-hidden="true" /><span className="sr-only">Jam selesai</span></span><strong>{booking.client_requested_end_time}</strong></div></div><div className="detail-block">{(booking.services ?? []).map((service) => <div className="detail-service-row" key={service.id}><PaintBrushIcon size={15} aria-hidden="true" /><span>{service.name} × {service.qty}</span><strong>{formatCurrency(service.subtotal)}</strong></div>)}</div><div className="admin-form"><label className="admin-field"><span>Alamat</span><textarea value={address} onChange={(event) => setAddress(event.target.value)} /></label><label className="admin-field"><span>Catatan</span><textarea value={notes} onChange={(event) => setNotes(event.target.value)} /></label></div><div className="admin-form"><span className="detail-label">Penjadwalan staff</span>{canManageSchedule ? <><label className="admin-field"><span>Staff</span><select value={staffId} onChange={(event) => setStaffId(event.target.value)}><option value="">Pilih staff</option>{staff.map((user) => <option key={user.id} value={user.id}>{user.name} ({user.role})</option>)}</select></label><div className="detail-grid"><label className="admin-field"><span>Mulai</span><input type="time" value={startsAt} onChange={(event) => setStartsAt(event.target.value)} /></label><label className="admin-field"><span>Selesai</span><input type="time" value={endsAt} onChange={(event) => setEndsAt(event.target.value)} /></label></div><button className="admin-button admin-button-primary" type="button" disabled={!canSave} onClick={() => onSave({ details: { client_address: address, notes }, schedule: { user_id: staffId, starts_at: buildScheduleDate(startsAt), ends_at: buildScheduleDate(endsAt) } })}><PencilSimpleIcon size={16} /> Simpan booking dan jadwal</button></> : <p className="muted-text">Jadwal ditentukan oleh owner atau admin.</p>}</div>{feedback && <div className="admin-alert" role="status">{feedback}</div>}<button className="admin-button admin-button-danger" type="button" onClick={onDelete}><TrashIcon size={16} /> Hapus booking</button><button className="admin-button admin-button-primary drawer-payment-action" type="button" onClick={onSendPaymentLink}>Kirim link pembayaran via WhatsApp</button></div>
}
