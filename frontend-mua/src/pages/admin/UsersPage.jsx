import { useCallback, useEffect, useMemo, useState } from 'react'
import { PencilSimpleIcon, PlusIcon, TrashIcon, FloppyDiskIcon } from '@phosphor-icons/react'
import AdminDataTable from '../../components/AdminDataTable'
import AdminLayout from '../../components/AdminLayout'
import ConfirmDialog from '../../components/ConfirmDialog'
import { createAdminUser, deleteAdminUser, listAdminUsers, unwrapList, updateAdminUser } from '../../api/adminApi'
import { getStoredSession } from '../../utils/session'
import { useToast } from '../../context/useToast'

const blankUser = { name: '', username: '', phone: '', instagram_url: '', password: '', role: 'staff', is_active: true }
const getError = (error) => error?.payload?.message ?? error?.message ?? 'Permintaan gagal diproses.'

export default function UsersPage() {
  const [rows, setRows] = useState([])
  const [filters, setFilters] = useState({ role: '', is_active: '' })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [form, setForm] = useState(blankUser)
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [deleteLoading, setDeleteLoading] = useState(false)
  const session = getStoredSession()
  const currentUserId = session?.user?.id
  const { toast } = useToast()


  const loadUsers = useCallback(async () => { setIsLoading(true); setError(''); try { setRows(unwrapList(await listAdminUsers(filters))) } catch (requestError) { setError(getError(requestError)) } finally { setIsLoading(false) } }, [filters])
  useEffect(() => { loadUsers() }, [loadUsers])

  const openCreate = () => { setEditing(null); setForm(blankUser); setModalOpen(true) }
  const openEdit = (user) => { setEditing(user); setForm({ name: user.name, username: user.username, phone: user.phone ?? '', instagram_url: user.instagram_url ?? '', password: '', role: user.role, is_active: user.is_active }); setModalOpen(true) }

  const saveUser = async (event) => {
    event.preventDefault()
    if (!form.name.trim() || !form.username.trim()) { toast({ type: 'info', message: 'Nama dan username wajib diisi.' }); return }
    if (!editing && form.password.length < 8) { toast({ type: 'info', message: 'Password minimal 8 karakter.' }); return }
    if (form.password && form.password.length < 8) { toast({ type: 'info', message: 'Password minimal 8 karakter.' }); return }
    try {
      const body = { name: form.name.trim(), username: form.username.trim(), phone: form.phone.trim() || null, instagram_url: form.instagram_url.trim() || null, role: form.role, is_active: form.is_active }
      if (form.password) body.password = form.password
      if (editing) await updateAdminUser(editing.id, body)
      else await createAdminUser(body)
      setEditing(null); setForm(blankUser); setModalOpen(false); toast({ type: 'success', title: 'Pengguna tersimpan', message: `${body.name} berhasil disimpan.` }); await loadUsers()
    } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) }
  }

  const removeUser = async () => {
    setDeleteLoading(true)
    try { await deleteAdminUser(editing.id); setConfirmOpen(false); setEditing(null); setModalOpen(false); toast({ type: 'success', title: 'Pengguna dinonaktifkan', message: `Akun ${editing.name} berhasil dinonaktifkan.` }); await loadUsers() } catch (requestError) { toast({ type: 'error', message: getError(requestError) }) } finally { setDeleteLoading(false) }
  }

  const columns = useMemo(() => [
    { key: 'name', label: 'Nama', render: (row) => <strong>{row.name}{row.id === currentUserId ? ' (Anda)' : ''}</strong> },
    { key: 'username', label: 'Username' },
    { key: 'phone', label: 'Telepon', render: (row) => row.phone || '—' },
    { key: 'role', label: 'Role', render: (row) => <span className={`text-status ${row.role === 'owner' ? 'is-active' : ''}`}>{row.role}</span> },
    { key: 'is_active', label: 'Status', render: (row) => <span className={`text-status ${row.is_active ? 'is-active' : 'is-inactive'}`}>{row.is_active ? 'Aktif' : 'Nonaktif'}</span> },
    { key: 'created_at', label: 'Dibuat', render: (row) => row.created_at ? new Date(row.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' },
    { key: 'actions', label: 'Aksi', render: (row) => {
      return <div className="table-actions">
        <button className="table-action" type="button" onClick={() => openEdit(row)}><PencilSimpleIcon size={15} /> Edit</button>
        <button className="table-action table-action-danger" type="button" disabled={row.id === currentUserId} onClick={() => { openEdit(row); setConfirmOpen(true) }}><TrashIcon size={15} /> Hapus</button>
      </div>
    } },
  ], [currentUserId])

  return <AdminLayout title="Pengguna" description="Kelola akun tim dan hak aksesnya (khusus superadmin)." onRefresh={loadUsers} isLoading={isLoading} action={<button className="admin-button admin-button-primary" type="button" onClick={openCreate}><PlusIcon size={16} /> Pengguna baru</button>}>
    <div className="admin-toolbar"><div /><div className="admin-filters">
      <select value={filters.role} onChange={(event) => setFilters({ ...filters, role: event.target.value })} aria-label="Filter role">
        <option value="">Semua role</option><option value="admin">Admin</option><option value="staff">Staff</option><option value="owner">Owner</option>
      </select>
      <select value={filters.is_active} onChange={(event) => setFilters({ ...filters, is_active: event.target.value })} aria-label="Filter status">
        <option value="">Semua status</option><option value="1">Aktif</option><option value="0">Nonaktif</option>
      </select>
    </div></div>
    <AdminDataTable columns={columns} rows={rows} isLoading={isLoading} error={error} onRetry={loadUsers} emptyMessage="Belum ada pengguna." />
    {modalOpen && <div className="admin-modal-overlay is-open"><section className="admin-modal" role="dialog" aria-modal="true" aria-labelledby="user-form-title">
      <button className="drawer-close" type="button" onClick={() => { setEditing(null); setForm(blankUser); setModalOpen(false) }} aria-label="Tutup">×</button>
      <span className="eyebrow">Manajemen tim</span>
<h2 id="user-form-title">{editing ? 'Edit pengguna' : 'Pengguna baru'}</h2>
      <form className="admin-form" onSubmit={saveUser}>
        <label className="admin-field"><span>Nama</span><input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
        <label className="admin-field"><span>Username</span><input value={form.username} onChange={(event) => setForm({ ...form, username: event.target.value })} required /></label>
        <label className="admin-field"><span>Telepon</span><input value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} /></label>
        <label className="admin-field"><span>Instagram URL</span><input type="url" value={form.instagram_url} onChange={(event) => setForm({ ...form, instagram_url: event.target.value })} placeholder="https://instagram.com/username" /></label>
        <label className="admin-field"><span>Password{editing ? ' (kosongkan jika tidak diubah)' : ''}</span><input type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} minLength={8} required={!editing} /></label>
        <label className="admin-field"><span>Role</span>
          <select value={form.role} onChange={(event) => setForm({ ...form, role: event.target.value })} disabled={editing?.id === currentUserId}>
            <option value="staff">Staff</option>{editing?.role === 'admin' && <option value="admin">Admin</option>}<option value="owner">Owner</option>
          </select>
        </label>
        <label className="admin-check"><input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> Akun aktif</label>
        <button className="admin-button admin-button-primary" type="submit"><FloppyDiskIcon size={16} /> Simpan</button>
      </form>
      {editing && <button className="admin-button admin-button-danger modal-delete" type="button" disabled={editing.id === currentUserId} onClick={() => setConfirmOpen(true)}><TrashIcon size={16} /> Hapus</button>}
    </section></div>}
    <ConfirmDialog open={confirmOpen} title="Hapus pengguna?" message={`Akun ${editing?.name ?? ''} akan dinonaktifkan (soft-delete).`} isLoading={deleteLoading} onCancel={() => setConfirmOpen(false)} onConfirm={removeUser} />
  </AdminLayout>
}
