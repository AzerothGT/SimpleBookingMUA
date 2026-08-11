const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

export async function request(path, options = {}) {
  const token = window.localStorage.getItem('auth_token')
  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const response = await fetch(`${API_URL}${path}`, { ...options, headers })
  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
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
}
