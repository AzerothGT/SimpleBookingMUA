const TOKEN_KEY = 'auth_token'
const USER_KEY = 'auth_user'
const EXPIRES_KEY = 'auth_expires_at'
const LEGACY_ROLE_KEY = 'demo_role'

export function getStoredSession() {
  if (typeof window === 'undefined') return null
  const token = window.localStorage.getItem(TOKEN_KEY)
  if (!token) return null

  let user = null
  try {
    user = JSON.parse(window.localStorage.getItem(USER_KEY) ?? 'null')
  } catch {
    user = null
  }

  const expiresAtRaw = window.localStorage.getItem(EXPIRES_KEY)
  return { token, user, expiresAt: expiresAtRaw ? new Date(expiresAtRaw) : null }
}

export function getToken() {
  return window.localStorage.getItem(TOKEN_KEY)
}

export function saveSession({ token, user, expires_at: expiresAt }) {
  window.localStorage.setItem(TOKEN_KEY, token)
  window.localStorage.setItem(USER_KEY, JSON.stringify(user ?? null))
  if (expiresAt) window.localStorage.setItem(EXPIRES_KEY, expiresAt)
  window.localStorage.removeItem(LEGACY_ROLE_KEY)
}

export function clearSession() {
  window.localStorage.removeItem(TOKEN_KEY)
  window.localStorage.removeItem(USER_KEY)
  window.localStorage.removeItem(EXPIRES_KEY)
  window.localStorage.removeItem(LEGACY_ROLE_KEY)
}

export function isSessionExpired(session = getStoredSession()) {
  if (!session?.expiresAt) return false
  return Number.isNaN(session.expiresAt.getTime()) || session.expiresAt.getTime() <= Date.now()
}

export function hasValidSession() {
  const session = getStoredSession()
  return Boolean(session?.token) && !isSessionExpired(session)
}
