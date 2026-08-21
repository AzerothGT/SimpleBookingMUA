import { useCallback, useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { ArrowLeftIcon, CheckCircleIcon, CreditCardIcon, WarningCircleIcon } from '@phosphor-icons/react'
import { createPublicSnapTransaction, getPublicBookingStatus, loadMidtransSnap, syncPublicPaymentStatus } from '../../api/bookingApi'


function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0))
}

function unwrap(payload) {
  return payload?.data ?? payload
}

export default function PaymentPage() {
  const [searchParams] = useSearchParams()
  const bookingId = searchParams.get('booking')
  const token = searchParams.get('token')
  const [booking, setBooking] = useState(null)
  const [loading, setLoading] = useState(true)
  const [paymentLoading, setPaymentLoading] = useState('')
  const [error, setError] = useState('')

  const refresh = useCallback(async () => {
    if (!bookingId || !token) {
      setError('Link pembayaran tidak lengkap.')
      setLoading(false)
      return
    }

    try {
      // Sync pulls the authoritative status from Midtrans, so the invoice stays
      // correct even when the webhook never reaches this environment.
      let payload
      try {
        payload = await syncPublicPaymentStatus(bookingId, token)
      } catch {
        payload = await getPublicBookingStatus(bookingId, token)
      }
      setBooking(unwrap(payload))
      setError('')
    } catch (requestError) {
      setError(requestError.message)
    } finally {
      setLoading(false)
    }
  }, [bookingId, token])

  useEffect(() => {
    refresh()
  }, [refresh])

  useEffect(() => {
    if (!booking || ['confirmed', 'cancelled', 'done'].includes(booking.status)) return undefined
    const interval = window.setInterval(refresh, 10000)
    return () => window.clearInterval(interval)
  }, [booking, refresh])

  const summary = booking?.payment_summary
  const paid = Number(summary?.paid ?? 0)
  const remaining = Number(summary?.remaining ?? 0)
  const dpAmount = Math.min(remaining, Number(summary?.minimum_dp ?? 0))
  const paidTypes = useMemo(() => new Set(booking?.transactions?.filter((transaction) => transaction.paid_at).map((transaction) => transaction.type) ?? []), [booking])
  const paymentProgress = summary?.total ? Math.min(100, Math.round((paid / Number(summary.total)) * 100)) : 0


  const startPayment = async (type) => {
    setPaymentLoading(type)
    setError('')
    try {
      const payload = await createPublicSnapTransaction(bookingId, token, type)
      const transaction = unwrap(payload)
      if (transaction.snap_token) {
        const snap = await loadMidtransSnap()
        const closeAndRefresh = () => {
          snap.hide()
          setPaymentLoading('')
          refresh()
          window.setTimeout(refresh, 2000)
          window.setTimeout(refresh, 5000)
        }

        snap.pay(transaction.snap_token, {
          onClose: closeAndRefresh,
          onSuccess: closeAndRefresh,
          onPending: closeAndRefresh,
          onError: closeAndRefresh,
        })
      } else if (transaction.redirect_url) {
        window.location.assign(transaction.redirect_url)
      } else {
        throw new Error('Tautan pembayaran belum tersedia.')
      }
    } catch (requestError) {
      setError(requestError.payload?.message ?? requestError.message)
    } finally {
      setPaymentLoading('')
    }
  }

  return (
    <main>
      <section className="booking-shell min-h-0" aria-labelledby="payment-title">
        <button className="payment-back" type="button" onClick={() => window.history.back()} aria-label="Kembali" title="Kembali"><ArrowLeftIcon size={18} weight="bold" aria-hidden="true" /></button>
        <div className="section-heading mt-6"><div><span className="eyebrow">Pembayaran</span><h1 id="payment-title">Selesaikan pembayaran.</h1></div><CreditCardIcon size={28} aria-hidden="true" /></div>
        {loading && <div className="admin-state">Memuat detail pembayaran...</div>}
        {!loading && error && <div className="login-error" role="alert"><WarningCircleIcon size={18} /> {error}</div>}
        {!loading && booking && <div className="payment-layout">
          <div className="form-panel">
            <span className="eyebrow">Ringkasan booking</span>
            <h2>{booking.status === 'confirmed' ? 'Pembayaran terkonfirmasi.' : 'Ringkasan booking.'}</h2>
            <p>{booking.starts_at ? new Date(booking.starts_at).toLocaleString('id-ID') : 'Jadwal belum tersedia'}</p>
            <div className="payment-progress" aria-label={`${paymentProgress}% pembayaran selesai`}>
              <div className="payment-progress-heading"><span>Progres pembayaran</span><strong>{paymentProgress}%</strong></div>
              <div className="payment-progress-track"><span style={{ width: `${paymentProgress}%` }} /></div>
              <p>{paymentProgress === 0 ? 'Belum ada pembayaran. Mulai dari DP untuk mengamankan jadwal.' : `${formatCurrency(paid)} dari ${formatCurrency(summary?.total)} sudah dibayar.`}</p>
            </div>
            <div className="public-booking-status">
              <span className="detail-label">Booking ID</span><strong>{booking.id}</strong>
              <span className="detail-label">Layanan</span>{booking.services?.map((service) => <div className="detail-line" key={service.id}><span>{service.name} × {service.qty}</span><strong>{formatCurrency(service.subtotal)}</strong></div>)}
              <div className="detail-line"><span>Total layanan</span><strong>{formatCurrency(summary?.total)}</strong></div>
              <div className="detail-line"><span>Sudah dibayar</span><strong>{formatCurrency(paid)}</strong></div>
              <div className="detail-line"><span>Sisa tagihan</span><strong>{formatCurrency(remaining)}</strong></div>
            </div>
          </div>
          <div className="form-panel">
            {booking.status === 'confirmed' || remaining === 0 ? <div className="success-mark"><CheckCircleIcon size={36} aria-hidden="true" /></div> : <>
              <span className="eyebrow">Pembayaran</span>
              <h2>Pilih nominal.</h2>
              <div className="payment-choice-list">
                {!paidTypes.has('dp') && <button className="button button-primary" type="button" disabled={Boolean(paymentLoading)} onClick={() => startPayment('dp')}>{paymentLoading === 'dp' ? 'Menyiapkan...' : `DP ${formatCurrency(dpAmount)}`}</button>}
                {remaining > 0 && <button className="button button-secondary" type="button" disabled={Boolean(paymentLoading)} onClick={() => startPayment('pelunasan')}>{paymentLoading === 'pelunasan' ? 'Menyiapkan...' : `Bayar lunas ${formatCurrency(remaining)}`}</button>}
              </div>
              <p className="payment-trust"><CreditCardIcon size={15} aria-hidden="true" /> Aman via Midtrans · Status otomatis diperbarui</p>
            </>}
          </div>
        </div>}
      </section>
    </main>
  )
}
