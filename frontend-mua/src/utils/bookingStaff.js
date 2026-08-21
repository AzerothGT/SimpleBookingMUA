export const formatBookingStaff = (booking) => {
  const scheduledNames = (booking.staff_schedules ?? [])
    .map((schedule) => schedule.staff?.name)
    .filter(Boolean)

  return scheduledNames.join(', ') || booking.staff?.name || 'Belum ditugaskan'
}
