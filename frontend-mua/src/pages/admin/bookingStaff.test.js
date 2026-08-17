import test from 'node:test'
import assert from 'node:assert/strict'
import { formatBookingStaff } from './bookingStaff.js'

test('formats every scheduled staff name', () => {
  const booking = {
    staff_schedules: [
      { staff: { name: 'Staff One' } },
      { staff: { name: 'Staff Two' } },
    ],
  }

  assert.equal(formatBookingStaff(booking), 'Staff One, Staff Two')
})

test('falls back to legacy staff', () => {
  assert.equal(formatBookingStaff({ staff: { name: 'Staff Lama' } }), 'Staff Lama')
})

test('ignores unnamed schedules and shows the unassigned label', () => {
  assert.equal(formatBookingStaff({ staff_schedules: [{ staff: null }] }), 'Belum ditugaskan')
})
