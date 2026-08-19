import { apiClient } from './client'

export function unwrap(payload) {
  return payload?.data ?? payload
}

export function unwrapList(payload) {
  return Array.isArray(payload) ? payload : payload?.data ?? []
}

export function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0)
}

export function formatDashboardDate(value = new Date()) {
  return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(value)
}

function formatTime(value) {
  if (!value) return 'Belum dijadwalkan'
  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value)).replace('.', ':')
}

export function normalizeBookings(payload) {
  return unwrapList(payload).map((booking) => {
    const services = booking.services ?? []
    const amount = services.reduce((sum, service) => sum + Number(service.subtotal ?? (service.price ?? 0) * (service.qty ?? 1)), 0)
    const staff = booking.staff ?? booking.assigned_staff ?? null
    const startsAt = booking.starts_at
    const endsAt = booking.ends_at

    return {
      id: booking.id,
      clientName: booking.client_name ?? 'Tanpa nama',
      serviceName: services.map((service) => service.name).join(', ') || 'Layanan belum dipilih',
      date: booking.client_requested_date ?? startsAt?.slice(0, 10) ?? '',
      time: startsAt && endsAt ? `${formatTime(startsAt)} - ${formatTime(endsAt)}` : booking.client_requested_end_time ?? 'Belum dijadwalkan',
      status: booking.status ?? 'pending',
      amount,
      staffName: staff?.name ?? 'Belum ditugaskan',
      staffId: staff?.id ?? null,
    }
  }).filter((record) => record.id)
}

function queryString(filters) {
  const query = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value) query.set(key, value)
  })
  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

export const listAdminBookings = (filters = {}) => apiClient.get(`/bookings${queryString(filters)}`)
export const getAdminBooking = (id) => apiClient.get(`/bookings/${id}`)
export const updateAdminBooking = (id, body) => apiClient.patch(`/bookings/${id}`, body)
export const deleteAdminBooking = (id) => apiClient.delete(`/bookings/${id}`)
export const assignAdminBooking = (id, body) => apiClient.post(`/bookings/${id}/assign-staff`, body)
export const createAdminPaymentLink = (id) => apiClient.post(`/bookings/${id}/payment-link`, {})
export const listAdminUsers = (filters = {}) => apiClient.get(`/users${queryString(filters)}`)
export const createAdminUser = (body) => apiClient.post('/users', body)
export const updateAdminUser = (id, body) => apiClient.put(`/users/${id}`, body)
export const deleteAdminUser = (id) => apiClient.delete(`/users/${id}`)

export const listAdminServices = () => apiClient.get('/services?include_inactive=1')
export const createAdminService = (body) => apiClient.post('/services', body)
export const updateAdminService = (id, body) => apiClient.patch(`/services/${id}`, body)
export const deleteAdminService = (id) => apiClient.delete(`/services/${id}`)

export const createServiceImage = (serviceId, body) => apiClient.post(`/services/${serviceId}/serviceImages`, body)
export const updateServiceImage = (serviceId, imageId, body) => apiClient.put(`/services/${serviceId}/serviceImages/${imageId}`, body)
export const deleteServiceImage = (serviceId, imageId) => apiClient.delete(`/services/${serviceId}/serviceImages/${imageId}`)

export const listActivityLogs = (filters = {}) => apiClient.get(`/activity-logs${queryString(filters)}`)
export const getActivityLog = (id) => apiClient.get(`/activity-logs/${id}`)
