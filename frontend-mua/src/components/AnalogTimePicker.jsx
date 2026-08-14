import { useState } from 'react'

const NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]

function parseValue(value) {
  if (!value || !/^\d{2}:\d{2}$/.test(value)) return null
  const [h, m] = value.split(':').map(Number)
  return { hours24: h % 24, minutes: m }
}

export default function AnalogTimePicker({ value, onChange }) {
  const parsed = parseValue(value)
  const [mode, setMode] = useState('hour')

  const hours24 = parsed?.hours24 ?? 12
  const minutes = parsed?.minutes ?? 0
  const displayHour = hours24 % 12 || 12
  const meridiem = hours24 >= 12 ? 'PM' : 'AM'

  const commit = (h, m) =>
    onChange(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)

  const pickNumber = (n) => {
    if (mode === 'hour') {
      let h = n % 12
      if (meridiem === 'PM') h += 12
      commit(h, minutes)
      setMode('minute')
    } else {
      commit(hours24, (n * 5) % 60)
    }
  }

  const toggleMeridiem = () => commit((hours24 + 12) % 24, minutes)

  const handAngle =
    mode === 'hour'
      ? ((hours24 % 12) + minutes / 60) / 12 * 360
      : minutes / 60 * 360

  return (
    <div className="analog-time-picker">
      <div className="atp-display">
        <button type="button" className={`atp-unit ${mode === 'hour' ? 'active' : ''}`} onClick={() => setMode('hour')}>
          {parsed ? String(displayHour).padStart(2, '0') : '--'}
        </button>
        <span className="atp-colon">:</span>
        <button type="button" className={`atp-unit ${mode === 'minute' ? 'active' : ''}`} onClick={() => setMode('minute')}>
          {parsed ? String(minutes).padStart(2, '0') : '--'}
        </button>
        <button type="button" className={`atp-meridiem ${parsed ? 'active' : ''}`} onClick={toggleMeridiem}>{meridiem}</button>
      </div>
      <div className="atp-clock">
        <div className="atp-face">
          {NUMBERS.map((n) => {
            const angle = (n * 30 - 90) * (Math.PI / 180)
            const x = 74 + Math.cos(angle) * 55
            const y = 74 + Math.sin(angle) * 55
            const selectedNumber = mode === 'hour' ? displayHour : minutes / 5 || 12
            return (
              <button
                type="button"
                key={n}
                className={`atp-num ${n === selectedNumber ? 'selected' : ''}`}
                style={{ left: `${x}px`, top: `${y}px` }}
                onClick={() => pickNumber(n)}
              >
                {mode === 'hour' ? n : String((n * 5) % 60).padStart(2, '0')}
              </button>
            )
          })}
          <div className="atp-hand" style={{ transform: `rotate(${handAngle}deg)` }} />
          <div className="atp-pin" />
        </div>
      </div>
      <p className="atp-hint">
        {mode === 'hour' ? 'Ketuk angka untuk memilih jam' : 'Ketuk angka untuk memilih menit'}
      </p>
    </div>
  )
}
