import { apiClient } from './client'

export function listServices() {
  return apiClient.get('/services')
}

export function checkSchedule(date) {
  return apiClient.post(`/schedule/check?client_requested_date=${encodeURIComponent(date)}`, undefined, {
    headers: { Accept: 'application/json' },
  })
}

export function getScheduleCalendar(from, to) {
  const query = new URLSearchParams({ from, to })
  return apiClient.get(`/schedule/calendar?${query}`)
}

export function createBooking(booking) {
  return apiClient.post('/bookings', booking)
}

export function listBookings() {
  return apiClient.get('/bookings')
}

export function login(credentials) {
  return apiClient.post('/login', credentials, { auth: false })
}

export function getPublicBookingStatus(id, token) {
  return apiClient.get(`/public/bookings/${encodeURIComponent(id)}/status?token=${encodeURIComponent(token)}`, { auth: false })
}

export function createPublicSnapTransaction(id, token, type = 'dp') {
  return apiClient.post(`/public/bookings/${encodeURIComponent(id)}/transactions/snap?token=${encodeURIComponent(token)}&type=${encodeURIComponent(type)}`, {}, { auth: false })
}

let snapLoader

export function loadMidtransSnap() {
  if (window.snap) return Promise.resolve(window.snap)
  if (snapLoader) return snapLoader

  snapLoader = new Promise((resolve, reject) => {
    const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY
    if (!clientKey) {
      reject(new Error('Konfigurasi pembayaran belum lengkap. Hubungi penyedia layanan.'))
      return
    }

    const script = document.createElement('script')
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js'
    script.dataset.clientKey = clientKey
    script.onload = () => window.snap ? resolve(window.snap) : reject(new Error('Midtrans Snap gagal dimuat.'))
    script.onerror = () => reject(new Error('Midtrans Snap gagal dimuat.'))
    document.head.appendChild(script)
  })

  return snapLoader
}
