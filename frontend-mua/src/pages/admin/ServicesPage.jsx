import { useCallback, useEffect, useMemo, useState } from 'react'
import { PencilSimpleIcon, PlusIcon, TrashIcon } from '@phosphor-icons/react'
import AdminDataTable from '../../components/AdminDataTable'
import AdminLayout from '../../components/AdminLayout'
import ConfirmDialog from '../../components/ConfirmDialog'
import { createAdminService, deleteAdminService, listAdminServices, unwrapList, updateAdminService } from '../../api/adminApi'
import { formatCurrency } from '../../api/adminApi'
import { getStoredSession } from '../../session'

const blankService = { name: '', price: '', is_active: true }
const getError = (error) => error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function ServicesPage() {
  const [rows, setRows] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [form, setForm] = useState(blankService)
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [feedback, setFeedback] = useState('')
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [deleteLoading, setDeleteLoading] = useState(false)
  const role = getStoredSession()?.user?.role ?? 'staff'
  const canManageServices = role === 'owner' || role === 'admin'

  const loadServices = useCallback(async () => { setIsLoading(true); setError(''); try { setRows(unwrapList(await listAdminServices())) } catch (requestError) { setError(getError(requestError)) } finally { setIsLoading(false) } }, [])
  useEffect(() => { loadServices() }, [loadServices])

  const openCreate = () => { setEditing(null); setForm(blankService); setFeedback(''); setModalOpen(true) }
  const openEdit = (service) => { setEditing(service); setForm({ name: service.name, price: service.price, is_active: service.is_active }); setFeedback(''); setModalOpen(true) }
  const saveService = async (event) => { event.preventDefault(); if (!form.name.trim() || Number(form.price) < 0) { setFeedback('Nama wajib diisi dan harga tidak boleh negatif.'); return } try { if (editing) await updateAdminService(editing.id, { ...form, price: Number(form.price) }); else await createAdminService({ ...form, price: Number(form.price) }); setEditing(null); setForm(blankService); setModalOpen(false); setFeedback('Layanan tersimpan.'); await loadServices() } catch (requestError) { setFeedback(getError(requestError)) } }
  const removeService = async () => { setDeleteLoading(true); try { await deleteAdminService(editing.id); setConfirmOpen(false); setEditing(null); setModalOpen(false); await loadServices() } catch (requestError) { setFeedback(getError(requestError)) } finally { setDeleteLoading(false) } }

  const columns = useMemo(() => {
    const serviceColumns = [
      { key: 'name', label: 'Nama layanan', render: (row) => <strong>{row.name}</strong> },
      { key: 'price', label: 'Harga', render: (row) => formatCurrency(row.price) },
      { key: 'is_active', label: 'Status', render: (row) => <span className={`text-status ${row.is_active ? 'is-active' : 'is-inactive'}`}>{row.is_active ? 'Aktif' : 'Nonaktif'}</span> },
      { key: 'images', label: 'Galeri', render: (row) => `${row.images?.length ?? 0} foto` },
    ]

    if (canManageServices) {
      serviceColumns.push({ key: 'actions', label: 'Aksi', render: (row) => <div className="table-actions"><button className="table-action" type="button" onClick={() => openEdit(row)}><PencilSimpleIcon size={15} /> Edit</button><button className="table-action table-action-danger" type="button" onClick={() => { openEdit(row); setConfirmOpen(true) }}><TrashIcon size={15} /> Hapus</button></div> })
    }

    return serviceColumns
  }, [canManageServices])

  return <AdminLayout title="Layanan" description="Atur katalog layanan dan harga yang tampil ke klien." onRefresh={loadServices} isLoading={isLoading} action={canManageServices && <button className="admin-button admin-button-primary" type="button" onClick={openCreate}><PlusIcon size={16} /> Layanan baru</button>}>
    {feedback && !editing && <div className="admin-alert" role="status">{feedback}</div>}
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadServices} emptyMessage={canManageServices ? <span>Belum ada layanan. <button className="table-action" type="button" onClick={openCreate}>Tambah layanan pertama</button></span> : 'Belum ada layanan.'} />
    {modalOpen && <div className="admin-modal-overlay is-open"><section className="admin-modal" role="dialog" aria-modal="true" aria-labelledby="service-form-title"><button className="drawer-close" type="button" onClick={() => { setEditing(null); setForm(blankService); setModalOpen(false) }} aria-label="Tutup">×</button><span className="eyebrow">Katalog</span><h2 id="service-form-title">{editing ? 'Edit layanan' : 'Layanan baru'}</h2><form className="admin-form" onSubmit={saveService}><label className="admin-field"><span>Nama layanan</span><input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label><label className="admin-field"><span>Harga</span><input type="number" min="0" value={form.price} onChange={(event) => setForm({ ...form, price: event.target.value })} required /></label><label className="admin-check"><input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> Layanan aktif</label>{feedback && <div className="admin-alert">{feedback}</div>}<button className="admin-button admin-button-primary" type="submit">Simpan layanan</button></form>{editing && <button className="admin-button admin-button-danger modal-delete" type="button" onClick={() => setConfirmOpen(true)}><TrashIcon size={16} /> Hapus layanan</button>}</section></div>}
    <ConfirmDialog open={confirmOpen} title="Hapus layanan?" message={`Layanan ${editing?.name ?? ''} akan dihapus permanen.`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={removeService} />
  </AdminLayout>
}
