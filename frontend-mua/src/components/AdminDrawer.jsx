
import { useEffect } from 'react'

export default function AdminDrawer({ open, title, onClose, children }) {
  useEffect(() => {
    if (!open) return undefined
    const handleKeyDown = (event) => { if (event.key === 'Escape') onClose() }
    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [open, onClose])

  if (!open) return null

  return <div className="admin-drawer-overlay" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose() }}><aside className="admin-drawer" role="dialog" aria-modal="true" aria-label={title}><div className="admin-drawer-body">{children}</div></aside></div>
}
