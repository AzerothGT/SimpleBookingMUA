import { useCallback, useEffect, useMemo, useState } from 'react'
import { CalendarBlankIcon, ClockIcon, EyeIcon, PaintBrushIcon, PencilSimpleIcon, PlusIcon, TrashIcon } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'
import AdminDataTable from '../../components/AdminDataTable'
import AdminDrawer from '../../components/AdminDrawer'
import AdminLayout from '../../components/AdminLayout'
import PaymentStatusBadge from '../../components/PaymentStatusBadge'
import { getPaymentState } from './paymentStatus'
import ConfirmDialog from '../../components/ConfirmDialog'
import StatusBadge from '../../components/StatusBadge'
import { getStoredSession } from '../../session'
import { useToast } from '../../context/useToast'
import { assignAdminBooking, createAdminPaymentLink, deleteAdminBooking, formatCurrency, getAdminBooking, listAdminBookings, listAdminUsers, updateAdminBooking, unwrap, unwrapList } from '../../api/adminApi'
import { formatBookingStaff } from './bookingStaff'

const statusOptions = [['', 'Semua status'], ['pending', 'Menunggu jadwal'], ['confirmed', 'Terkonfirmasi'], ['done', 'Selesai'], ['cancelled', 'Dibatalkan']]
const formatDate = (value) => value ? value.split('-').reverse().join('-') : '—'
const formatDateTime = (value) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
const paymentTypeLabels = { bank_transfer: 'Transfer bank', qris: 'QRIS', gopay: 'GoPay', shopeepay: 'ShopeePay', credit_card: 'Kartu kredit', echannel: 'Mandiri Bill', cstore: 'Gerai retail' }
const getError = (error) => error?.status === 401 ? 'Sesi login berakhir. Silakan masuk kembali.' : error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function BookingsPage() {
  const [rows, setRows] = useState([])
  const [filters, setFilters] = useState({ status: '', client_name: '', client_phone: '' })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [selected, setSelected] = useState(null)
  const [drawerLoading, setDrawerLoading] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [deleteLoading, setDeleteLoading] = useState(false)
  const [staff, setStaff] = useState([])
  const sessionUser = getStoredSession()?.user
  const { toast } = useToast()

  const loadBookings = useCallback(async () => {
    if (!window.localStorage.getItem('auth_token')) { setError('Silakan masuk untuk mengakses data booking.'); return }
    setIsLoading(true); setError('')
    try { setRows(unwrapList(await listAdminBookings(filters))) } catch (requestError) { if (requestError.status === 401) window.localStorage.removeItem('auth_token'); setError(getError(requestError)) } finally { setIsLoading(false) }
  }, [filters])

  useEffect(() => { const timeout = window.setTimeout(loadBookings, 300); return () => window.clearTimeout(timeout) }, [loadBookings])
  useEffect(() => {
    if (!['owner', 'admin'].includes(sessionUser?.role)) return
    listAdminUsers()
      .then((payload) => setStaff(unwrapList(payload).filter((user) => ['owner', 'admin', 'staff'].includes(user.role))))
      .catch(() => setStaff([]))
  }, [sessionUser?.role])

  const openBooking = useCallback(async (id) => {
    setDrawerLoading(true)
    try { setSelected(unwrap(await getAdminBooking(id))) } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setDrawerLoading(false) }
  }, [toast])

  const sendPaymentLink = async () => {
    try {
      const payload = unwrap(await createAdminPaymentLink(selected.id))
      const baseUrl = window.location.origin
      const paymentUrl = `${baseUrl}/payment?booking=${encodeURIComponent(payload.booking_id)}&token=${encodeURIComponent(payload.payment_access_token)}`
      const whatsappUrl = `https://wa.me/${selected.client_phone.replace(/\D/g, '')}?text=${encodeURIComponent(`Halo ${selected.client_name}, silakan selesaikan pembayaran booking melalui link berikut: ${paymentUrl}`)}`
      window.open(whatsappUrl, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
      toast({ type: 'error', message: getError(requestError) })
    }
  }

  const mutate = async (operation) => {
    try {
      const result = await operation()
      setSelected(result ? unwrap(result) : selected)
      toast({ type: 'success', title: 'Booking diperbarui', message: 'Booking dan jadwal berhasil diperbarui.' })
      await loadBookings()
    } catch (requestError) {
      toast({ type: 'error', message: getError(requestError) })
    }
  }

  const handleDelete = async () => { setDeleteLoading(true); try { await deleteAdminBooking(selected.id); setConfirmOpen(false); setSelected(null); await loadBookings() } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setDeleteLoading(false) } }
  const saveBooking = (body) => mutate(async () => { await updateAdminBooking(selected.id, body.details); return assignAdminBooking(selected.id, body.schedule) })

  const columns = useMemo(() => [
    { key: 'client_name', label: 'Klien', render: (row) => <strong>{row.client_name}</strong> },
    { key: 'booking_code', label: 'Booking code', render: (row) => <span className="booking-code-cell">{row.booking_code ?? '—'}</span> },
    { key: 'requested', label: 'Pengajuan', render: (row) => <span>{formatDate(row.client_requested_date)}<small>{row.client_requested_end_time}</small></span> },
    { key: 'services', label: 'Layanan', render: (row) => <span className="booking-services-cell">{(row.services ?? []).map((service) => service.name).join(', ') || '—'}</span> },
    { key: 'staff', label: 'Staff', render: (row) => formatBookingStaff(row) },
    { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'actions', label: 'Aksi', render: (row) => <button className="table-action" type="button" onClick={() => openBooking(row.id)} aria-label={`Lihat detail booking ${row.client_name}`} title="Lihat detail"><EyeIcon size={15} aria-hidden="true" /></button> },
  ], [openBooking])

  return <AdminLayout title="Booking" description="Kelola pengajuan, jadwal, dan status pekerjaan." onRefresh={loadBookings} isLoading={isLoading} action={<Link className="admin-button admin-button-primary" to="/booking"><PlusIcon size={16} /> Booking publik</Link>}>
    <div className="admin-toolbar"><div><span className="eyebrow">Filter data</span><h2>Daftar booking</h2></div><div className="admin-filters"><select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })} aria-label="Filter status">{statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><input value={filters.client_name} onChange={(event) => setFilters({ ...filters, client_name: event.target.value })} placeholder="Cari nama klien" aria-label="Cari nama klien" /><input value={filters.client_phone} onChange={(event) => setFilters({ ...filters, client_phone: event.target.value })} placeholder="Nomor telepon" aria-label="Cari nomor telepon" /></div></div>
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadBookings} emptyMessage="Belum ada booking yang cocok." />
    <AdminDrawer open={Boolean(selected)} title={selected?.client_name ?? 'Booking'} onClose={() => setSelected(null)}>
      {drawerLoading ? <div className="admin-state">Memuat detail...</div> : selected && <BookingDetail booking={selected} staff={staff} role={sessionUser?.role ?? 'staff'} onSave={saveBooking} onDelete={() => setConfirmOpen(true)} onSendPaymentLink={sendPaymentLink} />}
    </AdminDrawer>
    <ConfirmDialog open={confirmOpen} title="Batalkan booking?" message={`Booking ${selected?.client_name ?? ''} akan dibatalkan (status menjadi "cancelled").`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={handleDelete} />
  </AdminLayout>
}

function BookingDetail({ booking, staff, role, onSave, onDelete, onSendPaymentLink }) {
  const payment = getPaymentState(booking.transactions)
  const [notes, setNotes] = useState(booking.notes ?? '')
  const [address, setAddress] = useState(booking.client_address ?? '')

  const legacyAssignment = booking.user_id ? [{ user_id: booking.user_id, starts_at: booking.starts_at?.slice(11, 16) ?? '' }] : []
  const [assignments, setAssignments] = useState(() => (booking.staff_schedules?.length ? booking.staff_schedules.map((schedule) => ({ user_id: schedule.user_id, starts_at: schedule.starts_at?.slice(11, 16) ?? '' })) : legacyAssignment))
  const [endsAt, setEndsAt] = useState(booking.ends_at ? booking.ends_at.slice(11, 16) : '')
  const canManageSchedule = role === 'owner' || role === 'admin'
  const canSave = canManageSchedule && Boolean(assignments.length && endsAt && assignments.every((assignment) => assignment.user_id && assignment.starts_at) && new Set(assignments.map((assignment) => assignment.user_id)).size === assignments.length)
  const buildScheduleDate = (time) => new Date(`${booking.client_requested_date}T${time}:00`).toISOString()
  const addAssignment = () => setAssignments((current) => [...current, { user_id: '', starts_at: '' }])
  const removeAssignment = (index) => setAssignments((current) => current.filter((_, itemIndex) => itemIndex !== index))
  const updateAssignment = (index, field, value) => setAssignments((current) => current.map((assignment, itemIndex) => itemIndex === index ? { ...assignment, [field]: value } : assignment))

  return <div className="detail-content"><div className="detail-summary"><span className="eyebrow">Klien</span><strong>{booking.client_name}</strong><span>{booking.client_phone}</span><span>{booking.client_address}</span></div><div className="detail-grid"><div className="detail-icon-value"><span className="detail-icon-label" title="Tanggal usulan"><CalendarBlankIcon size={16} aria-hidden="true" /><span className="sr-only">Tanggal usulan</span></span><strong>{formatDate(booking.client_requested_date)}</strong></div><div className="detail-icon-value"><span className="detail-icon-label" title="Jam selesai"><ClockIcon size={16} aria-hidden="true" /><span className="sr-only">Jam selesai</span></span><strong>{booking.client_requested_end_time}</strong></div></div><div className="detail-block">{(booking.services ?? []).map((service) => <div className="detail-service-row" key={service.id}><PaintBrushIcon size={15} aria-hidden="true" /><span>{service.name} × {service.qty}</span><strong>{formatCurrency(service.subtotal)}</strong></div>)}</div><div className="detail-block"><span className="detail-label">Pembayaran</span><div className="detail-line"><span>Status</span><PaymentStatusBadge state={payment.key} /></div>{payment.transaction && <><div className="detail-line"><span>Metode</span><strong>{paymentTypeLabels[payment.transaction.payment_type] ?? payment.transaction.payment_type ?? '—'}</strong></div><div className="detail-line"><span>Nominal</span><strong>{formatCurrency(payment.transaction.gross_amount)}</strong></div>{payment.transaction.paid_at && <div className="detail-line"><span>Dibayar pada</span><strong>{formatDateTime(payment.transaction.paid_at)}</strong></div>}</>}</div>{canManageSchedule ? <div className="admin-form"><label className="admin-field"><span>Alamat</span><textarea value={address} onChange={(event) => setAddress(event.target.value)} /></label><label className="admin-field"><span>Catatan</span><textarea value={notes} onChange={(event) => setNotes(event.target.value)} /></label></div> : <div className="detail-summary"><span className="detail-label">Alamat</span><strong>{address || '—'}</strong><span className="detail-label">Catatan</span><strong>{notes || '—'}</strong></div>}{canManageSchedule && <div className="admin-form"><span className="detail-label">Penjadwalan staff</span><div className="staff-schedule-list">{assignments.map((assignment, index) => <div className="staff-schedule-row" key={`${assignment.user_id}-${index}`}><label className="admin-field"><span>Staff {index + 1}</span><select value={assignment.user_id} onChange={(event) => updateAssignment(index, 'user_id', event.target.value)}><option value="">Pilih staff</option>{staff.map((user) => <option key={user.id} value={user.id}>{user.name} ({user.role})</option>)}</select></label><label className="admin-field"><span>Mulai</span><input type="time" value={assignment.starts_at} onChange={(event) => updateAssignment(index, 'starts_at', event.target.value)} /></label>{assignments.length > 1 && <button className="admin-button admin-button-secondary staff-schedule-remove" type="button" aria-label={`Hapus staff ${index + 1}`} title="Hapus staff" onClick={() => removeAssignment(index)}><TrashIcon size={15} aria-hidden="true" /></button>}</div>)}</div><button className="admin-button admin-button-secondary" type="button" onClick={addAssignment}>Tambah staff</button><label className="admin-field"><span>Jam selesai bersama</span><input type="time" value={endsAt} onChange={(event) => setEndsAt(event.target.value)} /></label><button className="admin-button admin-button-primary" type="button" disabled={!canSave} onClick={() => onSave({ details: { client_address: address, notes }, schedule: { staff: assignments.map((assignment) => ({ user_id: assignment.user_id, starts_at: buildScheduleDate(assignment.starts_at) })), ends_at: buildScheduleDate(endsAt) } })}><PencilSimpleIcon size={16} /> Simpan booking dan jadwal</button></div>}<button className="admin-button admin-button-danger" type="button" onClick={onDelete}><TrashIcon size={16} /> Batalkan booking</button><button className="admin-button admin-button-primary drawer-payment-action" type="button" onClick={onSendPaymentLink}>Kirim link pembayaran via WhatsApp</button></div>
}
