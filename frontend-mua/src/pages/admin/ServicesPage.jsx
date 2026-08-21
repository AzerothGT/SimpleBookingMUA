import { useCallback, useEffect, useMemo, useState } from 'react'
import { PencilSimpleIcon, PlusIcon, TrashIcon, FloppyDiskIcon, StarIcon } from '@phosphor-icons/react'
import AdminDataTable from '../../components/AdminDataTable'
import AdminLayout from '../../components/AdminLayout'
import ConfirmDialog from '../../components/ConfirmDialog'
import { createAdminService, createServiceImage, deleteAdminService, deleteServiceImage, listAdminServices, unwrap, unwrapList, updateAdminService, updateServiceImage } from '../../api/adminApi'
import { formatCurrency } from '../../api/adminApi'
import { getStoredSession } from '../../session'
import { useToast } from '../../context/useToast'

const blankService = { name: '', description: '', price: '', is_active: true }
const getError = (error) => error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function ServicesPage() {
  const [rows, setRows] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [form, setForm] = useState(blankService)
  const [editing, setEditing] = useState(null)
  const [images, setImages] = useState([])
  const [imageUrl, setImageUrl] = useState('')
  const [imageBusy, setImageBusy] = useState(false)
  const [modalOpen, setModalOpen] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [deleteLoading, setDeleteLoading] = useState(false)
  const role = getStoredSession()?.user?.role ?? 'staff'
  const canManageServices = role === 'owner' || role === 'admin'
  const { toast } = useToast()

  const loadServices = useCallback(async () => { setIsLoading(true); setError(''); try { setRows(unwrapList(await listAdminServices())) } catch (requestError) { setError(getError(requestError)) } finally { setIsLoading(false) } }, [])
  useEffect(() => { loadServices() }, [loadServices])

  const openCreate = () => { setEditing(null); setForm(blankService); setImages([]); setImageUrl(''); setModalOpen(true) }
  const openEdit = (service) => { setEditing(service); setForm({ name: service.name, description: service.description ?? '', price: service.price, is_active: service.is_active }); setImages(service.images ?? []); setImageUrl(''); setModalOpen(true) }
  const saveService = async (event) => { event.preventDefault(); if (!form.name.trim() || Number(form.price) < 0) { toast({ type: 'info', message: 'Nama wajib diisi dan harga tidak boleh negatif.' }); return } try { if (editing) { await updateAdminService(editing.id, { ...form, price: Number(form.price) }) } else { const created = unwrap(await createAdminService({ ...form, price: Number(form.price) })); const pending = images.filter((image) => image.pending); let savedImages = created.images ?? []; for (const image of pending) { const saved = unwrap(await createServiceImage(created.id, { image_url: image.image_url, image_source: 'external', sort_order: savedImages.length, is_cover: image.is_cover })); savedImages = [...savedImages, saved] } } setEditing(null); setForm(blankService); setImages([]); setModalOpen(false); toast({ type: 'success', title: 'Layanan tersimpan', message: `${form.name.trim()} berhasil disimpan.` }); await loadServices() } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } }
  const syncImages = (nextImages) => { setImages(nextImages); setRows((prev) => prev.map((row) => row.id === editing?.id ? { ...row, images: nextImages } : row)) }
  const addImage = async () => { const url = imageUrl.trim(); if (!url) return; if (!editing) { setImages([...images, { id: `pending-${Date.now()}`, image_url: url, is_cover: images.length === 0, pending: true }]); setImageUrl(''); return } setImageBusy(true); try { const saved = unwrap(await createServiceImage(editing.id, { image_url: url, image_source: 'external', sort_order: images.length, is_cover: images.length === 0 })); syncImages([...images, saved]); setImageUrl('') } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setImageBusy(false) } }
  const removeImage = async (image) => { if (image.pending) { setImages(images.filter((item) => item.id !== image.id)); return } if (!editing) return; setImageBusy(true); try { await deleteServiceImage(editing.id, image.id); syncImages(images.filter((item) => item.id !== image.id)) } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setImageBusy(false) } }
  const setCoverImage = async (image) => { if (image.is_cover) return; if (image.pending || !editing) { setImages(images.map((item) => ({ ...item, is_cover: item.id === image.id }))); return } setImageBusy(true); try { const saved = unwrap(await updateServiceImage(editing.id, image.id, { is_cover: true })); syncImages(images.map((item) => ({ ...item, is_cover: item.id === saved.id }))) } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setImageBusy(false) } }
  const removeService = async () => { setDeleteLoading(true); try { await deleteAdminService(editing.id); setConfirmOpen(false); const removed = editing?.name; setEditing(null); setImages([]); setModalOpen(false); toast({ type: 'success', title: 'Layanan dihapus', message: `${removed ?? 'Layanan'} berhasil dihapus.` }); await loadServices() } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setDeleteLoading(false) } }

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
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadServices} emptyMessage={canManageServices ? <span>Belum ada layanan. <button className="table-action" type="button" onClick={openCreate}><PlusIcon size={14} /> Tambah layanan pertama</button></span> : 'Belum ada layanan.'} />
    {modalOpen && <div className="admin-modal-overlay is-open"><section className="admin-modal" role="dialog" aria-modal="true" aria-labelledby="service-form-title"><button className="drawer-close" type="button" onClick={() => { setEditing(null); setForm(blankService); setImages([]); setModalOpen(false) }} aria-label="Tutup">×</button><span className="eyebrow">Katalog</span><h2 id="service-form-title">{editing ? 'Edit layanan' : 'Layanan baru'}</h2><form className="admin-form" onSubmit={saveService}><label className="admin-field"><span>Nama layanan</span><input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label><label className="admin-field"><span>Deskripsi</span><textarea value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} placeholder="Deskripsi singkat layanan (opsional)" /></label><label className="admin-field"><span>Harga</span><input type="number" min="0" value={form.price} onChange={(event) => setForm({ ...form, price: event.target.value })} required /></label><label className="admin-check"><input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> Layanan aktif</label><div className="service-image-manager"><span className="eyebrow">Galeri foto</span>{images.length > 0 && <ul className="service-image-list">{images.map((image) => <li key={image.id} className={`service-image-item${image.is_cover ? ' is-cover' : ''}`}><img src={image.image_url} alt={form.name || 'Foto layanan'} loading="lazy" /><div className="service-image-meta">{image.is_cover ? <span className="service-image-cover">Sampul</span> : <button className="table-action" type="button" disabled={imageBusy} onClick={() => setCoverImage(image)}><StarIcon size={13} /> Sampul</button>}<button className="table-action table-action-danger" type="button" disabled={imageBusy} onClick={() => removeImage(image)} aria-label="Hapus foto"><TrashIcon size={13} /></button></div></li>)}</ul>}<div className="service-image-add"><input type="url" placeholder="https://contoh.com/foto.jpg" value={imageUrl} onChange={(event) => setImageUrl(event.target.value)} disabled={imageBusy} /><button className="admin-button admin-button-secondary" type="button" disabled={imageBusy || !imageUrl.trim()} onClick={addImage}><PlusIcon size={14} /> Foto</button></div></div><button className="admin-button admin-button-primary" type="submit"><FloppyDiskIcon size={16} /> Simpan</button></form>{editing && <button className="admin-button admin-button-danger modal-delete" type="button" onClick={() => setConfirmOpen(true)}><TrashIcon size={16} /> Hapus</button>}</section></div>}
    <ConfirmDialog open={confirmOpen} title="Hapus layanan?" message={`Layanan ${editing?.name ?? ''} akan dihapus permanen.`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={removeService} />
  </AdminLayout>
}
