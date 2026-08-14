import React, { useState } from 'react';

export default function AnalogTimePicker({ value, onChange }) {
  const [hours, setHours] = useState(12);
  const [minutes, setMinutes] = useState(0);
  const [meridiem, setMeridiem] = useState('AM');

  const handleHourClick = (h) => {
    let newH = h;
    if (meridiem === 'PM' && h < 12) newH += 12;
    if (meridiem === 'AM' && h >= 12) newH -= 12;
    setHours(newH);
  };

  const handleMinuteClick = (m) => {
    setMinutes(m);
  };

  const toggleMeridiem = () => {
    setMeridiem(meridiem === 'AM' ? 'PM' : 'AM');
  };

  const hour = hours % 12 || 12;
  const minStr = minutes.toString().padStart(2, '0');

  return (
    <div className="analog-time-picker">
      <div className="atp-display">
        <div className="atp-unit active" onClick={() => handleHourClick(hour - 1)}>{hour === 12 ? 12 : hour - 1}</div>
        <div className="atp-colon">:</div>
        <div className="atp-unit active" onClick={() => handleMinuteClick((minutes + 5) % 60)}>{minStr}</div>
        <div className="atp-unit" onClick={toggleMeridiem}>{meridiem}</div>
      </div>
      <div className="atp-clock">
        <div className="atp-face">
          {/* hour marks */}
          {Array.from({ length: 12 }).map((_, i) => {
            const angle = (i * 30 - 90) * (Math.PI / 180);
            const x = 74 + Math.cos(angle) * 55;
            const y = 74 + Math.sin(angle) * 55;
            return (
              <div
                key={i}
                className="atp-num"
                style={{
                  left: `${x}px`,
                  top: `${y}px`,
                  transform: 'translate(-50%, -50%)'
                }}
                onClick={() => handleHourClick(i + 1)}
              >
                {i + 1}
              </div>
            );
          })}
          {/* hand */}
          <div
            className="atp-hand"
            style={{
              transform: `rotate(${(hours % 12) * 30 + (minutes / 60) * 360}deg)`
            }}
          />
          <div className="atp-pin" />
        </div>
      </div>
      <div className="atp-hint">tap to adjust</div>
    </div>
  );
}