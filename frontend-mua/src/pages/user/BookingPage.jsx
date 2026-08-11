import { useCallback, useEffect, useMemo, useState } from 'react'
import { ArrowLeft, ArrowRight, ArrowUpRight, Check, Warning } from '@phosphor-icons/react'
import { createBooking, listServices } from '../../api/bookingApi'
import BookingCalendar from '../../components/BookingCalendar'
import Navbar from '../../components/Navbar'

const fallbackServices = [
  { id: 'fallback-natural', name: 'Makeup Natural', price: 500000, description: 'Fresh, ringan, dan effortless.' },
  { id: 'fallback-party', name: 'Makeup Party', price: 750000, description: 'Lebih polished untuk momen spesial.' },
  { id: 'fallback-wedding', name: 'Makeup Wedding', price: 1500000, description: 'Look lengkap untuk hari istimewa.' },
  { id: 'fallback-graduation', name: 'Makeup Graduation', price: 600000, description: 'Tahan lama untuk hari kelulusan.' },
  { id: 'fallback-photoshoot', name: 'Makeup Photoshoot', price: 800000, description: 'Detail siap tampil di kamera.' },
]

const emptyForm = {
  serviceId: '',
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

  useEffect(() => {
    let active = true
    listServices()
      .then((payload) => {
        if (!active) return
        const apiServices = unwrapData(payload).map((service) => ({
          id: service.id,
          name: service.name,
          price: Number(service.price ?? 0),
          description: service.description ?? 'Layanan makeup sesuai kebutuhanmu.',
        }))
        if (apiServices.length) setServices(apiServices)
      })
      .catch(() => setServicesError('Katalog layanan belum terhubung.'))
      .finally(() => active && setServicesLoading(false))

    return () => {
      active = false
    }
  }, [])

  const selectedService = useMemo(
    () => services.find((service) => String(service.id) === String(form.serviceId)),
    [services, form.serviceId],
  )

  const updateField = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: '' }))
  }

  const handleAvailabilityChange = useCallback((availability) => {
    setCalendarAvailability(availability)
  }, [])

  const validateStepOne = () => {
    const nextErrors = {}
    if (!form.serviceId) nextErrors.serviceId = 'Pilih layanan.'
    if (String(form.serviceId).startsWith('fallback-')) nextErrors.serviceId = 'Katalog layanan belum terhubung.'
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


  const nextStep = () => {
    const validators = [validateStepOne, validateStepTwo, validateStepThree, validateStepFour]
    const nextErrors = validators[step - 1]()
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
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
      return
    }

    setSubmitState({ status: 'loading', message: 'Mengirim pengajuan booking...' })
    try {
      await createBooking({
        service_id: form.serviceId,
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
      const fieldMap = { client_name: 'name', client_phone: 'phone', client_address: 'address', client_requested_end_time: 'endTime' }
      if (Object.keys(validationErrors).length) {
        setErrors(Object.fromEntries(Object.entries(validationErrors).map(([key, value]) => [fieldMap[key] ?? key, Array.isArray(value) ? value[0] : value])))
      }
      setSubmitState({ status: 'error', message: error.message })
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
          <div><h2 id="booking-title">Cek tanggal & ajukan booking</h2></div>
          <span className="step-counter">0{step} / 04</span>
        </div>
        <div className="progress" aria-label={`Tahap ${step} dari 4`}>
          {['Pilih layanan', 'Pilih tanggal & jam', 'Isi detail', 'Kirim'].map((label, index) => <span key={label} className={index + 1 <= step ? 'active' : ''}><i>{String(index + 1).padStart(2, '0')}</i>{label}</span>)}
        </div>

        <form className="booking-grid" onSubmit={submitBooking} noValidate>
          <div className="form-panel">
            {step === 1 && <>
              <div className="panel-intro"><h3>Pilih layanan</h3></div>
              <fieldset>
                <legend>Layanan makeup</legend>
                <div className="service-list">
                  {services.map((service) => <label className={`service-option ${String(form.serviceId) === String(service.id) ? 'selected' : ''}`} key={service.id}><input type="radio" name="service" value={service.id} checked={String(form.serviceId) === String(service.id)} onChange={(event) => updateField('serviceId', event.target.value)} /><span><strong>{service.name}</strong></span><b>{formatPrice(service.price)}</b></label>)}
                </div>
                {servicesLoading && <p className="muted-text">Memuat katalog layanan...</p>}
                {servicesError && <p className="field-error" role="alert">{servicesError}</p>}
                {errors.serviceId && <p className="field-error" role="alert">{errors.serviceId}</p>}
              </fieldset>
            </>}

            {step === 2 && <>
              <div className="panel-intro"><h3>Pilih tanggal</h3></div>
              <div className="selected-summary"><span>{selectedService?.name ?? 'Layanan terpilih'}</span><strong>Pilih tanggal di bawah</strong></div>
              <div className="step-two-layout">
                <BookingCalendar
                  selectedDate={form.date}
                  onSelectedDateChange={(date) => updateField('date', date)}
                  onAvailabilityChange={handleAvailabilityChange}
                />
                <div className="date-details">
                  <div>
                    <span className="step-kicker">Tanggal terpilih</span>
                    <strong className="selected-date">{form.date || 'Belum dipilih'}</strong>
                    {errors.date && <small id="booking-date-error" className="field-error">{errors.date}</small>}
                  </div>
                  <CalendarAvailability selectedDate={form.date} availability={calendarAvailability} />
                  <fieldset className="detail-group">
                    <legend>Jam</legend>
                    <label className="field"><span>Jam selesai yang diusulkan</span><input id="end-time" type="time" value={form.endTime} onChange={(event) => updateField('endTime', event.target.value)} aria-invalid={Boolean(errors.endTime)} aria-describedby={errors.endTime ? 'end-time-error' : undefined} />{errors.endTime && <small id="end-time-error" className="field-error">{errors.endTime}</small>}</label>
                  </fieldset>
                </div>
              </div>

            </>}

            {step === 3 && <>
              <div className="panel-intro"><h3>Isi detail</h3></div>
              <fieldset className="detail-group">
                <legend>Kontak</legend>
                <div className="field-row"><label className="field"><span>Nama lengkap</span><input id="client-name" value={form.name} onChange={(event) => updateField('name', event.target.value)} autoComplete="name" aria-invalid={Boolean(errors.name)} aria-describedby={errors.name ? 'client-name-error' : undefined} />{errors.name && <small id="client-name-error" className="field-error">{errors.name}</small>}</label><label className="field"><span>Nomor telepon</span><input id="client-phone" type="tel" value={form.phone} onChange={(event) => updateField('phone', event.target.value)} autoComplete="tel" aria-invalid={Boolean(errors.phone)} aria-describedby={errors.phone ? 'client-phone-error' : undefined} />{errors.phone && <small id="client-phone-error" className="field-error">{errors.phone}</small>}</label></div>
              </fieldset>
              <fieldset className="detail-group">
                <legend>Lokasi</legend>
                <label className="field"><span>Alamat makeup</span><textarea id="client-address" rows="2" value={form.address} onChange={(event) => updateField('address', event.target.value)} autoComplete="street-address" aria-invalid={Boolean(errors.address)} aria-describedby={errors.address ? 'client-address-error' : undefined} />{errors.address && <small id="client-address-error" className="field-error">{errors.address}</small>}</label>
                <label className="field"><span>Link Google Maps <em>opsional</em></span><input id="maps-url" type="url" placeholder="https://maps.google.com/..." value={form.mapsUrl} onChange={(event) => updateField('mapsUrl', event.target.value)} aria-invalid={Boolean(errors.mapsUrl)} aria-describedby={errors.mapsUrl ? 'maps-url-error' : undefined} />{errors.mapsUrl && <small id="maps-url-error" className="field-error">{errors.mapsUrl}</small>}</label>
              </fieldset>
              <fieldset className="detail-group">
                <legend>Catatan tambahan <em>opsional</em></legend>
                <label className="field"><span>Kebutuhan khusus</span><textarea rows="2" placeholder="Alergi, kulit sensitif, trial, atau booking grup" value={form.notes} onChange={(event) => updateField('notes', event.target.value)} /></label>
              </fieldset>
            </>}

            {step === 4 && <>
              <div className="panel-intro"><h3>Tinjau & kirim</h3></div>
              <div className="review-list">
                <ReviewRow label="Layanan" value={selectedService?.name ?? 'Belum dipilih'} />
                <ReviewRow label="Tanggal" value={form.date || 'Belum dipilih'} />
                <ReviewRow label="Jam selesai" value={form.endTime || 'Belum diusulkan'} />
                <ReviewRow label="Nama" value={form.name || 'Belum diisi'} />
                <ReviewRow label="Telepon" value={form.phone || 'Belum diisi'} />
                <ReviewRow label="Alamat" value={form.address || 'Belum diisi'} />
              </div>

            </>}
          </div>

          <aside className="summary-panel" aria-label="Ringkasan booking">
            <div className="summary-top"><span className="eyebrow">Ringkasan</span><span className="pending-pill">Pending</span></div>
                        <p className="summary-note">Pengajuan akan ditinjau staff sebelum dikonfirmasi.</p>
            <div className="summary-service"><span className="summary-number">01</span><div><strong>{selectedService?.name ?? 'Belum memilih layanan'}</strong><small>{selectedService ? formatPrice(selectedService.price) : 'Harga mulai'}</small></div></div>
            <dl><div><dt>Tanggal</dt><dd>{form.date || 'Belum dipilih'}</dd></div><div><dt>Jam selesai</dt><dd>{form.endTime || 'Belum diusulkan'}</dd></div><div><dt>Lokasi</dt><dd>{form.address || 'Belum diisi'}</dd></div></dl>

          </aside>

          <div className="form-actions">
            {step > 1 && <button type="button" className="button button-secondary" onClick={() => setStep((current) => current - 1)}><ArrowLeft size={16} weight="bold" aria-hidden="true" /> Kembali</button>}
            {step < 4 ? <button type="button" className="button button-primary" onClick={nextStep}>{step === 1 ? 'Lanjut pilih tanggal' : step === 2 ? 'Lanjut isi detail' : 'Tinjau pengajuan'} <ArrowRight size={16} weight="bold" aria-hidden="true" /></button> : <button type="submit" className="button button-primary" disabled={submitState.status === 'loading'}>{submitState.status === 'loading' ? 'Mengirim...' : 'Kirim pengajuan booking'} <ArrowUpRight size={16} weight="bold" aria-hidden="true" /></button>}
            {submitState.status === 'error' && <p className="submit-error" role="alert">{submitState.message}</p>}
          </div>
        </form>
      </section>

      <footer className="site-footer"><span>[Nama MUA]</span><span>Area layanan: [Kota/area layanan]</span><span>Booking tanpa akun · Status awal pending</span></footer>
    </main>
  )
}

function ReviewRow({ label, value }) {
  return <div className="review-row"><span>{label}</span><strong>{value}</strong></div>
}

function formatBusyTime(value) {
  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(value))
}

function CalendarAvailability({ selectedDate, availability }) {
  if (!selectedDate) return <div className="schedule-status idle"><p>Pilih tanggal untuk melihat jadwal final.</p></div>
  if (availability.loading) return <div className="schedule-status loading" role="status"><span className="spinner" /><p>Memuat jadwal bulan ini...</p></div>
  if (availability.error) return <div className="schedule-status error" role="status"><Warning size={16} weight="bold" aria-hidden="true" /><p>{availability.error}</p></div>
  if (!availability.busyRanges.length) return <div className="schedule-status empty" role="status"><Check size={16} weight="bold" aria-hidden="true" /><p>Belum ada jadwal final tercatat.</p></div>

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


