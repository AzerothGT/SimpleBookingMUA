import { clearSession, getToken, isSessionExpired } from '../utils/session'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

function forceRelogin() {
  clearSession()
  window.location.assign('/login')
}

export async function request(path, options = {}) {
  const { auth = true, ...requestOptions } = options
  const token = auth ? getToken() : null

  if (auth && token && isSessionExpired()) {
    forceRelogin()
    throw new Error('Sesi sudah berakhir. Silakan masuk kembali.')
  }

  const headers = {
    Accept: 'application/json',
    'ngrok-skip-browser-warning': 'true',
    ...(requestOptions.body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...requestOptions.headers,
  }

  const response = await fetch(`${API_URL}${path}`, { ...requestOptions, headers })
  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    if (auth && token && response.status === 401) forceRelogin()
    const error = new Error(payload?.message ?? 'Permintaan gagal diproses.')
    error.status = response.status
    error.payload = payload
    throw error
  }

  return payload
}

export const apiClient = {
  get: (path, options) => request(path, { ...options, method: 'GET' }),
  post: (path, body, options) => request(path, { ...options, method: 'POST', body: JSON.stringify(body) }),
  patch: (path, body, options) => request(path, { ...options, method: 'PATCH', body: JSON.stringify(body) }),
  put: (path, body, options) => request(path, { ...options, method: 'PUT', body: JSON.stringify(body) }),
  delete: (path, options) => request(path, { ...options, method: 'DELETE' }),
}
