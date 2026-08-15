import { WarningIcon, XIcon } from '@phosphor-icons/react'

export default function ConfirmDialog({ open, title, message, confirmLabel = 'Hapus', isLoading, onCancel, onConfirm }) {
  if (!open) return null

  return <div className="admin-dialog-overlay"><section className="admin-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title"><button className="drawer-close" type="button" onClick={onCancel} aria-label="Batal"><XIcon size={18} /></button><WarningIcon size={28} className="dialog-warning" aria-hidden="true" /><h2 id="confirm-title">{title}</h2><p>{message}</p><div className="dialog-actions"><button className="admin-button admin-button-secondary" type="button" onClick={onCancel} disabled={isLoading}>Batal</button><button className="admin-button admin-button-danger" type="button" onClick={onConfirm} disabled={isLoading}>{isLoading ? 'Memproses...' : confirmLabel}</button></div></section></div>
}
