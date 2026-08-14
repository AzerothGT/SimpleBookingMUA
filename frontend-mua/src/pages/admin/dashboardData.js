const today = new Date()
const todayKey = today.toISOString().slice(0, 10)
const nextDayKey = new Date(today.getTime() + 86400000).toISOString().slice(0, 10)

export const statusLabels = {
  pending: 'Menunggu jadwal',
  confirmed: 'Terkonfirmasi',
  done: 'Selesai',
  cancelled: 'Dibatalkan',
}

export const dashboardFixture = [
  { id: 'booking-001', clientName: 'Alya Prameswari', serviceName: 'Makeup Pengantin', date: todayKey, time: '08.00 - 11.00', status: 'confirmed', amount: 2500000, staffName: 'Sinta', staffId: 'staff-1' },
  { id: 'booking-002', clientName: 'Nadia Putri', serviceName: 'Makeup Wisuda', date: todayKey, time: '13.00 - 15.00', status: 'pending', amount: 750000, staffName: 'Rani', staffId: 'staff-2' },
  { id: 'booking-003', clientName: 'Dewi Lestari', serviceName: 'Makeup Bridesmaid', date: nextDayKey, time: '09.00 - 11.00', status: 'pending', amount: 1200000, staffName: 'Sinta', staffId: 'staff-1' },
  { id: 'booking-004', clientName: 'Maya Anindita', serviceName: 'Makeup Party', date: nextDayKey, time: '16.00 - 18.00', status: 'done', amount: 600000, staffName: 'Rani', staffId: 'staff-2' },
  { id: 'booking-005', clientName: 'Putri Maharani', serviceName: 'Makeup Pengantin', date: '2026-08-10', time: '07.00 - 10.00', status: 'cancelled', amount: 2500000, staffName: 'Sinta', staffId: 'staff-1' },
]

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
  const records = Array.isArray(payload) ? payload : payload?.data ?? []
  return records.map((booking) => {
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
