import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeftIcon, ArrowRightIcon, ArrowUpRightIcon, CaretDownIcon, CheckIcon, CopyIcon, WarningIcon } from '@phosphor-icons/react'
import { createBooking, createPublicSnapTransaction, getPublicBookingStatus, listServices, loadMidtransSnap } from '../../api/bookingApi'
import AnalogTimePicker from '../../components/AnalogTimePicker'
import BookingCalendar from '../../components/BookingCalendar'

import { useToast } from '../../context/useToast'


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
  const navigate = useNavigate()

  const [services, setServices] = useState([])
  const [form, setForm] = useState(emptyForm)
  const [step, setStep] = useState(1)
  const [stepDirection, setStepDirection] = useState('forward')
  const [calendarAvailability, setCalendarAvailability] = useState({ busyRanges: [], loading: true, error: '' })
  const [errors, setErrors] = useState({})
  const [submitState, setSubmitState] = useState({ status: 'idle', message: '' })
  const [publicSession, setPublicSession] = useState(() => {
    try {
      return JSON.parse(window.localStorage.getItem('public_booking_session') ?? 'null')
    } catch {
      return null
    }
  })
  const [publicBooking, setPublicBooking] = useState(null)
  const [publicError, setPublicError] = useState('')
  const [paymentLoading, setPaymentLoading] = useState(false)
  const [copyState, setCopyState] = useState('idle')
  const [navigationLoading, setNavigationLoading] = useState(false)
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
        if (apiServices.length) {
          setServices(apiServices)
        } else {
          setServicesError('Belum ada layanan aktif.')
        }
      })
      .catch(() => {
        setServices([])
        setServicesError('Katalog layanan belum terhubung.')
        toast({ type: 'error', title: 'Katalog layanan gagal dimuat', message: 'Booking belum dapat dikirim sampai katalog layanan tersedia.' })
      })
      .finally(() => active && setServicesLoading(false))

    return () => {
      active = false
    }
  }, [toast])

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
    if (!services.length || servicesError) nextErrors.serviceItems = 'Katalog layanan belum tersedia.'
    else if (!form.serviceItems.length) nextErrors.serviceItems = 'Pilih minimal satu layanan.'
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


  const validators = [validateStepOne, validateStepTwo, validateStepThree]

  const finishNavigation = () => {
    requestAnimationFrame(() => setNavigationLoading(false))
  }

  const nextStep = () => {
    if (navigationLoading) return
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
    setNavigationLoading(true)
    setStepDirection('forward')
    setErrors({})
    setStep((current) => Math.min(current + 1, 4))
    if (step === 1) window.dispatchEvent(new CustomEvent('booking_form_start'))
    finishNavigation()
  }

  const submitBooking = async (event) => {
    event.preventDefault()
    const nextErrors = { ...validateStepOne(), ...validateStepThree() }
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      setStep(Object.hasOwn(nextErrors, 'serviceItems') ? 1 : 3)
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
      const created = await createBooking({
        services: form.serviceItems.map((item) => ({ id: item.id, qty: item.qty })),
        client_name: form.name,
        client_phone: form.phone,
        client_address: form.address,
        maps_url: form.mapsUrl || undefined,
        client_requested_date: form.date,
        client_requested_end_time: form.endTime,
        notes: form.notes || undefined,
      })
      const booking = created?.data ?? created
      const token = booking?.payment_access_token
      if (!booking?.id || !token) throw new Error('Booking berhasil dibuat, tetapi akses pelacakan tidak tersedia.')
      const session = { bookingId: booking.id, token }
      window.localStorage.setItem('public_booking_session', JSON.stringify(session))
      setPublicSession(session)
      setPublicBooking(booking)
      setSubmitState({ status: 'success', message: 'Pengajuan booking berhasil dikirim.' })
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

  const goToPreviousStep = () => {
    if (navigationLoading) return
    setNavigationLoading(true)
    setStepDirection('backward')
    setStep((current) => Math.max(current - 1, 1))
    finishNavigation()
  }

  const copyBookingId = async () => {
    try {
      await navigator.clipboard.writeText(publicSession.bookingId)
      setCopyState('success')
      window.setTimeout(() => setCopyState('idle'), 2000)
    } catch {
      setCopyState('error')
    }
  }

  const resetBooking = () => {
    window.localStorage.removeItem('public_booking_session')
    setPublicSession(null)
    setPublicBooking(null)
    setPublicError('')
    setCopyState('idle')
    setForm(emptyForm)
    setStep(1)
    setErrors({})
    setCalendarAvailability({ busyRanges: [], loading: true, error: '' })
    setSubmitState({ status: 'idle', message: '' })
  }

  useEffect(() => {
    if (publicSession) setSubmitState((current) => current.status === 'success' ? current : { status: 'success', message: 'Pengajuan booking berhasil dikirim.' })
  }, [publicSession])

  useEffect(() => {
    if (!publicSession) return undefined
    let active = true
    let interval
    const refresh = async () => {
      try {
        const payload = await getPublicBookingStatus(publicSession.bookingId, publicSession.token)
        if (active) {
          const nextBooking = payload?.data ?? payload
          setPublicBooking(nextBooking)
          setPublicError('')
          if (['cancelled', 'done'].includes(nextBooking?.status) || ['capture', 'settlement'].includes(nextBooking?.payment?.transaction_status)) window.clearInterval(interval)
        }
      } catch (error) {
        if (!active) return
        if (error.status === 401) {
          window.localStorage.removeItem('public_booking_session')
          setPublicSession(null)
          setPublicBooking(null)
        }
        setPublicError(error.message)
      }
    }
    refresh()
    interval = window.setInterval(refresh, 10000)
    return () => { active = false; window.clearInterval(interval) }
  }, [publicSession])

  const startPayment = async () => {
    if (!publicSession || !publicBooking?.starts_at || !publicBooking?.ends_at) return
    setPaymentLoading(true)
    setPublicError('')
    try {
      const payload = await createPublicSnapTransaction(publicSession.bookingId, publicSession.token)
      const transaction = payload?.data ?? payload
      if (transaction.snap_token) {
        const snap = await loadMidtransSnap()
        snap.pay(transaction.snap_token)
      } else if (transaction.redirect_url) {
        window.location.assign(transaction.redirect_url)
      } else {
        throw new Error('Tautan pembayaran belum tersedia.')
      }
    } catch (error) {
      setPublicError(error.message)
    } finally {
      setPaymentLoading(false)
    }
  }

  if (submitState.status === 'success' && publicSession) {
    const scheduled = Boolean(publicBooking?.starts_at && publicBooking?.ends_at)
    const paid = ['capture', 'settlement'].includes(publicBooking?.payment?.transaction_status) && publicBooking?.payment?.paid_at
    return (
      <main className="success-page"><div className="success-card">
        <span className="eyebrow">Pengajuan terkirim</span><div className="success-mark" aria-hidden="true"><svg className="success-check" viewBox="0 0 32 32" fill="none"><path d="M7 16.5 13 22 25 10" pathLength="1" /></svg></div>
        <h1>{paid ? 'Pembayaran berhasil.' : scheduled ? 'Jadwal sudah tersedia.' : 'Menunggu konfirmasi jadwal.'}</h1>
        <p>{paid ? 'Pembayaran sedang tercatat di sistem.' : 'Simpan ID booking ini. Status akan diperbarui otomatis setelah tim menetapkan jadwal.'}</p>
        <div className="public-booking-status"><span className="detail-label">Booking ID</span><div className="public-booking-id-row"><strong>{publicSession.bookingId}</strong><button className="copy-booking-id" type="button" onClick={copyBookingId} aria-label={copyState === 'success' ? 'Booking ID tersalin' : `Salin booking ID ${publicSession.bookingId}`} title={copyState === 'success' ? 'Tersalin' : 'Salin booking ID'}>{copyState === 'success' ? <CheckIcon size={16} weight="bold" aria-hidden="true" /> : <CopyIcon size={16} weight="bold" aria-hidden="true" />}</button></div>{copyState === 'error' && <p className="copy-booking-id-error" role="alert">Tidak dapat menyalin otomatis. Pilih booking ID secara manual.</p>}<span className="detail-label">Status</span><strong>{publicBooking?.status ?? 'pending'}</strong>{scheduled && <><span className="detail-label">Jadwal mulai</span><strong>{new Date(publicBooking.starts_at).toLocaleString('id-ID')}</strong></>}</div>
        {publicError && <div className="login-error" role="alert">{publicError}</div>}
        {!paid && <button className="button button-primary" disabled={!scheduled || paymentLoading} onClick={startPayment}>{paymentLoading ? 'Menyiapkan pembayaran...' : scheduled ? 'Bayar sekarang' : 'Menunggu jadwal tim'}</button>}
        <button className="button button-secondary" onClick={resetBooking}>Ajukan booking lain</button>
      </div></main>
    )
  }

  return (
    <main className="booking-page">
      <section className="booking-shell" id="booking" aria-labelledby="booking-title">
        <div className="section-heading">
          <div><button type="button" className="booking-cancel" onClick={() => navigate('/')}><ArrowLeftIcon size={14} weight="bold" aria-hidden="true" /> Batal dan kembali</button><h2 id="booking-title">Booking</h2></div>
          <span className="step-counter">0{step} / 04</span>
        </div>
        <div className="progress" aria-label={`Tahap ${step} dari 4`}>
          {['Pilih layanan', 'Pilih tanggal & jam', 'Isi detail', 'Kirim'].map((label, index) => <span key={label} className={index + 1 <= step ? 'active' : ''}><i>{String(index + 1).padStart(2, '0')}</i>{label}</span>)}
        </div>

        <form className="booking-grid" onSubmit={submitBooking} noValidate>
          <div className={`form-panel step-transition step-transition-${stepDirection}`}>
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
                            <button type="button" className="qty-btn" disabled={navigationLoading} onClick={() => {
                              setNavigationLoading(true)
                              updateQty(service.id, -1)
                              finishNavigation()
                            }}>−</button>
                            <span className="qty-value">{item.qty}</span>
                            <button type="button" className="qty-btn" disabled={navigationLoading} onClick={() => {
                              setNavigationLoading(true)
                              updateQty(service.id, 1)
                              finishNavigation()
                            }}>+</button>
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
                  onClick={() => {
                    if (navigationLoading) return
                    setNavigationLoading(true)
                    setShowOptional((value) => !value)
                    finishNavigation()
                  }}
                  disabled={navigationLoading}
                  aria-expanded={showOptional}
                  aria-controls="optional-details"
                >
                  <span>{showOptional ? 'Sembunyikan' : 'Tambah'} detail opsional</span>
                  <CaretDownIcon className="optional-toggle-icon" size={14} weight="bold" aria-hidden="true" />
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
            <div className="summary-top">
              <div>
                <span className="eyebrow">Ringkasan booking</span>

              </div>
              <span className="pending-pill">Menunggu konfirmasi</span>
            </div>
            <div className="summary-service">
              <span className="summary-number">01</span>
              <div className="summary-service-content">
                <span className="summary-label">Layanan</span>
                <strong>{form.serviceItems.map((item) => {
                  const service = services.find((current) => current.id === item.id)
                  return service ? `${service.name} × ${item.qty}` : ''
                }).join(', ') || 'Belum memilih layanan'}</strong>
                <small>{form.serviceItems.length
                  ? `Total ${formatPrice(form.serviceItems.reduce((sum, item) => {
                      const service = services.find((current) => current.id === item.id)
                      return sum + (service ? service.price * item.qty : 0)
                    }, 0))}`
                  : 'Total akan muncul setelah memilih layanan'}</small>
              </div>
            </div>
            <dl>
              <div><dt>Tanggal</dt><dd>{formatDayLabel(form.date)}</dd></div>
              <div><dt>Jam selesai</dt><dd>{form.endTime || 'Belum diusulkan'}</dd></div>
              <div><dt>Lokasi</dt><dd>{form.address || 'Belum diisi'}</dd></div>
            </dl>
          </aside>

          <div className="form-actions">
            {step > 1 && <button type="button" className="button button-secondary" disabled={navigationLoading} onClick={goToPreviousStep}><ArrowLeftIcon size={16} weight="bold" aria-hidden="true" /> Kembali</button>}
            <div className="form-actions-main">
              {step < 4 ? <button type="button" className="button button-primary" disabled={navigationLoading} onClick={nextStep}>{navigationLoading ? <><span className="spinner" aria-hidden="true" /> Memproses...</> : <>{step === 1 ? 'Lanjut pilih tanggal' : step === 2 ? 'Lanjut isi detail' : 'Tinjau pengajuan'} <ArrowRightIcon size={16} weight="bold" aria-hidden="true" /></>}</button> : <button type="submit" className="button button-primary" disabled={submitState.status === 'loading' || navigationLoading}>{submitState.status === 'loading' ? 'Mengirim...' : 'Kirim pengajuan booking'} <ArrowUpRightIcon size={16} weight="bold" aria-hidden="true" /></button>}
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
  if (availability.error) return <div className="schedule-status error" role="status"><WarningIcon size={16} weight="bold" aria-hidden="true" /><p>{availability.error}</p></div>
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


