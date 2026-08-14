import { useCallback, useEffect, useRef, useState } from 'react'
import { ArrowLeft, ArrowRight, ArrowUpRight, CaretDown, Check, Warning } from '@phosphor-icons/react'
import { createBooking, listServices } from '../../api/bookingApi'
import AnalogTimePicker from '../../components/AnalogTimePicker'
import BookingCalendar from '../../components/BookingCalendar'
import Navbar from '../../components/Navbar'
import { useToast } from '../../context/ToastContext'

const fallbackServices = [
  { id: 'fallback-natural', name: 'Makeup Natural', price: 500000, description: 'Fresh, ringan, dan effortless.' },
  { id: 'fallback-party', name: 'Makeup Party', price: 750000, description: 'Lebih polished untuk momen spesial.' },
  { id: 'fallback-wedding', name: 'Makeup Wedding', price: 1500000, description: 'Look lengkap untuk hari istimewa.' },
  { id: 'fallback-graduation', name: 'Makeup Graduation', price: 600000, description: 'Tahan lama untuk hari kelulusan.' },
  { id: 'fallback-photoshoot', name: 'Makeup Photoshoot', price: 800000, description: 'Detail siap tampil di kamera.' },
]

const emptyForm = {
  serviceItems: [],
  date: '',
  endTime: '',
  name: '',
  phone: '',
  address: '',
  mapsUrl: '',
  notes: '',
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price)
}

function unwrapData(payload) {
  return Array.isArray(payload) ? payload : payload?.data ?? []
}


export default function BookingPage() {

  const [services, setServices] = useState(fallbackServices)
  const [form, setForm] = useState(emptyForm)
  const [step, setStep] = useState(1)
  const [calendarAvailability, setCalendarAvailability] = useState({ busyRanges: [], loading: true, error: '' })
  const [errors, setErrors] = useState({})
  const [submitState, setSubmitState] = useState({ status: 'idle', message: '' })
  const [servicesLoading, setServicesLoading] = useState(true)
  const [servicesError, setServicesError] = useState('')
  const [showOptional, setShowOptional] = useState(false)
  const { toast } = useToast()
  const errorTickRef = useRef(0)

  const highlightErrors = (errorKeys) => {
    if (!errorKeys.length) return
    errorTickRef.current += 1
    const tick = errorTickRef.current
    const firstField = document.getElementById(`field-${errorKeys[0]}`)
    if (firstField) firstField.scrollIntoView({ behavior: 'smooth', block: 'center' })
    errorKeys.forEach((key) => {
      const el = document.getElementById(`field-${key}`)
      if (el) {
        el.classList.remove('field-highlight')
        // restart animation
        void el.offsetWidth
        el.classList.add('field-highlight')
      }
    })
    const timeout = setTimeout(() => {
      errorKeys.forEach((key) => {
        if (errorTickRef.current !== tick) return
        const el = document.getElementById(`field-${key}`)
        if (el) el.classList.remove('field-highlight')
      })
    }, 2000)
    return () => clearTimeout(timeout)
  }

  useEffect(() => {
    let active = true
    listServices()
      .then((payload) => {
        if (!active) return
        const apiServices = unwrapData(payload).map((service) => ({
          id: String(service.id),
          name: service.name,
          price: Number(service.price ?? 0),
          description: service.description ?? 'Layanan makeup sesuai kebutuhanmu.',
        }))
        if (apiServices.length) setServices(apiServices)
      })
      .catch(() => {
        setServicesError('Katalog layanan belum terhubung.')
        toast({ type: 'error', title: 'Katalog layanan gagal dimuat', message: 'Menampilkan daftar layanan bawaan. Kamu tetap bisa melanjutkan booking.' })
      })
      .finally(() => active && setServicesLoading(false))

    return () => {
      active = false
    }
  }, [])

  const updateField = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: '' }))
  }

  const handleAvailabilityChange = useCallback((availability) => {
    setCalendarAvailability(availability)
  }, [])

  const toggleService = (serviceId, checked) => {
    setForm((prev) => ({
      ...prev,
      serviceItems: checked
        ? [...prev.serviceItems, { id: serviceId, qty: 1 }]
        : prev.serviceItems.filter((i) => i.id !== serviceId),
    }))
  }

  const updateQty = (serviceId, delta) => {
    setForm((prev) => ({
      ...prev,
      serviceItems: prev.serviceItems
        .map((i) => (i.id === serviceId ? { ...i, qty: i.qty + delta } : i))
        .filter((i) => i.qty > 0),
    }))
  }

  const validateStepOne = () => {
    const nextErrors = {}
    if (!form.serviceItems.length) nextErrors.serviceItems = 'Pilih minimal satu layanan.'
    return nextErrors
  }

  const validateStepTwo = () => {
    const nextErrors = {}
    if (!form.date) nextErrors.date = 'Pilih tanggal.'
    if (!form.endTime) nextErrors.endTime = 'Masukkan jam selesai.'
    return nextErrors
  }

  const validateStepThree = () => {
    const nextErrors = {}
    if (!form.endTime) nextErrors.endTime = 'Masukkan jam selesai.'
    if (!form.name.trim()) nextErrors.name = 'Isi nama.'
    if (!form.phone.trim()) nextErrors.phone = 'Isi nomor telepon.'
    if (!form.address.trim()) nextErrors.address = 'Isi alamat makeup.'
    if (form.mapsUrl && !/^https?:\/\//i.test(form.mapsUrl)) nextErrors.mapsUrl = 'Masukkan link yang valid.'
    return nextErrors
  }

  const validateStepFour = () => ({})

  const validators = [validateStepOne, validateStepTwo, validateStepThree]

  const nextStep = () => {
    const nextErrors = validators[step - 1]?.() ?? {}
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      const messages = Object.values(nextErrors)
      toast({
        type: 'error',
        title: `Lengkapi data di tahap 0${step}`,
        message: messages.join(' · '),
      })
      highlightErrors(Object.keys(nextErrors))
      return
    }
    setErrors({})
    setStep((current) => Math.min(current + 1, 4))
    if (step === 1) window.dispatchEvent(new CustomEvent('booking_form_start'))
  }

  const submitBooking = async (event) => {
    event.preventDefault()
    const nextErrors = validateStepThree()
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      setStep(3)
      toast({
        type: 'error',
        title: 'Lengkapi data sebelum mengirim',
        message: Object.values(nextErrors).join(' · '),
      })
      highlightErrors(Object.keys(nextErrors))
      return
    }

    setSubmitState({ status: 'loading', message: 'Mengirim pengajuan booking...' })
    try {
      await createBooking({
        services: form.serviceItems.map((item) => ({ id: item.id, qty: item.qty })),
        client_name: form.name,
        client_phone: form.phone,
        client_address: form.address,
        maps_url: form.mapsUrl || undefined,
        client_requested_date: form.date,
        client_requested_end_time: form.endTime,
        notes: form.notes || undefined,
      })
      setSubmitState({ status: 'success', message: 'Pengajuan booking diterima dengan status pending.' })
      window.dispatchEvent(new CustomEvent('booking_submit_success'))
    } catch (error) {
      const validationErrors = error.payload?.errors ?? {}
      const fieldMap = { client_name: 'name', client_phone: 'phone', client_address: 'address', client_requested_end_time: 'endTime', client_requested_date: 'date' }
      const stepForField = { name: 3, phone: 3, address: 3, mapsUrl: 3, notes: 3, endTime: 2, date: 2, serviceItems: 1 }
      if (Object.keys(validationErrors).length) {
        const mapped = Object.fromEntries(Object.entries(validationErrors).map(([key, value]) => [fieldMap[key] ?? key, Array.isArray(value) ? value[0] : value]))
        setErrors(mapped)
        setStep(stepForField[Object.keys(mapped)[0]] ?? 4)
        setTimeout(() => highlightErrors(Object.keys(mapped)), 0)
      }
      setSubmitState({ status: 'error', message: error.message })
      toast({ type: 'error', title: 'Pengajuan gagal dikirim', message: error.message })
      window.dispatchEvent(new CustomEvent('booking_submit_error'))
    }
  }

  const resetBooking = () => {
    setForm(emptyForm)
    setStep(1)
    setErrors({})
    setCalendarAvailability({ busyRanges: [], loading: true, error: '' })
    setSubmitState({ status: 'idle', message: '' })
  }

  if (submitState.status === 'success') {
    return (
      <main className="success-page">
        <div className="success-card">
          <span className="eyebrow">Pengajuan terkirim</span>
          <div className="success-mark" aria-hidden="true"><Check size={30} weight="bold" /></div>
          <h1>Booking-mu masuk sebagai pending.</h1>
          <p>{submitState.message} Staff akan meninjau tanggal, lokasi, dan kebutuhan layanan sebelum menentukan jam mulai aktual.</p>
          <div className="next-card">
            <strong>Langkah berikutnya</strong>
            <ol>
              <li>Staff mengecek ketersediaan dan lokasi.</li>
              <li>Staff menetapkan jam mulai aktual.</li>
              <li>Instruksi pembayaran dikirim melalui Midtrans.</li>
            </ol>
          </div>
          <button className="button button-secondary" onClick={resetBooking}>Ajukan booking lain</button>
        </div>
      </main>
    )
  }

  return (
    <main className="booking-page">
      <Navbar />
      <section className="booking-shell" id="booking" aria-labelledby="booking-title">
        <div className="section-heading">
          <div><h2 id="booking-title">Booking</h2></div>
          <span className="step-counter">0{step} / 04</span>
        </div>
        <div className="progress" aria-label={`Tahap ${step} dari 4`}>
          {['Pilih layanan', 'Pilih tanggal & jam', 'Isi detail', 'Kirim'].map((label, index) => <span key={label} className={index + 1 <= step ? 'active' : ''}><i>{String(index + 1).padStart(2, '0')}</i>{label}</span>)}
        </div>

        <form className="booking-grid" onSubmit={submitBooking} noValidate>
          <div className="form-panel">
            {step === 1 && <>

              <fieldset id="field-serviceItems">
                <div className="service-list">
                  {services.map((service) => {
                    const item = form.serviceItems.find((i) => i.id === service.id)
                    const selected = !!item
                    const inputId = `service-${service.id}`
                    return (
                      <label
                        className={`service-option ${selected ? 'selected' : ''}`}
                        key={service.id}
                        htmlFor={inputId}
                      >
                        <input
                          type="checkbox"
                          id={inputId}
                          name="service"
                          value={service.id}
                          checked={selected}
                          onChange={(e) => toggleService(service.id, e.target.checked)}
                          className="service-checkbox"
                        />
                        <div className={`service-check ${selected ? 'checked' : ''}`} aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                          </svg>
                        </div>
                        <span><strong>{service.name}</strong>{service.description ? <small>{service.description}</small> : null}</span>
                        <b>{formatPrice(service.price)}</b>
                        {selected && (
                          <div className="qty-control">
                            <button type="button" className="qty-btn" onClick={() => updateQty(service.id, -1)}>−</button>
                            <span className="qty-value">{item.qty}</span>
                            <button type="button" className="qty-btn" onClick={() => updateQty(service.id, 1)}>+</button>
                          </div>
                        )}
                      </label>
                    )
                  })}
                </div>
                {servicesLoading && <p className="muted-text">Memuat katalog layanan...</p>}
                {servicesError && <p className="field-error" role="alert">{servicesError}</p>}
                {errors.serviceItems && <p className="field-error" role="alert">{errors.serviceItems}</p>}
                {form.serviceItems.length > 0 && (
                  <div className="cart-summary">
                    <strong>Layanan terpilih:</strong>
                    <ul>
                      {form.serviceItems.map((item) => {
                        const service = services.find((s) => s.id === item.id)
                        if (!service) return null
                        return (
                          <li key={item.id}>
                            {service.name} × {item.qty} — {formatPrice(service.price * item.qty)}
                          </li>
                        )
                      })}
                    </ul>
                    <b>Total estimasi: {formatPrice(
                      form.serviceItems.reduce((sum, item) => {
                        const service = services.find((s) => s.id === item.id)
                        return sum + (service ? service.price * item.qty : 0)
                      }, 0)
                    )}</b>
                  </div>
                )}
              </fieldset>
            </>}

            {step === 2 && <>

              <div className="step-two-layout">
                <div id="field-date">
                  <BookingCalendar
                    selectedDate={form.date}
                    onSelectedDateChange={(date) => updateField('date', date)}
                    onAvailabilityChange={handleAvailabilityChange}
                  />
                  {errors.date && <small className="field-error" role="alert">{errors.date}</small>}
                </div>
                <div className="date-details">
                  <CalendarAvailability selectedDate={form.date} availability={calendarAvailability} />
                  <fieldset className="detail-group">
                    <div className="field" id="field-endTime">
                      <AnalogTimePicker value={form.endTime} onChange={(v) => updateField('endTime', v)} />
                      {errors.endTime && <small id="end-time-error" className="field-error" role="alert">{errors.endTime}</small>}
                    </div>
                  </fieldset>
                </div>
              </div>

            </>}

            {step === 3 && <>

              <fieldset className="detail-group">
                <legend>Detail kamu</legend>
                <div className="field-row">
                  <label className="field" id="field-name"><span>Nama lengkap</span><input id="client-name" value={form.name} onChange={(event) => updateField('name', event.target.value)} autoComplete="name" aria-invalid={Boolean(errors.name)} aria-describedby={errors.name ? 'client-name-error' : undefined} />{errors.name && <small id="client-name-error" className="field-error" role="alert">{errors.name}</small>}</label>
                  <label className="field" id="field-phone"><span>Nomor telepon</span><input id="client-phone" type="tel" value={form.phone} onChange={(event) => updateField('phone', event.target.value)} autoComplete="tel" aria-invalid={Boolean(errors.phone)} aria-describedby={errors.phone ? 'client-phone-error' : undefined} />{errors.phone && <small id="client-phone-error" className="field-error" role="alert">{errors.phone}</small>}</label>
                </div>
                <label className="field" id="field-address"><span>Alamat makeup</span><textarea id="client-address" rows={1} value={form.address} onChange={(event) => updateField('address', event.target.value)} autoComplete="street-address" aria-invalid={Boolean(errors.address)} aria-describedby={errors.address ? 'client-address-error' : undefined} />{errors.address && <small id="client-address-error" className="field-error" role="alert">{errors.address}</small>}</label>
                <button
                  type="button"
                  className={`optional-toggle ${showOptional ? 'open' : ''}`}
                  onClick={() => setShowOptional((value) => !value)}
                  aria-expanded={showOptional}
                  aria-controls="optional-details"
                >
                  <span>{showOptional ? 'Sembunyikan' : 'Tambah'} detail opsional</span>
                  <CaretDown className="optional-toggle-icon" size={14} weight="bold" aria-hidden="true" />
                </button>
                {showOptional || errors.mapsUrl || errors.notes ? (
                  <div className="optional-fields" id="optional-details">
                    <label className="field" id="field-mapsUrl"><span>Link Google Maps <em>opsional</em></span><input id="maps-url" type="url" placeholder="https://maps.google.com/..." value={form.mapsUrl} onChange={(event) => updateField('mapsUrl', event.target.value)} aria-invalid={Boolean(errors.mapsUrl)} aria-describedby={errors.mapsUrl ? 'maps-url-error' : undefined} />{errors.mapsUrl && <small id="maps-url-error" className="field-error" role="alert">{errors.mapsUrl}</small>}</label>
                    <label className="field"><span>Catatan khusus <em>opsional</em></span><textarea rows={2} placeholder="Alergi, kulit sensitif, trial, atau booking grup" value={form.notes} onChange={(event) => updateField('notes', event.target.value)} /></label>
                  </div>
                ) : null}
              </fieldset>
            </>}

            {step === 4 && <>

              <div className="review-list">
                <ReviewRow label="Nama" value={form.name || 'Belum diisi'} />
                <ReviewRow label="Telepon" value={form.phone || 'Belum diisi'} />
                {form.notes && <ReviewRow label="Catatan" value={form.notes} />}
              </div>
              <p className="review-trust">Pengajuan masuk sebagai <strong>pending</strong>. Staff meninjau tanggal, lokasi, dan layanan sebelum jam mulai dikonfirmasi — bukan pembayaran instan.</p>

            </>}
          </div>

          <aside className="summary-panel" aria-label="Ringkasan booking">
            <div className="summary-top"><span className="eyebrow">Ringkasan</span><span className="pending-pill">Pending</span></div>
                        <p className="summary-note">Pengajuan akan ditinjau staff sebelum dikonfirmasi.</p>
            <div className="summary-service"><span className="summary-number">01</span><div><strong>{form.serviceItems.map(item => {
              const s = services.find(sv => sv.id === item.id)
              return s ? `${s.name} × ${item.qty}` : ''
            }).join(', ') || 'Belum memilih layanan'}</strong><small>{form.serviceItems.length
              ? `Total: ${formatPrice(form.serviceItems.reduce((sum, item) => {
                  const s = services.find(sv => sv.id === item.id)
                  return sum + (s ? s.price * item.qty : 0)
                }, 0))}`
              : 'Harga mulai'}</small></div></div>
            <dl><div><dt>Tanggal</dt><dd>{formatDayLabel(form.date)}</dd></div><div><dt>Jam selesai</dt><dd>{form.endTime || 'Belum diusulkan'}</dd></div><div><dt>Lokasi</dt><dd>{form.address || 'Belum diisi'}</dd></div></dl>

          </aside>

          <div className="form-actions">
            {step > 1 && <button type="button" className="button button-secondary" onClick={() => setStep((current) => current - 1)}><ArrowLeft size={16} weight="bold" aria-hidden="true" /> Kembali</button>}
            <div className="form-actions-main">
              {step < 4 ? <button type="button" className="button button-primary" onClick={nextStep}>{step === 1 ? 'Lanjut pilih tanggal' : step === 2 ? 'Lanjut isi detail' : 'Tinjau pengajuan'} <ArrowRight size={16} weight="bold" aria-hidden="true" /></button> : <button type="submit" className="button button-primary" disabled={submitState.status === 'loading'}>{submitState.status === 'loading' ? 'Mengirim...' : 'Kirim pengajuan booking'} <ArrowUpRight size={16} weight="bold" aria-hidden="true" /></button>}
            </div>
          </div>
        </form>
      </section>

      <footer className="site-footer"><span>Booking tanpa akun · Status awal pending</span></footer>
    </main>
  )
}

function ReviewRow({ label, value }) {
  return <div className="review-row"><span>{label}</span><strong>{value}</strong></div>
}

function formatBusyTime(value) {
  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(value))
}

function formatDayLabel(value) {
  if (!value) return 'Belum dipilih'
  const date = new Date(value + 'T00:00:00')
  return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(date)
}

function CalendarAvailability({ selectedDate, availability }) {
  if (!selectedDate) return null
  if (availability.error) return <div className="schedule-status error" role="status"><Warning size={16} weight="bold" aria-hidden="true" /><p>{availability.error}</p></div>
  if (!availability.busyRanges.length) return null

  return (
    <div className="busy-ranges" aria-live="polite">
      <span className="step-kicker">Jadwal final tercatat</span>
      <ul>
        {availability.busyRanges.map((range) => (
          <li key={`${range.starts_at}-${range.ends_at}`}>
            <span>{formatBusyTime(range.starts_at)}</span>
            <i aria-hidden="true" />
            <span>{formatBusyTime(range.ends_at)}</span>
          </li>
        ))}
      </ul>
      <p>Tanggal tetap bisa dipilih. Staff akan menentukan jam mulai aktual.</p>
    </div>
  )
}


