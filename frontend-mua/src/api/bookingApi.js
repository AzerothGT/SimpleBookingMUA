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
