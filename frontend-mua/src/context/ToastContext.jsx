import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react'
import { CheckCircle, Warning, X } from '@phosphor-icons/react'

const ToastContext = createContext(null)

const TYPE_CONFIG = {
  error: { icon: Warning, label: 'Terjadi kesalahan', accent: '#a03b1f' },
  success: { icon: CheckCircle, label: 'Berhasil', accent: 'var(--green)' },
  info: { icon: Warning, label: 'Perhatian', accent: 'var(--orange)' },
}

const LEAVE_MS = 250

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const timers = useRef(new Map())

  const remove = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
    const timer = timers.current.get(id)
    if (timer) {
      clearTimeout(timer)
      timers.current.delete(id)
    }
  }, [])

  const dismiss = useCallback((id) => {
    setToasts((current) => current.map((toast) => (toast.id === id ? { ...toast, leaving: true } : toast)))
    const auto = timers.current.get(id)
    if (auto) {
      clearTimeout(auto)
      timers.current.delete(id)
    }
    const leave = setTimeout(() => remove(id), LEAVE_MS)
    timers.current.set(id, leave)
  }, [remove])

  const toast = useCallback(({ type = 'info', title, message, duration = 5000 }) => {
    const id = `${Date.now()}-${Math.random().toString(36).slice(2)}`
    setToasts((current) => [...current, { id, type, title, message }])
    if (duration) {
      const timer = setTimeout(() => dismiss(id), duration)
      timers.current.set(id, timer)
    }
    return id
  }, [dismiss])

  useEffect(() => {
    const activeTimers = timers.current
    return () => {
      activeTimers.forEach((timer) => clearTimeout(timer))
      activeTimers.clear()
    }
  }, [])

  const value = useMemo(() => ({ toast, dismiss }), [toast, dismiss])

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="toast-viewport" role="region" aria-label="Notifikasi">
        {[...toasts].reverse().map((item, index) => {
          const config = TYPE_CONFIG[item.type] ?? TYPE_CONFIG.info
          const Icon = config.icon
          return (
            <div
              key={item.id}
              className={`toast ${item.leaving ? 'toast-leaving' : ''} ${index > 0 ? 'toast-stacked' : ''}`}
              role={item.type === 'error' ? 'alert' : 'status'}
              style={{ '--toast-accent': config.accent, '--stack': index }}
            >
              <span className="toast-icon"><Icon size={18} weight="bold" aria-hidden="true" /></span>
              <div className="toast-body">
                <span className="toast-label">{config.label}</span>
                {item.title && <strong className="toast-title">{item.title}</strong>}
                {item.message && <p className="toast-message">{item.message}</p>}
              </div>
              <button type="button" className="toast-close" onClick={() => dismiss(item.id)} aria-label="Tutup notifikasi">
                <X size={16} weight="bold" aria-hidden="true" />
              </button>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const context = useContext(ToastContext)
  if (!context) throw new Error('useToast must be used within a ToastProvider')
  return context
}
