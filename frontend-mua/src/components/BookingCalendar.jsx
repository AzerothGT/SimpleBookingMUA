import { useEffect, useMemo, useState } from 'react'
import { DayPicker } from 'react-day-picker'
import { id } from 'date-fns/locale'
import 'react-day-picker/style.css'
import { getScheduleCalendar } from '../api/bookingApi'

function formatDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function parseDate(value) {
  if (!value) return undefined
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}

function getMonthRange(month) {
  return {
    from: formatDate(new Date(month.getFullYear(), month.getMonth(), 1)),
    to: formatDate(new Date(month.getFullYear(), month.getMonth() + 1, 0)),
  }
}

function normalizeCalendar(payload) {
  const entries = Array.isArray(payload) ? payload : payload?.data ?? []
  return new Map(entries.map((entry) => [entry.date, Array.isArray(entry.busy_ranges) ? entry.busy_ranges : []]))
}

function suggestInitialMonth() {
  const today = new Date()
  // Kalau sudah lewat tanggal 25, buka bulan depan
  if (today.getDate() > 25) {
    return new Date(today.getFullYear(), today.getMonth() + 1, 1)
  }
  return today
}

export default function BookingCalendar({ selectedDate, onSelectedDateChange, onAvailabilityChange }) {
  const selected = useMemo(() => parseDate(selectedDate), [selectedDate])
  const [month, setMonth] = useState(() => selected ?? suggestInitialMonth())
  const [busyByDate, setBusyByDate] = useState(() => new Map())
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    const { from, to } = getMonthRange(month)

    setLoading(true)
    setError('')
    getScheduleCalendar(from, to)
      .then((payload) => {
        if (!controller.signal.aborted) setBusyByDate(normalizeCalendar(payload))
      })
      .catch(() => {
        if (!controller.signal.aborted) {
          setBusyByDate(new Map())
          setError('Jadwal bulan ini gagal dimuat. Kamu tetap bisa memilih tanggal dan lanjut.')
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false)
      })

    return () => controller.abort()
  }, [month])

  useEffect(() => {
    onAvailabilityChange({
      busyRanges: selectedDate ? busyByDate.get(selectedDate) ?? [] : [],
      loading,
      error,
    })
  }, [busyByDate, error, loading, onAvailabilityChange, selectedDate])

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return (
    <div className="booking-calendar" aria-label="Pilih tanggal makeup">
      {loading ? (
        <div className="calendar-loading" role="status">
          <span className="spinner" aria-hidden="true" />
          <p>Memuat jadwal bulan ini...</p>
        </div>
      ) : (
        <>
          <DayPicker
            mode="single"
            locale={id}
            month={month}
            onMonthChange={setMonth}
            selected={selected}
            onSelect={(date) => date && onSelectedDateChange(formatDate(date))}
            disabled={[
              { before: today },
              (date) => {
                if (loading || error) return false
                return busyByDate.has(formatDate(date))
              },
            ]}
            modifiers={{
              free: (date) => {
                if (loading || error) return false
                if (date.getMonth() !== month.getMonth()) return false
                if (date < today) return false
                return !busyByDate.has(formatDate(date))
              },
            }}
            fixedWeeks
            showOutsideDays
          />
          <div className="calendar-legend" aria-label="Keterangan kalender: hijau = tanggal tersedia, abu = tanggal penuh">
            {error ? (
              <>
                <span className="legend-dot legend-dot--error" aria-hidden="true" />
                <span>Gagal memuat</span>
              </>
            ) : (
              <>
                <span className="legend-dot legend-dot--free" aria-hidden="true" />
                <span>Tersedia</span>
                <span className="legend-dot legend-dot--full" aria-hidden="true" />
                <span>Penuh</span>
              </>
            )}
          </div>
        </>
      )}
    </div>
  )
}
