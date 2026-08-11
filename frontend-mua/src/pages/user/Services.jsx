import { useEffect, useState } from 'react'
import Navbar from '../../components/Navbar'
import ServiceCard from '../../components/ServiceCard'
import { listServices } from '../../api/bookingApi'

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price)
}

export default function Services() {
  const [services, setServices] = useState([])
  const [status, setStatus] = useState('loading')

  useEffect(() => {
    listServices()
      .then((payload) => {
        const items = Array.isArray(payload) ? payload : payload?.data ?? []
        setServices(items.map((service) => ({
          ...service,
          priceLabel: formatPrice(Number(service.price ?? 0)),
          description: service.description ?? 'Layanan makeup sesuai kebutuhanmu.',
        })))
        setStatus('ready')
      })
      .catch(() => setStatus('error'))
  }, [])

  return (
    <main>
      <Navbar />
      <section className="booking-shell min-h-0" aria-labelledby="services-title">
        <div className="panel-intro max-w-2xl">
          <span className="eyebrow">Layanan makeup</span>
          <h1 id="services-title" className="m-0 mt-3 font-display text-5xl font-normal tracking-[-.06em]" style={{ color: 'var(--ink)' }}>Pilih look untuk momenmu.</h1>
          <p className="mt-5 text-base leading-7" style={{ color: 'var(--muted)' }}>Lihat pilihan layanan, lalu lanjutkan booking tanpa membuat akun.</p>
        </div>
        {status === 'loading' && <p className="muted-text">Memuat layanan...</p>}
        {status === 'error' && <p className="field-error" role="alert">Layanan belum dapat dimuat.</p>}
        {status === 'ready' && <div className="grid gap-4 md:grid-cols-2">{services.map((service) => <ServiceCard key={service.id} service={service} />)}</div>}
      </section>
    </main>
  )
}
