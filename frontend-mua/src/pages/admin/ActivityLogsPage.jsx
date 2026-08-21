import { useCallback, useEffect, useMemo, useState } from 'react'
import { EyeIcon } from '@phosphor-icons/react'
import AdminDataTable from '../../components/AdminDataTable'
import AdminDrawer from '../../components/AdminDrawer'
import AdminLayout from '../../components/AdminLayout'
import { getActivityLog, listActivityLogs, unwrap, unwrapList } from '../../api/adminApi'
import { useToast } from '../../context/useToast'

const getError = (error) => error?.status === 401 ? 'Sesi login berakhir. Silakan masuk kembali.' : error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function ActivityLogsPage() {
  const [rows, setRows] = useState([])
  const [filters, setFilters] = useState({ entity_type: '', action: '', booking_id: '' })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [selected, setSelected] = useState(null)
  const { toast } = useToast()

  const loadLogs = useCallback(async () => { setIsLoading(true); setError(''); try { setRows(unwrapList(await listActivityLogs(filters))) } catch (requestError) { setError(getError(requestError)) } finally { setIsLoading(false) } }, [filters])
  useEffect(() => { loadLogs() }, [loadLogs])

  const openLog = useCallback(async (id) => { try { setSelected(unwrap(await getActivityLog(id))) } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } }, [toast])
  const columns = useMemo(() => [{ key: 'created_at', label: 'Waktu', render: (row) => new Date(row.created_at).toLocaleString('id-ID') }, { key: 'action', label: 'Aksi', render: (row) => <strong>{row.action}</strong> }, { key: 'entity_type', label: 'Entitas', render: (row) => row.entity_type }, { key: 'user', label: 'Actor', render: (row) => row.user?.name ?? 'System' }, { key: 'detail', label: 'Detail', render: (row) => row.detail || '—' }, { key: 'actions', label: 'Aksi', render: (row) => <button className="table-action" type="button" onClick={() => openLog(row.id)}><EyeIcon size={15} /> Detail</button> }], [openLog])

  return <AdminLayout title="Aktivitas" description="Audit trail untuk melacak perubahan penting di sistem." onRefresh={loadLogs} isLoading={isLoading}><div className="admin-toolbar"><div /><div className="admin-filters"><select value={filters.entity_type} onChange={(event) => setFilters({ ...filters, entity_type: event.target.value })} aria-label="Filter entitas"><option value="">Semua entitas</option><option value="booking">Booking</option><option value="service">Layanan</option><option value="transaction">Transaksi</option><option value="user">User</option></select><input value={filters.action} onChange={(event) => setFilters({ ...filters, action: event.target.value })} placeholder="Filter aksi" aria-label="Filter aksi" /><input value={filters.booking_id} onChange={(event) => setFilters({ ...filters, booking_id: event.target.value })} placeholder="Booking ID" aria-label="Filter booking ID" /></div></div><AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadLogs} emptyMessage="Belum ada aktivitas." /><AdminDrawer open={Boolean(selected)} title="Detail aktivitas" onClose={() => setSelected(null)}>{selected ? <div className="detail-content"><div className="detail-block"><span className="detail-label">Aksi</span><strong>{selected.action}</strong></div><div className="detail-grid"><div><span className="detail-label">Actor</span><strong>{selected.user?.name ?? 'System'}</strong></div><div><span className="detail-label">Waktu</span><strong>{new Date(selected.created_at).toLocaleString('id-ID')}</strong></div><div><span className="detail-label">Entitas</span><strong>{selected.entity_type}</strong></div><div><span className="detail-label">Entity ID</span><strong className="break-value">{selected.entity_id ?? '—'}</strong></div></div><div className="detail-block"><span className="detail-label">Detail</span><p>{selected.detail || '—'}</p></div><div className="detail-block"><span className="detail-label">Metadata</span><pre className="metadata-block">{JSON.stringify(selected.meta ?? {}, null, 2)}</pre></div></div> : <div className="admin-state">Memuat detail...</div>}</AdminDrawer></AdminLayout>
}
